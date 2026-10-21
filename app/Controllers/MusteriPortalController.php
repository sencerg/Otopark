<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Excel;
use App\Services\DepolamaHesap;

/** Müşteri portalı — salt görüntüleme (araçlar, gün, depolama ücreti, hizmetler). */
final class MusteriPortalController extends Controller
{
    /** Ana liste: stok + çıkan birlikte. */
    public function dashboard(): void
    {
        $this->listeSayfasi('tumu');
    }

    public function stok(): void
    {
        $this->listeSayfasi('stok');
    }

    public function cikanlar(): void
    {
        $this->listeSayfasi('cikan');
    }

    /** @param 'tumu'|'stok'|'cikan' $mod */
    private function listeSayfasi(string $mod): void
    {
        $musteriId = (int) Auth::musteriId();
        $stoklar = $this->aracListe($musteriId, true);
        $cikanlar = $this->aracListe($musteriId, false);
        $araclar = match ($mod) {
            'stok' => $stoklar,
            'cikan' => $cikanlar,
            default => array_merge(
                array_map(static fn ($r) => $r + ['durum' => 'Stokta'], $stoklar),
                array_map(static fn ($r) => $r + ['durum' => 'Çıkmış'], $cikanlar)
            ),
        };
        if ($mod === 'tumu') {
            usort($araclar, static fn ($a, $b) => ((int) ($b['gun'] ?? 0)) <=> ((int) ($a['gun'] ?? 0)));
        }

        $hizmetOzet = Database::fetch(
            'SELECT COUNT(*)::int AS adet, COALESCE(SUM(e.tutar), 0)::float AS tutar
             FROM arac_ekstreleri e
             JOIN araclar a ON a.id = e.arac_id
             WHERE a.musteri_id = :m AND NOT COALESCE(a.arsiv, false)',
            ['m' => $musteriId]
        );
        $depToplam = array_sum(array_map(fn ($r) => (float) $r['tutar'], $araclar));
        $gunOrt = $araclar ? round(array_sum(array_map(fn ($r) => (int) $r['gun'], $araclar)) / count($araclar)) : 0;
        $baslik = match ($mod) {
            'stok' => 'Stoktaki Araçlar',
            'cikan' => 'Stoktan Çıkanlar',
            default => 'Tüm Araçlar',
        };

        $this->portal('musteri/dashboard', [
            'pageTitle' => $baslik,
            'musteriAdi' => Auth::user()['musteri_adi'] ?? '',
            'mod' => $mod,
            'araclar' => $araclar,
            'filtreler' => $this->aracFiltreSecenekleri($araclar),
            'kpi' => [
                'stok' => count($stoklar),
                'cikan' => count($cikanlar),
                'toplam' => count($stoklar) + count($cikanlar),
                'dep_tutar' => $depToplam,
                'hizmet_adet' => (int) ($hizmetOzet['adet'] ?? 0),
                'hizmet_tutar' => (float) ($hizmetOzet['tutar'] ?? 0),
                'gun_ort' => $gunOrt,
            ],
        ]);
    }

    public function hizmetler(): void
    {
        $satirlar = $this->hizmetListe((int) Auth::musteriId());

        $this->portal('musteri/hizmetler', [
            'pageTitle' => 'Aldığım Hizmetler',
            'musteriAdi' => Auth::user()['musteri_adi'] ?? '',
            'satirlar' => $satirlar,
            'filtreler' => $this->hizmetFiltreSecenekleri($satirlar),
            'toplam' => array_sum(array_map(fn ($r) => (float) $r['tutar'], $satirlar)),
        ]);
    }

    public function arac(string $id): void
    {
        $aracId = (int) $id;
        $arac = $this->aracDetay($aracId);
        if (!$arac) {
            $this->notFound();
        }

        $this->portal('musteri/arac', [
            'pageTitle' => trim(($arac['plaka'] ?: $arac['sase']) . ' — Detay'),
            'breadcrumb' => ['Detay' => null],
            'arac' => $arac,
            'konaklamalar' => DepolamaHesap::arac($aracId),
            'hizmetler' => $this->aracHizmetler($aracId),
        ]);
    }

