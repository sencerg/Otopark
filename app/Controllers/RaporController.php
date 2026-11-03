<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\DataTable;
use App\Core\Excel;
use App\Core\Request;
use App\Services\DepolamaHesap;
use App\Services\RaporKatalog;

final class RaporController extends Controller
{
    public function index(): void
    {
        Auth::requireIzin('menu.raporlar');
        $raporlar = RaporKatalog::erisilebilir();
        $this->view('raporlar/index', [
            'pageTitle' => 'Raporlar',
            'breadcrumb' => ['Raporlar' => null],
            'raporlar' => $raporlar,
            'interaktif' => array_values(array_filter($raporlar, static fn ($r) => $r['tip'] === 'interaktif')),
            'hazirla' => array_values(array_filter($raporlar, static fn ($r) => $r['tip'] === 'hazirla')),
        ]);
    }

    public function depolama(): void
    {
        Auth::requireIzin('menu.depolama_raporu');
        $this->view('raporlar/depolama', [
            'pageTitle' => 'Depolama Raporu',
            'breadcrumb' => ['Raporlar' => '/raporlar', 'Depolama Raporu' => null],
        ]);
    }

    private function aralik(): array
    {
        $bas = Request::date('baslangic') ?? date('Y-m-01');
        $bit = Request::date('bitis') ?? date('Y-m-d');

        return $bas <= $bit ? [$bas, $bit] : [$bit, $bas];
    }

