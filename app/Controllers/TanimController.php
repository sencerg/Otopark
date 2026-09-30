<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\DataTable;
use App\Core\Log;
use App\Core\Request;
use App\Core\Tanim;
use RuntimeException;

/**
 * BİA "Araç Tanımları" sayfasındaki tüm tanım listeleri için ortak CRUD.
 * Alan tipleri: text, number, decimal, select (options: tablo adı veya dizi), bool, textarea, password.
 */
final class TanimController extends Controller
{
    private static function tanimlar(): array
    {
        return [
            'arac_markalari' => ['baslik' => 'Araç Markaları', 'tablo' => 'markalar', 'icon' => 'mdi-car-info', 'grup' => 'Araç',
                'alanlar' => ['ad' => ['Marka Adı', 'text', true]]],
            'arac_serileri' => ['baslik' => 'Araç Serileri', 'tablo' => 'seriler', 'icon' => 'mdi-car-side', 'grup' => 'Araç',
                'alanlar' => ['marka_id' => ['Marka', 'select', true, 'markalar'], 'ad' => ['Seri Adı', 'text', true]]],
            'arac_modelleri' => ['baslik' => 'Araç Modelleri', 'tablo' => 'modeller', 'icon' => 'mdi-car-cog', 'grup' => 'Araç',
                'alanlar' => ['seri_id' => ['Seri', 'select', true, 'seriler_tam'], 'ad' => ['Model Adı', 'text', true]]],
            'arac_tipleri' => ['baslik' => 'Araç Tipleri', 'tablo' => 'arac_tipleri', 'icon' => 'mdi-truck-outline', 'grup' => 'Araç',
                'alanlar' => ['ad' => ['Araç Tipi', 'text', true]]],
            'kasa_tipleri' => ['baslik' => 'Kasa Tipleri', 'tablo' => 'kasa_tipleri', 'icon' => 'mdi-car-estate', 'grup' => 'Araç',
                'alanlar' => ['ad' => ['Kasa Tipi', 'text', true]]],
            'model_yillari' => ['baslik' => 'Model Yılları', 'tablo' => 'model_yillari', 'icon' => 'mdi-calendar-range', 'grup' => 'Araç', 'sira' => 'yil DESC',
                'alanlar' => ['yil' => ['Yıl', 'number', true]]],
            'renkler' => ['baslik' => 'Renkler', 'tablo' => 'renkler', 'icon' => 'mdi-palette-outline', 'grup' => 'Araç',
                'alanlar' => ['ad' => ['Renk', 'text', true]]],
            'yakit_tipleri' => ['baslik' => 'Yakıt Tipleri', 'tablo' => 'yakit_tipleri', 'icon' => 'mdi-gas-station-outline', 'grup' => 'Araç',
                'alanlar' => ['ad' => ['Yakıt Tipi', 'text', true]]],
            'vites_tipleri' => ['baslik' => 'Vites Tipleri', 'tablo' => 'vites_tipleri', 'icon' => 'mdi-car-shift-pattern', 'grup' => 'Araç',
                'alanlar' => ['ad' => ['Vites Tipi', 'text', true]]],
            'arac_envanterleri' => ['baslik' => 'Araç Envanterleri', 'tablo' => 'envanterler', 'icon' => 'mdi-toolbox-outline', 'grup' => 'Araç',
                'alanlar' => ['ad' => ['Envanter', 'text', true]]],
            'arac_donanimlari' => ['baslik' => 'Araç Donanımları', 'tablo' => 'donanimlar', 'icon' => 'mdi-cogs', 'grup' => 'Araç',
                'alanlar' => ['grup' => ['Grup', 'select', true, ['Güvenlik' => 'Güvenlik', 'İç Donanım' => 'İç Donanım', 'Multimedia' => 'Multimedia', 'Dış Donanım' => 'Dış Donanım']], 'ad' => ['Donanım', 'text', true]]],
            'hareket_nedenleri' => ['baslik' => 'Hareket Nedenleri', 'tablo' => 'hareket_nedenleri', 'icon' => 'mdi-swap-horizontal', 'grup' => 'Operasyon',
                'alanlar' => ['ad' => ['Hareket Nedeni', 'text', true]]],
            'maliyet_tipleri' => ['baslik' => 'Maliyet Tipleri / İş Emri Türleri', 'tablo' => 'maliyet_tipleri', 'icon' => 'mdi-cash-register', 'grup' => 'Operasyon', 'admin' => true,
                'alanlar' => ['ad' => ['Maliyet Tipi', 'text', true], 'varsayilan_tutar' => ['Varsayılan Tutar (₺)', 'decimal', true], 'depolama_mi' => ['Depolama Kalemi', 'bool', false], 'aktif' => ['Aktif', 'bool', false]]],
            'depolama_fiyatlari' => ['baslik' => 'Depolama Fiyatları', 'tablo' => 'depolama_fiyatlari', 'icon' => 'mdi-cash-clock', 'grup' => 'Operasyon', 'admin' => true, 'sira' => 'musteri_id, bayi_id',
                'alanlar' => ['musteri_id' => ['Müşteri', 'select', true, 'musteriler'], 'bayi_id' => ['Lokasyon', 'select', true, 'bayiler'], 'arac_tipi_id' => ['Araç Tipi (boş = tümü)', 'select', false, 'arac_tipleri'], 'gunluk_fiyat' => ['Günlük Fiyat (₺)', 'decimal', true]]],
            'musteriler' => ['baslik' => 'Müşteriler (Firmalar)', 'tablo' => 'musteriler', 'icon' => 'mdi-domain', 'grup' => 'Firma ve Personel', 'admin' => true,
                'alanlar' => ['ad' => ['Firma Adı', 'text', true], 'vergi_no' => ['Vergi No', 'text', false], 'telefon' => ['Telefon', 'text', false], 'eposta' => ['E-posta', 'text', false], 'aktif' => ['Aktif', 'bool', false]]],
            'bayiler' => ['baslik' => 'Lokasyonlar (Bayiler)', 'tablo' => 'bayiler', 'icon' => 'mdi-map-marker-multiple-outline', 'grup' => 'Firma ve Personel', 'admin' => true,
                'alanlar' => ['ad' => ['Lokasyon Adı', 'text', true], 'sehir' => ['Şehir', 'text', false], 'adres' => ['Adres', 'textarea', false], 'aktif' => ['Aktif', 'bool', false]]],
            'departmanlar' => ['baslik' => 'Departmanlar', 'tablo' => 'departmanlar', 'icon' => 'mdi-sitemap-outline', 'grup' => 'Firma ve Personel', 'admin' => true,
                'alanlar' => ['ad' => ['Departman', 'text', true]]],
            'personeller' => ['baslik' => 'Personeller', 'tablo' => 'personeller', 'icon' => 'mdi-account-hard-hat-outline', 'grup' => 'Firma ve Personel', 'sira' => 'ad_soyad',
                'alanlar' => ['ad_soyad' => ['Ad Soyad', 'text', true], 'departman_id' => ['Departman', 'select', false, 'departmanlar'], 'bayi_id' => ['Lokasyon', 'select', false, 'bayiler'], 'telefon' => ['Telefon', 'text', false], 'aktif' => ['Aktif', 'bool', false]]],
            'kullanicilar' => ['baslik' => 'Kullanıcılar', 'tablo' => 'users', 'icon' => 'mdi-account-key-outline', 'grup' => 'Firma ve Personel', 'admin' => true, 'sira' => 'name',
                'alanlar' => ['name' => ['Ad Soyad', 'text', true], 'email' => ['E-posta (Kullanıcı Adı)', 'text', true], 'password' => ['Şifre (değiştirmek için doldurun)', 'password', false],
                    'role' => ['Rol', 'select', true, ['admin' => 'Yönetici (tüm lokasyonlar)', 'bayi' => 'Bayi Kullanıcısı (sadece kendi lokasyonu)']],
                    'bayi_id' => ['Lokasyon', 'select', false, 'bayiler'], 'kullanici_grubu_id' => ['Kullanıcı Grubu', 'select', false, 'kullanici_gruplari'],
                    'telefon' => ['Telefon', 'text', false], 'is_active' => ['Aktif', 'bool', false]]],
        ];
    }

