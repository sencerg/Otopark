<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Tanim;
use RuntimeException;

/** Toplu Excel aktarımında ad → id eşleme (marka, seri, hizmet, tip vb.). */
final class TopluAktarim
{
    /** @return array<string,int> lower(ad) => id */
    public static function adHaritasi(string $liste): array
    {
        $map = [];
        foreach (Tanim::liste($liste) as $id => $ad) {
            $map[self::norm($ad)] = (int) $id;
        }

        return $map;
    }

    public static function norm(string $ad): string
    {
        $h = mb_strtolower(trim($ad), 'UTF-8');
        $h = strtr($h, [
            'ı' => 'i', 'İ' => 'i', 'i̇' => 'i',
            'ş' => 's', 'Ş' => 's',
            'ğ' => 'g', 'Ğ' => 'g',
            'ü' => 'u', 'Ü' => 'u',
            'ö' => 'o', 'Ö' => 'o',
            'ç' => 'c', 'Ç' => 'c',
        ]);
        $h = preg_replace('/\p{Mn}/u', '', $h) ?? $h;

        return preg_replace('/\s+/u', ' ', $h) ?? $h;
    }

    public static function bul(array $harita, ?string $ad, string $etiket, bool $zorunlu = true): ?int
    {
        $ad = trim((string) $ad);
        if ($ad === '' || $ad === '-') {
            if ($zorunlu) {
                throw new RuntimeException("{$etiket} boş olamaz.");
            }

            return null;
        }
        $id = $harita[self::norm($ad)] ?? null;
        if (!$id) {
            throw new RuntimeException("{$etiket} sistemde bulunamadı: {$ad}");
        }

        return $id;
    }

    public static function aracDurumu(?string $ad): ?int
    {
        $ad = trim((string) $ad);
        if ($ad === '') {
            return null;
        }
        foreach (Tanim::ARAC_DURUMU as $id => $label) {
            if (self::norm($label) === self::norm($ad)) {
                return (int) $id;
            }
        }
        throw new RuntimeException("Araç durumu geçersiz: {$ad}");
    }

    public static function modelYili(?string $yil): ?int
    {
        $yil = trim((string) $yil);
        if ($yil === '' || $yil === '-') {
            return null;
        }
        if (!preg_match('/^\d{4}$/', $yil)) {
            throw new RuntimeException("Model yılı geçersiz: {$yil}");
        }
        $row = Database::fetch('SELECT id FROM model_yillari WHERE yil = :y', ['y' => (int) $yil]);
        if (!$row) {
            throw new RuntimeException("Model yılı tanımlı değil: {$yil}");
        }

        return (int) $row['id'];
    }

    public static function seriId(int $markaId, string $seriAd): int
    {
        $seriAd = trim($seriAd);
        if ($seriAd === '' || $seriAd === '-') {
            throw new RuntimeException('Seri boş olamaz.');
        }
        $row = Database::fetch(
            'SELECT id FROM seriler WHERE marka_id = :m AND lower(ad) = lower(:a)',
            ['m' => $markaId, 'a' => $seriAd]
        );
        if ($row) {
            return (int) $row['id'];
        }

        // "Yeni Superb" / "New Octavia" → Superb / Octavia
        $cleaned = trim(preg_replace('/^(yeni|new)\s+/iu', '', $seriAd) ?? $seriAd);
        if ($cleaned !== '' && self::norm($cleaned) !== self::norm($seriAd)) {
            $row = Database::fetch(
                'SELECT id FROM seriler WHERE marka_id = :m AND lower(ad) = lower(:a)',
                ['m' => $markaId, 'a' => $cleaned]
            );
            if ($row) {
                return (int) $row['id'];
            }
        }

        $adaylar = Database::fetchAll('SELECT id, ad FROM seriler WHERE marka_id = :m ORDER BY length(ad) DESC', ['m' => $markaId]);
        $hedef = self::norm($cleaned !== '' ? $cleaned : $seriAd);
        foreach ($adaylar as $r) {
            $n = self::norm($r['ad']);
            if ($n === $hedef || str_contains(self::norm($seriAd), $n) || str_contains($n, $hedef)) {
                return (int) $r['id'];
            }
        }

        throw new RuntimeException("Seri bu markada bulunamadı: {$seriAd}");
    }

    public static function parseTutar(?string $ham): float
    {
        $ham = trim((string) $ham);
        if ($ham === '') {
            throw new RuntimeException('Fiyat / tutar boş olamaz.');
        }
        if (str_contains($ham, ',')) {
            $ham = str_replace(['.', ','], ['', '.'], $ham);
        }

        return (float) $ham;
    }
}