    public function depolamaListe(): void
    {
        Auth::requireIzin('menu.depolama_raporu');
        [$bas, $bit] = $this->aralik();
        $dt = new DataTable(
            DepolamaHesap::sorgu($bas, $bit) . '
             JOIN araclar a ON a.id = d.arac_id
             LEFT JOIN musteriler mu ON mu.id = d.musteri_id
             LEFT JOIN bayiler b ON b.id = d.bayi_id
             LEFT JOIN markalar m ON m.id = a.marka_id
             LEFT JOIN modeller mo ON mo.id = a.model_id
             LEFT JOIN seriler s ON s.id = a.seri_id
             LEFT JOIN arac_tipleri at ON at.id = a.arac_tipi_id',
            [
                'id' => 'd.giris_hareket_id', 'arac_id' => 'a.id', 'sase' => 'a.sase', 'plaka' => 'a.plaka', 'firma' => 'mu.ad', 'marka' => 'm.ad', 'model' => 'mo.ad', 'seri' => 's.ad',
                'arac_tipi' => 'at.ad', 'lokasyon' => 'b.ad',
                'bayi_id' => 'd.bayi_id', 'musteri_id' => 'd.musteri_id', 'arac_tipi_id' => 'a.arac_tipi_id',
                'dep_bas' => 'd.dep_bas', 'dep_bit' => 'd.dep_bit',
                'giris_tarihi' => "to_char(d.giris_tarihi, 'DD.MM.YYYY')", 'giris_tarihi_sort' => 'd.giris_tarihi',
                'cikis_tarihi' => "to_char(d.cikis_tarihi, 'DD.MM.YYYY')", 'cikis_tarihi_sort' => 'd.cikis_tarihi',
                'donem_bas' => "to_char(d.dep_bas, 'DD.MM.YYYY')", 'donem_bit' => "to_char(d.dep_bit, 'DD.MM.YYYY')",
                'gun' => 'd.gun', 'gunluk_fiyat' => 'd.gunluk_fiyat', 'tutar' => 'd.tutar',
                'bayi_gunluk_fiyat' => 'd.bayi_gunluk_fiyat', 'bayi_tutar' => 'd.bayi_tutar', 'fiyat_yok' => 'd.fiyat_yok',
            ],
            ['NOT a.arsiv', Auth::bayiKosulu('d.bayi_id')],
            [],
            ['a.sase', 'a.plaka', 'mu.ad', 'b.ad'],
            'd.giris_tarihi DESC'
        );
        foreach (['musteri_id' => 'd.musteri_id', 'bayi_id' => 'd.bayi_id', 'arac_tipi_id' => 'a.arac_tipi_id', 'marka_id' => 'a.marka_id'] as $key => $col) {
            if ($v = Request::int($key)) {
                $dt->where("{$col} = :f_{$key}", ["f_{$key}" => $v]);
            }
        }
        match (Request::str('durum')) {
            'stokta' => $dt->where('d.cikis_tarihi IS NULL'),
            'cikti' => $dt->where('d.cikis_tarihi IS NOT NULL'),
            default => null,
        };

        $enrich = static function (array $r): array {
            $hesap = DepolamaHesap::zenginlestir([[
                'bayi_id' => $r['bayi_id'] ?? null,
                'musteri_id' => $r['musteri_id'] ?? null,
                'arac_tipi_id' => $r['arac_tipi_id'] ?? null,
                'arac_id' => $r['arac_id'] ?? null,
                'dep_bas' => $r['dep_bas'] ?? null,
                'dep_bit' => $r['dep_bit'] ?? null,
                'gunluk_fiyat' => $r['gunluk_fiyat'] ?? 0,
                'bayi_gunluk_fiyat' => $r['bayi_gunluk_fiyat'] ?? ($r['gunluk_fiyat'] ?? 0),
                'gun' => $r['gun'] ?? 0,
                'tutar' => $r['tutar'] ?? 0,
                'bayi_tutar' => $r['bayi_tutar'] ?? ($r['tutar'] ?? 0),
                'fiyat_yok' => $r['fiyat_yok'] ?? false,
            ]])[0];
            $r['gun'] = $hesap['gun'];
            $r['tutar'] = $hesap['tutar'];
            $r['bayi_tutar'] = $hesap['bayi_tutar'] ?? $hesap['tutar'];
            $r['kar'] = round((float) $r['tutar'] - (float) $r['bayi_tutar'], 2);
            $r['gunluk_fiyat'] = $hesap['gunluk_fiyat'];
            $r['bayi_gunluk_fiyat'] = $hesap['bayi_gunluk_fiyat'] ?? $hesap['gunluk_fiyat'];
            $r['fiyat_yok'] = $hesap['fiyat_yok'];
            $r['fiyat_degisti'] = $hesap['fiyat_degisti'] ?? false;
            $r['fiyat_ozet'] = $hesap['fiyat_ozet'] ?? null;
            $r['bayi_fiyat_ozet'] = $hesap['bayi_fiyat_ozet'] ?? null;

            return $r;
        };

        $fiyat = Auth::fiyatGorebilir();
        $admin = Auth::isAdmin();
        $maske = Auth::fiyatMaskeli();
        $rol = static function (array $r) use ($admin, $maske): array {
            if ($maske) {
                $r['gunluk_fiyat'] = $r['bayi_gunluk_fiyat'] ?? $r['gunluk_fiyat'];
                $r['tutar'] = $r['bayi_tutar'] ?? $r['tutar'];
                $r['fiyat_ozet'] = $r['bayi_fiyat_ozet'] ?: ($r['fiyat_ozet'] ?? null);
                unset($r['bayi_tutar'], $r['bayi_gunluk_fiyat'], $r['kar'], $r['bayi_fiyat_ozet']);
            } elseif (!$admin) {
                unset($r['bayi_tutar'], $r['bayi_gunluk_fiyat'], $r['kar'], $r['bayi_fiyat_ozet']);
            }

            return $r;
        };
        if (Request::input('export')) {
            $kolonlar = [
                'sase' => 'Şase', 'plaka' => 'Plaka', 'firma' => 'Firma', 'marka' => 'Marka', 'model' => 'Model', 'seri' => 'Seri', 'arac_tipi' => 'Araç Tipi', 'lokasyon' => 'Lokasyon',
                'giris_tarihi' => 'Giriş Tarihi', 'cikis_tarihi' => 'Çıkış Tarihi', 'donem_bas' => 'Dönem Başı', 'donem_bit' => 'Dönem Sonu',
                'gun' => 'Gün',
            ];
            if ($admin) {
                $kolonlar += ['bayi_tutar' => 'Bayi tutarı', 'bayi_gunluk_fiyat' => 'Bayi günlük', 'gunluk_fiyat' => 'Müşteri günlük', 'tutar' => 'Müşteri tutarı', 'kar' => 'Kâr'];
            } elseif ($fiyat || $maske) {
                $kolonlar += ['gunluk_fiyat' => $maske ? 'Bayi günlük' : 'Müşteri günlük', 'tutar' => $maske ? 'Bayi tutarı' : 'Müşteri tutarı'];
            }
            Excel::download("depolama-raporu-{$bas}-{$bit}", $kolonlar, array_map(static function (array $r) use ($enrich, $rol, $fiyat, $maske) {
                $r = $rol($enrich($r));
                if (!empty($r['fiyat_degisti']) && !empty($r['fiyat_ozet'])) {
                    $r['gunluk_fiyat'] = $r['fiyat_ozet'];
                }
                if (!$fiyat && !$maske) {
                    unset($r['gunluk_fiyat'], $r['tutar'], $r['fiyat_ozet']);
                }

                return $r;
            }, $dt->all()));
        }
        $toplamMusteri = $dt->sum('d.tutar');
        $toplamBayi = $dt->sum('d.bayi_tutar');
        $dt->response(static function (array $r) use ($enrich, $rol, $fiyat, $maske): array {
            $r = $rol($enrich($r));
            if (!$fiyat && !$maske) {
                unset($r['gunluk_fiyat'], $r['tutar'], $r['fiyat_ozet'], $r['fiyat_yok'], $r['fiyat_degisti']);
            }

            return $r;
        }, [
            'toplam' => $maske ? $toplamBayi : (($fiyat || $admin) ? $toplamMusteri : 0),
            'toplam_bayi' => $admin ? $toplamBayi : null,
            'toplam_kar' => $admin ? round($toplamMusteri - $toplamBayi, 2) : null,
            'toplam_gun' => $dt->sum('d.gun'),
        ]);
    }

