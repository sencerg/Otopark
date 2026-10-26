<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Log;
use App\Core\Request;
use App\Services\DepolamaFiyatGecmisi;
use App\Services\DepolamaHesap;
use App\Services\HizmetFiyat;
use RuntimeException;

/**
 * Bağımsız "Tanımlamalar" sayfası: müşteri, otopark (lokasyon), hizmet ve marka / seri / model.
 * Alan: [etiket, tür (text|textarea|decimal|bool), zorunlu]
 */
final class TanimlamaController extends Controller
{
    private const TIPLER = [
        'musteri' => [
            'tablo' => 'musteriler', 'baslik' => 'Müşteri', 'admin' => true, 'sira' => 't.ad',
            'alanlar' => [
                'ad' => ['Firma Adı', 'text', true], 'vergi_no' => ['Vergi No', 'text', false], 'yetkili' => ['Yetkili Kişi', 'text', false],
                'telefon' => ['Telefon', 'text', false], 'eposta' => ['E-posta', 'text', false], 'adres' => ['Adres', 'textarea', false], 'aktif' => ['Aktif', 'bool', false],
            ],
            'ek' => '(SELECT COUNT(*) FROM araclar a WHERE a.musteri_id = t.id AND a.stokta AND NOT a.arsiv) AS stoktaki,
                     (SELECT COUNT(*) FROM depolama_fiyatlari f WHERE f.musteri_id = t.id AND f.arac_tipi_id IS NULL) AS ozel_fiyat,
                     (SELECT COUNT(*) FROM hizmet_fiyatlari hf WHERE hf.musteri_id = t.id) AS hizmet_fiyat',
        ],
        'lokasyon' => [
            'tablo' => 'bayiler', 'baslik' => 'Otopark', 'admin' => true, 'sira' => 't.id', 'bayi' => 't.id',
            'alanlar' => [
                'ad' => ['Otopark Adı', 'text', true], 'sehir' => ['Şehir', 'text', false], 'adres' => ['Adres', 'textarea', false],
                'aktif' => ['Aktif', 'bool', false],
            ],
            'ek' => '(SELECT COUNT(*) FROM araclar a WHERE a.bayi_id = t.id AND a.stokta AND NOT a.arsiv) AS stoktaki',
        ],
        'hizmet' => [
            'tablo' => 'maliyet_tipleri', 'baslik' => 'Hizmet', 'admin' => true, 'sira' => 't.ad',
            'alanlar' => [
                'ad' => ['Hizmet Adı', 'text', true],
                'bayi_fiyat_degistirebilir' => ['Bayi fiyatı değiştirebilir', 'bool', false],
                'tekrar_eklenebilir' => ['Bayi birden fazla ekleyebilir', 'bool', false],
                'aktif' => ['Aktif', 'bool', false],
            ],
            'ek' => '(SELECT COUNT(*) FROM arac_ekstreleri e WHERE e.maliyet_tipi_id = t.id) AS kullanim,
                     (SELECT COUNT(*) FROM hizmet_fiyatlari hf WHERE hf.maliyet_tipi_id = t.id) AS firma_fiyat',
        ],
        'personel' => [
            'tablo' => 'personeller', 'baslik' => 'Personel', 'sira' => 't.ad_soyad', 'ad_alan' => 'ad_soyad', 'bayi' => 't.bayi_id',
            'alanlar' => [
                'ad_soyad' => ['Ad Soyad', 'text', true],
                'telefon' => ['Telefon', 'text', false],
                'aktif' => ['Aktif', 'bool', false],
            ],
            'ek' => '(SELECT COUNT(*) FROM arac_hareketleri h WHERE h.teslim_alan_personel_id = t.id) AS kullanim,
                     (SELECT b.ad FROM bayiler b WHERE b.id = t.bayi_id) AS otopark',
        ],
        'marka' => [
            'tablo' => 'markalar', 'baslik' => 'Marka', 'sira' => 't.ad',
            'alanlar' => ['ad' => ['Marka Adı', 'text', true]],
            'ek' => '(SELECT COUNT(*) FROM seriler s WHERE s.marka_id = t.id) AS alt_sayi',
        ],
        'seri' => [
            'tablo' => 'seriler', 'baslik' => 'Seri', 'sira' => 't.ad', 'ust' => ['marka_id', 'markalar'],
            'alanlar' => ['ad' => ['Seri Adı', 'text', true]],
            'ek' => '(SELECT COUNT(*) FROM modeller m WHERE m.seri_id = t.id) AS alt_sayi',
        ],
        'model' => [
            'tablo' => 'modeller', 'baslik' => 'Model', 'sira' => 't.ad', 'ust' => ['seri_id', 'seriler'],
            'alanlar' => ['ad' => ['Model Adı', 'text', true]],
            'ek' => '(SELECT COUNT(*) FROM araclar a WHERE a.model_id = t.id) AS alt_sayi',
        ],
    ];

