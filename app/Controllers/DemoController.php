<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Env;
use App\Core\View;

/** Geliştirme ortamında demo verisini tek tıkla baştan kurar (APP_ENV=local ve DEMO_LOGIN=true). */
final class DemoController extends Controller
{
    public static function acik(): bool
    {
        return Env::get('APP_ENV') === 'local' && Env::get('DEMO_LOGIN', false) === true;
    }

    public function onay(): void
    {
        if (!self::acik()) {
            $this->notFound();
        }
        View::render('auth/demo_sifirla', [], 'auth');
    }

    public function sifirla(): void
    {
        if (!self::acik()) {
            $this->notFound();
        }
        set_time_limit(120);
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(BASE_PATH . '/bin/reset.php') . ' 2>&1', $cikti, $kod);
        Auth::logout();
        flash($kod === 0 ? 'success' : 'error', $kod === 0
            ? 'Demo verisi sıfırlandı. ' . (end($cikti) ?: '')
            : 'Sıfırlama başarısız: ' . implode(' ', array_slice($cikti, -2)));
        View::redirect('/login');
    }
}
