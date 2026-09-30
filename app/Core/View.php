<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        $viewsPath = dirname(__DIR__, 2) . '/views/';

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewsPath . $view . '.php';
        $content = ob_get_clean();

        if ($layout === null) {
            echo $content;
            return;
        }

        require $viewsPath . 'layouts/' . $layout . '.php';
    }

    public static function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