    /** Excel indirme: tip = tumu | stok | cikanlar | hizmetler */
    public function excel(string $tip): void
    {
        $musteriId = (int) Auth::musteriId();
        $firma = $this->excelFirmaAdi();

        if (in_array($tip, ['tumu', 'stok', 'cikanlar'], true)) {
            $stoklar = $this->aracListe($musteriId, true);
            $cikanlar = $this->aracListe($musteriId, false);
            $kaynak = match ($tip) {
                'stok' => array_map(static fn ($r) => $r + ['durum' => 'Stokta'], $stoklar),
                'cikanlar' => array_map(static fn ($r) => $r + ['durum' => 'Çıkmış'], $cikanlar),
                default => array_merge(
                    array_map(static fn ($r) => $r + ['durum' => 'Stokta'], $stoklar),
                    array_map(static fn ($r) => $r + ['durum' => 'Çıkmış'], $cikanlar)
                ),
            };
            $cikisKolon = $tip !== 'stok';
            $rows = array_map(fn ($r) => $this->aracExcelSatir($r, $cikisKolon, $tip === 'tumu'), $kaynak);
            $headers = [
                'plaka' => 'Plaka',
                'sase' => 'Şasi',
                'marka_seri' => 'Marka / Seri',
                'otopark' => 'Otopark',
                'lokasyon_detay' => 'Lokasyon Detay',
            ];
            if ($tip === 'tumu') {
                $headers['durum'] = 'Durum';
            }
            $headers['giris'] = 'Giriş';
            if ($cikisKolon) {
                $headers['cikis'] = 'Çıkış';
            }
            $headers += [
                'gun' => 'Gün',
                'gunluk_fiyat' => 'Günlük Ücret (₺)',
                'depolama' => 'Depolama (₺)',
                'hizmet_adet' => 'Hizmet Adet',
                'hizmet_tutar' => 'Hizmet Toplam (₺)',
            ];
            $sayfa = match ($tip) {
                'stok' => 'Stoktaki Araçlar',
                'cikanlar' => 'Stoktan Çıkanlar',
                default => 'Tüm Araçlar',
            };
            Excel::downloadXlsx($firma . '_' . str_replace(' ', '_', $sayfa), [
                $sayfa => ['headers' => $headers, 'rows' => $rows],
            ]);
        }

        if ($tip === 'hizmetler') {
            $rows = array_map(static fn ($r) => [
                'tarih' => $r['islem_tarihi'] ? date('d.m.Y', strtotime((string) $r['islem_tarihi'])) : '',
                'hizmet' => $r['hizmet'],
                'plaka' => $r['plaka'] ?? '',
                'sase' => $r['sase'] ?? '',
                'otopark' => $r['otopark'] ?? '',
                'aciklama' => $r['aciklama'] ?? '',
                'fatura_no' => $r['fatura_no'] ?? '',
                'tutar' => number_format((float) $r['tutar'], 2, ',', '.'),
            ], $this->hizmetListe($musteriId));
            Excel::downloadXlsx($firma . '_Hizmetler', [
                'Hizmetler' => [
                    'headers' => [
                        'tarih' => 'Tarih',
                        'hizmet' => 'Hizmet',
                        'plaka' => 'Plaka',
                        'sase' => 'Şasi',
                        'otopark' => 'Otopark',
                        'aciklama' => 'Açıklama',
                        'fatura_no' => 'Fatura No',
                        'tutar' => 'Tutar (₺)',
                    ],
                    'rows' => $rows,
                ],
            ]);
        }

        $this->notFound();
    }

