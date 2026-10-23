<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static ?array $user = null;
    /** @var list<string>|null */
    private static ?array $izinler = null;

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
                'SELECT u.id, u.name, u.email, u.role, u.bayi_id, u.musteri_id, u.telefon, u.resim, u.kullanici_grubu_id,
                        u.yetki_profil_id, u.bayi_kapsam,
                        b.ad AS bayi_adi, g.ad AS grup_adi, m.ad AS musteri_adi, yp.ad AS yetki_profil_adi
                 FROM users u
                 LEFT JOIN bayiler b ON b.id = u.bayi_id
                 LEFT JOIN kullanici_gruplari g ON g.id = u.kullanici_grubu_id
                 LEFT JOIN musteriler m ON m.id = u.musteri_id
                 LEFT JOIN yetki_profilleri yp ON yp.id = u.yetki_profil_id
                 WHERE u.id = :id AND u.is_active = TRUE',
                ['id' => $_SESSION['user_id']]
            );
            self::$izinler = null;
        }

        return self::$user;
    }

    /** @return list<string> */
    public static function izinler(): array
    {
        if (self::$izinler !== null) {
            return self::$izinler;
        }
        $user = self::user();
        if (!$user) {
            return self::$izinler = [];
        }
        // Yönetici rolü her zaman tam yetki (profil boş olsa bile)
        if (($user['role'] ?? null) === 'admin') {
            return self::$izinler = \App\Services\Yetki::tumKodlar();
        }
        $profilId = !empty($user['yetki_profil_id']) ? (int) $user['yetki_profil_id'] : 0;
        if (!$profilId) {
            \App\Services\Yetki::varsayilanlariDoldur();
            self::$user = null;
            $user = self::user();
            $profilId = !empty($user['yetki_profil_id']) ? (int) $user['yetki_profil_id'] : 0;
            if (!$profilId) {
                return self::$izinler = [];
            }
        }

        $izinler = \App\Services\Yetki::profilIzinleri($profilId);
        if ($izinler === []) {
            \App\Services\Yetki::varsayilanlariDoldur();
            $izinler = \App\Services\Yetki::profilIzinleri($profilId);
        }

        return self::$izinler = $izinler;
    }

    public static function can(string $izinKodu): bool
    {
        if (!self::check()) {
            return false;
        }
        if (self::isAdmin()) {
            return true;
        }

        return in_array($izinKodu, self::izinler(), true);
    }

    public static function requireIzin(string $izinKodu): void
    {
        self::requireLogin();
        if (!self::can($izinKodu)) {
            if (Request::isAjax()) {
                View::json(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.'], 403);
            }
            http_response_code(403);
            exit('Bu işlem için yetkiniz yok.');
        }
    }

    public static function id(): ?int
    {
        return isset(self::user()['id']) ? (int) self::user()['id'] : null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? null) === 'admin';
    }

    public static function isMusteri(): bool
    {
        return (self::user()['role'] ?? null) === 'musteri';
    }

    /** Fiyat / tutar görme: yönetici ve müşteri portalı. Bayi kullanıcıları görmez. */
    public static function fiyatGorebilir(): bool
    {
        return self::isAdmin() || self::isMusteri();
    }

    /** Müşteri portal kullanıcısının firma id'si. */
    public static function musteriId(): ?int
    {
        $user = self::user();
        if (!$user || ($user['role'] ?? null) !== 'musteri' || empty($user['musteri_id'])) {
            return null;
        }

        return (int) $user['musteri_id'];
    }

    /** Bayi kullanıcısının lokasyonu; yönetici / müşteri için null. */
    public static function bayiId(): ?int
    {
        $user = self::user();
        if (!$user || in_array($user['role'] ?? '', ['admin', 'musteri'], true)) {
            return null;
        }

        return !empty($user['bayi_id']) ? (int) $user['bayi_id'] : null;
    }

    /**
     * Bayi kullanıcıları için kayıt sorgularına eklenecek kısıt.
     * Yönetici: TRUE. Müşteri: FALSE (operasyon sorguları). Lokasyonlu bayi: kolon = id.
     */
    public static function bayiKosulu(string $column): string
    {
        $user = self::user();
        if (!$user || ($user['role'] ?? null) === 'admin') {
            return 'TRUE';
        }
        if (($user['role'] ?? null) === 'musteri') {
            return 'FALSE';
        }

        $bayiId = !empty($user['bayi_id']) ? (int) $user['bayi_id'] : null;

        return $bayiId ? "{$column} = {$bayiId}" : 'FALSE';
    }

    /**
     * Personel (veya benzer bayi bağlı kayıt) bu kullanıcının otoparkına mı ait?
     * Boş id = seçilmemiş (opsiyonel alan) → true.
     */
    public static function personelErisilebilir(?int $personelId): bool
    {
        if (!$personelId) {
            return true;
        }

        return Database::fetch(
            'SELECT 1 FROM personeller WHERE id = :id AND aktif AND ' . self::bayiKosulu('bayi_id'),
            ['id' => $personelId]
        ) !== null;
    }

    /** Form/API kayıtlarında personel otopark izolasyonu. */
    public static function dogrulaPersonel(?int $personelId, string $etiket = 'Personel'): void
    {
        if (!$personelId) {
            return;
        }
        if (!self::personelErisilebilir($personelId)) {
            throw new \RuntimeException("{$etiket} bulunamadı veya bu otoparka ait değil.");
        }
    }

    /** Giriş sonrası yönlendirme. */
    public static function homePath(): string
    {
        return self::isMusteri() ? '/musteri' : '/';
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        self::$user = null;
        self::$izinler = null;
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

    /** Admin / bayi operasyon ekranları — müşteri portalı buraya giremez. */
    public static function requireStaff(): void
    {
        self::requireLogin();
        if (self::isMusteri()) {
            View::redirect('/musteri');
        }
    }

    public static function requireMusteri(): void
    {
        self::requireLogin();
        if (!self::isMusteri() || !self::musteriId()) {
            http_response_code(403);
            exit('Bu alan yalnızca müşteri hesapları içindir.');
        }
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            View::redirect(self::homePath());
        }
    }
}