    private function tanim(string $tip): array
    {
        $t = self::tanimlar()[$tip] ?? null;
        if (!$t) {
            $this->notFound();
        }
        if ($tip === 'kullanicilar') {
            Auth::requireAdmin();
        }

        return $t;
    }

    private function yazmaYetkisi(array $t): void
    {
        if (!empty($t['admin']) && !Auth::isAdmin()) {
            throw new RuntimeException('Bu tanımı değiştirme yetkiniz yok.');
        }
    }

    public static function secenekler(string|array $kaynak): array
    {
        if (is_array($kaynak)) {
            return $kaynak;
        }

        return match ($kaynak) {
            'seriler_tam' => array_column(Database::fetchAll("SELECT s.id, m.ad || ' / ' || s.ad AS ad FROM seriler s JOIN markalar m ON m.id = s.marka_id ORDER BY m.ad, s.ad"), 'ad', 'id'),
            'bayiler' => array_column(Database::fetchAll('SELECT id, ad FROM bayiler ORDER BY id'), 'ad', 'id'),
            'musteriler' => array_column(Database::fetchAll('SELECT id, ad FROM musteriler ORDER BY ad'), 'ad', 'id'),
            default => Tanim::liste($kaynak),
        };
    }

    public function index(): void
    {
        $tanimlar = self::tanimlar();
        if (!Auth::isAdmin()) {
            unset($tanimlar['kullanicilar']);
        }
        $sayilar = [];
        foreach ($tanimlar as $tip => $t) {
            $sayilar[$tip] = (int) Database::fetch("SELECT COUNT(*) c FROM {$t['tablo']}")['c'];
        }
        $this->view('tanimlar/index', [
            'pageTitle' => 'Araç Tanımları', 'tanimlar' => $tanimlar, 'sayilar' => $sayilar,
            'breadcrumb' => ['Saha Operasyon Tanımları' => null, 'Araç Tanımları' => null],
        ]);
    }

