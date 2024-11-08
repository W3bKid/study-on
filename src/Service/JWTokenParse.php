<?php

namespace App\Service;

class JWTokenParse
{
    public static function parseJWToken(string $token): array
    {
        $payload = explode('.', $token)[1];
        return json_decode(base64_decode($payload), JSON_OBJECT_AS_ARRAY);
    }
}
