<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\DataTable;
use App\Core\Excel;
use App\Core\Log;
use App\Core\Request;
use App\Core\Tanim;
use App\Core\Upload;
use App\Core\View;
use App\Services\AracService;
use App\Services\DepolamaHesap;
use App\Services\TopluAktarim;
use RuntimeException;

final class AracController extends Controller
{
    public function index(): void
    {
        $this->view('araclar/liste', ['pageTitle' => 'Araç Listesi', 'breadcrumb' => ['Araç Yönetimi' => null]]);
    }

    public function liste(): void
    {
        $dt = new DataTable(
            'araclar a
             LEFT JOIN musteriler mu ON mu.id = a.musteri_id
             LEFT JOIN markalar m ON m.id = a.marka_id
             LEFT JOIN seriler s ON s.id = a.seri_id
             LEFT JOIN bayiler b ON b.id = a.bayi_id
             LEFT JOIN LATERAL (SELECT h.hareket_tipi FROM arac_hareketleri h WHERE h.arac_id = a.id AND NOT h.arsiv ORDER BY h.hareket_tarihi DESC LIMIT 1) sh ON TRUE',
            [
                'id' => 'a.id', 'sase' => 'a.sase', 'plaka' => 'a.plaka', 'firma' => 'mu.ad', 'marka' => 'm.ad', 'seri' => 's.ad', 'lokasyon' => 'b.ad',
                'son_hareket' => 'sh.hareket_tipi', 'stokta' => 'a.stokta',
                'kayit_kaynagi' => 'a.kayit_kaynagi',
                'musteri_musterisi' => 'a.musteri_musterisi',
                'giris_tarihi' => "to_char(a.stoga_giris_tarihi, 'DD.MM.YYYY')", 'giris_tarihi_sort' => 'a.stoga_giris_tarihi',
                'cikis_tarihi' => "to_char(a.stoktan_cikis_tarihi, 'DD.MM.YYYY')", 'cikis_tarihi_sort' => 'a.stoktan_cikis_tarihi',
                'depolama_suresi' => '(COALESCE(a.stoktan_cikis_tarihi::date, CURRENT_DATE) - a.stoga_giris_tarihi::date + 1)',
            ],
            ['NOT a.arsiv', Auth::bayiKosulu('a.bayi_id')],
            [],
            ['a.sase', 'a.plaka', 'b.ad', 'mu.ad', 'a.lokasyon_detay', 'a.musteri_musterisi'],
            'a.id DESC'
        );
        foreach (['marka_id', 'seri_id', 'musteri_id', 'arac_tipi_id', 'bayi_id'] as $key) {
            if ($v = Request::int($key)) {
                $dt->where("a.{$key} = :f_{$key}", ["f_{$key}" => $v]);
            }
        }
        match (Request::str('stok')) {
            'stokta' => $dt->where('a.stokta'),
            'cikti' => $dt->where('NOT a.stokta'),
            default => null,
        };
        if ($d = Request::date('baslangic')) {
            $dt->where('a.stoga_giris_tarihi::date >= :f_bas', ['f_bas' => $d]);
        }
        if ($d = Request::date('bitis')) {
            $dt->where('a.stoga_giris_tarihi::date <= :f_bit', ['f_bit' => $d]);
        }
        $arama = trim((string) (Request::str('q') ?? ''));
        $pin = Request::ids('pin');
        if ($arama === '' && $pin) {
            $dt->where('a.id = ANY(:pin::bigint[])', ['pin' => '{' . implode(',', $pin) . '}']);
        }

        $map = fn ($r) => $r + ['son_hareket_ad' => Tanim::HAREKET_TIPI[$r['son_hareket'] ?? 0] ?? '-'];
        if (Request::input('export')) {
            Excel::download('arac-listesi', [
                'sase' => 'Şase', 'plaka' => 'Plaka', 'firma' => 'Firma', 'marka' => 'Marka', 'seri' => 'Seri', 'lokasyon' => 'Lokasyon',
                'son_hareket_ad' => 'Son Hareket Tipi', 'giris_tarihi' => 'Giriş Tarihi', 'cikis_tarihi' => 'Çıkış Tarihi', 'depolama_suresi' => 'Depolama Süresi (Gün)',
            ], array_map($map, $dt->all()));
        }
        $dt->response($map);
    }

    public function ekle(): void
    {
        $this->view('araclar/form', [
            'pageTitle' => 'Araç Ekle',
            'breadcrumb' => ['Araç Yönetimi' => '/arac_yonetimi', 'Araç Ekle' => null],
            'arac' => null, 'envanter' => [], 'donanim' => [], 'dosyalar' => [], 'fotograflar' => [],
        ]);
        unset($_SESSION['_old']);
    }

    private function aracFormVerisi(): array
    {
        return [
            'sase' => Request::str('sase'), 'plaka' => Request::str('plaka'), 'arac_durumu' => Request::int('arac_durumu'),
            'arac_tipi_id' => Request::int('arac_tipi'), 'kasa_tipi_id' => Request::int('kasa_tipi'), 'renk_id' => Request::int('renk'),
            'park_kodu' => Request::str('park_kodu'), 'onceki_plaka' => Request::str('onceki_plaka'),
            'marka_id' => Request::int('marka_id'), 'seri_id' => Request::int('seri_id'), 'model_id' => Request::int('model_id'),
            'model_yili_id' => Request::int('yil'), 'motor_hacmi' => Request::int('motor_hacmi'), 'motor_gucu' => Request::int('motor_gucu'),
            'yakit_tipi_id' => Request::int('yakit_tipi'), 'vites_tipi_id' => Request::int('vites_tipi'),
            'km' => Request::int('km'), 'yakit_durumu' => Request::int('yakit_durumu'),
            'musteri_id' => Request::int('musteri_id'), 'bayi_id' => Request::int('bayi_id'),
            'lokasyon_turu' => Request::int('lokasyon_turu'), 'lokasyon_detay' => Request::str('lokasyon_detay'),
            'proje_adi' => Request::str('proje_adi'), 'konsinye' => Request::bool('konsinye'),
            'musteri_musterisi' => Request::str('musteri_musterisi'),
            'sigorta_tarihi' => Request::date('sigorta_tarihi'), 'kasko_tarihi' => Request::date('kasko_tarihi'), 'muayene_tarihi' => Request::date('muayene_tarihi'),
        ];
    }

    private function hareketFormVerisi(string $tarihAlani, string $saatAlani): array
    {
        $personelId = Request::int('teslim_alan_personel');
        Auth::dogrulaPersonel($personelId, 'Personel');

        return [
            'hareket_tipi' => Request::int('hareket_tipi') ?? 1,
            'hareket_nedeni_id' => Request::int('hareket_nedeni'),
            'lokasyon_turu' => Request::int('lokasyon_turu'), 'lokasyon_detay' => Request::str('lokasyon_detay'),
            'km' => Request::int('km'), 'yakit_durumu' => Request::int('yakit_durumu'),
            'teslim_eden' => Request::str('teslim_eden'), 'teslim_eden_telefon' => Request::str('teslim_eden_telefon'), 'teslim_eden_eposta' => Request::str('teslim_eden_eposta'),
            'teslim_alan' => Request::str('teslim_alan'), 'teslim_alan_telefon' => Request::str('teslim_alan_telefon'), 'teslim_alan_eposta' => Request::str('teslim_alan_eposta'),
            'teslim_alan_personel_id' => $personelId, 'aciklama' => Request::str('aciklama'),
            'hareket_tarihi' => Request::dateTime($tarihAlani, $saatAlani) ?? date('Y-m-d H:i:s'),
        ];
    }

