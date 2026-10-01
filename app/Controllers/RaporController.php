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
                'giris_tarihi' => "to_char(d.giris_tarihi, 'DD.MM.YYYY')", 'giris_tarihi_sort' => 'd.giris_tarihi',
                'cikis_tarihi' => "to_char(d.cikis_tarihi, 'DD.MM.YYYY')", 'cikis_tarihi_sort' => 'd.cikis_tarihi',
                'donem_bas' => "to_char(d.dep_bas, 'DD.MM.YYYY')", 'donem_bit' => "to_char(d.dep_bit, 'DD.MM.YYYY')",
                'gun' => 'd.gun', 'gunluk_fiyat' => 'd.gunluk_fiyat', 'tutar' => '(d.gun * d.gunluk_fiyat)', 'fiyat_yok' => 'd.fiyat_yok',
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

        if (Request::input('export')) {
            Excel::download("depolama-raporu-{$bas}-{$bit}", [
                'sase' => 'Şase', 'plaka' => 'Plaka', 'firma' => 'Firma', 'marka' => 'Marka', 'seri' => 'Seri', 'arac_tipi' => 'Araç Tipi', 'lokasyon' => 'Lokasyon',
                'giris_tarihi' => 'Giriş Tarihi', 'cikis_tarihi' => 'Çıkış Tarihi', 'donem_bas' => 'Dönem Başı', 'donem_bit' => 'Dönem Sonu',
                'gun' => 'Gün', 'gunluk_fiyat' => 'Günlük Fiyat', 'tutar' => 'Tutar',
            ], $dt->all());
        }
        $dt->response(null, ['toplam' => $dt->sum('d.gun * d.gunluk_fiyat'), 'toplam_gun' => $dt->sum('d.gun')]);
    }

    public function ekHizmet(): void
    {
        $this->view('raporlar/ek_hizmet', ['pageTitle' => 'Ek Hizmet Raporu', 'breadcrumb' => ['Raporlar' => null, 'Ek Hizmet Raporu' => null]]);
    }

    public function ekHizmetListe(): void
    {
        $dt = MaliyetController::ekstreTablosu();
        if (Request::input('export')) {
            Excel::download('ek-hizmet-raporu', [
                'islem_tarihi' => 'İşlem Tarihi', 'sase' => 'Şase', 'plaka' => 'Plaka', 'firma' => 'Firma', 'marka' => 'Marka', 'seri' => 'Seri',
                'lokasyon' => 'Lokasyon', 'maliyet_tipi' => 'Hizmet', 'aciklama' => 'Açıklama', 'fatura_no' => 'Fatura No', 'tutar' => 'Tutar',
            ], $dt->all());
        }
        $dt->response(null, ['toplam' => $dt->sum('e.tutar')]);
    }

    /** Hizmet bazında özet (rapor üstündeki kartlar) */
    public function ekHizmetOzet(): void
    {
        $bas = Request::date('baslangic') ?? date('Y-m-01');
        $bit = Request::date('bitis') ?? date('Y-m-d');
        $where = ['e.islem_tarihi BETWEEN :b AND :t', Auth::bayiKosulu('e.bayi_id')];
        $params = ['b' => $bas, 't' => $bit];
        foreach (['musteri_id', 'bayi_id', 'maliyet_tipi_id'] as $key) {
            if ($v = Request::int($key)) {
                $where[] = "e.{$key} = :{$key}";
                $params[$key] = $v;
            }
        }
        \App\Core\View::json(Database::fetchAll(
            'SELECT mt.ad, COUNT(*) AS adet, SUM(e.tutar) AS tutar FROM arac_ekstreleri e JOIN maliyet_tipleri mt ON mt.id = e.maliyet_tipi_id
             WHERE ' . implode(' AND ', $where) . ' GROUP BY mt.ad ORDER BY tutar DESC',
            $params
        ));
    }
}