    public function liste(string $tip): void
    {
        $t = $this->tanim($tip);
        $this->view('tanimlar/liste', [
            'pageTitle' => $t['baslik'], 'tip' => $tip, 't' => $t,
            'secenekler' => array_map(fn ($a) => isset($a[3]) ? self::secenekler($a[3]) : null, $t['alanlar']),
            'yazabilir' => empty($t['admin']) || Auth::isAdmin(),
            'breadcrumb' => ['Araç Tanımları' => '/arac_yonetimi/tanimlar', $t['baslik'] => null],
        ]);
    }

    public function data(string $tip): void
    {
        $t = $this->tanim($tip);
        $kolonlar = ['id' => 'id'];
        $aranabilir = [];
        foreach ($t['alanlar'] as $alan => $a) {
            if ($a[1] === 'password') {
                continue;
            }
            $kolonlar[$alan] = $alan;
            if (in_array($a[1], ['text', 'textarea', 'number'], true)) {
                $aranabilir[] = $alan;
            }
        }
        $dt = new DataTable($t['tablo'], $kolonlar, [], [], $aranabilir, $t['sira'] ?? 'id');
        $secenekler = array_map(fn ($a) => isset($a[3]) ? self::secenekler($a[3]) : null, $t['alanlar']);
        $dt->response(function ($r) use ($secenekler) {
            foreach ($secenekler as $alan => $opts) {
                if ($opts !== null) {
                    $r[$alan . '_ad'] = $opts[$r[$alan] ?? ''] ?? '-';
                }
            }

            return $r;
        });
    }

