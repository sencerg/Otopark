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
use App\Core\View;
use App\Services\AracService;
use RuntimeException;

final class SayimController extends Controller
{
    private const DEVAM = 1;
    private const TAMAMLANDI = 2;

    private const STOKTA = 1;
    private const DISARIDA = 2;
    private const STOGA_ALINDI = 3;
    private const BULUNAMADI = 4;

    /** Devam eden sayımda lokasyonun stoğunda olup henüz okutulmamış araçlar. */
    private const OKUTULMAYAN_KOSUL = 'a.stokta AND a.bayi_id = s.bayi_id
        AND NOT EXISTS (SELECT 1 FROM sayim_okutmalari o WHERE o.sayim_id = s.id AND (o.arac_id = a.id OR o.sase = a.sase))';

    public function index(): void
    {
        $this->view('sayim/liste', [
            'pageTitle' => 'Araç Sayımları',
            'breadcrumb' => ['Araç Yönetimi' => '/arac_hareketleri/giris', 'Araç Sayımları' => null],
        ]);
    }

    public function liste(): void
    {
        $okutulmayan = 'CASE WHEN s.durum = ' . self::DEVAM
            . ' THEN (SELECT COUNT(*) FROM araclar a WHERE ' . self::OKUTULMAYAN_KOSUL . ')'
            . ' ELSE (SELECT COUNT(*) FROM sayim_okutmalari o WHERE o.sayim_id = s.id AND o.sonuc = ' . self::BULUNAMADI . ') END';
        $dt = new DataTable(
            'sayimlar s
             JOIN bayiler b ON b.id = s.bayi_id
             LEFT JOIN personeller p ON p.id = s.sorumlu_personel_id',
            [
                'id' => 's.id', 'kod' => 's.kod', 'baslik' => 's.baslik', 'lokasyon' => 'b.ad', 'sorumlu' => 'p.ad_soyad',
                'tarih' => "to_char(s.created_at, 'DD.MM.YYYY HH24:MI')", 'tarih_sort' => 's.created_at',
                'okutulan' => '(SELECT COUNT(*) FROM sayim_okutmalari o WHERE o.sayim_id = s.id AND o.sonuc IN (' . self::STOKTA . ', ' . self::STOGA_ALINDI . '))',
                'disarida' => '(SELECT COUNT(*) FROM sayim_okutmalari o WHERE o.sayim_id = s.id AND o.sonuc = ' . self::DISARIDA . ')',
                'okutulmayan' => $okutulmayan,
                'durum' => 's.durum',
            ],
            ['NOT s.arsiv', Auth::bayiKosulu('s.bayi_id')],
            [],
            ['s.kod', 's.baslik', 'b.ad'],
            's.id DESC'
        );
        foreach (['bayi_id' => 's.bayi_id', 'sorumlu_personel_id' => 's.sorumlu_personel_id', 'durum' => 's.durum'] as $key => $col) {
            if ($v = Request::int($key)) {
                $dt->where("{$col} = :f_{$key}", ["f_{$key}" => $v]);
            }
        }
        if ($d = Request::date('baslangic')) {
            $dt->where('s.created_at >= :f_bas', ['f_bas' => $d]);
        }
        if ($d = Request::date('bitis')) {
            $dt->where("s.created_at < :f_bit::date + INTERVAL '1 day'", ['f_bit' => $d]);
        }

        $map = fn ($r) => $r + ['durum_ad' => Tanim::SAYIM_DURUM[$r['durum']] ?? '-', 'durum_renk' => Tanim::SAYIM_DURUM_RENK[$r['durum']] ?? 'secondary'];
        if (Request::input('export')) {
            Excel::download('arac-sayimlari', [
                'kod' => 'Sayım Kodu', 'baslik' => 'Başlık', 'lokasyon' => 'Lokasyon', 'sorumlu' => 'Sorumlu Personel', 'tarih' => 'Sayım Tarihi',
                'okutulan' => 'Okutulan', 'disarida' => 'Lokasyonda Değil', 'okutulmayan' => 'Okutulmayan / Bulunamayan', 'durum_ad' => 'Sayım Durumu',
            ], array_map($map, $dt->all()));
        }
        $dt->response($map);
    }