    public function save(): void
    {
        $this->form(function () {
            if (!Request::int('arac_tipi') || !Request::int('marka_id') || !Request::int('seri_id')) {
                throw new RuntimeException('Araç tipi, marka ve seri zorunludur.');
            }
            $sonuc = AracService::kaydetVeHareket(
                $this->aracFormVerisi(),
                Request::int('hareket_tipi') ? $this->hareketFormVerisi('hareket_tarihi', 'hareket_saati') : null,
                Request::ids('arac_envanterleri'),
                Request::ids('arac_donanimlari')
            );
            Upload::save('fotograf', 'arac', $sonuc['arac_id'], 'fotograf');
            Upload::save('dosya', 'arac', $sonuc['arac_id'], 'belge', (array) ($_POST['dosya_tanim'] ?? []));
            flash('success', $sonuc['yeni'] ? 'Araç kaydedildi.' : 'Mevcut araç güncellendi ve hareket eklendi.');
            View::redirect('/arac_yonetimi/duzenle/' . $sonuc['arac_id']);
        }, '/arac_yonetimi/ekle');
    }

    public function hizliSave(): void
    {
        $this->ajax(function () {
            if (!Request::int('arac_tipi')) {
                throw new RuntimeException('Araç tipi seçilmelidir.');
            }
            $arac = $this->aracFormVerisi();
            $arac['arac_tipi_id'] = Request::int('arac_tipi');
            $arac['model_yili_id'] = Request::int('yil');
            $arac['yakit_tipi_id'] = Request::int('yakit_tipi');
            $arac['vites_tipi_id'] = Request::int('vites_tipi');
            unset($arac['konsinye']);

            $sonuc = AracService::kaydetVeHareket($arac, $this->hareketFormVerisi('hareket_tarihi_tarih', 'hareket_tarihi_saat'), Request::ids('arac_envanterleri'));
            if ($sayimId = Request::int('from_sayim')) {
                SayimController::stogaAlindi($sayimId, (int) $sonuc['arac_id']);
            }
            $this->ok($sonuc['yeni'] ? 'Araç stoğa eklendi.' : 'Araç hareketi kaydedildi.', ['arac_id' => $sonuc['arac_id']]);
        });
    }

    private function aracDetay(int $id): array
    {
        $arac = Database::fetch(
            'SELECT a.*, m.ad AS marka, s.ad AS seri, mo.ad AS model, mu.ad AS musteri, b.ad AS bayi, at.ad AS arac_tipi,
                    yt.ad AS yakit_tipi, my.yil, r.ad AS renk, kt.ad AS kasa_tipi, vt.ad AS vites_tipi
             FROM araclar a
             LEFT JOIN markalar m ON m.id = a.marka_id LEFT JOIN seriler s ON s.id = a.seri_id LEFT JOIN modeller mo ON mo.id = a.model_id
             LEFT JOIN musteriler mu ON mu.id = a.musteri_id LEFT JOIN bayiler b ON b.id = a.bayi_id LEFT JOIN arac_tipleri at ON at.id = a.arac_tipi_id
             LEFT JOIN yakit_tipleri yt ON yt.id = a.yakit_tipi_id LEFT JOIN model_yillari my ON my.id = a.model_yili_id
             LEFT JOIN renkler r ON r.id = a.renk_id LEFT JOIN kasa_tipleri kt ON kt.id = a.kasa_tipi_id LEFT JOIN vites_tipleri vt ON vt.id = a.vites_tipi_id
             WHERE a.id = :id AND ' . Auth::bayiKosulu('a.bayi_id'),
            ['id' => $id]
        );
        if (!$arac) {
            $this->notFound();
        }

        return $arac;
    }

    public function duzenle(string $id): void
    {
        $arac = $this->aracDetay((int) $id);
        $dosyalar = Upload::list('arac', (int) $id);

        $this->view('araclar/form', [
            'pageTitle' => 'Araç Düzenle',
            'breadcrumb' => ['Araç Yönetimi' => '/arac_yonetimi', $arac['sase'] => null],
            'arac' => $arac,
            'envanter' => array_column(Database::fetchAll('SELECT envanter_id FROM arac_envanterleri WHERE arac_id = :a', ['a' => $id]), 'envanter_id'),
            'donanim' => array_column(Database::fetchAll('SELECT donanim_id FROM arac_donanimlari WHERE arac_id = :a', ['a' => $id]), 'donanim_id'),
            'fotograflar' => array_values(array_filter($dosyalar, fn ($d) => $d['tur'] === 'fotograf')),
            'dosyalar' => array_values(array_filter($dosyalar, fn ($d) => $d['tur'] !== 'fotograf')),
            'hareketler' => Database::fetchAll(
                'SELECT h.id, h.hareket_tipi, h.hareket_tarihi, b.ad AS bayi, hn.ad AS neden FROM arac_hareketleri h
                 LEFT JOIN bayiler b ON b.id = h.bayi_id LEFT JOIN hareket_nedenleri hn ON hn.id = h.hareket_nedeni_id
                 WHERE h.arac_id = :a AND NOT h.arsiv ORDER BY h.hareket_tarihi DESC',
                ['a' => $id]
            ),
            'ekstre' => Database::fetchAll(
                'SELECT e.id, e.tutar, e.islem_tarihi, e.aciklama, e.fatura_no, e.cikis_sonrasi, mt.ad AS maliyet_tipi,
                        mt.bayi_fiyat_degistirebilir, b.ad AS bayi, u.name AS kullanici
                 FROM arac_ekstreleri e JOIN maliyet_tipleri mt ON mt.id = e.maliyet_tipi_id
                 LEFT JOIN bayiler b ON b.id = e.bayi_id LEFT JOIN users u ON u.id = e.kullanici_id
                 WHERE e.arac_id = :a ORDER BY e.islem_tarihi DESC, e.id DESC',
                ['a' => $id]
            ),
            'depolama' => DepolamaHesap::arac((int) $id),
            'hizmetler' => \App\Services\HizmetFiyat::firmaHizmetleri(
                isset($arac['musteri_id']) ? (int) $arac['musteri_id'] : null,
                isset($arac['bayi_id']) ? (int) $arac['bayi_id'] : null
            ),
        ]);
        unset($_SESSION['_old']);
    }

    public function update(string $id): void
    {
        $arac = $this->aracDetay((int) $id);
        $this->form(function () use ($arac) {
            $data = $this->aracFormVerisi();
            unset($data['sase']);
            $yeniSase = AracService::dogrulaSase(Request::str('sase'));
            if ($yeniSase !== $arac['sase']) {
                $data['sase'] = $yeniSase;
            }
            if (!$data['musteri_id']) {
                throw new RuntimeException('Müşteri seçilmelidir.');
            }
            $data['bayi_id'] = AracService::bayiKontrol($data['bayi_id']);
            $data['plaka'] = AracService::normalizePlaka($data['plaka']);
            $data['onceki_plaka'] = AracService::normalizePlaka($data['onceki_plaka']);
            AracService::markaSeriModelKontrol($data);
            if ($giris = Request::date('stoga_giris_tarihi')) {
                $data['stoga_giris_tarihi'] = $giris . ' ' . ($arac['stoga_giris_tarihi'] ? date('H:i:s', strtotime($arac['stoga_giris_tarihi'])) : '00:00:00');
            }

            Database::transaction(function () use ($arac, $data) {
                AracService::guncelle((int) $arac['id'], $data);
                foreach (['arac_envanterleri' => 'envanter_id', 'arac_donanimlari' => 'donanim_id'] as $table => $col) {
                    Database::query("DELETE FROM {$table} WHERE arac_id = :a", ['a' => $arac['id']]);
                    foreach (Request::ids($table) as $v) {
                        Database::query("INSERT INTO {$table} (arac_id, {$col}) VALUES (:a, :v)", ['a' => $arac['id'], 'v' => $v]);
                    }
                }
                Upload::save('fotograf', 'arac', (int) $arac['id'], 'fotograf');
                Upload::save('dosya', 'arac', (int) $arac['id'], 'belge', (array) ($_POST['dosya_tanim'] ?? []));
                Log::islem('arac', 'Araç güncellendi: ' . $arac['sase'], (int) $arac['id']);
            });
            flash('success', 'Araç bilgileri güncellendi.');
            View::redirect('/arac_yonetimi/duzenle/' . $arac['id']);
        }, '/arac_yonetimi/duzenle/' . $arac['id']);
    }

