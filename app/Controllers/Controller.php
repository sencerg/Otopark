<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use RuntimeException;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    protected function ok(string $message, array $extra = []): never
    {
        View::json(['success' => true, 'message' => $message] + $extra);
    }

    protected function fail(string $message, int $status = 422): never
    {
        View::json(['success' => false, 'message' => $message], $status);
    }

    /** AJAX isteklerinde iş kuralı hatalarını JSON olarak döndürür. */
    protected function ajax(callable $callback): never
    {
        try {
            $callback();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            $this->fail(self::pdoKullaniciMesaji($e), 500);
        } catch (RuntimeException $e) {
            $this->fail($e->getMessage());
        }
        $this->fail('İşlem tamamlanamadı.', 500);
    }

    /** Form isteklerinde hatayı flash mesajla geri döndürür. */
    protected function form(callable $callback, string $backUrl): never
    {
        try {
            $callback();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['_old'] = $_POST;
            flash('error', self::pdoKullaniciMesaji($e));
        } catch (RuntimeException $e) {
            $_SESSION['_old'] = $_POST;
            flash('error', $e->getMessage());
        }
        View::redirect($backUrl);
    }

    protected function notFound(): never
    {
        http_response_code(404);
        View::render('errors/404', [], 'blank');
        exit;
    }

    /** Gerçek tekil ihlali (23505); "no unique … constraint" gibi ON CONFLICT hatalarını ayırır. */
    private static function pdoKullaniciMesaji(\PDOException $e): string
    {
        $msg = $e->getMessage();
        if ($e->getCode() === '23505' || str_contains($msg, 'duplicate key')) {
            return 'Bu kayıt zaten mevcut.';
        }

        return 'Veritabanı hatası oluştu.';
    }
}
