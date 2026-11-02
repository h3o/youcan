<?php

// Router for PHP's built-in dev server (mirrors public/.htaccess):
//   php -S localhost:8000 -t public dev-router.php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve real files directly (CSS, JS, images)
if ($path !== '/' && is_file(__DIR__ . '/public' . $path)) {
    return false;
}

// Route everything else to the front controller
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/public/index.php';