    private static function yeniKod(): string
    {
        $yil = date('Y');
        $son = Database::fetch('SELECT kod FROM sayimlar WHERE kod LIKE :p ORDER BY id DESC LIMIT 1', ['p' => "SY-{$yil}-%"]);
        $n = $son ? (int) substr($son['kod'], -5) + 1 : 1;

        return sprintf('SY-%s-%05d', $yil, $n);
    }

    public function ekle(): void
    {
        $this->view('sayim/form', [
            'pageTitle' => 'Araç Sayımı Ekle', 'kod' => self::yeniKod(),
            'breadcrumb' => ['Araç Sayımları' => '/sayim_kayitlari', 'Araç Sayımı Ekle' => null],
        ]);
        unset($_SESSION['_old']);
    }

    public function save(): void
    {
        $this->form(function () {
            $baslik = Request::str('baslik');
            if ($baslik === null) {
                throw new RuntimeException('Sayım başlığı zorunludur.');
            }
            $bayi = AracService::bayiKontrol(Request::int('bayi_id'));
            $personel = Request::int('sorumlu_personel');
            if ($personel && !Database::fetch('SELECT 1 FROM personeller WHERE id = :id AND aktif', ['id' => $personel])) {
                throw new RuntimeException('Sorumlu personel bulunamadı.');
            }
            $kod = self::yeniKod();
            $id = AracService::insert('sayimlar', [
                'kod' => $kod, 'baslik' => mb_substr($baslik, 0, 200), 'bayi_id' => $bayi,
                'sorumlu_personel_id' => $personel, 'olusturan_id' => Auth::id(),
            ]);
            Log::islem('sayim', 'Sayım başlatıldı: ' . $kod, $id);
            flash('success', 'Sayım başlatıldı. Araçları okutmaya başlayabilirsiniz.');
            View::redirect('/sayim_kayitlari/detay/' . $id);
        }, '/sayim_kayitlari/ekle');
    }

    private static function sayim(int $id): ?array
    {
        return Database::fetch(
            'SELECT s.*, b.ad AS lokasyon, p.ad_soyad AS sorumlu, u.name AS olusturan
             FROM sayimlar s JOIN bayiler b ON b.id = s.bayi_id
             LEFT JOIN personeller p ON p.id = s.sorumlu_personel_id LEFT JOIN users u ON u.id = s.olusturan_id
             WHERE s.id = :id AND NOT s.arsiv AND ' . Auth::bayiKosulu('s.bayi_id'),
            ['id' => $id]
        );
    }

    private function bul(int $id): array
    {
        return self::sayim($id) ?? $this->notFound();
    }

    private static function devamEden(int $id): array
    {
        $s = self::sayim($id);
        if (!$s) {
            throw new RuntimeException('Sayım bulunamadı.');
        }
        if ((int) $s['durum'] !== self::DEVAM) {
            throw new RuntimeException('Bu sayım tamamlanmış; değişiklik yapılamaz.');
        }

        return $s;
    }

    public function detay(string $id): void
    {
        $s = $this->bul((int) $id);
        $this->view('sayim/detay', [
            'pageTitle' => 'Araç Sayımı', 's' => $s,
            'breadcrumb' => ['Araç Sayımları' => '/sayim_kayitlari', $s['kod'] => null],
        ]);
    }

