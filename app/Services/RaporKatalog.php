<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;

/**
 * Rapor kataloğu.
 * tip: interaktif = filtre + canlı liste; hazirla = parametre seç → çıktı üret.
 */
final class RaporKatalog
{
    /**
     * @return list<array{
     *   kod:string, ad:string, aciklama:string, tip:string, kategori:string,
     *   izin:string, url:string, ikon:string
     * }>
     */
    public static function tum(): array
    {
        return [
            [
                'kod' => 'depolama',
                'ad' => 'Depolama Raporu',
                'aciklama' => 'Tarih aralığında stok günü, günlük fiyat ve tutar. Filtreleyip anlık inceleyin, Excel indirin.',
                'tip' => 'interaktif',
                'kategori' => 'Saha / Maliyet',
                'izin' => 'menu.depolama_raporu',
                'url' => '/depolama_raporu',
                'ikon' => 'mdi-parking',
            ],
            [
                'kod' => 'ek_hizmet',
                'ad' => 'Ek Hizmet Raporu',
                'aciklama' => 'Araçlara yazılan hizmet ve maliyet kayıtları. Canlı filtre, özet kartlar ve Excel.',
                'tip' => 'interaktif',
                'kategori' => 'Saha / Maliyet',
                'izin' => 'menu.ek_hizmet_raporu',
                'url' => '/ek_hizmet_raporu',
                'ikon' => 'mdi-cash-multiple',
            ],
            [
                'kod' => 'stok_ozet',
                'ad' => 'Stok Özet Raporu',
                'aciklama' => 'Lokasyon / müşteri seçin; anlık stok özetini hazırlayıp Excel olarak indirin.',
                'tip' => 'hazirla',
                'kategori' => 'Stok',
                'izin' => 'menu.stok_ozet_raporu',
                'url' => '/raporlar/stok_ozet',
                'ikon' => 'mdi-car-multiple',
            ],
            [
                'kod' => 'giris_cikis',
                'ad' => 'Giriş–Çıkış Özeti',
                'aciklama' => 'Dönem seçin; giriş ve çıkış adetlerini lokasyon / firmaya göre hazırlayın.',
                'tip' => 'hazirla',
                'kategori' => 'Hareket',
                'izin' => 'menu.giris_cikis_raporu',
                'url' => '/raporlar/giris_cikis',
                'ikon' => 'mdi-swap-horizontal',
            ],
        ];
    }

    /** Kullanıcının görebileceği raporlar. */
    public static function erisilebilir(): array
    {
        return array_values(array_filter(
            self::tum(),
            static fn (array $r) => Auth::can($r['izin'])
        ));
    }

    public static function bul(string $kod): ?array
    {
        foreach (self::tum() as $r) {
            if ($r['kod'] === $kod) {
                return $r;
            }
        }

        return null;
    }

    /** @return list<string> */
    public static function tipEtiket(string $tip): array
    {
        return match ($tip) {
            'interaktif' => ['Interaktif', 'info', 'Canlı liste ve filtre'],
            'hazirla' => ['Seçerek hazırla', 'warning', 'Parametre seç → rapor üret'],
            default => ['Rapor', 'secondary', ''],
        };
    }
}