    public function tesellum(string $id): void
    {
        $arac = $this->aracDetay((int) $id);
        $sonHareket = Database::fetch(
            'SELECT h.*, p.ad_soyad AS personel FROM arac_hareketleri h LEFT JOIN personeller p ON p.id = h.teslim_alan_personel_id
             WHERE h.arac_id = :a AND NOT h.arsiv ORDER BY h.hareket_tarihi DESC LIMIT 1',
            ['a' => $id]
        );
        View::render('araclar/tesellum', [
            'arac' => $arac, 'h' => $sonHareket,
            'envanter' => array_column(Database::fetchAll(
                'SELECT e.ad FROM arac_envanterleri ae JOIN envanterler e ON e.id = ae.envanter_id WHERE ae.arac_id = :a', ['a' => $id]
            ), 'ad'),
            'tumEnvanter' => Tanim::liste('envanterler'),
        ], 'print');
    }

    public function qrToplu(): void
    {
        $ids = Request::ids('ids');
        if (!$ids) {
            $this->notFound();
        }
        $araclar = Database::fetchAll(
            'SELECT a.id, a.sase, a.plaka, m.ad AS marka, s.ad AS seri, mu.ad AS musteri, a.lokasyon_detay
             FROM araclar a LEFT JOIN markalar m ON m.id = a.marka_id LEFT JOIN seriler s ON s.id = a.seri_id LEFT JOIN musteriler mu ON mu.id = a.musteri_id
             WHERE a.id = ANY(:ids::bigint[]) AND ' . Auth::bayiKosulu('a.bayi_id') . ' ORDER BY a.id',
            ['ids' => '{' . implode(',', $ids) . '}']
        );
        View::render('araclar/qr', ['araclar' => $araclar], 'print');
    }

    public function topluArsiv(): void
    {
        $this->ajax(function () {
            $ids = Request::ids('ids');
            if (!$ids) {
                throw new RuntimeException('Kayıt seçilmedi.');
            }
            $n = Database::query(
                'UPDATE araclar SET arsiv = TRUE, updated_at = NOW() WHERE id = ANY(:ids::bigint[]) AND ' . Auth::bayiKosulu('bayi_id'),
                ['ids' => '{' . implode(',', $ids) . '}']
            )->rowCount();
            Log::islem('arac', "{$n} araç arşivlendi");
            $this->ok("{$n} araç arşivlendi.");
        });
    }

    public function topluSil(): void
    {
        Auth::requireAdmin();
        Auth::requireIzin('arac.sil');
        $this->ajax(function () {
            $ids = Request::ids('ids');
            if (!$ids) {
                throw new RuntimeException('Kayıt seçilmedi.');
            }
            $n = 0;
            $hatalar = [];
            foreach ($ids as $id) {
                $arac = Database::fetch(
                    'SELECT id, sase FROM araclar WHERE id = :id AND ' . Auth::bayiKosulu('bayi_id'),
                    ['id' => $id]
                );
                if (!$arac) {
                    $hatalar[] = '#' . $id . ' bulunamadı';
                    continue;
                }
                try {
                    $deleted = Database::query('DELETE FROM araclar WHERE id = :id', ['id' => $id])->rowCount();
                } catch (\PDOException $e) {
                    error_log($e->getMessage());
                    $hatalar[] = $arac['sase'] . ' silinemedi';
                    continue;
                }
                if ($deleted) {
                    Log::islem('arac', 'Araç silindi: ' . $arac['sase'], (int) $arac['id']);
                    $n++;
                }
            }
            if ($n === 0) {
                throw new RuntimeException($hatalar ? implode(' | ', array_slice($hatalar, 0, 8)) : 'Seçilen araçlar silinemedi.');
            }
            $mesaj = "{$n} araç silindi.";
            if ($hatalar) {
                $mesaj .= ' ' . count($hatalar) . ' kayıt atlandı.';
            }
            $this->ok($mesaj);
        });
    }

    public function sil(string $id): void
    {
        Auth::requireAdmin();
        Auth::requireIzin('arac.sil');
        $this->ajax(function () use ($id) {
            $arac = AracService::bul((int) $id);
            $n = Database::query(
                'DELETE FROM araclar WHERE id = :id AND ' . Auth::bayiKosulu('bayi_id'),
                ['id' => (int) $arac['id']]
            )->rowCount();
            if ($n === 0) {
                throw new RuntimeException('Araç silinemedi veya erişim yok.');
            }
            Log::islem('arac', 'Araç silindi: ' . $arac['sase'], (int) $arac['id']);
            $this->ok('Araç silindi.', ['redirect' => '/arac_yonetimi']);
        });
    }

    public function dosyaSil(string $id): void
    {
        $dosya = Database::fetch('SELECT * FROM dosyalar WHERE id = :id', ['id' => $id]);
        $back = $_SERVER['HTTP_REFERER'] ?? '/';
        if ($dosya) {
            if ($dosya['ilgili_tip'] === 'arac') {
                try {
                    AracService::bul((int) $dosya['ilgili_id']);
                } catch (RuntimeException) {
                    $this->notFound();
                }
            }
            Database::query('DELETE FROM dosyalar WHERE id = :id', ['id' => $id]);
            $path = BASE_PATH . '/public' . $dosya['dosya_yolu'];
            if (is_file($path) && str_starts_with(realpath($path), realpath(BASE_PATH . '/public/uploads'))) {
                unlink($path);
            }
            flash('success', 'Dosya silindi.');
        }
        View::redirect($back);
    }

    /* ---------- Toplu işlemler ---------- */

    public function topluStokGirisi(): void
    {
        $this->view('araclar/toplu_stok', ['pageTitle' => 'Toplu Stok Girişi', 'breadcrumb' => ['Araç Yönetimi' => '/arac_yonetimi', 'Toplu Stok Girişi' => null]]);
    }

    public function topluStokCikisi(): void
    {
        $secili = $this->seciliAraclar(Request::ids('ids'));
        $this->view('araclar/toplu_cikis', [
            'pageTitle' => 'Toplu Stok Çıkışı',
            'breadcrumb' => ['Araç Yönetimi' => '/arac_yonetimi', 'Toplu Stok Çıkışı' => null],
            'secili' => $secili,
        ]);
    }

    public function topluEkMaliyet(): void
    {
        $secili = $this->seciliAraclar(Request::ids('ids'));
        if ($secili) {
            $this->view('araclar/toplu_maliyet_secili', [
                'pageTitle' => 'Seçilen araçlara maliyet',
                'breadcrumb' => ['Araç Yönetimi' => '/arac_yonetimi', 'Toplu Maliyet' => null],
                'secili' => $secili,
            ]);
            return;
        }
        $this->view('araclar/toplu_excel', [
            'pageTitle' => 'Toplu Ek Maliyet Girişi', 'action' => '/arac_yonetimi/excel_toplu_ek_maliyet_giris', 'sablon' => 'maliyet',
            'aciklama' => 'Şablonu indirin. “Hizmet Kalemi” sütununa ikinci sayfadaki (Maliyet Tipleri) adlardan birini yazın; fiyatı girip XLSX olarak yükleyin. Şasi sistemde kayıtlı olmalıdır.',
            'breadcrumb' => ['Araç Yönetimi' => '/arac_yonetimi', 'Toplu Ek Maliyet Girişi' => null],
        ]);
    }