    /** Silinmeden önce alt kaydı olmaması gereken tipler. */
    private const ALT_KAYIT = ['marka' => ['seriler', 'marka_id', 'Önce markanın serilerini silin.'], 'seri' => ['modeller', 'seri_id', 'Önce serinin modellerini silin.']];

    public static function alanlar(): array
    {
        return array_map(fn ($t) => ['baslik' => $t['baslik'], 'alanlar' => $t['alanlar'], 'admin' => !empty($t['admin'])], self::TIPLER);
    }

    private function tip(string $tip): array
    {
        return self::TIPLER[$tip] ?? $this->notFound();
    }

    private function kosul(array $t, array &$params): string
    {
        $kosul = isset($t['bayi']) ? Auth::bayiKosulu($t['bayi']) : 'TRUE';
        if (isset($t['ust'])) {
            $params['ust'] = Request::int('ust_id') ?? 0;
            $kosul .= " AND t.{$t['ust'][0]} = :ust";
        }

        return $kosul;
    }

    public function index(): void
    {
        $this->view('tanimlamalar/index', [
            'pageTitle' => 'Tanımlamalar', 'tipler' => self::alanlar(), 'admin' => Auth::isAdmin(),
            'breadcrumb' => ['Tanımlamalar' => null],
        ]);
    }

