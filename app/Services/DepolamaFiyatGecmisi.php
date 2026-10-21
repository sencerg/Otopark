<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

/**
 * Depolama günlük fiyatının zaman çizelgesi.
 * Değişim günü yeni fiyata dahildir (effective_from dahil, effective_to hariç).
 */
final class DepolamaFiyatGecmisi
{
    /**
     * Açık segmenti kapatıp yeni fiyatı başlatır. Aynı fiyat ise işlem yapmaz.
     * @param ?int $musteriId null = otopark standart tarife
     * @param ?int $aracTipiId null = tüm tipler
     */
    public static function kaydet(int $bayiId, ?int $musteriId, ?int $aracTipiId, float $gunlukFiyat, ?string $from = null): void
    {
        $gunlukFiyat = round($gunlukFiyat, 2);
        if ($gunlukFiyat < 0) {
            return;
        }
        $from = $from ?? date('Y-m-d');
        $acik = self::acikSegment($bayiId, $musteriId, $aracTipiId);
        if ($acik && (float) $acik['gunluk_fiyat'] === $gunlukFiyat) {
            return;
        }
        if ($acik) {
            $eskiFrom = substr((string) $acik['effective_from'], 0, 10);
            if ($from <= $eskiFrom) {
                // Aynı gün içinde yeniden değişiklik: açık satırı güncelle
                Database::query(
                    'UPDATE depolama_fiyat_gecmisi SET gunluk_fiyat = :f WHERE id = :id',
                    ['f' => $gunlukFiyat, 'id' => (int) $acik['id']]
                );

                return;
            }
            Database::query(
                'UPDATE depolama_fiyat_gecmisi SET effective_to = :t WHERE id = :id AND effective_to IS NULL',
                ['t' => $from, 'id' => (int) $acik['id']]
            );
        }
        Database::query(
            'INSERT INTO depolama_fiyat_gecmisi (bayi_id, musteri_id, arac_tipi_id, gunluk_fiyat, effective_from, effective_to, created_by)
             VALUES (:b, :m, :t, :f, :from, NULL, :u)',
            [
                'b' => $bayiId,
                'm' => $musteriId,
                't' => $aracTipiId,
                'f' => $gunlukFiyat,
                'from' => $from,
                'u' => Auth::id(),
            ]
        );
    }

    /** Firma / tip özel tarifeyi kapatır (bundan sonra standart tarife geçerli olur). */
    public static function kapat(int $bayiId, ?int $musteriId, ?int $aracTipiId, ?string $to = null): void
    {
        $acik = self::acikSegment($bayiId, $musteriId, $aracTipiId);
        if (!$acik) {
            return;
        }
        $to = $to ?? date('Y-m-d');
        $eskiFrom = substr((string) $acik['effective_from'], 0, 10);
        if ($to <= $eskiFrom) {
            Database::query('DELETE FROM depolama_fiyat_gecmisi WHERE id = :id', ['id' => (int) $acik['id']]);

            return;
        }
        Database::query(
            'UPDATE depolama_fiyat_gecmisi SET effective_to = :t WHERE id = :id AND effective_to IS NULL',
            ['t' => $to, 'id' => (int) $acik['id']]
        );
    }

    /** @return ?array{id:int|string,gunluk_fiyat:mixed,effective_from:mixed} */
    private static function acikSegment(int $bayiId, ?int $musteriId, ?int $aracTipiId): ?array
    {
        return Database::fetch(
            'SELECT id, gunluk_fiyat, effective_from FROM depolama_fiyat_gecmisi
             WHERE bayi_id = :b
               AND musteri_id IS NOT DISTINCT FROM :m
               AND arac_tipi_id IS NOT DISTINCT FROM :t
               AND effective_to IS NULL
             LIMIT 1',
            ['b' => $bayiId, 'm' => $musteriId, 't' => $aracTipiId]
        ) ?: null;
    }
}
