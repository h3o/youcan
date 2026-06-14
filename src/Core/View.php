<?php

namespace App\Core;

class View
{
    public static function render(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require BASE_PATH . '/views/' . $template . '.php';
        $content = ob_get_clean();

        require BASE_PATH . '/views/layout/head.php';
        require BASE_PATH . '/views/layout/nav.php';
        echo $content;
        require BASE_PATH . '/views/layout/footer.php';
    }

    public static function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
