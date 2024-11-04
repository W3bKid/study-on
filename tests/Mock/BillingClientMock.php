<?php

namespace App\Tests\Mock;

use App\Security\User;
use App\Service\BillingClient;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class BillingClientMock extends BillingClient
{
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
        ];
        $payload = base64_encode(json_encode($payload));
        $signature = base64_encode(hash_hmac('sha512', "$header.$payload", $signing_key, true));
        return "$header.$payload.$signature";
    }
}
