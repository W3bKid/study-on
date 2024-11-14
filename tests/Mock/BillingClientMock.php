<?php

namespace App\Tests\Mock;

use App\DTO\CourseDTO;
use App\Security\User;
use App\Service\BillingClient;
use App\Service\JWTokenParse;
use DateInterval;
use DateTimeImmutable;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class BillingClientMock extends BillingClient
{
    private $transactions;

    private $courses = [
        [
            'title' =>  'Основы программирования',
            'character_code' => 'osnovy_programmirovaniya',
            'type' => 3,
            'price' => 10
        ],
        [
            'title' => 'Основы личной финансовой грамотности',
            'character_code' => 'osnovy_lichnoj_finansovoj_gramotnosti',
            'type' => 0,
            'price' => 10
        ],
        [
            'title' => 'Основы фотографии',
            'character_code' => 'osnovy_fotografii',
            'type' => 1,
            'price' => 20
        ],
        [
            'title' => 'Непокупаемый',
            'character_code' => 'cant_bue',
            'type' => 3,
            'price' => 200000000
        ]
    ];
    private const USER = [
        'username' => 'user@example.com',
        'password' => 'password',
        'roles' => ['ROLE_USER'],
        'refreshToken' => 'to9ohch4Zeishie2',
        'balance' => 0,
    ];
    private const ADMIN = [
        'username' => 'admin@billing.ru',
        'password' => '12345678',
        'roles' => ['ROLE_USER', 'ROLE_SUPER_ADMIN'],
        'refreshToken' => 'ahpoim7Ohyiepi3e',
        'balance' => 0,
    ];

    public function __construct()
    {
        $this->transactions = [
            [
                'type' => 2,
                'amount' => 200,
                'customer' => self::USER,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P3Y')),
            ],
            [
                'type' => 2,
                'amount' => 2000,
                'customer' => self::ADMIN,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P3Y')),
            ],
            [
                'type' => 1,
                'amount' => $this->courses[1]['price'],
                'course' => $this->courses[1],
                'customer' => self::ADMIN,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P1Y1M2D')),
            ],
            // rent - expires
            [
                'type' => 1,
                'amount' => $this->courses[3]['price'],
                'expires' => (new DateTimeImmutable())->sub(new DateInterval('P1Y3M6D')),
                'course' => $this->courses[3],
                'customer' => self::USER,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P1Y3M13D')),
            ],
            [
                'type' => 1,
                'amount' => $this->courses[3]['price'],
                'expires' => (new DateTimeImmutable())->sub(new DateInterval('P2Y3M6D')),
                'course' => $this->courses[3],
                'customer' => self::ADMIN,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P2Y3M13D')),
            ],
            [
                'type' => 1,
                'amount' => $this->courses[3]['price'],
                'expires' => (new DateTimeImmutable())->sub(new DateInterval('P2Y3M6D')),
                'course' => $this->courses[3],
                'customer' => self::USER,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P2Y3M13D')),
            ],
            // rent
            [
                'type' => 1,
                'amount' => $this->courses[3]['price'],
                'expires' => (new DateTimeImmutable())->add(new DateInterval('P15D')),
                'course' => $this->courses[3],
                'customer' => self::USER,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P12D')),
            ],
        ];
    }

    public function auth(string $username, string $password): array
    {
        if ($username === self::USER['username'] && $password === self::USER['password']) {
            $token = $this->generateToken($username, self::USER['roles']);
        } elseif ($username === self::ADMIN['username'] && $password === self::ADMIN['password']) {
            $token = $this->generateToken($username, self::ADMIN['roles']);
        } else {
            throw new AuthenticationException('Неправильные логин или пароль');
        }
        return [
            'token' => $token,
            'refreshToken' => 'refreshToken'
        ];
    }

    public function currentUser(string $token): User
    {
        $jwtParts = explode('.', $token);
        $payload = json_decode(base64_decode($jwtParts[1]), JSON_OBJECT_AS_ARRAY);
        $user = new User();

        if ($payload['username'] === self::USER['username']) {
            $user->setEmail(self::USER['username'])
                ->setBalance(self::USER['balance'])
                ->setRoles(json_decode($payload['roles']))
                ->setRefreshToken(self::USER['refreshToken'])
                ->setApiToken($token);
        } elseif ($payload['username'] === self::ADMIN['username']) {
            $user->setEmail(self::ADMIN['username'])
                ->setBalance(self::ADMIN['balance'])
                ->setRoles(json_decode($payload['roles']))
                ->setRefreshToken(self::ADMIN['refreshToken'])
                ->setApiToken($token);
        } else {
            throw new AuthenticationException('Невалидный токен');
        }

        return $user;
    }

    public function refreshToken(User $user): User
    {
        return $user->setRefreshToken($this->generateToken($user->getEmail(), $user->getRoles()));
    }

    public function register(string $username, string $password): string|User
    {
        if ($username === self::ADMIN['username'] || $username === self::USER['username']) {
            throw new CustomUserMessageAuthenticationException('Email уже используется');
        }
        $token = $this->generateToken($username, ['ROLE_USER']);

        return (new User())->setEmail($username)
            ->setRefreshToken('refreshToken')
            ->setApiToken($token)
            ->setBalance(0)
            ->setRoles(['ROLE_USER']);
    }

    private function generateToken(string $username, array $roles): string
    {
        $signing_key = "signingKey";
        $header = [
            "alg" => "HS512",
            "typ" => "JWT"
        ];
        $header = base64_encode(json_encode($header));

        $payload =  [
            'username' => $username,
            'roles' => json_encode($roles),
            'exp' => (new \DateTime())->setTime('25', '32', '32', '3123')->getTimestamp(),
        ];

        $payload = base64_encode(json_encode($payload));
        $signature = base64_encode(hash_hmac('sha512', "$header.$payload", $signing_key, true));
        return "$header.$payload.$signature";
    }

    public function getCourses()
    {
        return $this->courses;
    }

    public function getCourseByCode(string $code)
    {
        foreach ($this->courses as $course) {
            if ($course['character_code'] === $code) {
                return $course;
            }
        }

        return [];
    }

    public function createCourse(CourseDTO $courseDTO, User $user)
    {
        $editCourse = null;
        if (in_array($courseDTO->getCharacterCode(), array_column($this->courses, 'code'))) {
            return 'Курс с таким кодом уже существует.';
        }
        $this->courses[] = [
            'character_code' => $courseDTO->getCharacterCode(),
            'title' => $courseDTO->getTitle(),
            'price' => $courseDTO->getPrice(),
            'type' => $courseDTO->getType(),
        ];

        return $editCourse;
    }

    public function editCourse(CourseDTO $courseDTO, User $user)
    {
        $editCourse = null;
        foreach ($this->courses as &$course) {
            if ($course['character_code'] === $courseDTO->getCharacterCode()) {
                $course = [
                    'character_code' => $courseDTO->getCharacterCode(),
                    'title' => $courseDTO->getTitle(),
                    'price' => $courseDTO->getPrice(),
                    'type' => $courseDTO->getType(),
                ];
            }
            $editCourse = $course;
        }
        return $editCourse;
    }

    public function getTransactions(
        string $token,
        string $type = null,
        bool $skipExpired = false,
        string $courseCode = null
    ) {
        $transactions = $this->transactions;
        if (isset($type)) {
            $transactions = array_filter($transactions, function ($transaction) use ($type) {
                if ($transaction['type'] !== 2) {
                    return $transaction['type'] === $type;
                }
            });
        }

        if (isset($courseCode)) {
            $transactions = array_filter($transactions, function ($transaction) use ($courseCode) {
                if ($transaction['type'] !== 2) {
                    return $transaction['course']['character_code'] === $courseCode;
                }
            });
        }

        if (isset($skipExpired)) {
            $transactions = array_filter($transactions, function ($transaction) {
                if ($transaction['type'] !== 2) {
                    return !isset($transaction['expires']) || $transaction['expires'] > new \DateTimeImmutable();
                }
            });
        }
        return json_encode($transactions);
    }

    public function courseIsPaid(string $characterCode, User $user)
    {
        $username = $user->getEmail();
        foreach ($this->transactions as $transaction) {
            if ($transaction['type'] == 2) {
                continue;
            }

            if (
                $transaction['course']['character_code'] === $characterCode &&
                $transaction['customer']['username'] === $username
            ) {
                return true;
            }
        }

        return false;
    }
}
