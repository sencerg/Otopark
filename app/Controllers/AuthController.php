<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Env;
use App\Core\View;

final class AuthController
{
    /** Yalnızca yerel ortamda giriş ekranında gösterilen demo hesaplar (bin/seed.php ile aynı). */
    private const DEMO_HESAPLAR = [
        ['Yönetici', 'Tüm lokasyonlar', 'admin@otopark.local', 'admin123', 'mdi-shield-account'],
        ['Ankara Bayi', 'Sadece BİA ANKARA', 'ankara@otopark.local', 'ankara123', 'mdi-store'],
        ['Seyrantepe Bayi', 'Sadece BİA SEYRANTEPE', 'seyrantepe@otopark.local', 'seyrantepe123', 'mdi-store-outline'],
    ];

    public function showLogin(): void
    {
        $demo = Env::get('APP_ENV') === 'local' && Env::get('DEMO_LOGIN', false) === true;
        View::render('auth/login', ['demoHesaplar' => $demo ? self::DEMO_HESAPLAR : []], 'auth');
        unset($_SESSION['_old']);
    }

    public function login(): void
    {
        $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

        if ($email === '' || $password === '' || !Auth::attempt($email, $password)) {
            $_SESSION['_old'] = ['email' => $email];
            flash('error', 'Kullanıcı adı veya şifre hatalı.');
            View::redirect('/login');
        }

        View::redirect('/');
    }

    public function logout(): void
    {
        Auth::logout();
        View::redirect('/login');
    }
}
