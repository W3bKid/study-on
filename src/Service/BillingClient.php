<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;
use App\Security\User;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

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

    public function currentUser(string $token)
    {
        $url = $this->baseApiPath . 'users/current';
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $token];
        $response = $this->request(url: $url, headers: $headers);
        $userData = json_decode($response['data'], JSON_OBJECT_AS_ARRAY);
        return (new User())->setEmail($userData['username'])->setRoles($userData['roles'])->setApiToken($token);
    }

    public function refreshToken(User $user): User
    {
        $url = $this->baseApiPath . 'token/refresh' . '?refreshToken=' . $user->getRefreshToken();
        $response = $this->request(url: $url, method: 'POST');
        $tokens = json_decode($response['data'], JSON_OBJECT_AS_ARRAY);
        return $user->setRefreshToken($tokens['refreshToken'])->setApiToken($tokens['token']);
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

        if ($statusCode == 500) {
            throw new BillingUnavailableException();
        }

        curl_close($curl);
        return [
            'data' => $response,
            'statusCode' => $statusCode,
        ];
    }
}
