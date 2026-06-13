<?php

namespace App\Core;

class Csrf
{
    private const TOKEN_KEY = '_csrf_token';

    public static function generate(): string
    {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    public static function validate(string $submitted): bool
    {
        $token = $_SESSION[self::TOKEN_KEY] ?? '';
        return $token !== '' && hash_equals($token, $submitted);
    }

    public static function fail(): never
    {
        http_response_code(419);
        exit('Invalid or missing CSRF token.');
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::generate(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