    public function excelArac(string $id): void
    {
        $aracId = (int) $id;
        $arac = $this->aracDetay($aracId);
        if (!$arac) {
            $this->notFound();
        }
        $konaklamalar = DepolamaHesap::arac($aracId);
        $hizmetler = $this->aracHizmetler($aracId);
        $ozet = [[
            'plaka' => $arac['plaka'] ?? '',
            'sase' => $arac['sase'] ?? '',
            'marka_seri' => trim(($arac['marka'] ?? '') . ' ' . ($arac['seri'] ?? '')),
            'arac_tipi' => $arac['arac_tipi'] ?? '',
            'otopark' => $arac['otopark'] ?? '',
            'lokasyon' => $arac['lokasyon_detay'] ?? '',
            'durum' => $arac['stokta'] ? 'Stokta' : 'Çıkmış',
        ]];
        $depRows = array_map(static fn ($k) => [
            'otopark' => $k['bayi'] ?? '',
            'giris' => $k['giris_tarihi'] ? date('d.m.Y H:i', strtotime((string) $k['giris_tarihi'])) : '',
            'cikis' => $k['cikis_tarihi'] ? date('d.m.Y H:i', strtotime((string) $k['cikis_tarihi'])) : 'Devam ediyor',
            'gun' => (int) $k['gun'],
            'gunluk' => !empty($k['fiyat_degisti']) && !empty($k['fiyat_ozet'])
                ? (string) $k['fiyat_ozet']
                : number_format((float) $k['gunluk_fiyat'], 2, ',', '.'),
            'tutar' => number_format((float) ($k['tutar'] ?? 0), 2, ',', '.'),
        ], $konaklamalar);
        $hizRows = array_map(static fn ($h) => [
            'tarih' => $h['islem_tarihi'] ? date('d.m.Y', strtotime((string) $h['islem_tarihi'])) : '',
            'hizmet' => $h['hizmet'],
            'aciklama' => $h['aciklama'] ?? '',
            'tutar' => number_format((float) $h['tutar'], 2, ',', '.'),
        ], $hizmetler);
        $ad = preg_replace('/[^\p{L}\p{N}\-_]+/u', '', (string) ($arac['plaka'] ?: $arac['sase'])) ?: 'Arac';
        Excel::downloadXlsx($this->excelFirmaAdi() . '_' . $ad, [
            'Araç' => [
                'headers' => [
                    'plaka' => 'Plaka', 'sase' => 'Şasi', 'marka_seri' => 'Marka / Seri',
                    'arac_tipi' => 'Tip', 'otopark' => 'Otopark', 'lokasyon' => 'Lokasyon', 'durum' => 'Durum',
                ],
                'rows' => $ozet,
            ],
            'Depolama' => [
                'headers' => [
                    'otopark' => 'Otopark', 'giris' => 'Giriş', 'cikis' => 'Çıkış',
                    'gun' => 'Gün', 'gunluk' => 'Günlük (₺)', 'tutar' => 'Tutar (₺)',
                ],
                'rows' => $depRows,
            ],
            'Hizmetler' => [
                'headers' => [
                    'tarih' => 'Tarih', 'hizmet' => 'Hizmet', 'aciklama' => 'Açıklama', 'tutar' => 'Tutar (₺)',
                ],
                'rows' => $hizRows,
            ],
        ]);
    }

    private function excelFirmaAdi(): string
    {
        $firma = preg_replace('/[^\p{L}\p{N}\-_ ]+/u', '', (string) (Auth::user()['musteri_adi'] ?? 'Musteri')) ?: 'Musteri';

        return str_replace(' ', '_', trim($firma));
    }

    /** @param array<string,mixed> $r */
    private function aracExcelSatir(array $r, bool $cikisKolon, bool $durumKolon = false): array
    {
        $row = [
            'plaka' => $r['plaka'] ?? '',
            'sase' => $r['sase'] ?? '',
            'marka_seri' => trim(($r['marka'] ?? '') . ' ' . ($r['seri'] ?? '')),
            'otopark' => $r['otopark'] ?? '',
            'lokasyon_detay' => $r['lokasyon_detay'] ?? '',
            'giris' => !empty($r['giris_tarihi']) ? date('d.m.Y H:i', strtotime((string) $r['giris_tarihi'])) : '',
            'gun' => (int) ($r['gun'] ?? 0),
            'gunluk_fiyat' => !empty($r['fiyat_degisti']) && !empty($r['fiyat_ozet'])
                ? (string) $r['fiyat_ozet']
                : number_format((float) ($r['gunluk_fiyat'] ?? 0), 2, ',', '.'),
            'depolama' => number_format((float) ($r['tutar'] ?? 0), 2, ',', '.'),
            'hizmet_adet' => (int) ($r['hizmet_adet'] ?? 0),
            'hizmet_tutar' => number_format((float) ($r['hizmet_tutar'] ?? 0), 2, ',', '.'),
        ];
        if ($durumKolon) {
            $row['durum'] = $r['durum'] ?? (!empty($r['stokta']) ? 'Stokta' : 'Çıkmış');
        }
        if ($cikisKolon) {
            $row['cikis'] = !empty($r['cikis_tarihi']) ? date('d.m.Y H:i', strtotime((string) $r['cikis_tarihi'])) : '';
        }

        return $row;
    }

