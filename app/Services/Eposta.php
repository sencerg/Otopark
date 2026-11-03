<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Env;

/** E-posta kuyruğu. SMTP yoksa mektup storage/mail altına yazılır. */
final class Eposta
{
    public static function kuyruga(?int $userId, string $alici, string $konu, string $govde): void
    {
        $alici = trim($alici);
        if ($alici === '' || !filter_var($alici, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        Database::query(
            'INSERT INTO eposta_kuyruk (user_id, alici, konu, govde) VALUES (:u, :a, :k, :g)',
            ['u' => $userId, 'a' => $alici, 'k' => mb_substr($konu, 0, 200), 'g' => $govde]
        );
    }

    public static function isle(int $limit = 30): int
    {
        $rows = Database::fetchAll(
            "SELECT id, alici, konu, govde, deneme FROM eposta_kuyruk WHERE durum = 'bekliyor' ORDER BY id LIMIT " . max(1, $limit)
        );
        $n = 0;
        foreach ($rows as $row) {
            try {
                $durum = self::gonder((string) $row['alici'], (string) $row['konu'], (string) $row['govde'], (int) $row['id']);
                Database::query(
                    'UPDATE eposta_kuyruk SET durum = :d, gonderildi_at = NOW(), hata = NULL WHERE id = :id',
                    ['d' => $durum, 'id' => $row['id']]
                );
                $n++;
            } catch (\Throwable $e) {
                Database::query(
                    "UPDATE eposta_kuyruk SET deneme = deneme + 1, hata = :h, durum = CASE WHEN deneme + 1 >= 3 THEN 'hata' ELSE 'bekliyor' END WHERE id = :id",
                    ['h' => mb_substr($e->getMessage(), 0, 500), 'id' => $row['id']]
                );
            }
        }

        return $n;
    }

    private static function gonder(string $alici, string $konu, string $govde, int $id): string
    {
        $host = (string) Env::get('SMTP_HOST', '');
        if ($host !== '') {
            $baslik = 'From: ' . (string) Env::get('MAIL_FROM', 'noreply@otopark.local') . "\r\nContent-Type: text/plain; charset=UTF-8";
            if (!mail($alici, '=?UTF-8?B?' . base64_encode($konu) . '?=', $govde, $baslik)) {
                throw new \RuntimeException('E-posta gönderilemedi.');
            }

            return 'gitti';
        }

        $dir = BASE_PATH . '/storage/mail';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $dosya = $dir . '/' . $id . '.eml';
        $icerik = "To: {$alici}\r\nSubject: {$konu}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$govde}";
        if (file_put_contents($dosya, $icerik) === false) {
            throw new \RuntimeException('Yerel e-posta dosyası yazılamadı.');
        }

        return 'yerel';
    }
}
