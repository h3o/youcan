<?php

namespace App\Core;

class Response
{
    public static function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    public static function abort(int $status): void
    {
        http_response_code($status);
        if ($status === 403) {
            $template = 'errors/403';
        } else {
            $template = 'errors/404';
        }
        View::render($template, ['pageTitle' => $status === 403 ? 'Forbidden' : 'Not Found']);
        exit;
    }
}