    /** Müşteri kartı: firma bilgileri + hizmet / otopark fiyatları sekmeli sayfa. */
    public function musteriForm(?string $id = null): void
    {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            exit('Bu işlem için yetkiniz yok.');
        }
        $yeni = $id === null || $id === 'yeni';
        $musteri = null;
        $fiyatlar = [];
        $otoparkFiyatlari = [];
        if (!$yeni) {
            if (!ctype_digit((string) $id)) {
                $this->notFound();
            }
            $musteri = Database::fetch('SELECT * FROM musteriler WHERE id = :id', ['id' => (int) $id]);
            if (!$musteri) {
                $this->notFound();
            }
            $fiyatlar = HizmetFiyat::liste((int) $musteri['id'], null);
            $otoparkFiyatlari = DepolamaHesap::musteriOtoparkFiyatlari((int) $musteri['id']);
        }
        $hizmetler = Database::fetchAll(
            'SELECT id, ad FROM maliyet_tipleri WHERE aktif ORDER BY ad'
        );
        $bayiler = Database::fetchAll('SELECT id, ad, sehir FROM bayiler WHERE aktif ORDER BY ad');
        $sekme = match (Request::str('sekme')) {
            'fiyat' => 'fiyat',
            'otopark' => 'otopark',
            default => 'bilgi',
        };
        $otoparkOzelSayisi = count(array_filter(
            $otoparkFiyatlari,
            static fn ($r) => $r['firma_fiyat'] !== null && $r['firma_fiyat'] !== ''
        ));
        $this->view('tanimlamalar/musteri', [
            'pageTitle' => $yeni ? 'Yeni Müşteri' : ($musteri['ad'] ?? 'Müşteri'),
            'breadcrumb' => ['Tanımlamalar' => '/tanimlamalar', 'Müşteriler' => '/tanimlamalar#sekme-musteri', ($yeni ? 'Yeni' : 'Düzenle') => null],
            'musteri' => $musteri,
            'yeni' => $yeni,
            'fiyatlar' => $fiyatlar,
            'hizmetler' => $hizmetler,
            'bayiler' => $bayiler,
            'otoparkFiyatlari' => $otoparkFiyatlari,
            'otoparkOzelSayisi' => $otoparkOzelSayisi,
            'admin' => true,
            'sekme' => $sekme,
        ]);
    }

    public function liste(string $tip): void
    {
        $t = $this->tip($tip);
        $params = [];
        $rows = Database::fetchAll(
            "SELECT t.*, {$t['ek']} FROM {$t['tablo']} t WHERE " . $this->kosul($t, $params) . " ORDER BY {$t['sira']}",
            $params
        );
        $this->ok('', ['data' => $rows]);
    }

    public function kaydet(string $tip): void
    {
        $t = $this->tip($tip);
        $this->ajax(function () use ($t, $tip) {
            if (!empty($t['admin']) && !Auth::isAdmin()) {
                throw new RuntimeException('Bu tanımı yalnızca yönetici değiştirebilir.');
            }
            $id = Request::int('id');
            $data = [];
            foreach ($t['alanlar'] as $alan => [$etiket, $tur, $zorunlu]) {
                $deger = match ($tur) {
                    'bool' => Request::bool($alan) ? 'true' : 'false',
                    'decimal' => Request::decimal($alan),
                    default => Request::str($alan),
                };
                if ($zorunlu && ($deger === null || $deger === '')) {
                    throw new RuntimeException("{$etiket} alanı zorunludur.");
                }
                if ($tur === 'decimal' && $deger !== null && $deger < 0) {
                    throw new RuntimeException("{$etiket} negatif olamaz.");
                }
                $data[$alan] = $deger;
            }
            if (isset($t['ust'])) {
                [$ustAlan, $ustTablo] = $t['ust'];
                $ustId = Request::int($ustAlan);
                if (!$ustId || !Database::fetch("SELECT 1 FROM {$ustTablo} WHERE id = :id", ['id' => $ustId])) {
                    throw new RuntimeException('Üst kayıt seçilmelidir.');
                }
                $data[$ustAlan] = $ustId;
            }
            $this->dogrula($tip, $data, $id);

            if ($tip === 'personel') {
                $bayiId = Auth::bayiId() ?? Request::int('bayi_id');
                if (!$bayiId) {
                    throw new RuntimeException('Personel için otopark seçilmelidir.');
                }
                if (!Database::fetch(
                    'SELECT 1 FROM bayiler WHERE id = :id AND aktif AND ' . Auth::bayiKosulu('id'),
                    ['id' => $bayiId]
                )) {
                    throw new RuntimeException('Seçilen otopark bulunamadı veya erişim yetkiniz yok.');
                }
                $data['bayi_id'] = $bayiId;
            }
            // DB kolonları kalır; genel varsayılan fiyat kullanılmaz
            if ($tip === 'lokasyon' && !$id) {
                $data['gunluk_fiyat'] = 0;
                $data['fiyat_carpani'] = 1;
            }
            if ($tip === 'hizmet' && !$id) {
                $data['varsayilan_tutar'] = 0;
            }

            $adAlan = $t['ad_alan'] ?? 'ad';
            $ustKosul = isset($t['ust']) ? " AND {$t['ust'][0]} = :ust" : '';
            $bayiTekil = $tip === 'personel' ? ' AND bayi_id = :bayi_tekil' : '';
            if (Database::fetch(
                "SELECT 1 FROM {$t['tablo']} WHERE lower({$adAlan}) = lower(:ad) AND id <> :id{$ustKosul}{$bayiTekil}",
                ['ad' => $data[$adAlan], 'id' => $id ?? 0]
                + (isset($t['ust']) ? ['ust' => $data[$t['ust'][0]]] : [])
                + ($tip === 'personel' ? ['bayi_tekil' => $data['bayi_id']] : [])
            )) {
                throw new RuntimeException("Bu adla kayıtlı bir " . mb_strtolower($t['baslik']) . ' zaten var.');
            }

            if ($id) {
                $kosul = isset($t['bayi']) ? Auth::bayiKosulu($t['bayi']) : 'TRUE';
                $set = implode(', ', array_map(fn ($c) => "{$c} = :{$c}", array_keys($data)));
                if (Database::query("UPDATE {$t['tablo']} t SET {$set} WHERE t.id = :_id AND {$kosul}", $data + ['_id' => $id])->rowCount() === 0) {
                    throw new RuntimeException('Kayıt bulunamadı veya erişim yetkiniz yok.');
                }
            } else {
                $cols = array_keys($data);
                $id = (int) Database::fetch(
                    "INSERT INTO {$t['tablo']} (" . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ') RETURNING id',
                    $data
                )['id'];
            }
            if ($tip === 'musteri' && Request::bool('fiyat_senkron')) {
                $this->musteriHizmetFiyatlariniKaydet($id);
            }
            if ($tip === 'musteri' && Request::bool('otopark_senkron')) {
                $this->musteriOtoparkFiyatlariniKaydet($id);
            }
            Log::islem('tanim', "{$t['baslik']} kaydedildi: {$data[$adAlan]}", $id);
            $yonlendir = null;
            if ($tip === 'musteri' && Request::bool('sayfa')) {
                $sekme = Request::bool('otopark_senkron') ? 'otopark'
                    : (Request::bool('fiyat_senkron') || !Request::int('id') ? 'fiyat' : '');
                $yonlendir = '/tanimlamalar/musteri/' . $id . ($sekme ? '?sekme=' . $sekme : '');
            }
            $this->ok("{$t['baslik']} kaydedildi.", ['id' => $id, 'redirect' => $yonlendir]);
        });
    }

    /** Firma bazlı hizmet fiyat listesi (yalnızca müşteri bağlamı). */
    public function fiyatListe(): void
    {
        $this->ajax(function () {
            $musteriId = Request::int('musteri_id');
            if (!$musteriId) {
                throw new RuntimeException('Müşteri seçilmelidir.');
            }
            $this->ok('', ['data' => HizmetFiyat::liste($musteriId, null)]);
        });
    }

    /**
     * POST: fiyat_bayi_id[] + fiyat_hizmet_id[] + fiyat_tutar[]
     * Aynı otopark + hizmet çifti bir kez; tüm satırlar müşteri için senkronize edilir.
     */
    private function musteriHizmetFiyatlariniKaydet(int $musteriId): void
    {
        $tutarlar = (array) ($_POST['fiyat_tutar'] ?? []);
        $hizmetler = (array) ($_POST['fiyat_hizmet_id'] ?? []);
        $bayiler = (array) ($_POST['fiyat_bayi_id'] ?? []);
        $gelen = [];
        foreach ($hizmetler as $i => $hizmetId) {
            if (!is_scalar($hizmetId) || (int) $hizmetId <= 0) {
                continue;
            }
            $bayiId = $bayiler[$i] ?? null;
            if (!is_scalar($bayiId) || (int) $bayiId <= 0) {
                throw new RuntimeException('Hizmet fiyatı için otopark seçilmelidir.');
            }
            $bayiId = (int) $bayiId;
            $ham = $tutarlar[$i] ?? '';
            if (!is_scalar($ham) || trim((string) $ham) === '') {
                continue;
            }
            $tutar = Request::parseDecimal((string) $ham);
            if ($tutar === null || $tutar < 0) {
                throw new RuntimeException('Firma hizmet fiyatı geçersiz.');
            }
            $hizmetId = (int) $hizmetId;
            $anahtar = $bayiId . ':' . $hizmetId;
            if (isset($gelen[$anahtar])) {
                throw new RuntimeException('Aynı otopark ve hizmet için birden fazla fiyat satırı var.');
            }
            if (!Database::fetch('SELECT 1 FROM maliyet_tipleri WHERE id = :id AND aktif', ['id' => $hizmetId])) {
                throw new RuntimeException('Seçilen hizmet bulunamadı veya pasif.');
            }
            if (!Database::fetch('SELECT 1 FROM bayiler WHERE id = :id AND aktif', ['id' => $bayiId])) {
                throw new RuntimeException('Seçilen otopark bulunamadı veya pasif.');
            }
            $gelen[$anahtar] = ['b' => $bayiId, 't' => $hizmetId, 'u' => $tutar];
            Database::query(
                'INSERT INTO hizmet_fiyatlari (musteri_id, bayi_id, maliyet_tipi_id, tutar, updated_at)
                 VALUES (:m, :b, :t, :u, NOW())
                 ON CONFLICT (musteri_id, bayi_id, maliyet_tipi_id)
                 DO UPDATE SET tutar = EXCLUDED.tutar, updated_at = NOW()',
                ['m' => $musteriId, 'b' => $bayiId, 't' => $hizmetId, 'u' => $tutar]
            );
        }
        if ($gelen) {
            $keep = array_values($gelen);
            $conds = [];
            $params = ['m' => $musteriId];
            foreach ($keep as $i => $row) {
                $conds[] = "(bayi_id = :b{$i} AND maliyet_tipi_id = :t{$i})";
                $params["b{$i}"] = $row['b'];
                $params["t{$i}"] = $row['t'];
            }
            Database::query(
                'DELETE FROM hizmet_fiyatlari WHERE musteri_id = :m AND NOT (' . implode(' OR ', $conds) . ')',
                $params
            );
        } else {
            Database::query('DELETE FROM hizmet_fiyatlari WHERE musteri_id = :m', ['m' => $musteriId]);
        }
    }

    /**
     * Müşteri kartından otopark (depolama) günlük ücretlerini senkronize eder.
     * POST: otopark_bayi_id[] + otopark_tutar[] — boş tutar = bu firmada o otopark için fiyat kaldırılır.
     * Araç tipi boş (NULL) satırları yönetir; tip bazlı eski tarifelere dokunulmaz.
     */
    private function musteriOtoparkFiyatlariniKaydet(int $musteriId): void
    {
        $bayiler = (array) ($_POST['otopark_bayi_id'] ?? []);
        $tutarlar = (array) ($_POST['otopark_tutar'] ?? []);
        $gelen = [];
        foreach ($bayiler as $i => $bayiId) {
            if (!is_scalar($bayiId) || (int) $bayiId <= 0) {
                continue;
            }
            $bayiId = (int) $bayiId;
            if (isset($gelen[$bayiId])) {
                throw new RuntimeException('Aynı otopark için birden fazla satır var.');
            }
            if (!Database::fetch('SELECT 1 FROM bayiler WHERE id = :id AND aktif', ['id' => $bayiId])) {
                throw new RuntimeException('Seçilen otopark bulunamadı veya pasif.');
            }
            $gelen[$bayiId] = true;
            $ham = $tutarlar[$i] ?? '';
            if (!is_scalar($ham) || trim((string) $ham) === '') {
                Database::query(
                    'DELETE FROM depolama_fiyatlari WHERE musteri_id = :m AND bayi_id = :b AND arac_tipi_id IS NULL',
                    ['m' => $musteriId, 'b' => $bayiId]
                );
                DepolamaFiyatGecmisi::kapat($bayiId, $musteriId, null);
                continue;
            }
            $tutar = Request::parseDecimal((string) $ham);
            if ($tutar === null || $tutar < 0) {
                throw new RuntimeException('Otopark firma ücreti geçersiz.');
            }
            // NULL arac_tipi_id ile ON CONFLICT bazı ortamlarda eşleşmez / yanlış "unique" hatası verir.
            $guncellenen = Database::query(
                'UPDATE depolama_fiyatlari SET gunluk_fiyat = :f
                 WHERE musteri_id = :m AND bayi_id = :b AND arac_tipi_id IS NULL',
                ['m' => $musteriId, 'b' => $bayiId, 'f' => $tutar]
            )->rowCount();
            if ($guncellenen === 0) {
                Database::query(
                    'INSERT INTO depolama_fiyatlari (musteri_id, bayi_id, arac_tipi_id, gunluk_fiyat)
                     VALUES (:m, :b, NULL, :f)',
                    ['m' => $musteriId, 'b' => $bayiId, 'f' => $tutar]
                );
            }
            DepolamaFiyatGecmisi::kaydet($bayiId, $musteriId, null, (float) $tutar);
        }
    }

    private function dogrula(string $tip, array $data, ?int $id): void
    {
        if ($tip === 'musteri') {
            if ($data['eposta'] && !filter_var($data['eposta'], FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Geçerli bir e-posta giriniz.');
            }
        }
    }

    public function sil(string $tip, string $id): void
    {
        $t = $this->tip($tip);
        $this->ajax(function () use ($t, $tip, $id) {
            if (!empty($t['admin']) && !Auth::isAdmin()) {
                throw new RuntimeException('Bu tanımı yalnızca yönetici silebilir.');
            }
            if (isset(self::ALT_KAYIT[$tip])) {
                [$altTablo, $altAlan, $mesaj] = self::ALT_KAYIT[$tip];
                if (Database::fetch("SELECT 1 FROM {$altTablo} WHERE {$altAlan} = :id LIMIT 1", ['id' => $id])) {
                    throw new RuntimeException($mesaj);
                }
            }
            try {
                $kosul = isset($t['bayi']) ? Auth::bayiKosulu($t['bayi']) : 'TRUE';
                if (Database::query(
                    "DELETE FROM {$t['tablo']} t WHERE t.id = :id AND {$kosul}",
                    ['id' => (int) $id]
                )->rowCount() === 0) {
                    throw new RuntimeException('Kayıt bulunamadı veya erişim yetkiniz yok.');
                }
            } catch (\PDOException $e) {
                if (str_contains($e->getMessage(), 'foreign key')) {
                    throw new RuntimeException('Bu kayıt araç veya hareket kayıtlarında kullanıldığı için silinemez.' . (isset($t['alanlar']['aktif']) ? ' Pasif yapabilirsiniz.' : ''));
                }
                throw $e;
            }
            Log::islem('tanim', "{$t['baslik']} silindi", (int) $id);
            $this->ok("{$t['baslik']} silindi.");
        });
    }
}
