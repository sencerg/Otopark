<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Log;
use App\Core\Request;
use App\Services\Yetki;
use RuntimeException;

final class YetkiController extends Controller
{
    public function index(): void
    {
        Auth::requireIzin('menu.yetki');
        Yetki::varsayilanlariDoldur();

        $this->view('yetki/index', [
            'pageTitle' => 'Yetki Yönetimi',
            'breadcrumb' => ['Yetki Yönetimi' => null],
            'katalog' => Yetki::katalog(),
            'profiller' => Yetki::profiller(),
            'bayiler' => Database::fetchAll('SELECT id, ad FROM bayiler WHERE aktif ORDER BY ad'),
            'musteriler' => Database::fetchAll('SELECT id, ad FROM musteriler WHERE aktif ORDER BY ad'),
            'gruplar' => Database::fetchAll('SELECT id, ad FROM kullanici_gruplari ORDER BY ad'),
            'kullanicilar' => $this->kullaniciListesi(),
            'seciliProfil' => Request::int('profil') ?: (int) (Yetki::profiller()[0]['id'] ?? 0),
        ]);
    }

    public function profilIzinleri(): void
    {
        $this->ajax(function () {
            Auth::requireIzin('menu.yetki');
            $id = Request::int('id');
            if (!$id || !Database::fetch('SELECT 1 FROM yetki_profilleri WHERE id = :id', ['id' => $id])) {
                throw new RuntimeException('Profil bulunamadı.');
            }
            $this->ok('', ['izinler' => Yetki::profilIzinleri($id)]);
        });
    }

    public function profilKaydet(): void
    {
        $this->ajax(function () {
            Auth::requireIzin('yetki.yonet');
            $id = Request::int('id');
            $ad = trim(Request::str('ad') ?? '');
            $tip = Request::str('tip') ?? 'bayi';
            $aciklama = trim(Request::str('aciklama') ?? '');
            if ($ad === '') {
                throw new RuntimeException('Profil adı zorunludur.');
            }
            if (!in_array($tip, ['admin', 'bayi', 'musteri'], true)) {
                throw new RuntimeException('Geçersiz profil tipi.');
            }
            if ($id) {
                $p = Database::fetch('SELECT * FROM yetki_profilleri WHERE id = :id', ['id' => $id]);
                if (!$p) {
                    throw new RuntimeException('Profil bulunamadı.');
                }
                Database::query(
                    'UPDATE yetki_profilleri SET ad = :a, tip = :t, aciklama = :c, updated_at = NOW() WHERE id = :id',
                    ['a' => $ad, 't' => $p['sistem_mi'] ? $p['tip'] : $tip, 'c' => $aciklama !== '' ? $aciklama : null, 'id' => $id]
                );
            } else {
                $id = (int) Database::fetch(
                    'INSERT INTO yetki_profilleri (ad, tip, aciklama, sistem_mi)
                     VALUES (:a, :t, :c, FALSE) RETURNING id',
                    ['a' => $ad, 't' => $tip, 'c' => $aciklama !== '' ? $aciklama : null]
                )['id'];
            }
            Log::islem('yetki', "Profil kaydedildi: {$ad}", $id);
            $this->ok('Profil kaydedildi.', ['id' => $id, 'profiller' => Yetki::profiller()]);
        });
    }

    public function izinKaydet(): void
    {
        $this->ajax(function () {
            Auth::requireIzin('yetki.yonet');
            $id = Request::int('profil_id');
            if (!$id || !Database::fetch('SELECT 1 FROM yetki_profilleri WHERE id = :id AND aktif', ['id' => $id])) {
                throw new RuntimeException('Profil bulunamadı.');
            }
            $kodlar = Request::input('izinler');
            if (!is_array($kodlar)) {
                $kodlar = [];
            }
            Yetki::izinleriKaydet($id, array_map('strval', $kodlar));
            Log::islem('yetki', 'Profil izinleri güncellendi', $id);
            $this->ok('İzinler kaydedildi.', ['izin_sayisi' => count(Yetki::profilIzinleri($id))]);
        });
    }

