<?php

namespace App\Core;

class Url
{
    private static $base = '';

    public static function init(string $base): void
    {
        self::$base = rtrim($base, '/');
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
