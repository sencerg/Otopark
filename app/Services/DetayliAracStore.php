<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Detaylı Araç Ekle — taslaklar PostgreSQL'de (canlıda kalıcı).
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

    /** @return list<array{kod:string,ad:string}> */
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

    /** @return list<array<string,mixed>> */
    public static function liste(): array
    {
        self::ensureTable();
        $rows = Database::fetchAll(
            'SELECT id, icerik, durum, created_at, updated_at
             FROM detayli_arac_taslaklari
             ORDER BY updated_at DESC
             LIMIT 100'
        );
        $out = [];
        foreach ($rows as $r) {
            $row = self::satirdan($r);
            if ($row) {
                $out[] = $row;
            }
        }

        return $out;
    }

    public static function bul(string $id): ?array
    {
        self::ensureTable();
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id) ?? '';
        if ($id === '') {
            return null;
        }
        $r = Database::fetch(
            'SELECT id, icerik, durum, created_at, updated_at FROM detayli_arac_taslaklari WHERE id = :id',
            ['id' => $id]
        );
        if ($r) {
            return self::satirdan($r);
        }

        // Eski dosya taslağını bir kez DB'ye taşı
        return self::dosyadanTasi($id);
    }

    public static function olustur(array $seed = []): array
    {
        self::ensureTable();
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
            'kullanici_id' => null,
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
        self::ensureTable();
        $id = (string) $row['id'];
        $durum = (string) ($row['durum'] ?? 'taslak');
        $kullaniciId = isset($row['kullanici_id']) && $row['kullanici_id'] !== null && $row['kullanici_id'] !== ''
            ? (int) $row['kullanici_id']
            : null;
        $json = json_encode($row, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Taslak kaydedilemedi.');
        }

        Database::query(
            'INSERT INTO detayli_arac_taslaklari (id, kullanici_id, durum, icerik, created_at, updated_at)
             VALUES (:id, :u, :d, CAST(:c AS jsonb), COALESCE(:ca::timestamptz, NOW()), NOW())
             ON CONFLICT (id) DO UPDATE SET
                kullanici_id = EXCLUDED.kullanici_id,
                durum = EXCLUDED.durum,
                icerik = EXCLUDED.icerik,
                updated_at = NOW()',
            [
                'id' => $id,
                'u' => $kullaniciId,
                'd' => $durum,
                'c' => $json,
                'ca' => $row['created_at'] ?? null,
            ]
        );
    }

    /** @param array<string,mixed> $r */
    private static function satirdan(array $r): ?array
    {
        $icerik = $r['icerik'] ?? null;
        if (is_string($icerik)) {
            $icerik = json_decode($icerik, true);
        }
        if (!is_array($icerik)) {
            return null;
        }
        $icerik['id'] = $r['id'] ?? ($icerik['id'] ?? null);
        $icerik['durum'] = $r['durum'] ?? ($icerik['durum'] ?? 'taslak');
        if (!empty($r['created_at']) && empty($icerik['created_at'])) {
            $icerik['created_at'] = $r['created_at'];
        }
        if (!empty($r['updated_at'])) {
            $icerik['updated_at'] = is_string($r['updated_at']) ? $r['updated_at'] : date('c', strtotime((string) $r['updated_at']));
        }

        return $icerik;
    }

    private static function dosyadanTasi(string $id): ?array
    {
        foreach (['detayli_arac', 'vis'] as $klasor) {
            $file = BASE_PATH . '/storage/' . $klasor . '/' . $id . '.json';
            if (!is_file($file)) {
                continue;
            }
            $data = json_decode((string) file_get_contents($file), true);
            if (!is_array($data)) {
                continue;
            }
            $data['id'] = $id;
            try {
                self::yaz($data);
            } catch (\Throwable) {
                // tablo yoksa / yazılamazsa yine dosyadan dön
                return $data;
            }

            return $data;
        }

        return null;
    }

    private static bool $tableChecked = false;

    private static function ensureTable(): void
    {
        if (self::$tableChecked) {
            return;
        }
        self::$tableChecked = true;
        try {
            Database::query(
                "CREATE TABLE IF NOT EXISTS detayli_arac_taslaklari (
                    id            VARCHAR(32) PRIMARY KEY,
                    kullanici_id  BIGINT REFERENCES users(id),
                    durum         VARCHAR(40) NOT NULL DEFAULT 'taslak',
                    icerik        JSONB NOT NULL DEFAULT '{}'::jsonb,
                    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                    updated_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
                )"
            );
        } catch (\Throwable) {
            // migrate ile gelir; burada sessiz geç
            self::$tableChecked = false;
        }
    }
}