    /** @return array{okutulan: array, disarida: array, okutulmayan: array} */
    private static function listeler(array $s): array
    {
        $gorunur = Auth::bayiKosulu('a.bayi_id');
        $okutmalar = Database::fetchAll(
            "SELECT o.id, o.sase, o.sonuc, u.name AS okutan,
                    to_char(o.okutma_tarihi, 'DD.MM.YYYY HH24:MI') AS okutma_tarihi,
                    CASE WHEN {$gorunur} THEN a.plaka END AS plaka,
                    CASE WHEN {$gorunur} THEN m.ad END AS marka,
                    CASE WHEN {$gorunur} THEN sr.ad END AS seri,
                    CASE WHEN {$gorunur} THEN to_char(a.stoga_giris_tarihi, 'DD.MM.YYYY HH24:MI') END AS stoga_giris,
                    CASE WHEN a.id IS NULL THEN 'Sistemde kayıtlı değil'
                         WHEN a.bayi_id = :bayi AND a.stokta THEN 'Şu an bu lokasyonun stoğunda'
                         WHEN a.bayi_id = :bayi2 THEN 'Stoktan çıkmış görünüyor'
                         WHEN {$gorunur} THEN 'Başka lokasyonda kayıtlı: ' || b.ad
                         ELSE 'Başka lokasyonda kayıtlı' END AS sistem_durumu
             FROM sayim_okutmalari o
             LEFT JOIN araclar a ON a.id = o.arac_id
             LEFT JOIN markalar m ON m.id = a.marka_id LEFT JOIN seriler sr ON sr.id = a.seri_id
             LEFT JOIN bayiler b ON b.id = a.bayi_id
             LEFT JOIN users u ON u.id = o.okutan_id
             WHERE o.sayim_id = :id
             ORDER BY o.okutma_tarihi DESC NULLS LAST, o.id DESC",
            ['id' => $s['id'], 'bayi' => $s['bayi_id'], 'bayi2' => $s['bayi_id']]
        );
        $sonuc = ['okutulan' => [], 'disarida' => [], 'okutulmayan' => []];
        foreach ($okutmalar as $o) {
            $o['sonuc_ad'] = Tanim::SAYIM_SONUC[$o['sonuc']] ?? '-';
            $liste = match ((int) $o['sonuc']) {
                self::STOKTA, self::STOGA_ALINDI => 'okutulan',
                self::DISARIDA => 'disarida',
                default => 'okutulmayan',
            };
            $sonuc[$liste][] = $o;
        }

        if ((int) $s['durum'] === self::DEVAM) {
            $sonuc['okutulmayan'] = Database::fetchAll(
                "SELECT a.id AS arac_id, a.sase, a.plaka, m.ad AS marka, sr.ad AS seri, a.lokasyon_detay,
                        to_char(a.stoga_giris_tarihi, 'DD.MM.YYYY HH24:MI') AS stoga_giris, 'Henüz Okutulmadı' AS sonuc_ad
                 FROM sayimlar s JOIN araclar a ON " . self::OKUTULMAYAN_KOSUL . '
                 LEFT JOIN markalar m ON m.id = a.marka_id LEFT JOIN seriler sr ON sr.id = a.seri_id
                 WHERE s.id = :id ORDER BY a.stoga_giris_tarihi DESC NULLS LAST',
                ['id' => $s['id']]
            );
        }

        return $sonuc;
    }

    public function veri(string $id): void
    {
        $this->ajax(function () use ($id) {
            $s = self::sayim((int) $id) ?? throw new RuntimeException('Sayım bulunamadı.');
            $this->ok('', self::listeler($s) + ['durum' => (int) $s['durum']]);
        });
    }

    public function okut(string $id): void
    {
        $this->ajax(function () use ($id) {
            $s = self::devamEden((int) $id);
            $girdi = AracService::normalizeSase(Request::str('sase'));
            if (strlen($girdi) < 5) {
                throw new RuntimeException('Geçerli bir şasi numarası veya plaka giriniz.');
            }
            $arac = Database::fetch(
                "SELECT id, sase, bayi_id, stokta FROM araclar
                 WHERE sase = :s OR upper(replace(coalesce(plaka, ''), ' ', '')) = :s2
                 ORDER BY (sase = :s3) DESC, (bayi_id = :b) DESC, stokta DESC LIMIT 1",
                ['s' => $girdi, 's2' => $girdi, 's3' => $girdi, 'b' => $s['bayi_id']]
            );
            $sase = $arac['sase'] ?? $girdi;
            $onceki = Database::fetch('SELECT sonuc FROM sayim_okutmalari WHERE sayim_id = :id AND sase = :s', ['id' => $s['id'], 's' => $sase]);
            if ($onceki) {
                $this->ok("{$sase} bu sayımda zaten okutuldu.", ['tekrar' => true, 'sase' => $sase]);
            }

            $stokta = $arac && $arac['stokta'] && (int) $arac['bayi_id'] === (int) $s['bayi_id'];
            AracService::insert('sayim_okutmalari', [
                'sayim_id' => $s['id'], 'sase' => $sase, 'arac_id' => $arac['id'] ?? null,
                'sonuc' => $stokta ? self::STOKTA : self::DISARIDA,
                'okutan_id' => Auth::id(), 'okutma_tarihi' => date('Y-m-d H:i:s'),
            ]);
            Database::query('UPDATE sayimlar SET updated_at = NOW() WHERE id = :id', ['id' => $s['id']]);

            $this->ok($stokta ? "{$sase}: stokta, kayıt eklendi." : "{$sase}: bu araç bu lokasyonun stoğunda değil.", ['stokta' => $stokta, 'sase' => $sase]);
        });
    }

