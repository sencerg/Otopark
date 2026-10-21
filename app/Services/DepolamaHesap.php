<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Depolama süresi = seçilen aralıkla kesişen gün sayısı (giriş günü dahil, Referans ile aynı).
 *
 * Günlük ücret önceliği (her gün için fiyat geçmişinden):
 * 1) Müşteri + otopark + araç tipi özel fiyat
 * 2) Müşteri + otopark özel fiyat (araç tipi boş)
 * 3) Otopark standart günlük × fiyat çarpanı
 *
 * Canlı tablolar geçmiş yoksa yedek olarak kullanılır.
 * Firma özel fiyatı çarpanla tekrar çarpılmaz; girilen tutar günlük ücrettir.
 */
final class DepolamaHesap
{
    public static function sorgu(string $baslangic, string $bitis): string
    {
        $bas = Database::connection()->quote($baslangic);
        $bit = Database::connection()->quote($bitis);

        // tutar: her gün için fiyat geçmişinden (yoksa canlı tarifeden) toplanır
        $gunlukIfade = 'COALESCE(
            (SELECT g.gunluk_fiyat FROM depolama_fiyat_gecmisi g
             WHERE g.bayi_id = k.bayi_id AND g.musteri_id IS NOT DISTINCT FROM k.musteri_id
               AND g.arac_tipi_id IS NOT DISTINCT FROM a0.arac_tipi_id
               AND g.effective_from <= gs::date AND (g.effective_to IS NULL OR gs::date < g.effective_to)
             ORDER BY g.effective_from DESC LIMIT 1),
            (SELECT g.gunluk_fiyat FROM depolama_fiyat_gecmisi g
             WHERE g.bayi_id = k.bayi_id AND g.musteri_id IS NOT DISTINCT FROM k.musteri_id
               AND g.arac_tipi_id IS NULL
               AND g.effective_from <= gs::date AND (g.effective_to IS NULL OR gs::date < g.effective_to)
             ORDER BY g.effective_from DESC LIMIT 1),
            (SELECT g.gunluk_fiyat FROM depolama_fiyat_gecmisi g
             WHERE g.bayi_id = k.bayi_id AND g.musteri_id IS NULL AND g.arac_tipi_id IS NULL
               AND g.effective_from <= gs::date AND (g.effective_to IS NULL OR gs::date < g.effective_to)
             ORDER BY g.effective_from DESC LIMIT 1),
            f1.gunluk_fiyat,
            f2.gunluk_fiyat,
            NULLIF(bx.gunluk_fiyat, 0) * COALESCE(bx.fiyat_carpani, 1),
            0
        )';