    public function profilSil(): void
    {
        $this->ajax(function () {
            Auth::requireIzin('yetki.yonet');
            $id = Request::int('id');
            $p = Database::fetch('SELECT * FROM yetki_profilleri WHERE id = :id', ['id' => $id]);
            if (!$p) {
                throw new RuntimeException('Profil bulunamadı.');
            }
            if ($p['sistem_mi']) {
                throw new RuntimeException('Sistem profilleri silinemez. İzinlerini düzenleyebilirsiniz.');
            }
            if (Database::fetch('SELECT 1 FROM users WHERE yetki_profil_id = :id LIMIT 1', ['id' => $id])) {
                throw new RuntimeException('Bu profile bağlı kullanıcılar var. Önce kullanıcıları başka profile taşıyın.');
            }
            Database::query('DELETE FROM yetki_profilleri WHERE id = :id', ['id' => $id]);
            Log::islem('yetki', 'Profil silindi: ' . $p['ad'], $id);
            $this->ok('Profil silindi.', ['profiller' => Yetki::profiller()]);
        });
    }

    public function kullaniciKaydet(): void
    {
        $this->ajax(function () {
            Auth::requireIzin('kullanici.yonet');
            $id = Request::int('id');
            $name = trim(Request::str('name') ?? '');
            $email = mb_strtolower(trim(Request::str('email') ?? ''));
            $kullaniciAdi = mb_strtolower(trim(Request::str('kullanici_adi') ?? ''));
            $telefon = trim(Request::str('telefon') ?? '');
            $yetkiTipi = Request::str('yetki_tipi') ?: null;
            $role = Request::str('role') ?? 'bayi';
            $profilId = Request::int('yetki_profil_id');
            $bayiId = Request::int('bayi_id');
            $musteriId = Request::int('musteri_id');
            $kapsam = Request::str('bayi_kapsam') ?: null;
            $grupId = Request::int('kullanici_grubu_id');
            $aktif = Request::bool('is_active');
            $sifre = is_string($_POST['password'] ?? null) ? (string) $_POST['password'] : '';
            $sifreTekrar = is_string($_POST['password_tekrar'] ?? null) ? (string) $_POST['password_tekrar'] : '';

            // yetki_tipi: admin | otopark | otopark_personel | musteri → role + kapsam + varsayılan profil
            if ($yetkiTipi !== null) {
                [$role, $kapsam, $autoProfil] = match ($yetkiTipi) {
                    'admin' => ['admin', null, $this->sistemProfilId('admin')],
                    'otopark' => ['bayi', 'genel', $this->sistemProfilId('bayi', 'otopark')],
                    'otopark_personel' => ['bayi', 'kisisel', $this->sistemProfilId('bayi', 'personel')],
                    'musteri' => ['musteri', null, $this->sistemProfilId('musteri')],
                    default => throw new RuntimeException('Geçersiz yetki tipi.'),
                };
                if (!$profilId) {
                    $profilId = $autoProfil;
                }
            }

            if ($name === '' || $email === '') {
                throw new RuntimeException('Ad ve e-posta zorunludur.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Geçerli bir e-posta giriniz.');
            }
            if ($kullaniciAdi === '') {
                $kullaniciAdi = preg_replace('/[^a-z0-9._-]/', '', explode('@', $email)[0] ?? '') ?: '';
            }
            if ($kullaniciAdi === '' || !preg_match('/^[a-z0-9._-]{3,80}$/', $kullaniciAdi)) {
                throw new RuntimeException('Kullanıcı adı 3–80 karakter; harf, rakam, . _ - olabilir.');
            }
            if (!in_array($role, ['admin', 'bayi', 'musteri'], true)) {
                throw new RuntimeException('Geçersiz rol.');
            }
            if (!$profilId || !Database::fetch(
                'SELECT 1 FROM yetki_profilleri WHERE id = :id AND tip = :t AND aktif',
                ['id' => $profilId, 't' => $role]
            )) {
                throw new RuntimeException('Seçilen yetki profili bu role uygun değil.');
            }
            if ($role === 'bayi') {
                if (!$bayiId) {
                    throw new RuntimeException('Otopark / personel için otopark seçilmelidir.');
                }
                if (!in_array($kapsam, ['genel', 'kisisel'], true)) {
                    $kapsam = 'genel';
                }
                $musteriId = null;
            } elseif ($role === 'musteri') {
                if (!$musteriId) {
                    throw new RuntimeException('Müşteri kullanıcısı için firma seçilmelidir.');
                }
                $bayiId = null;
                $kapsam = null;
            } else {
                $bayiId = null;
                $musteriId = null;
                $kapsam = null;
            }

            if ($id === Auth::id() && (!$aktif || $role !== 'admin')) {
                throw new RuntimeException('Kendi hesabınızı pasifleştiremez veya yetkisini düşüremezsiniz.');
            }

            $emailVar = Database::fetch(
                'SELECT id FROM users WHERE lower(email) = lower(:e)' . ($id ? ' AND id <> :id' : ''),
                $id ? ['e' => $email, 'id' => $id] : ['e' => $email]
            );
            if ($emailVar) {
                throw new RuntimeException('Bu e-posta zaten kayıtlı.');
            }
            $adiVar = Database::fetch(
                'SELECT id FROM users WHERE kullanici_adi IS NOT NULL AND lower(kullanici_adi) = lower(:a)'
                . ($id ? ' AND id <> :id' : ''),
                $id ? ['a' => $kullaniciAdi, 'id' => $id] : ['a' => $kullaniciAdi]
            );
            if ($adiVar) {
                throw new RuntimeException('Bu kullanıcı adı zaten kullanılıyor.');
            }

            $data = [
                'name' => $name,
                'email' => $email,
                'kullanici_adi' => $kullaniciAdi,
                'telefon' => $telefon !== '' ? $telefon : null,
                'role' => $role,
                'yetki_profil_id' => $profilId,
                'bayi_id' => $bayiId,
                'musteri_id' => $musteriId,
                'bayi_kapsam' => $kapsam,
                'kullanici_grubu_id' => $grupId ?: null,
                'is_active' => $aktif ? 'true' : 'false',
            ];

            if ($sifre !== '') {
                if (mb_strlen($sifre) < 6) {
                    throw new RuntimeException('Şifre en az 6 karakter olmalıdır.');
                }
                if ($sifreTekrar !== '' && $sifre !== $sifreTekrar) {
                    throw new RuntimeException('Şifre ve şifre tekrarı aynı değil.');
                }
                $data['password_hash'] = password_hash($sifre, PASSWORD_DEFAULT);
            } elseif (!$id) {
                throw new RuntimeException('Yeni kullanıcı için şifre zorunludur.');
            }

            if ($id) {
                $set = implode(', ', array_map(static fn ($c) => "{$c} = :{$c}", array_keys($data)));
                if (Database::query(
                    "UPDATE users SET {$set}, updated_at = NOW() WHERE id = :_id",
                    $data + ['_id' => $id]
                )->rowCount() === 0) {
                    throw new RuntimeException('Kullanıcı bulunamadı.');
                }
            } else {
                $cols = array_keys($data);
                $id = (int) Database::fetch(
                    'INSERT INTO users (' . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ') RETURNING id',
                    $data
                )['id'];
            }
            Log::islem('yetki', "Kullanıcı kaydedildi: {$email}", $id);
            $this->ok('Kullanıcı kaydedildi.', ['id' => $id, 'kullanicilar' => $this->kullaniciListesi()]);
        });
    }