    public function okutmaSil(string $id): void
    {
        $this->ajax(function () use ($id) {
            $o = Database::fetch('SELECT sayim_id, sase, sonuc FROM sayim_okutmalari WHERE id = :id', ['id' => (int) $id]);
            if (!$o || (int) $o['sonuc'] === self::BULUNAMADI) {
                throw new RuntimeException('Okutma kaydı bulunamadı.');
            }
            self::devamEden((int) $o['sayim_id']);
            Database::query('DELETE FROM sayim_okutmalari WHERE id = :id', ['id' => (int) $id]);
            $this->ok("{$o['sase']} okutması silindi.");
        });
    }

    /** Hızlı araç ekle penceresinden "Stoğa Al" ile kaydedilen aracı sayımda okutulanlara taşır. */
    public static function stogaAlindi(int $sayimId, int $aracId): void
    {
        $s = self::sayim($sayimId);
        if (!$s || (int) $s['durum'] !== self::DEVAM) {
            return;
        }
        Database::query(
            'UPDATE sayim_okutmalari o SET sonuc = :yeni, arac_id = a.id
             FROM araclar a
             WHERE a.id = :arac AND a.stokta AND a.bayi_id = :bayi
               AND o.sayim_id = :sayim AND o.sase = a.sase AND o.sonuc = :eski',
            ['yeni' => self::STOGA_ALINDI, 'arac' => $aracId, 'bayi' => $s['bayi_id'], 'sayim' => $sayimId, 'eski' => self::DISARIDA]
        );
    }

    public function tamamla(string $id): void
    {
        $this->ajax(function () use ($id) {
            $s = self::devamEden((int) $id);
            $n = Database::transaction(function () use ($s) {
                $n = Database::query(
                    'INSERT INTO sayim_okutmalari (sayim_id, sase, arac_id, sonuc)
                     SELECT s.id, a.sase, a.id, ' . self::BULUNAMADI . '
                     FROM sayimlar s JOIN araclar a ON ' . self::OKUTULMAYAN_KOSUL . '
                     WHERE s.id = :id',
                    ['id' => $s['id']]
                )->rowCount();
                Database::query('UPDATE sayimlar SET durum = :d, tamamlanma_tarihi = NOW(), updated_at = NOW() WHERE id = :id', ['d' => self::TAMAMLANDI, 'id' => $s['id']]);

                return $n;
            });
            Log::islem('sayim', "Sayım tamamlandı: {$s['kod']} ({$n} araç bulunamadı)", (int) $s['id']);
            $this->ok($n ? "Sayım tamamlandı. {$n} araç sahada bulunamadı olarak kaydedildi." : 'Sayım tamamlandı. Stoktaki tüm araçlar okutuldu.');
        });
    }

    public function excel(string $id): void
    {
        $s = $this->bul((int) $id);
        $listeler = self::listeler($s);
        $adlar = ['okutulan' => 'Okutulanlar', 'disarida' => 'Lokasyonda Değil', 'okutulmayan' => (int) $s['durum'] === self::DEVAM ? 'Henüz Okutulmayanlar' : 'Bulunamayanlar'];
        $satirlar = [];
        foreach ($adlar as $key => $ad) {
            foreach ($listeler[$key] as $r) {
                $satirlar[] = $r + ['liste' => $ad];
            }
        }
        Excel::download('sayim-' . $s['kod'], [
            'liste' => 'Liste', 'sase' => 'Şasi', 'plaka' => 'Plaka', 'marka' => 'Marka', 'seri' => 'Seri', 'sonuc_ad' => 'Durum',
            'sistem_durumu' => 'Sistemdeki Durumu', 'stoga_giris' => 'Stoğa Giriş', 'okutan' => 'Okutan', 'okutma_tarihi' => 'Okutma Tarihi',
        ], $satirlar);
    }

    public function topluArsiv(): void
    {
        $this->ajax(function () {
            $ids = Request::ids('ids');
            if (!$ids) {
                throw new RuntimeException('Kayıt seçilmedi.');
            }
            $n = Database::query(
                'UPDATE sayimlar s SET arsiv = TRUE, updated_at = NOW() WHERE s.id = ANY(:ids::bigint[]) AND ' . Auth::bayiKosulu('s.bayi_id'),
                ['ids' => '{' . implode(',', $ids) . '}']
            )->rowCount();
            $this->ok("{$n} sayım arşivlendi.");
        });
    }
}
