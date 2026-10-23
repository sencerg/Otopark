<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Yalnızca müşteri (firma) bazlı hizmet ücreti.
 * Genel / varsayılan fiyat yoktur; kayıt yoksa tutar 0 ve tanimli() false.
 *
 * maliyet_tipleri.bayi_fiyat_degistirebilir = true ise bayi müşteri fiyatını
 * uygulama anında değiştirebilir; aksi halde katalog tutarı kilitlidir.
 */
final class HizmetFiyat
{
    public static function tanimli(?int $musteriId, int $maliyetTipiId): bool
    {
        if (!$musteriId) {
            return false;
        }

        return Database::fetch(
            'SELECT 1 FROM hizmet_fiyatlari WHERE musteri_id = :m AND maliyet_tipi_id = :t',
            ['m' => $musteriId, 't' => $maliyetTipiId]
        ) !== null;
    }

    public static function tutar(?int $musteriId, int $maliyetTipiId): float
    {
        if (!$musteriId) {
            return 0.0;
        }
        $ozel = Database::fetch(
            'SELECT tutar FROM hizmet_fiyatlari WHERE musteri_id = :m AND maliyet_tipi_id = :t',
            ['m' => $musteriId, 't' => $maliyetTipiId]
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
     * Müşteri fiyatı zorunlu (admin elle tutar girebilir).
     * Bayi: kilitli hizmette yalnızca katalog; değiştirilebilirde gelen tutar geçerli.
     */
    public static function tutarBayiIcin(?int $musteriId, int $maliyetTipiId, mixed $gelenTutar, bool $admin = false): float
    {
        if (self::tanimli($musteriId, $maliyetTipiId)) {
            $katalog = self::tutar($musteriId, $maliyetTipiId);
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

        // Bayi, fiyat tanımsızken ve değiştirilebilir hizmette elle tutar girebilir
        if (!$admin && self::bayiDegistirebilir($maliyetTipiId)
            && $gelenTutar !== null && $gelenTutar !== '' && (float) $gelenTutar > 0) {
            return round((float) $gelenTutar, 2);
        }

        return 0.0;
    }

    public static function aracIcin(int $aracId, int $maliyetTipiId): float
    {
        $arac = Database::fetch('SELECT musteri_id FROM araclar WHERE id = :id', ['id' => $aracId]);

        return self::tutar($arac ? (int) ($arac['musteri_id'] ?? 0) ?: null : null, $maliyetTipiId);
    }

    /** @return list<array{id:int,musteri_id:int,maliyet_tipi_id:int,tutar:float,musteri:?string,hizmet:?string}> */
    public static function liste(?int $musteriId = null, ?int $maliyetTipiId = null): array
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

        return Database::fetchAll(
            'SELECT f.id, f.musteri_id, f.maliyet_tipi_id, f.tutar, mu.ad AS musteri, mt.ad AS hizmet
             FROM hizmet_fiyatlari f
             JOIN musteriler mu ON mu.id = f.musteri_id
             JOIN maliyet_tipleri mt ON mt.id = f.maliyet_tipi_id
             WHERE ' . implode(' AND ', $kosul) . '
             ORDER BY mu.ad, mt.ad',
            $p
        );
    }
}
