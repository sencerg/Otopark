<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Depolama süresi = seçilen aralıkla kesişen gün sayısı (giriş günü dahil, BİA ile aynı).
 * Günlük fiyat: müşteri + lokasyon + araç tipi eşleşmesi, yoksa müşteri + lokasyon (araç tipi boş).
 */
final class DepolamaHesap
{
    public static function sorgu(string $baslangic, string $bitis): string
    {
        $bas = Database::connection()->quote($baslangic);
        $bit = Database::connection()->quote($bitis);

        return "(SELECT k.giris_hareket_id, k.arac_id, k.musteri_id, k.bayi_id, k.giris_tarihi, k.cikis_tarihi,
                    GREATEST(k.giris_tarihi::date, {$bas}::date) AS dep_bas,
                    LEAST(COALESCE(k.cikis_tarihi::date, CURRENT_DATE), {$bit}::date) AS dep_bit,
                    LEAST(COALESCE(k.cikis_tarihi::date, CURRENT_DATE), {$bit}::date) - GREATEST(k.giris_tarihi::date, {$bas}::date) + 1 AS gun,
                    COALESCE(f1.gunluk_fiyat, f2.gunluk_fiyat, 0) AS gunluk_fiyat
                 FROM arac_konaklamalari k
                 JOIN araclar a0 ON a0.id = k.arac_id
                 LEFT JOIN depolama_fiyatlari f1 ON f1.musteri_id = k.musteri_id AND f1.bayi_id = k.bayi_id AND f1.arac_tipi_id = a0.arac_tipi_id
                 LEFT JOIN depolama_fiyatlari f2 ON f2.musteri_id = k.musteri_id AND f2.bayi_id = k.bayi_id AND f2.arac_tipi_id IS NULL
                 WHERE k.giris_tarihi::date <= {$bit}::date
                   AND COALESCE(k.cikis_tarihi::date, CURRENT_DATE) >= {$bas}::date) d";
    }

    public static function arac(int $aracId): array
    {
        return Database::fetchAll(
            'SELECT d.*, d.gun * d.gunluk_fiyat AS tutar, b.ad AS bayi FROM ' . self::sorgu('2000-01-01', date('Y-m-d')) . '
             LEFT JOIN bayiler b ON b.id = d.bayi_id
             WHERE d.arac_id = :a ORDER BY d.giris_tarihi DESC',
            ['a' => $aracId]
        );
    }
}
