<?php

namespace App\Controllers;

use App\Core\Lang;
use App\Core\Response;

class LangController
{
    public function set(array $params): void
    {
        $code = $params['code'] ?? 'en';
        if (in_array($code, Lang::supported(), true)) {
            $_SESSION['locale'] = $code;
            Lang::setLocale($code);
        }

        // Redirect back to the same page (extract path only to prevent open redirect)
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $path    = parse_url($referer, PHP_URL_PATH) ?? '/';
        Response::redirect($path ?: '/');
    }
}
