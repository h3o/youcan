<?php

namespace App\Helpers;

use HTMLPurifier;
use HTMLPurifier_Config;

class Purifier
{
    private static ?HTMLPurifier $instance = null;

    public static function sanitize(string $html): string
    {
        if (self::$instance === null) {
            $config = HTMLPurifier_Config::createDefault();
            $cacheDir = sys_get_temp_dir() . '/youcan-purifier-cache';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            $config->set('Cache.SerializerPath', $cacheDir);
            $config->set('HTML.Allowed',
                'p,br,h1,h2,h3,strong,em,u,s,ol,ul,li,blockquote,a[href|title|target],span'
            );
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
            $config->set('Attr.AllowedFrameTargets', ['_blank']);
            $config->set('HTML.SafeIframe', true);
            self::$instance = new HTMLPurifier($config);
        }
        return self::$instance->purify($html);
    }
}
