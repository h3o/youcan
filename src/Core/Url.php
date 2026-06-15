<?php

namespace App\Core;

class Url
{
    private static $base    = '';
    private static $appUrl  = '';

    public static function init(string $base, string $appUrl = ''): void
    {
        self::$base   = rtrim($base, '/');
        self::$appUrl = rtrim($appUrl, '/');
    }

    /** Build an absolute URL including scheme + host, e.g. https://example.com/ajty/stories/foo */
    public static function absolute(string $path = '/'): string
    {
        return self::$appUrl . self::to($path);
    }

    /**
     * Build an app URL by prepending the configured base path.
     * url('/stories/foo') => '/ajty/stories/foo'
     */
    public static function to(string $path = '/'): string
    {
        return self::$base . '/' . ltrim($path, '/');
    }

    /** Raw base path without trailing slash, e.g. '/ajty' or ''. */
    public static function base(): string
    {
        return self::$base;
    }
}
