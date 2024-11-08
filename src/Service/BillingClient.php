<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;
use App\Security\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class BillingClient
{
    private string $billingDomain;
    private string $billingVersion;
    private string $baseApiPath;

    public function __construct()
    {
        $this->billingDomain = $_ENV['BILLING_DOMAIN'];
        $this->billingVersion = $_ENV['BILLING_VERSION'];
        $this->baseApiPath = $this->billingDomain . $this->billingVersion;
    }

    public function auth(string $username, string $password): array
    {
        $url = $this->baseApiPath . 'auth';
        $body = [
            'username' => $username,
            'password' => $password
        ];
        $headers = ['Content-Type' => 'application/json'];
        $response = $this->request(url: $url, body: $body, headers: $headers, method: 'POST');

        if ($response['statusCode'] == 401) {
            throw new UserNotFoundException();
        }

        return json_decode($response['data'], JSON_OBJECT_AS_ARRAY);
    }

    public function currentUser(string $token): User
    {
        $url = $this->baseApiPath . 'users/current';
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $token];
        $response = $this->request(url: $url, headers: $headers);
        $userData = json_decode($response['data'], JSON_OBJECT_AS_ARRAY);
        return (new User())
            ->setEmail($userData['username'])
            ->setRoles($userData['roles'])
            ->setApiToken($token)
            ->setBalance($userData['balance']);
    }

    public function refreshToken(User $user): User
    {
        $url = $this->baseApiPath . 'token/refresh' . '?refreshToken=' . $user->getRefreshToken();
        $response = $this->request(url: $url, method: 'POST');
        $tokens = json_decode($response['data'], JSON_OBJECT_AS_ARRAY);
        return $user->setRefreshToken($tokens['refreshToken'])->setApiToken($tokens['token']);
    }

    public function register(string $username, string $password): User|string
    {
        $url = $this->baseApiPath . 'register';
        $response = $this->request(url: $url, body: ['email' => $username, 'password' => $password], method: 'POST');
        $data = json_decode($response['data'], JSON_OBJECT_AS_ARRAY);
        if ($response['statusCode'] == 201) {
            return (new User())
                ->setRefreshToken($data['refreshToken'])
                ->setApiToken($data['token']);
        }
        throw new CustomUserMessageAuthenticationException($data['message']);
    }

    public function getCourses()
    {
        $url =  $this->baseApiPath . 'courses';
        $response = $this->request(url: $url);
        if ($response['statusCode'] == 200) {
            return json_decode($response['data'], JSON_OBJECT_AS_ARRAY);
        }
        throw new CustomUserMessageAuthenticationException($response['message'], code: $response['statusCode']);
    }

    /**
     * @param string $token
     * @param string|null $type
     * @param bool $skipExpired
     * @param string|null $courseCode
     * @return false|mixed
     * @throws BillingUnavailableException
     */
    public function getTransactions(
        string $token,
        string $type = null,
        bool $skipExpired = false,
        string $courseCode = null
    ) {
        $url =  $this->baseApiPath . 'transactions/';
        $parameters = [];

        if (null !== $type) {
            $parameters['filter[type]'] = $type;
        }

        if (null !== $courseCode) {
            $parameters['filter[course_code]'] = $courseCode;
        }

        if ($skipExpired) {
            $parameters['filter[skip_expired]'] = $skipExpired;
        }

        $url = $this->setQueryParams($url, $parameters);
        $response = $this->request(url: $url, headers: ['Authorization' => "Bearer $token"], method: 'GET');
        if ($response['statusCode'] == 200) {
            return $response['data'];
        }
        return false;
    }

    public function getCourseByCode(string $courseCode)
    {
        $url =  $this->baseApiPath . 'courses/' . $courseCode;

        $response = $this->request(url: $url, method: 'GET');

        if ($response['statusCode'] == 200) {
            return json_decode($response['data'], JSON_OBJECT_AS_ARRAY);
        }

        return false;
    }

    public function request(
        string $url,
        array $body = [],
        array $headers = [],
        string $method = 'GET',
    ): array {

        $curl = curl_init();
        $curlHeaders = [];
        foreach ($headers as $header => $value) {
            $curlHeaders[] = "$header: $value";
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => $curlHeaders
        ));

        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl)['http_code'];
        if ($statusCode >= 500 || (bool)curl_error($curl)) {
            dd($statusCode, (bool)curl_error($curl), $response);
            throw new BillingUnavailableException();
        }

        curl_close($curl);

//        dd(curl_getinfo($curl), (bool)curl_error($curl));

        return [
            'data' => $response,
            'statusCode' => $statusCode,
        ];
    }

    public function setQueryParams(string $url, array $queryParams)
    {

        foreach ($queryParams as $key => $value) {
            if (strpos($url, "?")) {
                $url .= "&" . $key . "=" . $value;
            } else {
                $url .= "?" . $key . "=" . $value;
            }
        }

        return $url;
    }
}
