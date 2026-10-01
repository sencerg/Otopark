<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Log;
use App\Core\Request;
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
                     (SELECT COUNT(*) FROM depolama_fiyatlari f WHERE f.musteri_id = t.id) AS ozel_fiyat',
        ],
        'lokasyon' => [
            'tablo' => 'bayiler', 'baslik' => 'Otopark', 'admin' => true, 'sira' => 't.id', 'bayi' => 't.id',
            'alanlar' => [
                'ad' => ['Otopark Adı', 'text', true], 'sehir' => ['Şehir', 'text', false], 'adres' => ['Adres', 'textarea', false],
                'gunluk_fiyat' => ['Standart Günlük Fiyat (₺)', 'decimal', true], 'fiyat_carpani' => ['Günlük Fiyat Çarpanı', 'decimal', true], 'aktif' => ['Aktif', 'bool', false],
            ],
            'ek' => '(SELECT COUNT(*) FROM araclar a WHERE a.bayi_id = t.id AND a.stokta AND NOT a.arsiv) AS stoktaki',
        ],
        'hizmet' => [
            'tablo' => 'maliyet_tipleri', 'baslik' => 'Hizmet', 'admin' => true, 'sira' => 't.ad',
            'alanlar' => ['ad' => ['Hizmet Adı', 'text', true], 'varsayilan_tutar' => ['Varsayılan Ücret (₺)', 'decimal', true], 'aktif' => ['Aktif', 'bool', false]],
            'ek' => '(SELECT COUNT(*) FROM arac_ekstreleri e WHERE e.maliyet_tipi_id = t.id) AS kullanim',
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
            $ustKosul = isset($t['ust']) ? " AND {$t['ust'][0]} = :ust" : '';
            if (Database::fetch(
                "SELECT 1 FROM {$t['tablo']} WHERE lower(ad) = lower(:ad) AND id <> :id{$ustKosul}",
                ['ad' => $data['ad'], 'id' => $id ?? 0] + (isset($t['ust']) ? ['ust' => $data[$t['ust'][0]]] : [])
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
            Log::islem('tanim', "{$t['baslik']} kaydedildi: {$data['ad']}", $id);
            $this->ok("{$t['baslik']} kaydedildi.", ['id' => $id]);
        });
    }

    private function dogrula(string $tip, array $data, ?int $id): void
    {
        if ($tip === 'musteri') {
            if ($data['eposta'] && !filter_var($data['eposta'], FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Geçerli bir e-posta giriniz.');
            }
        }
        if ($tip === 'lokasyon' && ($data['fiyat_carpani'] <= 0 || $data['fiyat_carpani'] > 10)) {
            throw new RuntimeException('Günlük fiyat çarpanı 0\'dan büyük ve en fazla 10 olmalıdır.');
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
                if (Database::query("DELETE FROM {$t['tablo']} WHERE id = :id", ['id' => (int) $id])->rowCount() === 0) {
                    throw new RuntimeException('Kayıt bulunamadı.');
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
