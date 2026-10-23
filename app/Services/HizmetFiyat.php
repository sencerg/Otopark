<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Hizmet ücreti: müşteri × otopark (bayi) × hizmet.
 * Genel / varsayılan fiyat yoktur.
 *
 * maliyet_tipleri.bayi_fiyat_degistirebilir = true ise bayi müşteri+otopark
 * fiyatını uygulama anında değiştirebilir; aksi halde katalog tutarı kilitlidir.
 */
final class HizmetFiyat
{
    public static function tanimli(?int $musteriId, int $maliyetTipiId, ?int $bayiId = null): bool
    {
        if (!$musteriId || !$bayiId) {
            return false;
        }

        return Database::fetch(
            'SELECT 1 FROM hizmet_fiyatlari
             WHERE musteri_id = :m AND maliyet_tipi_id = :t AND bayi_id = :b',
            ['m' => $musteriId, 't' => $maliyetTipiId, 'b' => $bayiId]
        ) !== null;
    }

    public static function tutar(?int $musteriId, int $maliyetTipiId, ?int $bayiId = null): float
    {
        if (!$musteriId || !$bayiId) {
            return 0.0;
        }
        $ozel = Database::fetch(
            'SELECT tutar FROM hizmet_fiyatlari
             WHERE musteri_id = :m AND maliyet_tipi_id = :t AND bayi_id = :b',
            ['m' => $musteriId, 't' => $maliyetTipiId, 'b' => $bayiId]
        );

        return $ozel ? round((float) $ozel['tutar'], 2) : 0.0;
    }

    public static function bayiDegistirebilir(int $maliyetTipiId): bool
    {
        $row = Database::fetch(
            'SELECT bayi_fiyat_degistirebilir FROM maliyet_tipleri WHERE id = :id',
            ['id' => $maliyetTipiId]
        );

        return $row ? (bool) $row['bayi_fiyat_degistirebilir'] : false;
    }

    /**
     * Müşteri+otopark fiyatı zorunlu (admin elle tutar girebilir).
     * Bayi: kilitli hizmette yalnızca katalog; değiştirilebilirde gelen tutar geçerli.
     */
    public static function tutarBayiIcin(
        ?int $musteriId,
        int $maliyetTipiId,
        mixed $gelenTutar,
        bool $admin = false,
        ?int $bayiId = null
    ): float {
        if (self::tanimli($musteriId, $maliyetTipiId, $bayiId)) {
            $katalog = self::tutar($musteriId, $maliyetTipiId, $bayiId);
            if (!$admin && !self::bayiDegistirebilir($maliyetTipiId)) {
                return $katalog;
            }
            if ($gelenTutar === null || $gelenTutar === '' || (is_numeric($gelenTutar) && (float) $gelenTutar < 0)) {
                return $katalog;
            }

            return round((float) $gelenTutar, 2);
        }

        if ($admin && $gelenTutar !== null && $gelenTutar !== '' && (float) $gelenTutar > 0) {
            return round((float) $gelenTutar, 2);
        }

        if (!$admin && self::bayiDegistirebilir($maliyetTipiId)
            && $gelenTutar !== null && $gelenTutar !== '' && (float) $gelenTutar > 0) {
            return round((float) $gelenTutar, 2);
        }

        return 0.0;
    }

    public static function aracIcin(int $aracId, int $maliyetTipiId): float
    {
        $arac = Database::fetch('SELECT musteri_id, bayi_id FROM araclar WHERE id = :id', ['id' => $aracId]);
        if (!$arac) {
            return 0.0;
        }

        return self::tutar(
            (int) ($arac['musteri_id'] ?? 0) ?: null,
            $maliyetTipiId,
            (int) ($arac['bayi_id'] ?? 0) ?: null
        );
    }

    /**
     * @return list<array{id:int,musteri_id:int,bayi_id:int,maliyet_tipi_id:int,tutar:float,musteri:?string,hizmet:?string,otopark:?string}>
     */
    public static function liste(?int $musteriId = null, ?int $maliyetTipiId = null, ?int $bayiId = null): array
    {
        $kosul = ['TRUE'];
        $p = [];
        if ($musteriId) {
            $kosul[] = 'f.musteri_id = :m';
            $p['m'] = $musteriId;
        }
        if ($maliyetTipiId) {
            $kosul[] = 'f.maliyet_tipi_id = :t';
            $p['t'] = $maliyetTipiId;
        }
        if ($bayiId) {
            $kosul[] = 'f.bayi_id = :b';
            $p['b'] = $bayiId;
        }

        return Database::fetchAll(
            'SELECT f.id, f.musteri_id, f.bayi_id, f.maliyet_tipi_id, f.tutar,
                    mu.ad AS musteri, mt.ad AS hizmet, b.ad AS otopark
             FROM hizmet_fiyatlari f
             JOIN musteriler mu ON mu.id = f.musteri_id
             JOIN maliyet_tipleri mt ON mt.id = f.maliyet_tipi_id
             JOIN bayiler b ON b.id = f.bayi_id
             WHERE ' . implode(' AND ', $kosul) . '
             ORDER BY b.ad, mt.ad',
            $p
        );
    }
}