    public function ekHizmet(): void
    {
        Auth::requireIzin('menu.ek_hizmet_raporu');
        $this->view('raporlar/ek_hizmet', [
            'pageTitle' => 'Ek Hizmet Raporu',
            'breadcrumb' => ['Raporlar' => '/raporlar', 'Ek Hizmet Raporu' => null],
        ]);
    }

    public function ekHizmetListe(): void
    {
        Auth::requireIzin('menu.ek_hizmet_raporu');
        $dt = MaliyetController::ekstreTablosu();
        $admin = Auth::isAdmin();
        $maske = Auth::fiyatMaskeli();
        $map = static function (array $r): array {
            $r = \App\Services\HizmetFiyat::gorunenEkstre($r);
            $r['cikis_sonrasi_ad'] = !empty($r['cikis_sonrasi']) ? 'Evet' : '';

            return $r;
        };
        if (Request::input('export')) {
            $kolon = [
                'islem_tarihi' => 'İşlem Tarihi', 'sase' => 'Şase', 'plaka' => 'Plaka', 'firma' => 'Firma', 'marka' => 'Marka', 'model' => 'Model', 'seri' => 'Seri',
                'lokasyon' => 'Lokasyon', 'maliyet_tipi' => 'Hizmet', 'aciklama' => 'Açıklama', 'fatura_no' => 'Fatura No',
                'cikis_sonrasi_ad' => 'Çıkış Sonrası Eklendi',
            ];
            if ($admin) {
                $kolon['bayi_tutar'] = 'Bayi tutarı';
                $kolon['tutar'] = 'Müşteri tutarı';
                $kolon['kar'] = 'Kâr';
            } else {
                $kolon['tutar'] = $maske ? 'Bayi tutarı' : 'Müşteri tutarı';
            }
            Excel::download('ek-hizmet-raporu', $kolon, array_map($map, $dt->all()));
        }
        $dt->response($map, MaliyetController::ekstreToplamlari($dt, $admin, $maske));
    }