    public function kaydet(string $tip): void
    {
        $t = $this->tanim($tip);
        $this->ajax(function () use ($t, $tip) {
            $this->yazmaYetkisi($t);
            $id = Request::int('id');
            $data = [];
            foreach ($t['alanlar'] as $alan => [$etiket, $tur, $zorunlu]) {
                $deger = match ($tur) {
                    'bool' => Request::bool($alan),
                    'number' => Request::int($alan),
                    'decimal' => Request::decimal($alan),
                    'select' => Request::str($alan),
                    default => Request::str($alan),
                };
                if ($tur === 'password') {
                    if ($deger === null) {
                        if (!$id) {
                            throw new RuntimeException('Yeni kullanıcı için şifre zorunludur.');
                        }
                        continue;
                    }
                    if (mb_strlen($deger) < 6) {
                        throw new RuntimeException('Şifre en az 6 karakter olmalıdır.');
                    }
                    $data['password_hash'] = password_hash($deger, PASSWORD_DEFAULT);
                    continue;
                }
                if ($zorunlu && ($deger === null || $deger === '')) {
                    throw new RuntimeException("{$etiket} alanı zorunludur.");
                }
                $data[$alan] = is_bool($deger) ? ($deger ? 'true' : 'false') : $deger;
            }
            if ($tip === 'kullanicilar') {
                $data['email'] = mb_strtolower((string) $data['email']);
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Geçerli bir e-posta giriniz.');
                }
                if ($data['role'] === 'bayi' && empty($data['bayi_id'])) {
                    throw new RuntimeException('Bayi kullanıcısı için lokasyon seçilmelidir.');
                }
                if ($id === Auth::id() && ($data['is_active'] === 'false' || $data['role'] !== 'admin')) {
                    throw new RuntimeException('Kendi hesabınızı pasifleştiremez veya yetkisini düşüremezsiniz.');
                }
            }

            if ($id) {
                $set = implode(', ', array_map(fn ($c) => "{$c} = :{$c}", array_keys($data)));
                Database::query("UPDATE {$t['tablo']} SET {$set} WHERE id = :_id", $data + ['_id' => $id]);
            } else {
                $cols = array_keys($data);
                $id = (int) Database::fetch(
                    "INSERT INTO {$t['tablo']} (" . implode(', ', $cols) . ') VALUES (' . implode(', ', array_map(fn ($c) => ':' . $c, $cols)) . ') RETURNING id',
                    $data
                )['id'];
            }
            Log::islem('tanim', $t['baslik'] . ' kaydedildi', $id);
            $this->ok('Kayıt kaydedildi.');
        });
    }

    public function getir(string $tip, string $id): void
    {
        $t = $this->tanim($tip);
        $cols = implode(', ', array_merge(['id'], array_keys(array_filter($t['alanlar'], fn ($a) => $a[1] !== 'password'))));
        $row = Database::fetch("SELECT {$cols} FROM {$t['tablo']} WHERE id = :id", ['id' => $id]);
        $row ? $this->ok('', ['data' => $row]) : $this->fail('Kayıt bulunamadı.', 404);
    }

    public function sil(string $tip, string $id): void
    {
        $t = $this->tanim($tip);
        $this->ajax(function () use ($t, $tip, $id) {
            $this->yazmaYetkisi($t);
            if ($tip === 'kullanicilar' && (int) $id === Auth::id()) {
                throw new RuntimeException('Kendi hesabınızı silemezsiniz.');
            }
            try {
                Database::query("DELETE FROM {$t['tablo']} WHERE id = :id", ['id' => $id]);
            } catch (\PDOException $e) {
                if (str_contains($e->getMessage(), 'foreign key')) {
                    throw new RuntimeException('Bu kayıt başka kayıtlarda kullanıldığı için silinemez. Pasif yapabilirsiniz.');
                }
                throw $e;
            }
            Log::islem('tanim', $t['baslik'] . ' silindi', (int) $id);
            $this->ok('Kayıt silindi.');
        });
    }
}
