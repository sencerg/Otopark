<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static ?array $user = null;

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
        if (self::$user === null && isset($_SESSION['user_id'])) {
            self::$user = Database::fetch(
                'SELECT u.id, u.name, u.email, u.role, u.bayi_id, u.telefon, u.resim, u.kullanici_grubu_id,
                        b.ad AS bayi_adi, g.ad AS grup_adi
                 FROM users u
                 LEFT JOIN bayiler b ON b.id = u.bayi_id
                 LEFT JOIN kullanici_gruplari g ON g.id = u.kullanici_grubu_id
                 WHERE u.id = :id AND u.is_active = TRUE',
                ['id' => $_SESSION['user_id']]
            );
        }

        return self::$user;
    }

    public static function id(): ?int
    {
        return isset(self::user()['id']) ? (int) self::user()['id'] : null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? null) === 'admin';
    }

    /** Bayi kullanıcısının lokasyonu; yönetici için null (tüm lokasyonlar). */
    public static function bayiId(): ?int
    {
        $user = self::user();

        return $user && $user['role'] !== 'admin' && $user['bayi_id'] ? (int) $user['bayi_id'] : null;
    }

    /** Bayi kullanıcıları için kayıt sorgularına eklenecek kısıt. */
    public static function bayiKosulu(string $column): string
    {
        $bayiId = self::bayiId();

        return $bayiId ? "{$column} = {$bayiId}" : 'TRUE';
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        self::$user = null;
        session_regenerate_id(true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            if (Request::isAjax()) {
                View::json(['success' => false, 'message' => 'Oturum süresi doldu.'], 401);
            }
            View::redirect('/login');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            exit('Bu işlem için yetkiniz yok.');
        }
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            View::redirect('/');
        }
    }
}