    public function seciliMaliyet(): void
    {
        if (!Auth::can('maliyet.yonet') && !Auth::can('menu.toplu')) {
            Auth::requireIzin('maliyet.yonet');
        }
        $ids = Request::ids('ids');
        $geri = '/arac_yonetimi/toplu_ek_maliyet_girisi' . ($ids ? '?' . http_build_query(['ids' => $ids]) : '');
        $this->form(function () use ($ids) {
            if (!$ids) {
                throw new RuntimeException('Araç seçilmedi.');
            }
            $satirlar = $this->maliyetSatirlari();
            if (!$satirlar) {
                throw new RuntimeException('En az bir maliyet girin.');
            }
            $araclar = $this->seciliAraclar($ids);
            if (!$araclar) {
                throw new RuntimeException('Seçilen araçlar bulunamadı.');
            }
            $basarili = 0;
            $hatalar = [];
            foreach ($araclar as $arac) {
                foreach ($satirlar as $payload) {
                    try {
                        AracService::maliyetEkle((int) $arac['id'], $payload);
                        $basarili++;
                    } catch (RuntimeException $e) {
                        $hatalar[] = ($arac['sase'] ?: $arac['plaka']) . ': ' . $e->getMessage();
                    }
                }
            }
            if ($basarili === 0) {
                throw new RuntimeException($hatalar ? implode(' | ', array_slice($hatalar, 0, 6)) : 'Maliyet eklenemedi.');
            }
            $kalem = count($satirlar);
            Log::islem('maliyet', "Seçili araçlara maliyet: {$basarili} kayıt, {$kalem} kalem");
            flash('success', count($araclar) . " araca {$kalem} maliyet satırı işlendi ({$basarili} kayıt).");
            if ($hatalar) {
                flash('error', count($hatalar) . ' kayıt atlandı: ' . implode(' | ', array_slice($hatalar, 0, 6)));
            }
            View::redirect('/arac_yonetimi');
        }, $geri);
    }

    public function topluLokasyon(): void
    {
        $this->view('araclar/toplu_excel', [
            'pageTitle' => 'Toplu Adres (Lokasyon) Güncelleme', 'action' => '/arac_yonetimi/excel_toplu_kayit_guncelleme_lokasyon', 'sablon' => 'lokasyon',
            'aciklama' => 'Şasi numarasına göre aracın lokasyon detayı (park yeri) ve park kodu güncellenir.',
            'breadcrumb' => ['Araç Yönetimi' => '/arac_yonetimi', 'Adres Düzenle' => null],
        ]);
    }

    public function sablon(string $tip): void
    {
        if ($tip === 'stok') {
            $this->stokSablonXlsx();
        }
        if ($tip === 'maliyet') {
            $this->maliyetSablonXlsx();
        }
        if ($tip === 'cikis') {
            // Referans BIA şablonu: Plaka (opsiyonel) + Şase; plaka boş / şasi tek başına OK
            Excel::downloadXlsx('Toplu_Stok_Cikisi_Sablonu', [
                'Worksheet' => [
                    'headers' => [
                        'plaka' => 'Plaka *',
                        'sase' => 'Şase Numarası *',
                    ],
                    'rows' => [
                        ['plaka' => '', 'sase' => 'TMBAN8NZ7TC066835'],
                        ['plaka' => '34ABC123', 'sase' => ''],
                    ],
                ],
            ]);
        }
        if ($tip === 'lokasyon') {
            Excel::download('sablon-lokasyon', [
                'sase' => 'sase', 'lokasyon_detay' => 'lokasyon_detay', 'park_kodu' => 'park_kodu',
            ], [['sase' => 'TMBAN8NZ7TC066835', 'lokasyon_detay' => 'B Blok 4', 'park_kodu' => 'P-104']]);
        }
        $this->notFound();
    }

    private function stokSablonXlsx(): never
    {
        $markalar = Database::fetchAll('SELECT id, ad FROM markalar ORDER BY ad');
        $seriler = Database::fetchAll(
            'SELECT s.id, m.ad AS marka, s.ad AS seri FROM seriler s JOIN markalar m ON m.id = s.marka_id ORDER BY m.ad, s.ad'
        );
        $ornekMarka = $markalar[0]['ad'] ?? 'Renault';
        $ornekSeri = Database::fetch('SELECT s.ad FROM seriler s JOIN markalar m ON m.id = s.marka_id WHERE m.ad = :a ORDER BY s.ad LIMIT 1', ['a' => $ornekMarka])['ad'] ?? '';
        Excel::downloadXlsx('Toplu_Stok_Girisi', [
            'Toplu Stok Girişi' => [
                'headers' => [
                    'plaka' => 'Plaka *',
                    'sase' => 'Şase Numarası *',
                    'marka' => 'MARKA *',
                    'seri' => 'Seri *',
                    'arac_durumu' => 'Araç Durumu',
                    'kasa_tipi' => 'Kasa Tipi',
                    'renk' => 'Renk',
                    'onceki_plaka' => 'Önceki Plaka',
                    'arac_tipi' => 'Araç Tipi',
                    'yil' => 'Yıl',
                    'yakit_tipi' => 'Yakıt Tipi',
                    'vites_tipi' => 'Vites Tipi',
                    'km' => 'KM',
                    'yakit_durumu' => 'Yakıt Durumu',
                    'lokasyon_detay' => 'Lokasyon Detay',
                    'sevkiyat_kodu' => 'Sevkiyat Kodu',
                    'irsaliye_kodu' => 'İrsaliye Kodu',
                ],
                'rows' => [[
                    'plaka' => '',
                    'sase' => 'TMBAN8NZ7TC066835',
                    'marka' => $ornekMarka,
                    'seri' => $ornekSeri,
                    'arac_durumu' => 'Sıfır',
                    'kasa_tipi' => '-',
                    'renk' => '-',
                    'onceki_plaka' => '',
                    'arac_tipi' => 'Otomobil',
                    'yil' => date('Y'),
                    'yakit_tipi' => '',
                    'vites_tipi' => '',
                    'km' => '0',
                    'yakit_durumu' => '0',
                    'lokasyon_detay' => 'Açık Saha',
                    'sevkiyat_kodu' => '',
                    'irsaliye_kodu' => '',
                ]],
            ],
            'Araç Markaları' => [
                'list' => array_merge([['ID', 'Marka Adı']], array_map(fn ($m) => [(string) $m['id'], $m['ad']], $markalar)),
            ],
            'Araç Serileri' => [
                'list' => array_merge(
                    [['ID', 'Marka ID', 'Seri Adı']],
                    array_map(fn ($s) => [(string) $s['id'], $s['marka'], $s['seri']], $seriler)
                ),
            ],
        ]);
    }

    private function maliyetSablonXlsx(): never
    {
        $tipler = Database::fetchAll('SELECT id, ad FROM maliyet_tipleri WHERE aktif ORDER BY ad');
        $ornekHizmet = $tipler[0]['ad'] ?? 'Araç Hazırlık';
        Excel::downloadXlsx('Toplu_Ek_Maliyet_Sablonu', [
            'Worksheet' => [
                'headers' => [
                    'plaka' => 'Plaka *',
                    'sase' => 'Şase Numarası *',
                    'marka' => 'Marka',
                    'maliyet_tipi' => 'Hizmet Kalemi *',
                    'tutar' => 'Fiyat',
                ],
                'rows' => [[
                    'plaka' => '',
                    'sase' => 'TMBAN8NZ7TC066835',
                    'marka' => '',
                    'maliyet_tipi' => $ornekHizmet,
                    'tutar' => '360',
                ]],
            ],
            'Maliyet Tipleri' => [
                'list' => array_merge([['ID', 'Maliyet Tipi']], array_map(fn ($t) => [(string) $t['id'], $t['ad']], $tipler)),
            ],
        ]);
    }

    /** @return array{0:string,1:string} [tmp_path, original_name] */
    private function yuklenenDosya(): array
    {
        $file = $_FILES['import_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Lütfen bir dosya seçiniz.');
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt', 'xlsx', 'xls'], true)) {
            throw new RuntimeException('Dosya XLSX veya CSV formatında olmalıdır.');
        }

