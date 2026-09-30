<?php

declare(strict_types=1);

namespace App\Core;

final class Upload
{
    private const ALLOWED = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];
    private const MAX_BYTES = 20 * 1024 * 1024;

    /** $_FILES[$field] içindeki (tekli veya çoklu) dosyaları kaydeder ve dosyalar tablosuna yazar. */
    public static function save(string $field, string $ilgiliTip, int $ilgiliId, string $tur = 'belge', array $tanimlar = []): int
    {
        if (empty($_FILES[$field]['name'])) {
            return 0;
        }

        $files = $_FILES[$field];
        $names = (array) $files['name'];
        $saved = 0;
        $dir = '/uploads/' . $ilgiliTip . '/' . date('Y/m');
        $absDir = BASE_PATH . '/public' . $dir;
        if (!is_dir($absDir)) {
            mkdir($absDir, 0775, true);
        }

        foreach ($names as $i => $name) {
            $error = ((array) $files['error'])[$i] ?? UPLOAD_ERR_NO_FILE;
            $tmp = ((array) $files['tmp_name'])[$i] ?? '';
            $size = ((array) $files['size'])[$i] ?? 0;
            $ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));

            if ($error !== UPLOAD_ERR_OK || $size > self::MAX_BYTES || !in_array($ext, self::ALLOWED, true) || !is_uploaded_file($tmp)) {
                continue;
            }

            $fileName = bin2hex(random_bytes(12)) . '.' . $ext;
            if (!move_uploaded_file($tmp, $absDir . '/' . $fileName)) {
                continue;
            }

            Database::query(
                'INSERT INTO dosyalar (ilgili_tip, ilgili_id, tur, tanim, dosya_yolu, orijinal_ad, kullanici_id)
                 VALUES (:t, :i, :tur, :tanim, :yol, :ad, :u)',
                [
                    't' => $ilgiliTip, 'i' => $ilgiliId, 'tur' => in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) && $tur === 'fotograf' ? 'fotograf' : $tur,
                    'tanim' => trim((string) ($tanimlar[$i] ?? '')) ?: null, 'yol' => $dir . '/' . $fileName,
                    'ad' => mb_substr((string) $name, 0, 250), 'u' => Auth::id(),
                ]
            );
            $saved++;
        }

        return $saved;
    }

    public static function list(string $ilgiliTip, int $ilgiliId): array
    {
        return Database::fetchAll(
            'SELECT * FROM dosyalar WHERE ilgili_tip = :t AND ilgili_id = :i ORDER BY id',
            ['t' => $ilgiliTip, 'i' => $ilgiliId]
        );
    }
}
