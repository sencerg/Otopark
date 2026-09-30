<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;

final class AuthController
{
    public function showLogin(): void
    {
        View::render('auth/login', [], 'auth');
        unset($_SESSION['_old']);
    }

    public function login(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

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