    /** @return list<array<string,mixed>> */
    private function hizmetListe(int $musteriId): array
    {
        return Database::fetchAll(
            'SELECT e.id, e.islem_tarihi, e.tutar, e.aciklama, e.fatura_no,
                    mt.ad AS hizmet, a.plaka, a.sase, b.ad AS otopark,
                    mk.ad AS marka, s.ad AS seri
             FROM arac_ekstreleri e
             JOIN araclar a ON a.id = e.arac_id
             JOIN maliyet_tipleri mt ON mt.id = e.maliyet_tipi_id
             LEFT JOIN bayiler b ON b.id = a.bayi_id
             LEFT JOIN markalar mk ON mk.id = a.marka_id
             LEFT JOIN seriler s ON s.id = a.seri_id
             WHERE a.musteri_id = :m AND NOT COALESCE(a.arsiv, false)
             ORDER BY e.islem_tarihi DESC NULLS LAST, e.id DESC',
            ['m' => $musteriId]
        );
    }

    /**
     * @param list<array<string,mixed>> $araclar
     * @return array{markalar:list<string>,seriler:array<string,list<string>>,modeller:list<string>,otoparklar:list<string>,parklar:list<string>}
     */
    private function aracFiltreSecenekleri(array $araclar): array
    {
        $markalar = [];
        $seriler = [];
        $modeller = [];
        $otoparklar = [];
        $parklar = [];
        foreach ($araclar as $r) {
            $m = trim((string) ($r['marka'] ?? ''));
            $s = trim((string) ($r['seri'] ?? ''));
            $mo = trim((string) ($r['model'] ?? ''));
            $o = trim((string) ($r['otopark'] ?? ''));
            $p = trim((string) ($r['park_kodu'] ?? ''));
            $lok = trim((string) ($r['lokasyon_detay'] ?? ''));
            if ($m !== '') {
                $markalar[$m] = $m;
                if ($s !== '') {
                    $seriler[$m][$s] = $s;
                }
            }
            if ($mo !== '') {
                $modeller[$mo] = $mo;
            }
            if ($o !== '') {
                $otoparklar[$o] = $o;
            }
            if ($p !== '') {
                $parklar[$p] = $p;
            }
            if ($lok !== '') {
                $parklar[$lok] = $lok;
            }
        }
        ksort($markalar, SORT_NATURAL | SORT_FLAG_CASE);
        ksort($modeller, SORT_NATURAL | SORT_FLAG_CASE);
        ksort($otoparklar, SORT_NATURAL | SORT_FLAG_CASE);
        ksort($parklar, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($seriler as $m => $list) {
            ksort($list, SORT_NATURAL | SORT_FLAG_CASE);
            $seriler[$m] = array_values($list);
        }

        return [
            'markalar' => array_values($markalar),
            'seriler' => $seriler,
            'modeller' => array_values($modeller),
            'otoparklar' => array_values($otoparklar),
            'parklar' => array_values($parklar),
        ];
    }

    /**
     * @param list<array<string,mixed>> $satirlar
     * @return array{hizmetler:list<string>,otoparklar:list<string>,markalar:list<string>}
     */
    private function hizmetFiltreSecenekleri(array $satirlar): array
    {
        $hizmetler = [];
        $otoparklar = [];
        $markalar = [];
        foreach ($satirlar as $r) {
            $h = trim((string) ($r['hizmet'] ?? ''));
            $o = trim((string) ($r['otopark'] ?? ''));
            $m = trim((string) ($r['marka'] ?? ''));
            if ($h !== '') {
                $hizmetler[$h] = $h;
            }
            if ($o !== '') {
                $otoparklar[$o] = $o;
            }
            if ($m !== '') {
                $markalar[$m] = $m;
            }
        }
        ksort($hizmetler, SORT_NATURAL | SORT_FLAG_CASE);
        ksort($otoparklar, SORT_NATURAL | SORT_FLAG_CASE);
        ksort($markalar, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'hizmetler' => array_values($hizmetler),
            'otoparklar' => array_values($otoparklar),
            'markalar' => array_values($markalar),
        ];
    }

    private function aracDetay(int $aracId): ?array
    {
        return Database::fetch(
            'SELECT a.id, a.plaka, a.sase, a.stokta, a.stoga_giris_tarihi, a.lokasyon_detay,
                    b.ad AS otopark, mk.ad AS marka, s.ad AS seri, at.ad AS arac_tipi
             FROM araclar a
             LEFT JOIN bayiler b ON b.id = a.bayi_id
             LEFT JOIN markalar mk ON mk.id = a.marka_id
             LEFT JOIN seriler s ON s.id = a.seri_id
             LEFT JOIN arac_tipleri at ON at.id = a.arac_tipi_id
             WHERE a.id = :id AND a.musteri_id = :m AND NOT COALESCE(a.arsiv, false)',
            ['id' => $aracId, 'm' => (int) Auth::musteriId()]
        ) ?: null;
    }

    /** @return list<array<string,mixed>> */
    private function aracHizmetler(int $aracId): array
    {
        return Database::fetchAll(
            'SELECT e.islem_tarihi, e.tutar, e.aciklama, mt.ad AS hizmet
             FROM arac_ekstreleri e
             JOIN maliyet_tipleri mt ON mt.id = e.maliyet_tipi_id
             WHERE e.arac_id = :a
             ORDER BY e.islem_tarihi DESC NULLS LAST, e.id DESC',
            ['a' => $aracId]
        );
    }

    /**
     * @param bool $stokta true = açık konaklama / stokta; false = son tamamlanmış konaklama / çıkmış
     * @return list<array<string,mixed>>
     */
    private function aracListe(int $musteriId, bool $stokta): array
    {
        $alanlar = 'a.id, a.plaka, a.sase, a.lokasyon_detay, a.park_kodu, a.stokta, a.arac_tipi_id,
                    d.bayi_id, d.musteri_id, d.dep_bas, d.dep_bit,
                    b.ad AS otopark, mk.ad AS marka, s.ad AS seri, md.ad AS model, at.ad AS arac_tipi,
                    d.giris_tarihi, d.cikis_tarihi, d.gun, d.gunluk_fiyat, d.carpan, d.taban_fiyat, d.fiyat_yok, d.tutar,
                    (SELECT COUNT(*)::int FROM arac_ekstreleri e WHERE e.arac_id = a.id) AS hizmet_adet,
                    (SELECT COALESCE(SUM(e.tutar), 0)::float FROM arac_ekstreleri e WHERE e.arac_id = a.id) AS hizmet_tutar';
        $join = 'FROM ' . DepolamaHesap::sorgu('2000-01-01', '9999-12-31') . '
                 JOIN araclar a ON a.id = d.arac_id
                 JOIN bayiler b ON b.id = d.bayi_id
                 LEFT JOIN markalar mk ON mk.id = a.marka_id
                 LEFT JOIN seriler s ON s.id = a.seri_id
                 LEFT JOIN modeller md ON md.id = a.model_id
                 LEFT JOIN arac_tipleri at ON at.id = a.arac_tipi_id';

        if ($stokta) {
            return DepolamaHesap::zenginlestir(Database::fetchAll(
                "SELECT {$alanlar} {$join}
                 WHERE d.musteri_id = :m
                   AND d.cikis_tarihi IS NULL
                   AND a.stokta
                   AND NOT COALESCE(a.arsiv, false)
                 ORDER BY d.gun DESC, COALESCE(a.plaka, a.sase)",
                ['m' => $musteriId]
            ));
        }

        // Araç başına en son tamamlanmış konaklama
        return DepolamaHesap::zenginlestir(Database::fetchAll(
            "SELECT * FROM (
                SELECT DISTINCT ON (a.id) {$alanlar} {$join}
                 WHERE d.musteri_id = :m
                   AND d.cikis_tarihi IS NOT NULL
                   AND NOT a.stokta
                   AND NOT COALESCE(a.arsiv, false)
                 ORDER BY a.id, d.cikis_tarihi DESC
             ) x ORDER BY cikis_tarihi DESC, COALESCE(plaka, sase)",
            ['m' => $musteriId]
        ));
    }

    private function portal(string $view, array $data): void
    {
        \App\Core\View::render($view, $data, 'musteri');
    }
}
