<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;

final class DashboardController extends Controller
{
    /** Lokasyon filtresi: bayi kullanıcısı her zaman kendi lokasyonu */
    private function bayiFiltresi(): ?int
    {
        return Auth::bayiId() ?? Request::int('bayi_id');
    }

    private function kosul(string $col, array &$params): string
    {
        $bayi = $this->bayiFiltresi();
        if ($bayi === null) {
            return 'TRUE';
        }
        $params['bayi'] = $bayi;

        return "{$col} = :bayi";
    }

    public function index(): void
    {
        $bas = Request::date('baslangic') ?? date('Y-m-d');
        $bit = Request::date('bitis') ?? date('Y-m-d');
        if ($bas > $bit) {
            [$bas, $bit] = [$bit, $bas];
        }
        $gun = (int) ((strtotime($bit) - strtotime($bas)) / 86400) + 1;
        $oncekiBit = date('Y-m-d', strtotime($bas . ' -1 day'));
        $oncekiBas = date('Y-m-d', strtotime($oncekiBit . ' -' . ($gun - 1) . ' days'));

        $p = [];
        $aracK = $this->kosul('a.bayi_id', $p);
        $anlikStok = (int) Database::fetch("SELECT COUNT(*) c FROM araclar a WHERE a.stokta AND NOT a.arsiv AND {$aracK}", $p)['c'];

        $hareketSay = function (int $tip, string $b, string $t): int {
            $p = ['tip' => $tip, 'b' => $b, 't' => $t];
            $k = $this->kosul('h.bayi_id', $p);

            return (int) Database::fetch(
                "SELECT COUNT(*) c FROM arac_hareketleri h WHERE h.hareket_tipi = :tip AND NOT h.arsiv AND h.hareket_tarihi::date BETWEEN :b AND :t AND {$k}",
                $p
            )['c'];
        };
        $maliyetTop = function (string $b, string $t): array {
            $p = ['b' => $b, 't' => $t];
            $k = $this->kosul('e.bayi_id', $p);

            return Database::fetch("SELECT COUNT(*) adet, COALESCE(SUM(tutar),0) tutar FROM arac_ekstreleri e WHERE e.islem_tarihi BETWEEN :b AND :t AND {$k}", $p);
        };

        $kpi = [
            'giris' => [$hareketSay(1, $bas, $bit), $hareketSay(1, $oncekiBas, $oncekiBit)],
            'cikis' => [$hareketSay(2, $bas, $bit), $hareketSay(2, $oncekiBas, $oncekiBit)],
            'maliyet' => [$maliyetTop($bas, $bit), $maliyetTop($oncekiBas, $oncekiBit)],
        ];

        $p = [];
        $k = $this->kosul('a.bayi_id', $p);
        $firmaDagilimi = Database::fetchAll(
            "SELECT COALESCE(mu.ad, 'Tanımsız') ad, COUNT(*) adet FROM araclar a LEFT JOIN musteriler mu ON mu.id = a.musteri_id
             WHERE a.stokta AND NOT a.arsiv AND {$k} GROUP BY mu.ad ORDER BY adet DESC",
            $p
        );

        $p = ['b' => $bas, 't' => $bit];
        $k = $this->kosul('e.bayi_id', $p);
        $hizmetDagilimi = Database::fetchAll(
            "SELECT mt.ad, COUNT(*) adet, SUM(e.tutar) tutar FROM arac_ekstreleri e JOIN maliyet_tipleri mt ON mt.id = e.maliyet_tipi_id
             WHERE e.islem_tarihi BETWEEN :b AND :t AND {$k} GROUP BY mt.ad ORDER BY tutar DESC",
            $p
        );

        $p = [];
        $k = $this->kosul('a.bayi_id', $p);
        $ozet = Database::fetch(
            "SELECT COUNT(*) toplam_arac, COUNT(*) FILTER (WHERE a.stokta) stokta, COUNT(*) FILTER (WHERE NOT a.stokta) cikan,
                    COUNT(DISTINCT a.musteri_id) firma, COUNT(DISTINCT a.marka_id) marka
             FROM araclar a WHERE NOT a.arsiv AND {$k}",
            $p
        );
        $p = [];
        $k = $this->kosul('e.bayi_id', $p);
        $ozet += Database::fetch("SELECT COUNT(*) maliyet_adet, COALESCE(SUM(tutar),0) maliyet_tutar FROM arac_ekstreleri e WHERE {$k}", $p);
        $p = [];
        $k = $this->kosul('h.bayi_id', $p);
        $ozet += Database::fetch("SELECT COUNT(*) hareket FROM arac_hareketleri h WHERE NOT h.arsiv AND {$k}", $p);

        $this->view('dashboard/index', [
            'pageTitle' => 'Ana Dashboard', 'hidePageHeader' => true,
            'bas' => $bas, 'bit' => $bit, 'bayiId' => $this->bayiFiltresi(),
            'anlikStok' => $anlikStok, 'kpi' => $kpi,
            'firmaDagilimi' => $firmaDagilimi, 'hizmetDagilimi' => $hizmetDagilimi, 'ozet' => $ozet,
            'duyurular' => Database::fetchAll('SELECT * FROM duyurular WHERE aktif ORDER BY created_at DESC LIMIT 6'),
            'sonIslemler' => Database::fetchAll(
                'SELECT l.modul, l.aciklama, l.created_at, u.name FROM islem_loglari l LEFT JOIN users u ON u.id = l.kullanici_id
                 WHERE ' . (Auth::bayiId() ? 'u.bayi_id = ' . Auth::bayiId() : 'TRUE') . ' ORDER BY l.id DESC LIMIT 8'
            ),
        ]);
    }