    /** Seçerek hazırla: anlık stok özeti */
    public function stokOzet(): void
    {
        Auth::requireIzin('menu.stok_ozet_raporu');
        $musteriId = Request::int('musteri_id');
        $bayiId = Auth::isAdmin() ? Request::int('bayi_id') : Auth::bayiId();
        $hazir = Request::input('hazirla') || Request::input('export');
        $satirlar = [];
        $ozet = ['adet' => 0, 'firma' => 0, 'lokasyon' => 0];

        if ($hazir) {
            $p = [];
            $kosul = ['a.stokta', 'NOT a.arsiv', Auth::bayiKosulu('a.bayi_id')];
            if ($musteriId) {
                $kosul[] = 'a.musteri_id = :m';
                $p['m'] = $musteriId;
            }
            if ($bayiId) {
                $kosul[] = 'a.bayi_id = :b';
                $p['b'] = $bayiId;
            }
            $satirlar = Database::fetchAll(
                'SELECT a.sase, a.plaka, mu.ad AS firma, m.ad AS marka, mo.ad AS model, s.ad AS seri, b.ad AS lokasyon,
                        a.lokasyon_detay, to_char(a.stoga_giris_tarihi, \'DD.MM.YYYY\') AS giris,
                        CASE WHEN a.stoga_giris_tarihi IS NULL THEN NULL
                             ELSE (CURRENT_DATE - a.stoga_giris_tarihi::date + 1) END AS gun
                 FROM araclar a
                 LEFT JOIN musteriler mu ON mu.id = a.musteri_id
                 LEFT JOIN markalar m ON m.id = a.marka_id
                 LEFT JOIN modeller mo ON mo.id = a.model_id
                 LEFT JOIN seriler s ON s.id = a.seri_id
                 LEFT JOIN bayiler b ON b.id = a.bayi_id
                 WHERE ' . implode(' AND ', $kosul) . '
                 ORDER BY b.ad, mu.ad, a.sase',
                $p
            );
            $ozet['adet'] = count($satirlar);
            $ozet['firma'] = count(array_unique(array_filter(array_column($satirlar, 'firma'))));
            $ozet['lokasyon'] = count(array_unique(array_filter(array_column($satirlar, 'lokasyon'))));

            if (Request::input('export')) {
                Excel::download('stok-ozet-' . date('Y-m-d'), [
                    'sase' => 'Şase', 'plaka' => 'Plaka', 'firma' => 'Firma', 'marka' => 'Marka', 'model' => 'Model', 'seri' => 'Seri',
                    'lokasyon' => 'Lokasyon', 'lokasyon_detay' => 'Lokasyon Detay', 'giris' => 'Giriş', 'gun' => 'Stok Günü',
                ], $satirlar);
            }
        }

        $this->view('raporlar/hazirla', [
            'pageTitle' => 'Stok Özet Raporu',
            'breadcrumb' => ['Raporlar' => '/raporlar', 'Stok Özet' => null],
            'rapor' => RaporKatalog::bul('stok_ozet'),
            'formAction' => '/raporlar/stok_ozet',
            'hazir' => (bool) $hazir,
            'satirlar' => $satirlar,
            'ozet' => $ozet,
            'filtreler' => [
                'musteri_id' => $musteriId,
                'bayi_id' => $bayiId,
            ],
            'alanlar' => ['musteri', 'lokasyon'],
            'kolonlar' => [
                'sase' => 'Şase', 'plaka' => 'Plaka', 'firma' => 'Firma', 'marka' => 'Marka', 'model' => 'Model', 'seri' => 'Seri',
                'lokasyon' => 'Lokasyon', 'lokasyon_detay' => 'Detay', 'giris' => 'Giriş', 'gun' => 'Gün',
            ],
        ]);
    }

    /** Seçerek hazırla: dönem giriş–çıkış özeti */
    public function girisCikis(): void
    {
        Auth::requireIzin('menu.giris_cikis_raporu');
        [$bas, $bit] = $this->aralik();
        $musteriId = Request::int('musteri_id');
        $bayiId = Auth::isAdmin() ? Request::int('bayi_id') : Auth::bayiId();
        $hazir = Request::input('hazirla') || Request::input('export');
        $satirlar = [];
        $ozet = ['giren' => 0, 'cikan' => 0];

        if ($hazir) {
            $p = ['bas' => $bas . ' 00:00:00', 'bit' => $bit . ' 23:59:59'];
            $kosul = ['NOT h.arsiv', 'h.hareket_tarihi BETWEEN :bas AND :bit', Auth::bayiKosulu('h.bayi_id')];
            if ($musteriId) {
                $kosul[] = 'h.musteri_id = :m';
                $p['m'] = $musteriId;
            }
            if ($bayiId) {
                $kosul[] = 'h.bayi_id = :b';
                $p['b'] = $bayiId;
            }
            $satirlar = Database::fetchAll(
                'SELECT COALESCE(b.ad, \'—\') AS lokasyon, COALESCE(mu.ad, \'—\') AS firma,
                        COUNT(*) FILTER (WHERE h.hareket_tipi = 1)::int AS giren,
                        COUNT(*) FILTER (WHERE h.hareket_tipi = 2)::int AS cikan
                 FROM arac_hareketleri h
                 LEFT JOIN bayiler b ON b.id = h.bayi_id
                 LEFT JOIN musteriler mu ON mu.id = h.musteri_id
                 WHERE ' . implode(' AND ', $kosul) . '
                 GROUP BY b.ad, mu.ad
                 ORDER BY b.ad, mu.ad',
                $p
            );
            $ozet['giren'] = (int) array_sum(array_column($satirlar, 'giren'));
            $ozet['cikan'] = (int) array_sum(array_column($satirlar, 'cikan'));

            if (Request::input('export')) {
                Excel::download("giris-cikis-{$bas}-{$bit}", [
                    'lokasyon' => 'Lokasyon', 'firma' => 'Firma', 'giren' => 'Giren', 'cikan' => 'Çıkan',
                ], $satirlar);
            }
        }

        $this->view('raporlar/hazirla', [
            'pageTitle' => 'Giriş–Çıkış Özeti',
            'breadcrumb' => ['Raporlar' => '/raporlar', 'Giriş–Çıkış Özeti' => null],
            'rapor' => RaporKatalog::bul('giris_cikis'),
            'formAction' => '/raporlar/giris_cikis',
            'hazir' => (bool) $hazir,
            'satirlar' => $satirlar,
            'ozet' => $ozet,
            'filtreler' => [
                'baslangic' => $bas,
                'bitis' => $bit,
                'musteri_id' => $musteriId,
                'bayi_id' => $bayiId,
            ],
            'alanlar' => ['tarih', 'musteri', 'lokasyon'],
            'kolonlar' => [
                'lokasyon' => 'Lokasyon', 'firma' => 'Firma', 'giren' => 'Giren', 'cikan' => 'Çıkan',
            ],
        ]);
    }
}
