<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $user = Database::fetch(
            'SELECT * FROM users WHERE email = :email AND is_active = TRUE',
            ['email' => mb_strtolower(trim($email))]
        );

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        Database::query('UPDATE users SET last_login_at = NOW() WHERE id = :id', ['id' => $user['id']]);

        return true;
    }

    public static function user(): ?array
    {
        static $user = null;

        if ($user === null && isset($_SESSION['user_id'])) {
            $user = Database::fetch(
                'SELECT id, name, email, role FROM users WHERE id = :id AND is_active = TRUE',
                ['id' => $_SESSION['user_id']]
            );
        }

        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            View::redirect('/login');
        }
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            View::redirect('/');
        }
    }
}