    public function saha(): void
    {
        $p = [];
        $hk = $this->kosul('h.bayi_id', $p);
        $yillar = Database::fetchAll(
            "SELECT y::int AS yil,
                    COUNT(h.id) FILTER (WHERE h.hareket_tipi = 1) giren,
                    COUNT(h.id) FILTER (WHERE h.hareket_tipi = 2) cikan
             FROM generate_series(EXTRACT(YEAR FROM CURRENT_DATE)::int - 2, EXTRACT(YEAR FROM CURRENT_DATE)::int) y
             LEFT JOIN arac_hareketleri h ON EXTRACT(YEAR FROM h.hareket_tarihi) = y AND NOT h.arsiv AND {$hk}
             GROUP BY y ORDER BY y DESC",
            $p
        );
        foreach ($yillar as &$y) {
            $gunSayisi = (int) $y['yil'] === (int) date('Y') ? (int) date('z') + 1 : (int) date('z', mktime(0, 0, 0, 12, 31, (int) $y['yil'])) + 1;
            $y['gunluk_giren'] = $y['giren'] / $gunSayisi;
            $y['gunluk_cikan'] = $y['cikan'] / $gunSayisi;
            $p2 = ['s' => $y['yil'] . '-12-31 23:59:59'];
            $k2 = $this->kosul('a.bayi_id', $p2);
            $y['stok'] = (int) Database::fetch(
                "SELECT COUNT(*) c FROM arac_konaklamalari k JOIN araclar a ON a.id = k.arac_id
                 WHERE k.giris_tarihi <= :s AND (k.cikis_tarihi IS NULL OR k.cikis_tarihi > :s) AND NOT a.arsiv AND {$k2}",
                $p2
            )['c'];
        }
        unset($y);

        $p = ['y' => date('Y')];
        $k = $this->kosul('e.bayi_id', $p);
        $maliyetler = Database::fetchAll(
            "SELECT mt.ad, SUM(e.tutar) tutar, COUNT(*) adet FROM arac_ekstreleri e JOIN maliyet_tipleri mt ON mt.id = e.maliyet_tipi_id
             WHERE EXTRACT(YEAR FROM e.islem_tarihi) = :y AND {$k} GROUP BY mt.ad ORDER BY tutar DESC",
            $p
        );

        $p = [];
        $k = $this->kosul('a.bayi_id', $p);
        $firmalar = Database::fetchAll(
            "SELECT COALESCE(mu.ad, 'Tanımsız') ad, COUNT(*) adet FROM araclar a LEFT JOIN musteriler mu ON mu.id = a.musteri_id
             WHERE a.stokta AND NOT a.arsiv AND {$k} GROUP BY mu.ad ORDER BY adet DESC",
            $p
        );

        $p = [];
        $k = $this->kosul('a.bayi_id', $p);
        $sureler = Database::fetch(
            "SELECT COUNT(*) FILTER (WHERE g <= 30) a, COUNT(*) FILTER (WHERE g BETWEEN 31 AND 90) b,
                    COUNT(*) FILTER (WHERE g BETWEEN 91 AND 180) c, COUNT(*) FILTER (WHERE g > 180) d
             FROM (SELECT CURRENT_DATE - a.stoga_giris_tarihi::date + 1 AS g FROM araclar a WHERE a.stokta AND NOT a.arsiv AND {$k}) t",
            $p
        );

        $this->view('dashboard/saha', [
            'pageTitle' => 'Stok Dashboard', 'breadcrumb' => ['Saha Operasyon Araç Yönetimi' => null, 'Stok Dashboard' => null],
            'yillar' => $yillar, 'maliyetler' => $maliyetler, 'firmalar' => $firmalar, 'sureler' => $sureler, 'bayiId' => $this->bayiFiltresi(),
        ]);
    }

    public function ik(): void
    {
        $this->view('dashboard/ik', [
            'pageTitle' => 'İK ve Satın Alma Yönetimi — Dashboard', 'breadcrumb' => ['İK' => null, 'Dashboard' => null],
            'personelSayisi' => Database::fetchAll(
                'SELECT d.ad, COUNT(p.id) adet FROM departmanlar d LEFT JOIN personeller p ON p.departman_id = d.id AND p.aktif GROUP BY d.ad ORDER BY adet DESC, d.ad'
            ),
            'destek' => Database::fetch(
                'SELECT COUNT(*) FILTER (WHERE durum = 1) acik, COUNT(*) FILTER (WHERE durum = 2) islemde, COUNT(*) FILTER (WHERE durum IN (3,4)) kapali
                 FROM destek_talepleri WHERE NOT arsiv' . (Auth::bayiId() ? ' AND talep_eden_id IN (SELECT id FROM users WHERE bayi_id = ' . Auth::bayiId() . ')' : '')
            ),
            'loglar' => Database::fetchAll(
                'SELECT l.modul, l.aciklama, l.created_at, u.name FROM islem_loglari l LEFT JOIN users u ON u.id = l.kullanici_id
                 WHERE ' . (Auth::bayiId() ? 'u.bayi_id = ' . Auth::bayiId() : 'TRUE') . ' ORDER BY l.id DESC LIMIT 15'
            ),
        ]);
    }
}
