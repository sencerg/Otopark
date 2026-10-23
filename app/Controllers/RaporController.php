<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\DataTable;
use App\Core\Excel;
use App\Core\Request;
use App\Services\DepolamaHesap;

final class RaporController extends Controller
{
    public function depolama(): void
    {
        $this->view('raporlar/depolama', ['pageTitle' => 'Depolama Raporu', 'breadcrumb' => ['Raporlar' => null, 'Depolama Raporu' => null]]);
    }

    private function aralik(): array
    {
        $bas = Request::date('baslangic') ?? date('Y-m-01');
        $bit = Request::date('bitis') ?? date('Y-m-d');

        return $bas <= $bit ? [$bas, $bit] : [$bit, $bas];
    }

    public function depolamaListe(): void
    {
        [$bas, $bit] = $this->aralik();
        $dt = new DataTable(
            DepolamaHesap::sorgu($bas, $bit) . '
             JOIN araclar a ON a.id = d.arac_id
             LEFT JOIN musteriler mu ON mu.id = d.musteri_id
             LEFT JOIN bayiler b ON b.id = d.bayi_id
             LEFT JOIN markalar m ON m.id = a.marka_id
             LEFT JOIN seriler s ON s.id = a.seri_id
             LEFT JOIN arac_tipleri at ON at.id = a.arac_tipi_id',
            [
                'id' => 'd.giris_hareket_id', 'arac_id' => 'a.id', 'sase' => 'a.sase', 'plaka' => 'a.plaka', 'firma' => 'mu.ad', 'marka' => 'm.ad', 'seri' => 's.ad',
                'arac_tipi' => 'at.ad', 'lokasyon' => 'b.ad',
                'bayi_id' => 'd.bayi_id', 'musteri_id' => 'd.musteri_id', 'arac_tipi_id' => 'a.arac_tipi_id',
                'dep_bas' => 'd.dep_bas', 'dep_bit' => 'd.dep_bit',
                'giris_tarihi' => "to_char(d.giris_tarihi, 'DD.MM.YYYY')", 'giris_tarihi_sort' => 'd.giris_tarihi',
                'cikis_tarihi' => "to_char(d.cikis_tarihi, 'DD.MM.YYYY')", 'cikis_tarihi_sort' => 'd.cikis_tarihi',
                'donem_bas' => "to_char(d.dep_bas, 'DD.MM.YYYY')", 'donem_bit' => "to_char(d.dep_bit, 'DD.MM.YYYY')",
                'gun' => 'd.gun', 'gunluk_fiyat' => 'd.gunluk_fiyat', 'tutar' => 'd.tutar', 'fiyat_yok' => 'd.fiyat_yok',
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
                'gun' => $r['gun'] ?? 0,
                'tutar' => $r['tutar'] ?? 0,
                'fiyat_yok' => $r['fiyat_yok'] ?? false,
            ]])[0];
            $r['gun'] = $hesap['gun'];
            $r['tutar'] = $hesap['tutar'];
            $r['gunluk_fiyat'] = $hesap['gunluk_fiyat'];
            $r['fiyat_yok'] = $hesap['fiyat_yok'];
            $r['fiyat_degisti'] = $hesap['fiyat_degisti'] ?? false;
            $r['fiyat_ozet'] = $hesap['fiyat_ozet'] ?? null;

            return $r;
        };

        $fiyat = Auth::fiyatGorebilir();
        if (Request::input('export')) {
            $kolonlar = [
                'sase' => 'Şase', 'plaka' => 'Plaka', 'firma' => 'Firma', 'marka' => 'Marka', 'seri' => 'Seri', 'arac_tipi' => 'Araç Tipi', 'lokasyon' => 'Lokasyon',
                'giris_tarihi' => 'Giriş Tarihi', 'cikis_tarihi' => 'Çıkış Tarihi', 'donem_bas' => 'Dönem Başı', 'donem_bit' => 'Dönem Sonu',
                'gun' => 'Gün',
            ];
            if ($fiyat) {
                $kolonlar += ['gunluk_fiyat' => 'Günlük Fiyat', 'tutar' => 'Tutar'];
            }
            Excel::download("depolama-raporu-{$bas}-{$bit}", $kolonlar, array_map(static function (array $r) use ($enrich, $fiyat) {
                $r = $enrich($r);
                if (!empty($r['fiyat_degisti']) && !empty($r['fiyat_ozet'])) {
                    $r['gunluk_fiyat'] = $r['fiyat_ozet'];
                }
                if (!$fiyat) {
                    unset($r['gunluk_fiyat'], $r['tutar'], $r['fiyat_ozet']);
                }

                return $r;
            }, $dt->all()));
        }
        $dt->response(static function (array $r) use ($enrich, $fiyat): array {
            $r = $enrich($r);
            if (!$fiyat) {
                unset($r['gunluk_fiyat'], $r['tutar'], $r['fiyat_ozet'], $r['fiyat_yok'], $r['fiyat_degisti']);
            }

            return $r;
        }, ['toplam' => $fiyat ? $dt->sum('d.tutar') : 0, 'toplam_gun' => $dt->sum('d.gun')]);
    }

    public function ekHizmet(): void
    {
        $this->view('raporlar/ek_hizmet', ['pageTitle' => 'Ek Hizmet Raporu', 'breadcrumb' => ['Raporlar' => null, 'Ek Hizmet Raporu' => null]]);
    }

    public function ekHizmetListe(): void
    {
        $dt = MaliyetController::ekstreTablosu();
        $fiyat = Auth::fiyatGorebilir();
        if (Request::input('export')) {
            $kolonlar = [
                'islem_tarihi' => 'İşlem Tarihi', 'sase' => 'Şase', 'plaka' => 'Plaka', 'firma' => 'Firma', 'marka' => 'Marka', 'seri' => 'Seri',
                'lokasyon' => 'Lokasyon', 'maliyet_tipi' => 'Hizmet', 'aciklama' => 'Açıklama', 'fatura_no' => 'Fatura No',
                'cikis_sonrasi_ad' => 'Çıkış Sonrası Eklendi',
            ];
            if ($fiyat) {
                $kolonlar = array_merge(
                    array_slice($kolonlar, 0, 9, true),
                    ['tutar' => 'Tutar'],
                    array_slice($kolonlar, 9, null, true)
                );
            }
            Excel::download('ek-hizmet-raporu', $kolonlar, array_map(static function ($r) use ($fiyat) {
                $r['cikis_sonrasi_ad'] = $r['cikis_sonrasi'] ? 'Evet' : '';
                if (!$fiyat) {
                    unset($r['tutar']);
                }

                return $r;
            }, $dt->all()));
        }
        $dt->response(
            $fiyat ? null : static function (array $r): array {
                unset($r['tutar']);

                return $r;
            },
            ['toplam' => $fiyat ? $dt->sum('e.tutar') : 0, 'ozet' => $fiyat ? $dt->groupSum('mt.ad', 'e.tutar') : []]
        );
    }
}
