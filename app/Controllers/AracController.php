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
                'giris_tarihi' => "to_char(a.stoga_giris_tarihi, 'DD.MM.YYYY')", 'giris_tarihi_sort' => 'a.stoga_giris_tarihi',
                'cikis_tarihi' => "to_char(a.stoktan_cikis_tarihi, 'DD.MM.YYYY')", 'cikis_tarihi_sort' => 'a.stoktan_cikis_tarihi',
                'depolama_suresi' => '(COALESCE(a.stoktan_cikis_tarihi::date, CURRENT_DATE) - a.stoga_giris_tarihi::date + 1)',
            ],
            ['NOT a.arsiv', Auth::bayiKosulu('a.bayi_id')],
            [],
            ['a.sase', 'a.plaka', 'b.ad', 'mu.ad', 'a.lokasyon_detay'],
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
            'sigorta_tarihi' => Request::date('sigorta_tarihi'), 'kasko_tarihi' => Request::date('kasko_tarihi'), 'muayene_tarihi' => Request::date('muayene_tarihi'),
        ];
    }

    private function hareketFormVerisi(string $tarihAlani, string $saatAlani): array
    {
        return [
            'hareket_tipi' => Request::int('hareket_tipi') ?? 1,
            'hareket_nedeni_id' => Request::int('hareket_nedeni'),
            'lokasyon_turu' => Request::int('lokasyon_turu'), 'lokasyon_detay' => Request::str('lokasyon_detay'),
            'km' => Request::int('km'), 'yakit_durumu' => Request::int('yakit_durumu'),
            'teslim_eden' => Request::str('teslim_eden'), 'teslim_eden_telefon' => Request::str('teslim_eden_telefon'), 'teslim_eden_eposta' => Request::str('teslim_eden_eposta'),
            'teslim_alan' => Request::str('teslim_alan'), 'teslim_alan_telefon' => Request::str('teslim_alan_telefon'), 'teslim_alan_eposta' => Request::str('teslim_alan_eposta'),
            'teslim_alan_personel_id' => Request::int('teslim_alan_personel'), 'aciklama' => Request::str('aciklama'),
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
                'SELECT e.id, e.tutar, e.islem_tarihi, e.aciklama, e.fatura_no, mt.ad AS maliyet_tipi, b.ad AS bayi, u.name AS kullanici
                 FROM arac_ekstreleri e JOIN maliyet_tipleri mt ON mt.id = e.maliyet_tipi_id
                 LEFT JOIN bayiler b ON b.id = e.bayi_id LEFT JOIN users u ON u.id = e.kullanici_id
                 WHERE e.arac_id = :a ORDER BY e.islem_tarihi DESC, e.id DESC',
                ['a' => $id]
            ),
            'depolama' => DepolamaHesap::arac((int) $id),
        ]);
        unset($_SESSION['_old']);
    }

    public function update(string $id): void
    {
        $arac = $this->aracDetay((int) $id);
        $this->form(function () use ($arac) {
            $data = $this->aracFormVerisi();
            unset($data['sase']);
            $yeniSase = AracService::normalizeSase(Request::str('sase'));
            if (strlen($yeniSase) < 5) {
                throw new RuntimeException('Geçerli bir şasi numarası giriniz.');
            }
            if ($yeniSase !== $arac['sase']) {
                $data['sase'] = $yeniSase;
            }
            if (!$data['musteri_id']) {
                throw new RuntimeException('Müşteri seçilmelidir.');
            }
            $data['bayi_id'] = AracService::bayiKontrol($data['bayi_id']);
            $data['plaka'] = $data['plaka'] ? mb_strtoupper($data['plaka']) : null;
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
        $this->ajax(function () {
            $ids = Request::ids('ids');
            if (!$ids) {
                throw new RuntimeException('Kayıt seçilmedi.');
            }
            $n = Database::query('DELETE FROM araclar WHERE id = ANY(:ids::bigint[])', ['ids' => '{' . implode(',', $ids) . '}'])->rowCount();
            Log::islem('arac', "{$n} araç silindi");
            $this->ok("{$n} araç silindi.");
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

    public function topluEkMaliyet(): void
    {
        $this->view('araclar/toplu_excel', [
            'pageTitle' => 'Toplu Ek Maliyet Girişi', 'action' => '/arac_yonetimi/excel_toplu_ek_maliyet_giris', 'sablon' => 'maliyet',
            'aciklama' => 'Her satır bir maliyet kaydıdır. Şasi numarası sistemde kayıtlı olmalıdır. Maliyet tipi adı, tanımlardaki adla aynı yazılmalıdır.',
            'breadcrumb' => ['Araç Yönetimi' => '/arac_yonetimi', 'Toplu Ek Maliyet Girişi' => null],
        ]);
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
        $sablonlar = [
            'stok' => [['sase', 'plaka', 'marka', 'seri', 'lokasyon_detay', 'sevkiyat_kodu', 'irsaliye_kodu', 'proje_adi'], ['VF1RJA00776597244', '06 ABC 123', 'Renault', 'Clio', 'A Blok 12', 'SV-001', 'IRS-001', '']],
            'maliyet' => [['sase', 'maliyet_tipi', 'tutar', 'islem_tarihi', 'aciklama', 'fatura_no'], ['VF1RJA00776597244', 'Araç Dış Yıkama Hizmeti', '360', date('Y-m-d'), '', '']],
            'lokasyon' => [['sase', 'lokasyon_detay', 'park_kodu'], ['VF1RJA00776597244', 'B Blok 4', 'P-104']],
        ];
        if (!isset($sablonlar[$tip])) {
            $this->notFound();
        }
        [$basliklar, $ornek] = $sablonlar[$tip];
        Excel::download('sablon-' . $tip, array_combine($basliklar, $basliklar), [array_combine($basliklar, $ornek)]);
    }

    private function yuklenenDosya(): string
    {
        $file = $_FILES['import_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Lütfen bir dosya seçiniz.');
        }
        if (!in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), ['csv', 'txt'], true)) {
            throw new RuntimeException('Dosya CSV formatında olmalıdır. Excel\'de "Farklı Kaydet → CSV UTF-8" seçeneğini kullanın.');
        }

        return $file['tmp_name'];
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
                $satirlar = Excel::read($this->yuklenenDosya());
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

            $markalar = array_change_key_case(array_flip(array_map('mb_strtolower', Tanim::liste('markalar'))));
            $tarih = Request::dateTime('hareket_tarihi', 'hareket_saati') ?? date('Y-m-d H:i:s');
            $basarili = 0;
            $hatalar = [];

            foreach (array_values($satirlar) as $i => $s) {
                try {
                    $markaId = (int) ($s['marka_id'] ?? 0) ?: ($markalar[mb_strtolower(trim($s['marka'] ?? ''))] ?? null);
                    $seriId = (int) ($s['seri_id'] ?? 0) ?: null;
                    if (!$seriId && $markaId && !empty($s['seri'])) {
                        $seriId = (int) (Database::fetch('SELECT id FROM seriler WHERE marka_id = :m AND lower(ad) = lower(:a)', ['m' => $markaId, 'a' => trim($s['seri'])])['id'] ?? 0)
                            ?: AracService::insert('seriler', ['marka_id' => $markaId, 'ad' => trim($s['seri'])]);
                    }
                    AracService::kaydetVeHareket(
                        [
                            'sase' => $s['sase'], 'plaka' => $s['plaka'] ?? null, 'marka_id' => $markaId, 'seri_id' => $seriId,
                            'musteri_id' => $musteriId, 'bayi_id' => $bayiId, 'arac_tipi_id' => Request::int('arac_tipi'),
                            'lokasyon_turu' => Request::int('lokasyon_turu'), 'lokasyon_detay' => $s['lokasyon_detay'] ?? null,
                            'proje_adi' => ($s['proje_adi'] ?? '') ?: null, 'arac_durumu' => Request::int('arac_durumu'),
                        ],
                        [
                            'hareket_tipi' => 1, 'hareket_nedeni_id' => Request::int('hareket_nedeni'), 'hareket_tarihi' => $tarih,
                            'lokasyon_turu' => Request::int('lokasyon_turu'), 'lokasyon_detay' => $s['lokasyon_detay'] ?? null,
                            'sevkiyat_kodu' => ($s['sevkiyat_kodu'] ?? '') ?: null, 'irsaliye_kodu' => ($s['irsaliye_kodu'] ?? '') ?: null,
                            'teslim_eden' => Request::str('teslim_eden'), 'teslim_alan_personel_id' => Request::int('teslim_alan_personel'),
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

    public function excelTopluMaliyet(): void
    {
        $this->form(function () {
            $satirlar = Excel::read($this->yuklenenDosya());
            $tipler = array_change_key_case(array_flip(array_map('mb_strtolower', Tanim::liste('maliyet_tipleri'))));
            $basarili = 0;
            $hatalar = [];
            foreach ($satirlar as $i => $s) {
                try {
                    $arac = Database::fetch('SELECT id FROM araclar WHERE sase = :s', ['s' => AracService::normalizeSase($s['sase'] ?? '')]);
                    if (!$arac) {
                        throw new RuntimeException('Şasi bulunamadı');
                    }
                    $tipId = $tipler[mb_strtolower(trim($s['maliyet_tipi'] ?? ''))] ?? null;
                    if (!$tipId) {
                        throw new RuntimeException('Maliyet tipi tanımlı değil');
                    }
                    $tutar = str_contains($s['tutar'] ?? '', ',') ? (float) str_replace(['.', ','], ['', '.'], $s['tutar']) : (float) ($s['tutar'] ?? 0);
                    AracService::maliyetEkle((int) $arac['id'], [
                        'maliyet_tipi_id' => $tipId, 'tutar' => $tutar, 'aciklama' => ($s['aciklama'] ?? '') ?: null,
                        'fatura_no' => ($s['fatura_no'] ?? '') ?: null,
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
            $satirlar = Excel::read($this->yuklenenDosya());
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
}
