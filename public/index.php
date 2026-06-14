<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

// Load .env
(require BASE_PATH . '/src/Core/env.php')();

// Start session
use App\Core\Session;
use App\Core\Lang;
use App\Core\Url;

Session::start();

// Initialize base URL (e.g. '/ajty' for subdirectory hosting, '' for root)
Url::init($_ENV['APP_BASE'] ?? '');

// Initialize localization
Lang::init();

// Global helpers available in all views and controllers
function t(string $key, array $params = []): string
{
    return Lang::t($key, $params);
}

function url(string $path = '/'): string
{
    return Url::to($path);
}

// Dispatch
$router  = require BASE_PATH . '/src/routes.php';
$request = new \App\Core\Request();
$router->dispatch($request);
