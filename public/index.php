<?php

declare(strict_types=1);

use App\Core\Router;

if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/bootstrap.php';

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

ob_start();

set_exception_handler(static function (Throwable $e): void {
    error_log($e->__toString());
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $wantsJson = \App\Core\Request::isAjax()
        || str_contains($path, '/liste')
        || str_starts_with($path, '/api/');
    if ($wantsJson) {
        \App\Core\View::json([
            'draw' => (int) ($_REQUEST['draw'] ?? 0),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => $e->getMessage(),
        ], 500);
    }
    http_response_code(500);
    echo \App\Core\Env::get('APP_DEBUG', false)
        ? '<pre>' . htmlspecialchars($e->__toString()) . '</pre>'
        : 'Beklenmeyen bir hata oluştu.';
});

$router = new Router();
require BASE_PATH . '/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