    /** Sistem profili id: tip + opsiyonel otopark|personel. */
    private function sistemProfilId(string $tip, ?string $alt = null): ?int
    {
        $rows = Database::fetchAll(
            'SELECT id, ad FROM yetki_profilleri WHERE sistem_mi AND aktif AND tip = :t ORDER BY id',
            ['t' => $tip]
        );
        if ($rows === []) {
            return null;
        }
        if ($tip !== 'bayi' || $alt === null) {
            return (int) $rows[0]['id'];
        }
        foreach ($rows as $r) {
            $personel = Yetki::otoparkPersonelMi((string) $r['ad']);
            if ($alt === 'personel' && $personel) {
                return (int) $r['id'];
            }
            if ($alt === 'otopark' && !$personel) {
                return (int) $r['id'];
            }
        }

        return (int) $rows[0]['id'];
    }

    /** @return list<array<string,mixed>> */
    private function kullaniciListesi(): array
    {
        return Database::fetchAll(
            "SELECT u.id, u.name, u.email, u.kullanici_adi, u.telefon, u.role, u.bayi_id, u.musteri_id, u.bayi_kapsam, u.yetki_profil_id,
                    u.kullanici_grubu_id, u.is_active, u.last_login_at,
                    b.ad AS bayi_adi, m.ad AS musteri_adi, yp.ad AS profil_adi
             FROM users u
             LEFT JOIN bayiler b ON b.id = u.bayi_id
             LEFT JOIN musteriler m ON m.id = u.musteri_id
             LEFT JOIN yetki_profilleri yp ON yp.id = u.yetki_profil_id
             ORDER BY CASE u.role WHEN 'admin' THEN 0 WHEN 'bayi' THEN 1 ELSE 2 END, u.name"
        );
    }
}
