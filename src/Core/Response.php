<?php

namespace App\Core;

class Response
{
    public static function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    public static function abort(int $status): never
    {
        http_response_code($status);
        $template = match ($status) {
            403     => 'errors/403',
            default => 'errors/404',
        };
        View::render($template, ['pageTitle' => $status === 403 ? 'Forbidden' : 'Not Found']);
        exit;
    }
}
