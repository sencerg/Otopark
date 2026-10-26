<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Detaylı Araç Ekle — Teslim Alma taslakları + ekspertiz şeması.
 */
final class DetayliAracStore
{
    /** @return list<array{kod:string,ad:string}> */
    public static function adimlar(): array
    {
        return [
            ['kod' => 'kontrol', 'ad' => 'Kontrol'],
            ['kod' => 'taraflar', 'ad' => 'Taraflar'],
            ['kod' => 'arac', 'ad' => 'Araç'],
            ['kod' => 'ekspertiz', 'ad' => 'Ekspertiz'],
            ['kod' => 'ozet', 'ad' => 'Özet'],
        ];
    }

    /** Ekspertiz iç adımları (4. aşama). */
    public static function ekspertizAdimlari(): array
    {
        return [
            ['kod' => 'hasar', 'ad' => 'Hasar tespiti'],
            ['kod' => 'mekanik', 'ad' => 'Mekanik / elektrik'],
            ['kod' => 'foto', 'ad' => 'Fotoğraflar'],
            ['kod' => 'lokasyon', 'ad' => 'Lokasyon'],
            ['kod' => 'kondisyon', 'ad' => 'Kondisyon'],
        ];
    }

    public static function hasarPanelleri(): array
    {
        return [
            'on_tampon' => 'Ön tampon',
            'kaput' => 'Kaput',
            'tavan' => 'Tavan',
            'bagaj' => 'Bagaj / arka kapak',
            'arka_tampon' => 'Arka tampon',
            'sol_on_kapi' => 'Sol ön kapı',
            'sol_arka_kapi' => 'Sol arka kapı',
            'sag_on_kapi' => 'Sağ ön kapı',
            'sag_arka_kapi' => 'Sağ arka kapı',
            'sol_camurluk' => 'Sol çamurluk',
            'sag_camurluk' => 'Sağ çamurluk',
            'sol_on_cam' => 'Sol ön cam / ayna',
            'sag_on_cam' => 'Sağ ön cam / ayna',
            'far_sol' => 'Sol far',
            'far_sag' => 'Sağ far',
            'stop_sol' => 'Sol stop',
            'stop_sag' => 'Sağ stop',
            'jant_sol_on' => 'Sol ön jant',
            'jant_sag_on' => 'Sağ ön jant',
            'jant_sol_arka' => 'Sol arka jant',
            'jant_sag_arka' => 'Sağ arka jant',
        ];
    }

    public static function hasarSeviyeleri(): array
    {
        return ['Yok', 'Çizik', 'Göçük', 'Boyalı', 'Değişen', 'Hasarlı'];
    }

    public static function mekanikAlanlari(): array
    {
        return [
            'motor' => 'Motor',
            'elektrik' => 'Elektrik',
            'suspansiyon' => 'Süspansiyon',
            'fren' => 'Fren',
            'sanziman' => 'Şanzıman',
            'klima' => 'Klima',
            'egzoz' => 'Egzoz',
            'direksiyon' => 'Direksiyon',
        ];
    }

    public static function mekanikSeviyeleri(): array
    {
        return ['İyi', 'Orta', 'Kötü', 'Bilinmiyor'];
    }

    public static function fotoEtiketleri(): array
    {
        return ['Ön', 'Arka', 'Sol', 'Sağ', 'İç ön', 'İç arka', 'Gösterge / KM', 'Motor bölmesi', 'Hasar detay 1', 'Hasar detay 2', 'Evrak / ruhsat'];
    }

    public static function kondisyonSeviyeleri(): array
    {
        return ['Çok iyi', 'İyi', 'Orta', 'Kötü'];
    }

    private static function dir(): string
    {
        $dir = BASE_PATH . '/storage/detayli_arac';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private static function path(string $id): string
    {
        return self::dir() . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $id) . '.json';
    }

    /** @return list<array<string,mixed>> */
    public static function liste(): array
    {
        $rows = [];
        foreach (glob(self::dir() . '/*.json') ?: [] as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            if (is_array($data)) {
                $rows[] = $data;
            }
        }
        usort($rows, static fn ($a, $b) => strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? '')));

        return $rows;
    }

    public static function bul(string $id): ?array
    {
        $file = self::path($id);
        // Eski taslak klasörü (geçiş)
        if (!is_file($file)) {
            $eski = BASE_PATH . '/storage/vis/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $id) . '.json';
            if (is_file($eski)) {
                $file = $eski;
            }
        }
        if (!is_file($file)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($file), true);

        return is_array($data) ? $data : null;
    }

    public static function olustur(array $seed = []): array
    {
        $id = bin2hex(random_bytes(8));
        $now = date('c');
        $row = array_merge([
            'id' => $id,
            'akis' => 'teslim_alma',
            'durum' => 'taslak',
            'adim' => 'taraflar',
            'ekspertiz_alt' => 'hasar',
            'veri' => [],
            'taraflar' => [],
            'ekspertiz' => [
                'hasar' => [],
                'mekanik' => [],
                'fotograflar' => [],
                'lokasyon' => [],
                'kondisyon' => [],
            ],
            'arac_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $seed);
        self::yaz($row);

        return $row;
    }

    public static function kaydet(string $id, array $patch): array
    {
        $row = self::bul($id);
        if (!$row) {
            throw new \RuntimeException('Kayıt bulunamadı.');
        }
        if (isset($patch['veri']) && is_array($patch['veri'])) {
            $row['veri'] = array_merge($row['veri'] ?? [], $patch['veri']);
            unset($patch['veri']);
        }
        if (isset($patch['ekspertiz']) && is_array($patch['ekspertiz'])) {
            $row['ekspertiz'] = array_merge($row['ekspertiz'] ?? [], $patch['ekspertiz']);
            unset($patch['ekspertiz']);
        }
        foreach ($patch as $k => $v) {
            $row[$k] = $v;
        }
        $row['updated_at'] = date('c');
        self::yaz($row);

        return $row;
    }

    private static function yaz(array $row): void
    {
        file_put_contents(
            self::path((string) $row['id']),
            json_encode($row, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
}