        return "(SELECT k.giris_hareket_id, k.arac_id, k.musteri_id, k.bayi_id, k.giris_tarihi, k.cikis_tarihi,
                    GREATEST(k.giris_tarihi::date, {$bas}::date) AS dep_bas,
                    LEAST(COALESCE(k.cikis_tarihi::date, CURRENT_DATE), {$bit}::date) AS dep_bit,
                    LEAST(COALESCE(k.cikis_tarihi::date, CURRENT_DATE), {$bit}::date) - GREATEST(k.giris_tarihi::date, {$bas}::date) + 1 AS gun,
                    ROUND(
                        COALESCE(
                            f1.gunluk_fiyat,
                            f2.gunluk_fiyat,
                            NULLIF(bx.gunluk_fiyat, 0) * COALESCE(bx.fiyat_carpani, 1),
                            0
                        ),
                        2
                    ) AS gunluk_fiyat,
                    COALESCE(f1.gunluk_fiyat, f2.gunluk_fiyat, NULLIF(bx.gunluk_fiyat, 0), 0) AS taban_fiyat,
                    COALESCE(bx.fiyat_carpani, 1) AS carpan,
                    (f1.id IS NULL AND f2.id IS NULL AND COALESCE(bx.gunluk_fiyat, 0) = 0) AS fiyat_yok,
                    ROUND((
                        SELECT COALESCE(SUM({$gunlukIfade}), 0)
                        FROM generate_series(
                            GREATEST(k.giris_tarihi::date, {$bas}::date),
                            LEAST(COALESCE(k.cikis_tarihi::date, CURRENT_DATE), {$bit}::date),
                            '1 day'::interval
                        ) gs
                    ), 2) AS tutar
                 FROM arac_konaklamalari k
                 JOIN araclar a0 ON a0.id = k.arac_id
                 LEFT JOIN bayiler bx ON bx.id = k.bayi_id
                 LEFT JOIN depolama_fiyatlari f1 ON f1.musteri_id = k.musteri_id AND f1.bayi_id = k.bayi_id AND f1.arac_tipi_id = a0.arac_tipi_id
                 LEFT JOIN depolama_fiyatlari f2 ON f2.musteri_id = k.musteri_id AND f2.bayi_id = k.bayi_id AND f2.arac_tipi_id IS NULL
                 WHERE k.giris_tarihi::date <= {$bit}::date
                   AND COALESCE(k.cikis_tarihi::date, CURRENT_DATE) >= {$bas}::date) d";
    }

    /**
     * Müşteri kartı için otopark listesi + varsayılan / firma özel günlük ücret.
     * @return list<array{id:int,ad:string,sehir:?string,gunluk_fiyat:float,fiyat_carpani:float,varsayilan:float,firma_fiyat:?float}>
     */
    public static function musteriOtoparkFiyatlari(int $musteriId): array
    {
        return Database::fetchAll(
            'SELECT b.id, b.ad, b.sehir, b.gunluk_fiyat, b.fiyat_carpani,
                    ROUND(COALESCE(NULLIF(b.gunluk_fiyat, 0), 0) * COALESCE(b.fiyat_carpani, 1), 2) AS varsayilan,
                    f.gunluk_fiyat AS firma_fiyat
             FROM bayiler b
             LEFT JOIN depolama_fiyatlari f
               ON f.bayi_id = b.id AND f.musteri_id = :m AND f.arac_tipi_id IS NULL
             WHERE b.aktif
             ORDER BY b.ad',
            ['m' => $musteriId]
        );
    }

    public static function arac(int $aracId): array
    {
        // Bitiş = uzak gelecek: tamamlanmış konaklamada gerçek çıkış günü kullanılır.
        // Açık konaklamada COALESCE(cikis, CURRENT_DATE) zaten bugüne düşer; "bugün" ile kesilmez.
        return self::zenginlestir(Database::fetchAll(
            'SELECT d.*, b.ad AS bayi, a.arac_tipi_id
             FROM ' . self::sorgu('2000-01-01', '9999-12-31') . '
             LEFT JOIN bayiler b ON b.id = d.bayi_id
             JOIN araclar a ON a.id = d.arac_id
             WHERE d.arac_id = :a ORDER BY d.giris_tarihi DESC',
            ['a' => $aracId]
        ));
    }

    /**
     * Konaklama satırlarına dönemsel fiyat kırılımı uygular (tutar / gün / özet).
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    public static function zenginlestir(array $rows): array
    {
        foreach ($rows as &$row) {
            $segmentler = self::segmentler($row);
            $row['segmentler'] = $segmentler;
            if ($segmentler === []) {
                $row['fiyat_degisti'] = false;
                $row['fiyat_ozet'] = null;
                continue;
            }
            $row['gun'] = array_sum(array_column($segmentler, 'gun'));
            $row['tutar'] = round(array_sum(array_column($segmentler, 'tutar')), 2);
            if ($row['tutar'] > 0) {
                $row['fiyat_yok'] = false;
            }
            $row['fiyat_degisti'] = count($segmentler) > 1;
            $row['fiyat_ozet'] = self::fiyatOzet($segmentler);
            if (count($segmentler) === 1) {
                $row['gunluk_fiyat'] = $segmentler[0]['gunluk_fiyat'];
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * @param list<array{gun:int,gunluk_fiyat:float,tutar:float}> $segmentler
     */
    public static function fiyatOzet(array $segmentler): string
    {
        return implode(' + ', array_map(
            static fn (array $s) => (int) $s['gun'] . ' gün × ' . number_format((float) $s['gunluk_fiyat'], 2, ',', '.') . ' ₺',
            $segmentler
        ));
    }

    /** Görünümlerde depolama satırı açıklaması. */
    public static function fiyatAciklama(array $konaklama): string
    {
        if (!empty($konaklama['fiyat_yok'])) {
            return 'Fiyat tanımlı değil';
        }
        if (!empty($konaklama['fiyat_degisti']) && !empty($konaklama['fiyat_ozet'])) {
            return (string) $konaklama['fiyat_ozet'];
        }
        $segmentler = $konaklama['segmentler'] ?? [];
        if (count($segmentler) === 1) {
            return self::fiyatOzet($segmentler);
        }

        return (int) ($konaklama['gun'] ?? 0) . ' gün × ' . number_format((float) ($konaklama['gunluk_fiyat'] ?? 0), 2, ',', '.') . ' ₺';
    }

    /**
     * Konaklama aralığını fiyat dönemlerine böler.
     * @param array<string,mixed> $k dep_bas/dep_bit veya giris/cikis + bayi_id + musteri_id (+ arac_tipi_id|arac_id)
     * @return list<array{gun:int,gunluk_fiyat:float,tutar:float,bas:string,bit:string}>
     */
    public static function segmentler(array $k): array
    {
        $bas = self::tarih($k['dep_bas'] ?? $k['giris_tarihi'] ?? null);
        $bit = self::tarih($k['dep_bit'] ?? $k['cikis_tarihi'] ?? date('Y-m-d'));
        if ($bas === null || $bit === null || $bit < $bas) {
            return [];
        }

        $bayiId = (int) ($k['bayi_id'] ?? 0);
        $musteriId = isset($k['musteri_id']) && $k['musteri_id'] !== null && $k['musteri_id'] !== ''
            ? (int) $k['musteri_id'] : null;
        $aracTipiId = isset($k['arac_tipi_id']) && $k['arac_tipi_id'] !== null && $k['arac_tipi_id'] !== ''
            ? (int) $k['arac_tipi_id'] : null;
        if ($aracTipiId === null && !empty($k['arac_id'])) {
            $tip = Database::fetch('SELECT arac_tipi_id FROM araclar WHERE id = :id', ['id' => (int) $k['arac_id']]);
            $aracTipiId = $tip && $tip['arac_tipi_id'] !== null ? (int) $tip['arac_tipi_id'] : null;
        }
        if ($bayiId <= 0) {
            return [];
        }

        $gecmis = Database::fetchAll(
            'SELECT musteri_id, arac_tipi_id, gunluk_fiyat, effective_from::text AS effective_from,
                    effective_to::text AS effective_to
             FROM depolama_fiyat_gecmisi
             WHERE bayi_id = :b
               AND (musteri_id IS NULL OR musteri_id IS NOT DISTINCT FROM :m)
               AND (arac_tipi_id IS NULL OR arac_tipi_id IS NOT DISTINCT FROM :t)
               AND effective_from <= :bit
               AND (effective_to IS NULL OR effective_to > :bas)
             ORDER BY effective_from',
            ['b' => $bayiId, 'm' => $musteriId, 't' => $aracTipiId, 'bas' => $bas, 'bit' => $bit]
        );

        $yedek = (float) ($k['gunluk_fiyat'] ?? 0);
        $points = [$bas];
        $bitExclusive = date('Y-m-d', strtotime($bit . ' +1 day'));
        foreach ($gecmis as $g) {
            $ef = substr((string) $g['effective_from'], 0, 10);
            $et = $g['effective_to'] !== null ? substr((string) $g['effective_to'], 0, 10) : null;
            if ($ef > $bas && $ef < $bitExclusive) {
                $points[] = $ef;
            }
            if ($et !== null && $et > $bas && $et < $bitExclusive) {
                $points[] = $et;
            }
        }
        $points[] = $bitExclusive;
        $points = array_values(array_unique($points));
        sort($points);

        $segmentler = [];
        for ($i = 0, $n = count($points) - 1; $i < $n; $i++) {
            $from = $points[$i];
            $toExclusive = $points[$i + 1];
            $toInclusive = date('Y-m-d', strtotime($toExclusive . ' -1 day'));
            if ($toInclusive < $from) {
                continue;
            }
            $fiyat = self::fiyatCoz($gecmis, $musteriId, $aracTipiId, $from, $yedek);
            $gun = (int) ((strtotime($toInclusive) - strtotime($from)) / 86400) + 1;
            if ($gun <= 0) {
                continue;
            }
            $son = $segmentler !== [] ? array_key_last($segmentler) : null;
            if ($son !== null && (float) $segmentler[$son]['gunluk_fiyat'] === $fiyat) {
                $segmentler[$son]['gun'] += $gun;
                $segmentler[$son]['bit'] = $toInclusive;
                $segmentler[$son]['tutar'] = round($segmentler[$son]['gun'] * $fiyat, 2);
                continue;
            }
            $segmentler[] = [
                'gun' => $gun,
                'gunluk_fiyat' => $fiyat,
                'tutar' => round($gun * $fiyat, 2),
                'bas' => $from,
                'bit' => $toInclusive,
            ];
        }

        return $segmentler;
    }

    /**
     * @param list<array<string,mixed>> $gecmis
     */
    private static function fiyatCoz(array $gecmis, ?int $musteriId, ?int $aracTipiId, string $gun, float $yedek): float
    {
        $aday = null;
        $oncelik = -1;
        foreach ($gecmis as $g) {
            $ef = substr((string) $g['effective_from'], 0, 10);
            $et = $g['effective_to'] !== null ? substr((string) $g['effective_to'], 0, 10) : null;
            if ($gun < $ef || ($et !== null && $gun >= $et)) {
                continue;
            }
            $gMusteri = $g['musteri_id'] !== null ? (int) $g['musteri_id'] : null;
            $gTip = $g['arac_tipi_id'] !== null ? (int) $g['arac_tipi_id'] : null;
            $p = match (true) {
                $musteriId !== null && $gMusteri === $musteriId && $aracTipiId !== null && $gTip === $aracTipiId => 3,
                $musteriId !== null && $gMusteri === $musteriId && $gTip === null => 2,
                $gMusteri === null && $gTip === null => 1,
                default => 0,
            };
            if ($p > $oncelik) {
                $oncelik = $p;
                $aday = (float) $g['gunluk_fiyat'];
            }
        }

        return $oncelik > 0 ? round((float) $aday, 2) : round($yedek, 2);
    }

    private static function tarih(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        $s = substr((string) $v, 0, 10);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : null;
    }
}
