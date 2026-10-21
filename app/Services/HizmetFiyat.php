<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Firma bazlı hizmet ücreti çözümleyici.
 * Öncelik: hizmet_fiyatlari (müşteri + hizmet) → maliyet_tipleri.varsayilan_tutar
 * Otopark günlük fiyat / çarpan yalnızca depolama içindir; buraya karışmaz.
 */
final class HizmetFiyat
{
    public static function tutar(?int $musteriId, int $maliyetTipiId): float
    {
        if ($musteriId) {
            $ozel = Database::fetch(
                'SELECT tutar FROM hizmet_fiyatlari WHERE musteri_id = :m AND maliyet_tipi_id = :t',
                ['m' => $musteriId, 't' => $maliyetTipiId]
            );
            if ($ozel) {
                return round((float) $ozel['tutar'], 2);
            }
        }

        $tip = Database::fetch('SELECT varsayilan_tutar FROM maliyet_tipleri WHERE id = :id', ['id' => $maliyetTipiId]);

        return $tip ? round((float) $tip['varsayilan_tutar'], 2) : 0.0;
    }

    /** Bayi bu hizmetin tutarını formda/ekstrede değiştirebilir mi? Admin her zaman değiştirebilir. */
    public static function bayiDegistirebilir(int $maliyetTipiId): bool
    {
        $tip = Database::fetch(
            'SELECT bayi_fiyat_degistirebilir FROM maliyet_tipleri WHERE id = :id',
            ['id' => $maliyetTipiId]
        );

        return $tip ? (bool) $tip['bayi_fiyat_degistirebilir'] : false;
    }

    /**
     * Bayi + kilitli hizmet: katalog/firma fiyatını zorlar.
     * Admin veya değiştirilebilir hizmet: gelen tutarı kullanır (boşsa katalog).
     */
    public static function tutarBayiIcin(?int $musteriId, int $maliyetTipiId, mixed $gelenTutar, bool $admin = false): float
    {
        $katalog = self::tutar($musteriId, $maliyetTipiId);
        if (!$admin && !self::bayiDegistirebilir($maliyetTipiId)) {
            return $katalog;
        }
        if ($gelenTutar === null || $gelenTutar === '' || (is_numeric($gelenTutar) && (float) $gelenTutar <= 0)) {
            return $katalog;
        }

        return round((float) $gelenTutar, 2);
    }

    /** Araç bağlamında (müşteri aracın firmasıdır). */
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
