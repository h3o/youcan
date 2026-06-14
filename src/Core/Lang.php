<?php

namespace App\Core;

class Lang
{
    private const SUPPORTED = ['en', 'sk', 'cs', 'kl'];
    private static $locale = 'sk';
    private static $strings = [];

    public static function init(): void
    {
        $locale = $_SESSION['locale'] ?? 'sk';
        self::setLocale(in_array($locale, self::SUPPORTED, true) ? $locale : 'sk');
    }

    public static function setLocale(string $locale): void
    {
        self::$locale = $locale;
        self::$strings = require BASE_PATH . '/lang/' . $locale . '.php';
    }

    public static function t(string $key, array $params = []): string
    {
        $value = self::$strings[$key] ?? self::fallback($key);
        foreach ($params as $k => $v) {
            $value = str_replace(':' . $k, (string)$v, $value);
        }
        return $value;
    }

    private static function fallback(string $key): string
    {
        if (self::$locale === 'en') {
            return $key;
        }
        static $en = null;
        $en = $en ?? require BASE_PATH . '/lang/en.php';
        return $en[$key] ?? $key;
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }

    public static function htmlLang(): string
    {
        return self::$locale === 'kl' ? 'tlh' : self::$locale;
    }

    public static function supported(): array
    {
        return self::SUPPORTED;
    }
}
