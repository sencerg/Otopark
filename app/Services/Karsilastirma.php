<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class Karsilastirma
{
    private static ?array $veri = null;

    public static function veri(): array
    {
        return self::$veri ??= require BASE_PATH . '/app/karsilastirma.php';
    }

    /** Maddeleri kayıtlı kararlarla birleştirir. */
    public static function maddeler(): array
    {
        $kararlar = [];
        foreach (Database::fetchAll(
            'SELECT k.kod, k.karar, k.notlar, k.updated_at, u.name AS guncelleyen
             FROM karsilastirma_kararlari k LEFT JOIN users u ON u.id = k.guncelleyen_id'
        ) as $r) {
            $kararlar[$r['kod']] = $r;
        }

        return array_map(static fn (array $m) => $m + [
            'karar' => $kararlar[$m['kod']]['karar'] ?? 'bekliyor',
            'notlar' => $kararlar[$m['kod']]['notlar'] ?? '',
            'karar_tarihi' => $kararlar[$m['kod']]['updated_at'] ?? null,
            'guncelleyen' => $kararlar[$m['kod']]['guncelleyen'] ?? null,
        ], self::veri()['maddeler']);
    }

    public static function madde(string $kod): ?array
    {
        foreach (self::veri()['maddeler'] as $m) {
            if ($m['kod'] === $kod) {
                return $m;
            }
        }

        return null;
    }

    public static function markdown(): string
    {
        $v = self::veri();
        $maddeler = self::maddeler();
        $hucre = static fn (?string $s) => str_replace(["\r", "\n", '|'], [' ', ' ', '\\|'], (string) $s);

        $out = ["# BİA ↔ MD Karşılaştırması: İş Akışı ve Farklar", ''];
        $out[] = '> Bu dosya uygulamadaki "BİA ↔ MD Karşılaştırma" sayfasından üretilir (' . date('d.m.Y H:i') . '). Kararlar sayfadan güncellenir.';
        $out[] = '';
        $out[] = '## Kaynaklar';
        $out[] = '';
        foreach (['bia' => 'BİA', 'md' => 'MD', 'sistem' => 'Sistem'] as $k => $ad) {
            $out[] = "- **{$ad}:** {$v['kaynaklar'][$k]}";
        }
        $out[] = '';
        $out[] = '## Özet';
        $out[] = '';
        foreach ($v['ozet'] as $s) {
            $out[] = "- {$s}";
        }
        $out[] = '';
        $out[] = '## Sözlük (MD terimi → BİA karşılığı)';
        $out[] = '';
        $out[] = '| MD | BİA | Not |';
        $out[] = '| --- | --- | --- |';
        foreach ($v['sozluk'] as $s) {
            $out[] = '| ' . $hucre($s['md']) . ' | ' . $hucre($s['bia']) . ' | ' . $hucre($s['not']) . ' |';
        }
        $out[] = '';
        $out[] = '## İş akışı';
        foreach (['bia' => 'BİA akışı (sistem şu an böyle çalışıyor)', 'md' => 'MD akışı'] as $k => $baslik) {
            $out[] = '';
            $out[] = "### {$baslik}";
            $out[] = '';
            foreach ($v['akis'][$k] as $i => $adim) {
                $out[] = ($i + 1) . ". **{$adim['baslik']}:** {$adim['detay']}";
            }
        }
        $out[] = '';
        $out[] = '## Farklar ve kararlar';
        foreach ($v['kategoriler'] as $katKod => $katAd) {
            $grup = array_filter($maddeler, static fn ($m) => $m['kategori'] === $katKod);
            if (!$grup) {
                continue;
            }
            $out[] = '';
            $out[] = "### {$katAd}";
            foreach ($grup as $m) {
                $out[] = '';
                $out[] = "#### {$m['kod']} · {$m['baslik']}";
                $out[] = '';
                $out[] = '- **Fark türü:** ' . $v['turler'][$m['tur']][0];
                $out[] = "- **BİA:** {$m['bia']}";
                $out[] = "- **MD:** {$m['md']}";
                $out[] = "- **Sistemde şu an:** {$m['sistem']}";
                $out[] = '- **Karar:** ' . ($v['kararlar'][$m['karar']][0] ?? $m['karar']) . ($m['notlar'] !== '' ? ' — ' . $hucre($m['notlar']) : '');
            }
        }
        $out[] = '';

        return implode("\n", $out);
    }
}