        return [$file['tmp_name'], $file['name']];
    }

    /** Başlık hücresi şasi mi plaka mı. */
    private function baslikRolu(string $hucre): ?string
    {
        $h = str_replace(['İ', 'I', 'ı'], ['i', 'i', 'i'], trim($hucre));
        $h = mb_strtolower($h, 'UTF-8');
        $h = rtrim($h, " *\t");
        if (preg_match('/^(plaka|plate)$/u', $h)) {
            return 'plaka';
        }
        if (preg_match('/^(şase( numarası| no)?|sase( numarasi| no)?|şasi( numarası)?|sasi|chassis|vin)$/u', $h)) {
            return 'sase';
        }

        return null;
    }

    private function saseGibi(string $deger): bool
    {
        $n = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $deger) ?? '');

        return strlen($n) === 17;
    }

    /** @return list<string> */
    private function hucreBol(string $ham): array
    {
        if (str_contains($ham, "\t")) {
            return array_map('trim', explode("\t", $ham));
        }
        if (preg_match('/[;|]/u', $ham)) {
            return array_map('trim', preg_split('/[;|]/u', $ham) ?: []);
        }
        $genis = array_map('trim', preg_split('/\s{2,}/u', $ham) ?: []);
        if (count(array_filter($genis, static fn ($p) => $p !== '')) > 1) {
            return $genis;
        }
        if (preg_match('/^([A-Za-z0-9]{17})\s+(.+)$/', $ham, $m)) {
            return [trim($m[1]), trim($m[2])];
        }
        if (preg_match('/^(.+?)\s+([A-Za-z0-9]{17})$/', $ham, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        return [trim($ham)];
    }

    /**
     * Tek sütun alt alta, ya da Excel'den yan yana kopyalanmış şasi + plaka.
     *
     * @return list<array{ham:string,sase:string,plaka:string}>
     */
    private function yapistirmaSatirlari(string $metin): array
    {
        $satirlar = [];
        $rol = null;
        foreach (preg_split('/\R/u', $metin) ?: [] as $ham) {
            $ham = trim($ham, " \n\r\0\x0B");
            if ($ham === '') {
                continue;
            }
            $hucre = $this->hucreBol($ham);
            $roller = [];
            $baslik = true;
            foreach ($hucre as $h) {
                if ($h === '') {
                    $roller[] = null;
                    continue;
                }
                $r = $this->baslikRolu($h);
                if ($r === null) {
                    $baslik = false;
                    break;
                }
                $roller[] = $r;
            }
            if ($baslik && array_filter($roller)) {
                $rol = $roller;
                continue;
            }

            $sase = '';
            $plaka = '';
            if ($rol) {
                foreach ($hucre as $i => $h) {
                    $r = $rol[$i] ?? null;
                    if ($r === 'sase' && $sase === '') {
                        $sase = $h;
                    }
                    if ($r === 'plaka' && $plaka === '') {
                        $plaka = $h;
                    }
                }
            } else {
                $dolu = array_values(array_filter($hucre, static fn ($h) => $h !== ''));
                if (count($dolu) === 1) {
                    if ($this->saseGibi($dolu[0])) {
                        $sase = $dolu[0];
                    } else {
                        $plaka = $dolu[0];
                    }
                } elseif (count($dolu) >= 2) {
                    $aSase = $this->saseGibi($dolu[0]);
                    $bSase = $this->saseGibi($dolu[1]);
                    if ($bSase && !$aSase) {
                        $plaka = $dolu[0];
                        $sase = $dolu[1];
                    } else {
                        $sase = $dolu[0];
                        $plaka = $dolu[1];
                    }
                }
            }
            if ($sase === '' && $plaka === '') {
                continue;
            }
            $satirlar[] = ['ham' => $ham, 'sase' => $sase, 'plaka' => $plaka];
        }

        return $satirlar;
    }

    /** @return list<array{ham:string,sase:string,plaka:string}> */
    private function cikisKaynakSatirlari(): array
    {
        if (!empty($_FILES['import_file']['name'])) {
            [$path, $name] = $this->yuklenenDosya();
            $satirlar = [];
            foreach (Excel::read($path, $name) as $s) {
                $sase = trim((string) ($s['sase'] ?? ''));
                $plaka = trim((string) ($s['plaka'] ?? ''));
                if ($sase === '' && $plaka === '') {
                    continue;
                }
                $satirlar[] = ['ham' => trim($sase . "\t" . $plaka), 'sase' => $sase, 'plaka' => $plaka];
            }

            return $satirlar;
        }

        $metin = Request::input('yapiskan');
        $satirlar = $this->yapistirmaSatirlari(is_string($metin) ? $metin : '');
        $plakalar = (array) ($_POST['satir']['plaka'] ?? []);
        $saseList = (array) ($_POST['satir']['sase'] ?? []);
        $max = max(count($plakalar), count($saseList));
        for ($i = 0; $i < $max; $i++) {
            $sase = trim((string) ($saseList[$i] ?? ''));
            $plaka = trim((string) ($plakalar[$i] ?? ''));
            if ($sase === '' && $plaka === '') {
                continue;
            }
            $satirlar[] = ['ham' => trim($sase . ' ' . $plaka), 'sase' => $sase, 'plaka' => $plaka];
        }

        return $satirlar;
    }

    private function stoktaMi(mixed $deger): bool
    {
        return $deger === true || $deger === 1 || $deger === '1' || $deger === 't' || $deger === 'true';
    }

    /**
     * Her satırı stokta, stokta değil, kayıtlı değil, tekrar veya belirsiz diye ayırır.
     *
     * @param list<array{ham:string,parcalar:list<string>}> $satirlar
     * @return list<array{ham:string,durum:string,mesaj:string,arac:?array}>
     */
    private function cikisSatirlariniAyir(array $satirlar): array
    {
        $anahtarlar = [];
        foreach ($satirlar as $satir) {
            foreach ([$satir['sase'] ?? '', $satir['plaka'] ?? ''] as $parca) {
                $anahtar = AracService::normalizePlaka((string) $parca);
                if ($anahtar !== null) {
                    $anahtarlar[$anahtar] = true;
                }
            }
        }

        $bulunan = [];
        $anahtarListe = array_keys($anahtarlar);
        if ($anahtarListe) {
            $dizi = '{' . implode(',', $anahtarListe) . '}';
            $rows = Database::fetchAll(
                "SELECT a.id, a.sase, a.plaka, a.stokta, a.stoga_giris_tarihi, a.musteri_id, a.bayi_id,
                        regexp_replace(upper(coalesce(a.plaka, '')), '[^0-9A-Z]', '', 'g') AS plaka_k
                 FROM araclar a
                 WHERE NOT COALESCE(a.arsiv, false) AND " . Auth::bayiKosulu('a.bayi_id') . "
                   AND (
                        a.sase = ANY(:k::text[])
                        OR regexp_replace(upper(coalesce(a.plaka, '')), '[^0-9A-Z]', '', 'g') = ANY(:k2::text[])
                   )",
                ['k' => $dizi, 'k2' => $dizi]
            );
            foreach ($rows as $row) {
                $bulunan[$row['sase']][] = $row;
                if ($row['plaka_k'] !== '' && $row['plaka_k'] !== $row['sase']) {
                    $bulunan[$row['plaka_k']][] = $row;
                }
            }
        }

        $eslesen = function (string $deger, bool $saseAlani) use ($bulunan): array {
            $anahtar = AracService::normalizePlaka($deger);
            if ($anahtar === null) {
                return [];
            }
            $sase = [];
            $plaka = [];
            foreach ($bulunan[$anahtar] ?? [] as $arac) {
                if ($arac['sase'] === $anahtar) {
                    $sase[(int) $arac['id']] = $arac;
                }
                if ($arac['plaka_k'] === $anahtar) {
                    $plaka[(int) $arac['id']] = $arac;
                }
            }
            if ($saseAlani && $this->saseGibi($deger)) {
                return $sase;
            }
            if (!$saseAlani) {
                return $plaka !== [] ? $plaka : $sase;
            }

            return $sase !== [] ? $sase : $plaka;
        };

        $sonuc = [];
        $gorulen = [];
        foreach ($satirlar as $satir) {
            $saseBul = $satir['sase'] !== '' ? $eslesen($satir['sase'], true) : [];
            $plakaBul = $satir['plaka'] !== '' ? $eslesen($satir['plaka'], false) : [];
            if ($saseBul && $plakaBul) {
                $ortak = array_intersect_key($saseBul, $plakaBul);
                if (!$ortak) {
                    $sonuc[] = ['ham' => $satir['ham'], 'durum' => 'uyusmuyor', 'mesaj' => 'Şasi ile plaka farklı araç', 'arac' => null, 'sase' => $satir['sase'], 'plaka' => $satir['plaka']];
                    continue;
                }
                $havuz = $ortak;
            } else {
                $havuz = $saseBul ?: $plakaBul;
            }
            if (count($havuz) > 1) {
                $sonuc[] = ['ham' => $satir['ham'], 'durum' => 'belirsiz', 'mesaj' => 'Birden fazla kayıt', 'arac' => null, 'sase' => $satir['sase'], 'plaka' => $satir['plaka']];
                continue;
            }
            $arac = $havuz ? reset($havuz) : null;
            if (!$arac) {
                $sonuc[] = ['ham' => $satir['ham'], 'durum' => 'kayitli_degil', 'mesaj' => 'Kayıtlı değil', 'arac' => null, 'sase' => $satir['sase'], 'plaka' => $satir['plaka']];
                continue;
            }
            $satirCevap = ['ham' => $satir['ham'], 'arac' => $arac, 'sase' => $satir['sase'], 'plaka' => $satir['plaka']];
            if (!$this->stoktaMi($arac['stokta'])) {
                $sonuc[] = $satirCevap + ['durum' => 'stokta_degil', 'mesaj' => 'Stokta değil'];
                continue;
            }
            $id = (int) $arac['id'];
            if (isset($gorulen[$id])) {
                $sonuc[] = $satirCevap + ['durum' => 'tekrar', 'mesaj' => 'Aynı araç tekrar yazılmış'];
                continue;
            }
            $gorulen[$id] = true;
            $sonuc[] = $satirCevap + ['durum' => 'stokta', 'mesaj' => 'Stokta'];
        }

        return $sonuc;
    }

    public function topluStokCikisKontrol(): void
    {
        $this->ajax(function () {
            $metin = Request::input('yapiskan');
            $satirlar = $this->yapistirmaSatirlari(is_string($metin) ? $metin : '');
            if (!$satirlar) {
                throw new RuntimeException('Yapıştırılacak şasi veya plaka yok.');
            }
            $ayrim = $this->cikisSatirlariniAyir($satirlar);
            $say = array_count_values(array_column($ayrim, 'durum'));
            $this->ok('Kontrol edildi.', [
                'stokta' => (int) ($say['stokta'] ?? 0),
                'stokta_degil' => (int) ($say['stokta_degil'] ?? 0),
                'kayitli_degil' => (int) ($say['kayitli_degil'] ?? 0),
                'satirlar' => array_map(static fn (array $s) => [
                    'ham' => $s['ham'],
                    'durum' => $s['durum'],
                    'mesaj' => $s['mesaj'],
                    'girilen_sase' => (string) ($s['sase'] ?? ''),
                    'girilen_plaka' => (string) ($s['plaka'] ?? ''),
                    'sase' => (string) ($s['arac']['sase'] ?? ''),
                    'plaka' => (string) ($s['arac']['plaka'] ?? ''),
                ], $ayrim),
            ]);
        });
    }

    public function excelTopluGiris(): void
    {
        $this->form(function () {
            $musteriId = Request::int('musteri_id');
            $bayiId = AracService::bayiKontrol(Request::int('bayi_id'));
            if (!$musteriId) {
                throw new RuntimeException('Müşteri seçilmelidir.');
            }

            $satirlar = [];
            if (!empty($_FILES['import_file']['name'])) {
                [$path, $name] = $this->yuklenenDosya();
                $satirlar = Excel::read($path, $name);
            } else {
                foreach ((array) ($_POST['satir']['sase'] ?? []) as $i => $sase) {
                    $satirlar[] = [
                        'sase' => $sase, 'plaka' => $_POST['satir']['plaka'][$i] ?? '', 'marka_id' => $_POST['satir']['marka_id'][$i] ?? '',
                        'seri_id' => $_POST['satir']['seri_id'][$i] ?? '', 'lokasyon_detay' => $_POST['satir']['lokasyon_detay'][$i] ?? '',
                        'sevkiyat_kodu' => $_POST['satir']['sevkiyat_kodu'][$i] ?? '', 'irsaliye_kodu' => $_POST['satir']['irsaliye_kodu'][$i] ?? '',
                        'proje_adi' => $_POST['satir']['proje_adi'][$i] ?? '',
                    ];
                }
            }
            $satirlar = array_filter($satirlar, fn ($s) => trim((string) ($s['sase'] ?? '')) !== '');
            if (!$satirlar) {
                throw new RuntimeException('Aktarılacak satır bulunamadı.');
            }

            $markalar = TopluAktarim::adHaritasi('markalar');
            $aracTipleri = TopluAktarim::adHaritasi('arac_tipleri');
            $kasaTipleri = TopluAktarim::adHaritasi('kasa_tipleri');
            $renkler = TopluAktarim::adHaritasi('renkler');
            $yakitlar = TopluAktarim::adHaritasi('yakit_tipleri');
            $vitesler = TopluAktarim::adHaritasi('vites_tipleri');
            $tarih = Request::dateTime('hareket_tarihi', 'hareket_saati') ?? date('Y-m-d H:i:s');
            $varsayilanAracTipi = Request::int('arac_tipi');
            $varsayilanDurum = Request::int('arac_durumu');
            $personelId = Request::int('teslim_alan_personel');
            Auth::dogrulaPersonel($personelId, 'Personel');
            $basarili = 0;
            $hatalar = [];

            foreach (array_values($satirlar) as $i => $s) {
                try {
                    $markaId = (int) ($s['marka_id'] ?? 0) ?: TopluAktarim::bul($markalar, $s['marka'] ?? '', 'Marka');
                    $seriId = (int) ($s['seri_id'] ?? 0) ?: null;
                    if (!$seriId) {
                        $seriId = TopluAktarim::seriId($markaId, (string) ($s['seri'] ?? ''));
                    }
                    // Araç tipi / durumu isteğe bağlı (Excel boş bırakılabilir; form varsayılanı varsa o kullanılır)
                    $aracTipiId = TopluAktarim::bul($aracTipleri, $s['arac_tipi'] ?? '', 'Araç tipi', false) ?? $varsayilanAracTipi;
                    $aracDurumu = TopluAktarim::aracDurumu($s['arac_durumu'] ?? null) ?? $varsayilanDurum;
                    AracService::kaydetVeHareket(
                        [
                            'sase' => $s['sase'],
                            'plaka' => ($s['plaka'] ?? '') !== '' ? $s['plaka'] : null,
                            'onceki_plaka' => ($s['onceki_plaka'] ?? '') !== '' ? $s['onceki_plaka'] : null,
                            'marka_id' => $markaId,
                            'seri_id' => $seriId,
                            'musteri_id' => $musteriId,
                            'bayi_id' => $bayiId,
                            'arac_tipi_id' => $aracTipiId ?: null,
                            'arac_durumu' => $aracDurumu ?: null,
                            'kasa_tipi_id' => TopluAktarim::bul($kasaTipleri, $s['kasa_tipi'] ?? '', 'Kasa tipi', false),
                            'renk_id' => TopluAktarim::bul($renkler, $s['renk'] ?? '', 'Renk', false),
                            'model_yili_id' => TopluAktarim::modelYili($s['yil'] ?? null),
                            'yakit_tipi_id' => TopluAktarim::bul($yakitlar, $s['yakit_tipi'] ?? '', 'Yakıt tipi', false),
                            'vites_tipi_id' => TopluAktarim::bul($vitesler, $s['vites_tipi'] ?? '', 'Vites tipi', false),
                            'km' => ($s['km'] ?? '') !== '' ? (int) $s['km'] : null,
                            'yakit_durumu' => ($s['yakit_durumu'] ?? '') !== '' ? (int) $s['yakit_durumu'] : null,
                            'lokasyon_turu' => Request::int('lokasyon_turu'),
                            'lokasyon_detay' => ($s['lokasyon_detay'] ?? '') !== '' ? $s['lokasyon_detay'] : null,
                            'proje_adi' => ($s['proje_adi'] ?? '') !== '' ? $s['proje_adi'] : null,
                        ],
                        [
                            'hareket_tipi' => 1,
                            'hareket_nedeni_id' => Request::int('hareket_nedeni'),
                            'hareket_tarihi' => $tarih,
                            'lokasyon_turu' => Request::int('lokasyon_turu'),
                            'lokasyon_detay' => ($s['lokasyon_detay'] ?? '') !== '' ? $s['lokasyon_detay'] : null,
                            'sevkiyat_kodu' => ($s['sevkiyat_kodu'] ?? '') !== '' ? $s['sevkiyat_kodu'] : null,
                            'irsaliye_kodu' => ($s['irsaliye_kodu'] ?? '') !== '' ? $s['irsaliye_kodu'] : null,
                            'teslim_eden' => Request::str('teslim_eden'),
                            'teslim_alan_personel_id' => $personelId,
                            'km' => ($s['km'] ?? '') !== '' ? (int) $s['km'] : null,
                            'yakit_durumu' => ($s['yakit_durumu'] ?? '') !== '' ? (int) $s['yakit_durumu'] : null,
                        ],
                        Request::ids('arac_envanterleri')
                    );
                    $basarili++;
                } catch (\PDOException $e) {
                    error_log($e->getMessage());
                    $hatalar[] = 'Satır ' . ($i + 1) . ' (' . ($s['sase'] ?? '?') . '): kayıt hatası';
                } catch (RuntimeException $e) {
                    $hatalar[] = 'Satır ' . ($i + 1) . ' (' . ($s['sase'] ?? '?') . '): ' . $e->getMessage();
                }
            }

            Log::islem('arac', "Toplu stok girişi: {$basarili} araç");
            flash('success', "{$basarili} araç stoğa alındı.");
            if ($hatalar) {
                flash('error', count($hatalar) . ' satır aktarılamadı: ' . implode(' | ', array_slice($hatalar, 0, 10)));
            }
            View::redirect('/arac_hareketleri/giris');
        }, '/arac_yonetimi/toplu_stok_girisi');
    }

    public function excelTopluCikis(): void
    {
        $this->form(function () {
            $cikisTarihi = Request::dateTime('hareket_tarihi', 'hareket_saati') ?? date('Y-m-d H:i:s');
            $cikisTs = strtotime($cikisTarihi);
            if ($cikisTs > time()) {
                throw new RuntimeException('Çıkış tarihi ileri bir tarih/saat olamaz.');
            }
            $sevkiyatTipi = Tanim::sevkiyatTipi(Request::int('sevkiyat_tipi'));
            $nedenId = Request::int('hareket_nedeni');
            if (!$nedenId || !Database::fetch('SELECT 1 FROM hareket_nedenleri WHERE id = :id', ['id' => $nedenId])) {
                throw new RuntimeException('Hareket nedeni seçilmelidir.');
            }
            $personelId = Request::int('teslim_alan_personel');
            Auth::dogrulaPersonel($personelId, 'Personel');

            $ortak = [
                'hareket_nedeni_id' => $nedenId,
                'sevkiyat_tipi' => $sevkiyatTipi,
                'teslim_alan' => Request::str('teslim_alan'),
                'teslim_alan_telefon' => Request::str('teslim_alan_telefon'),
                'teslim_alan_personel_id' => $personelId,
                'sevkiyat_kodu' => Request::str('sevkiyat_kodu'),
                'irsaliye_kodu' => Request::str('irsaliye_kodu'),
                'sofor_adi_soyadi' => Request::str('sofor_adi_soyadi'),
                'sofor_telefon' => Request::str('sofor_telefon'),
                'cekici_plakasi' => Request::str('cekici_plakasi'),
                'aciklama' => Request::str('aciklama'),
                'hareket_tarihi' => $cikisTarihi,
            ];

            $satirlar = $this->cikisKaynakSatirlari();
            if (!$satirlar) {
                throw new RuntimeException('Çıkış yapılacak satır bulunamadı. Excel yükleyin veya şasi / plaka yapıştırın.');
            }

            $basarili = 0;
            $uyari = ['kayitli_degil' => [], 'stokta_degil' => [], 'tekrar' => [], 'belirsiz' => [], 'uyusmuyor' => [], 'tarih' => [], 'kayit' => []];
            foreach ($this->cikisSatirlariniAyir($satirlar) as $satir) {
                if ($satir['durum'] !== 'stokta') {
                    $uyari[$satir['durum']][] = $satir['ham'];
                    continue;
                }
                $arac = $satir['arac'];
                $girisTs = strtotime((string) ($arac['stoga_giris_tarihi'] ?? ''));
                if ($girisTs && $cikisTs < $girisTs) {
                    $uyari['tarih'][] = ($arac['sase'] ?: $satir['ham']) . ' (giriş: ' . date('d.m.Y H:i', $girisTs) . ')';
                    continue;
                }
                try {
                    AracService::hareketEkle((int) $arac['id'], [
                        'hareket_tipi' => 2,
                        'hareket_nedeni_id' => $ortak['hareket_nedeni_id'],
                        'musteri_id' => $arac['musteri_id'],
                        'bayi_id' => $arac['bayi_id'],
                        'teslim_alan' => $ortak['teslim_alan'],
                        'teslim_alan_telefon' => $ortak['teslim_alan_telefon'],
                        'teslim_alan_personel_id' => $ortak['teslim_alan_personel_id'],
                        'sevkiyat_tipi' => $ortak['sevkiyat_tipi'],
                        'sofor_adi_soyadi' => $ortak['sofor_adi_soyadi'],
                        'sofor_telefon' => $ortak['sofor_telefon'],
                        'cekici_plakasi' => $ortak['cekici_plakasi'],
                        'sevkiyat_kodu' => $ortak['sevkiyat_kodu'],
                        'irsaliye_kodu' => $ortak['irsaliye_kodu'],
                        'aciklama' => $ortak['aciklama'],
                        'hareket_tarihi' => $ortak['hareket_tarihi'],
                    ]);
                    $basarili++;
                } catch (\PDOException $e) {
                    error_log($e->getMessage());
                    $uyari['kayit'][] = $satir['ham'];
                } catch (RuntimeException $e) {
                    $uyari['kayit'][] = $satir['ham'] . ': ' . $e->getMessage();
                }
            }

            Log::islem('hareket', "Toplu stok çıkışı: {$basarili} araç");
            if ($basarili > 0) {
                flash('success', "{$basarili} araç stoktan çıkarıldı.");
            }
            $etiket = [
                'kayitli_degil' => 'Kayıtlı değil',
                'stokta_degil' => 'Stokta değil',
                'tekrar' => 'Aynı araç tekrar yazılmış',
                'belirsiz' => 'Birden fazla kayıt',
                'uyusmuyor' => 'Şasi ile plaka farklı araç',
                'tarih' => 'Çıkış tarihi girişten önce',
                'kayit' => 'Kaydedilemedi',
            ];
            $parcalar = [];
            foreach ($etiket as $anahtar => $ad) {
                if (!$uyari[$anahtar]) {
                    continue;
                }
                $goster = array_slice($uyari[$anahtar], 0, 20);
                $ek = count($uyari[$anahtar]) > 20 ? ' ve ' . (count($uyari[$anahtar]) - 20) . ' tane daha' : '';
                $parcalar[] = $ad . ' (' . count($uyari[$anahtar]) . '): ' . implode(', ', $goster) . $ek;
            }
            if ($parcalar) {
                flash('error', implode(' · ', $parcalar));
            } elseif ($basarili === 0) {
                flash('error', 'Çıkış yapılacak stoktaki araç bulunamadı.');
            }
            if ($basarili === 0) {
                $_SESSION['_old'] = $_POST;
            }
            View::redirect($basarili > 0 ? '/arac_hareketleri/cikis' : '/arac_yonetimi/toplu_stok_cikisi');
        }, '/arac_yonetimi/toplu_stok_cikisi');
    }

    public function excelTopluMaliyet(): void
    {
        $this->form(function () {
            [$path, $name] = $this->yuklenenDosya();
            $satirlar = Excel::read($path, $name);
            $tipler = TopluAktarim::adHaritasi('maliyet_tipleri');
            $basarili = 0;
            $hatalar = [];
            foreach ($satirlar as $i => $s) {
                try {
                    $sase = AracService::normalizeSase($s['sase'] ?? '');
                    if ($sase === '') {
                        throw new RuntimeException('Şasi boş');
                    }
                    $arac = Database::fetch(
                        'SELECT id FROM araclar WHERE sase = :s AND ' . Auth::bayiKosulu('bayi_id'),
                        ['s' => $sase]
                    );
                    if (!$arac && ($s['plaka'] ?? '') !== '') {
                        $arac = Database::fetch(
                            "SELECT id FROM araclar WHERE upper(replace(coalesce(plaka,''), ' ', '')) = :p AND " . Auth::bayiKosulu('bayi_id'),
                            ['p' => mb_strtoupper(str_replace(' ', '', $s['plaka']))]
                        );
                    }
                    if (!$arac) {
                        throw new RuntimeException('Şasi / plaka bulunamadı');
                    }
                    $tipId = TopluAktarim::bul($tipler, $s['maliyet_tipi'] ?? '', 'Hizmet kalemi');
                    $tutar = TopluAktarim::parseTutar($s['tutar'] ?? '');
                    AracService::maliyetEkle((int) $arac['id'], [
                        'maliyet_tipi_id' => $tipId,
                        'tutar' => $tutar,
                        'aciklama' => ($s['aciklama'] ?? '') !== '' ? $s['aciklama'] : null,
                        'fatura_no' => ($s['fatura_no'] ?? '') !== '' ? $s['fatura_no'] : null,
                        'islem_tarihi' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $s['islem_tarihi'] ?? '') ? $s['islem_tarihi'] : date('Y-m-d'),
                    ]);
                    $basarili++;
                } catch (\PDOException $e) {
                    error_log($e->getMessage());
                    $hatalar[] = 'Satır ' . ($i + 1) . ': kayıt hatası';
                } catch (RuntimeException $e) {
                    $hatalar[] = 'Satır ' . ($i + 1) . ': ' . $e->getMessage();
                }
            }
            flash('success', "{$basarili} maliyet kaydı eklendi.");
            if ($hatalar) {
                flash('error', count($hatalar) . ' satır aktarılamadı: ' . implode(' | ', array_slice($hatalar, 0, 10)));
            }
            View::redirect('/arac_yonetimi/toplu_ek_maliyet_girisi');
        }, '/arac_yonetimi/toplu_ek_maliyet_girisi');
    }

    public function excelTopluLokasyon(): void
    {
        $this->form(function () {
            [$path, $name] = $this->yuklenenDosya();
            $satirlar = Excel::read($path, $name);
            $basarili = 0;
            foreach ($satirlar as $s) {
                $n = Database::query(
                    'UPDATE araclar SET lokasyon_detay = COALESCE(NULLIF(:l, \'\'), lokasyon_detay), park_kodu = COALESCE(NULLIF(:p, \'\'), park_kodu), updated_at = NOW()
                     WHERE sase = :s AND ' . Auth::bayiKosulu('bayi_id'),
                    ['l' => $s['lokasyon_detay'] ?? '', 'p' => $s['park_kodu'] ?? '', 's' => AracService::normalizeSase($s['sase'] ?? '')]
                )->rowCount();
                $basarili += $n;
            }
            Log::islem('arac', "Toplu lokasyon güncelleme: {$basarili} araç");
            flash('success', "{$basarili} aracın lokasyonu güncellendi (" . count($satirlar) . ' satır okundu).');
            View::redirect('/arac_yonetimi/toplu_kayit_guncelleme_lokasyon');
        }, '/arac_yonetimi/toplu_kayit_guncelleme_lokasyon');
    }

    /** @return list<array<string,mixed>> */
    private function maliyetSatirlari(): array
    {
        $liste = static function (string $key): array {
            $value = $_POST[$key] ?? [];

            return is_array($value) ? array_values($value) : [$value];
        };
        $tipler = $liste('maliyet_tipi_id');
        $tutar = $liste('tutar');
        $aciklama = $liste('aciklama');
        $irsaliye = $liste('irsaliye');
        $faturaNo = $liste('fatura_no');
        $faturaTarihi = $liste('fatura_tarihi');
        $islem = $liste('islem_tarihi');
        $satirlar = [];
        foreach ($tipler as $i => $tip) {
            $hucre = static function (array $kolon) use ($i): string {
                return trim((string) ($kolon[$i] ?? ''));
            };
            $tipId = (int) $tip;
            $tutarDeger = $hucre($tutar);
            $aciklamaDeger = $hucre($aciklama);
            $irsaliyeDeger = $hucre($irsaliye);
            $faturaNoDeger = $hucre($faturaNo);
            $faturaTarihDeger = $hucre($faturaTarihi);
            if ($tipId <= 0 && $tutarDeger === '' && $aciklamaDeger === '' && $irsaliyeDeger === '' && $faturaNoDeger === '' && $faturaTarihDeger === '') {
                continue;
            }
            if ($tipId <= 0) {
                throw new RuntimeException('Her maliyet satırında tip seçilmelidir.');
            }
            $islemTarihi = $hucre($islem);
            if ($islemTarihi !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $islemTarihi)) {
                throw new RuntimeException('İşlem tarihi geçersiz.');
            }
            if ($faturaTarihDeger !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $faturaTarihDeger)) {
                throw new RuntimeException('Fatura tarihi geçersiz.');
            }
            $satirlar[] = [
                'maliyet_tipi_id' => $tipId,
                'tutar' => $tutarDeger === '' ? null : $tutarDeger,
                'aciklama' => $aciklamaDeger !== '' ? $aciklamaDeger : null,
                'irsaliye' => $irsaliyeDeger !== '' ? $irsaliyeDeger : null,
                'fatura_no' => $faturaNoDeger !== '' ? $faturaNoDeger : null,
                'fatura_tarihi' => $faturaTarihDeger !== '' ? $faturaTarihDeger : null,
                'islem_tarihi' => $islemTarihi !== '' ? $islemTarihi : date('Y-m-d'),
            ];
        }

        return $satirlar;
    }

    /** @param list<int> $ids
     *  @return list<array<string,mixed>>
     */
    private function seciliAraclar(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        return Database::fetchAll(
            'SELECT a.id, a.sase, a.plaka, a.stokta, mu.ad AS firma
             FROM araclar a
             LEFT JOIN musteriler mu ON mu.id = a.musteri_id
             WHERE a.id = ANY(:ids::bigint[]) AND ' . Auth::bayiKosulu('a.bayi_id') . '
             ORDER BY a.sase',
            ['ids' => '{' . implode(',', $ids) . '}']
        );
    }
}
