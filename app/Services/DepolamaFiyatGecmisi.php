<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use PDOException;

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
        if ($acik && self::ayniFiyat((float) $acik['gunluk_fiyat'], $gunlukFiyat)) {
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

        // Kalan açık satırları (veri bozulması / yarış) kapat; aksi halde open_uidx patlar.
        self::aciklariKapat($bayiId, $musteriId, $aracTipiId, $from);

        try {
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
        } catch (PDOException $e) {
            if (!self::isDuplicate($e)) {
                throw $e;
            }
            // open_uidx: aynı gün / kaçırılmış açık satır — fiyatı güncelle
            $n = Database::query(
                'UPDATE depolama_fiyat_gecmisi SET gunluk_fiyat = :f
                 WHERE bayi_id = :b
                   AND musteri_id IS NOT DISTINCT FROM :m
                   AND arac_tipi_id IS NOT DISTINCT FROM :t
                   AND effective_to IS NULL',
                ['f' => $gunlukFiyat, 'b' => $bayiId, 'm' => $musteriId, 't' => $aracTipiId]
            )->rowCount();
            if ($n === 0) {
                throw $e;
            }
        }
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
            self::aciklariSil($bayiId, $musteriId, $aracTipiId);

            return;
        }
        self::aciklariKapat($bayiId, $musteriId, $aracTipiId, $to);
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
             ORDER BY effective_from DESC, id DESC
             LIMIT 1',
            ['b' => $bayiId, 'm' => $musteriId, 't' => $aracTipiId]
        ) ?: null;
    }

    /** effective_from < $to olan tüm açık segmentleri kapatır. */
    private static function aciklariKapat(int $bayiId, ?int $musteriId, ?int $aracTipiId, string $to): void
    {
        Database::query(
            'UPDATE depolama_fiyat_gecmisi SET effective_to = :to
             WHERE bayi_id = :b
               AND musteri_id IS NOT DISTINCT FROM :m
               AND arac_tipi_id IS NOT DISTINCT FROM :t
               AND effective_to IS NULL
               AND effective_from < :to::date',
            ['to' => $to, 'b' => $bayiId, 'm' => $musteriId, 't' => $aracTipiId]
        );
        // Aynı gün başlayan artık geçersiz açık satırlar (yeni insert öncesi)
        Database::query(
            'DELETE FROM depolama_fiyat_gecmisi
             WHERE bayi_id = :b
               AND musteri_id IS NOT DISTINCT FROM :m
               AND arac_tipi_id IS NOT DISTINCT FROM :t
               AND effective_to IS NULL
               AND effective_from >= :to::date',
            ['to' => $to, 'b' => $bayiId, 'm' => $musteriId, 't' => $aracTipiId]
        );
    }

    private static function aciklariSil(int $bayiId, ?int $musteriId, ?int $aracTipiId): void
    {
        Database::query(
            'DELETE FROM depolama_fiyat_gecmisi
             WHERE bayi_id = :b
               AND musteri_id IS NOT DISTINCT FROM :m
               AND arac_tipi_id IS NOT DISTINCT FROM :t
               AND effective_to IS NULL',
            ['b' => $bayiId, 'm' => $musteriId, 't' => $aracTipiId]
        );
    }

    private static function ayniFiyat(float $a, float $b): bool
    {
        return abs(round($a, 2) - round($b, 2)) < 0.001;
    }

    private static function isDuplicate(PDOException $e): bool
    {
        return $e->getCode() === '23505' || str_contains($e->getMessage(), 'duplicate key');
    }
}
