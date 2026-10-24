<?php

declare(strict_types=1);

namespace App\Services;

/**
 * VIS (Araç Ekspertiz) bağımsız depolama.
 * Mevcut araclar / hareket / maliyet tablolarına yazmaz.
 */
final class VisStore
{
    private static function dir(): string
    {
        $dir = BASE_PATH . '/storage/vis';
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
            'durum' => 'taslak',
            'modul' => 'tanim',
            'alan' => 'plaka',
            'veri' => [],
            'hasar' => [],
            'mekanik' => [],
            'fotograflar' => [],
            'lokasyon' => [],
            'kondisyon' => [],
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
            throw new \RuntimeException('Ekspertiz kaydı bulunamadı.');
        }
        if (isset($patch['veri']) && is_array($patch['veri'])) {
            $row['veri'] = array_merge($row['veri'] ?? [], $patch['veri']);
            unset($patch['veri']);
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

    /** Wizard alan tanımları (VIS Araç tanımı akışına yakın). */
    public static function tanimAlanlari(): array
    {
        return [
            'plaka' => ['Plaka', 'text', 'Plakayı yazın', true],
            'sase' => ['Şasi numarası', 'text', '17 haneli şasi', true],
            'arac_turu' => ['Araç türü', 'select', null, true, ['Binek', 'Hafif Ticari', 'SUV', 'Motosiklet']],
            'marka' => ['Marka', 'text', 'Marka', true],
            'model' => ['Model', 'text', 'Model', true],
            'ilk_tescil' => ['İlk tescil tarihi', 'date', null, false],
            'govde_tipi' => ['Gövde tipi', 'select', null, false, ['Sedan', 'Hatchback', 'Station', 'Coupe', 'Cabrio', 'Pickup']],
            'koltuk' => ['Koltuk sayısı', 'number', null, false],
            'model_yili' => ['Model Yılı', 'number', null, true],
            'motor' => ['Motor', 'text', 'Örn. 1.5 Dizel', false],
            'cekis' => ['Teker çekişi', 'select', null, false, ['Önden', 'Arkadan', '4x4']],
            'vites' => ['Vites', 'select', null, false, ['Manuel', 'Otomatik']],
            'varyant' => ['Varyant', 'text', null, false],
            'renk' => ['Renk', 'text', null, false],
            'lastikler' => ['Lastikler', 'text', null, false],
            'km' => ['Aracın kilometresi', 'number', null, true],
            'garanti' => ['Garanti', 'select', null, false, ['Var', 'Yok', 'Bilinmiyor']],
            'anahtar' => ['Anahtar sayısı', 'number', null, false],
            'doseme' => ['Döşeme', 'text', null, false],
            'donanim' => ['Donanım', 'textarea', 'Not / ekipman listesi', false],
            'ozet' => ['Genel bakış', 'textarea', 'Özet not', false],
        ];
    }

    /** @return list<array{kod:string,ad:string}> */
    public static function moduller(): array
    {
        return [
            ['kod' => 'tanim', 'ad' => 'Araç tanımı'],
            ['kod' => 'hasar', 'ad' => 'Hasar tespiti'],
            ['kod' => 'mekanik', 'ad' => 'Mekanik ve Elektrik'],
            ['kod' => 'foto', 'ad' => 'Fotoğraflar ve Dokümanlar'],
            ['kod' => 'lokasyon', 'ad' => 'Lokasyon'],
            ['kod' => 'kondisyon', 'ad' => 'Kondisyon'],
            ['kod' => 'cikis', 'ad' => 'Çıkış'],
        ];
    }

    /** Hasar panelleri (basit şema). */
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
        ];
    }

    public static function hasarSeviyeleri(): array
    {
        return ['Yok', 'Çizik', 'Göçük', 'Boyalı', 'Değişen', 'Hasarlı'];
    }
}
