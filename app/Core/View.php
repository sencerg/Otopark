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
        $junk = '';
        while (ob_get_level() > 0) {
            $junk .= (string) ob_get_clean();
        }
        if (trim($junk) !== '') {
            error_log('JSON öncesi çıktı atıldı: ' . substr(preg_replace('/\s+/', ' ', $junk) ?? '', 0, 500));
        }

        try {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $status = 500;
            $json = json_encode([
                'draw' => (int) ($_REQUEST['draw'] ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Yanıt yazılamadı.',
            ], JSON_UNESCAPED_UNICODE);
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Connection: close');
        echo $json;
        exit;
    }
}
