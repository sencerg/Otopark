<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use RuntimeException;

/** Bayi–Moex ve müşteri–Moex konuşmaları. */
final class MesajMerkezi
{
    /** @return array<string,mixed> */
    public static function ac(int $acanId, string $tur, string $konu, string $metin, ?int $bayiId, ?int $musteriId, ?int $sorumluId, string $oncelik, ?int $aracId = null): array
    {
        $tur = $tur === 'ticket' ? 'ticket' : 'mesaj';
        $oncelik = in_array($oncelik, ['dusuk', 'normal', 'yuksek'], true) ? $oncelik : 'normal';
        $konu = trim($konu);
        $metin = trim($metin);
        if ($konu === '' || $metin === '') {
            throw new RuntimeException('Konu ve mesaj zorunludur.');
        }
        self::tarafKontrol($bayiId, $musteriId);
        $id = Database::transaction(function () use ($acanId, $tur, $konu, $metin, $bayiId, $musteriId, $sorumluId, $oncelik, $aracId) {
            $n = (int) Database::fetch("SELECT nextval('konusma_kod_seq') AS n")['n'];
            $kod = ($tur === 'ticket' ? 'TK-' : 'MS-') . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
            $konusmaId = (int) Database::fetch(
                'INSERT INTO konusmalar (tur, kod, konu, oncelik, acan_id, bayi_id, musteri_id, sorumlu_id, arac_id)
                 VALUES (:tur, :kod, :konu, :oncelik, :acan, :bayi, :musteri, :sorumlu, :arac) RETURNING id',
                [
                    'tur' => $tur,
                    'kod' => $kod,
                    'konu' => mb_substr($konu, 0, 200),
                    'oncelik' => $oncelik,
                    'acan' => $acanId,
                    'bayi' => $bayiId,
                    'musteri' => $musteriId,
                    'sorumlu' => $tur === 'ticket' ? $sorumluId : null,
                    'arac' => $aracId,
                ]
            )['id'];
            $mesajId = self::mesajEkle($konusmaId, $acanId, $metin);

            return ['konusma' => $konusmaId, 'mesaj' => $mesajId];
        });

        return $id;
    }

    public static function yaz(int $konusmaId, int $userId, string $metin): int
    {
        $konusma = self::getir($konusmaId);
        if (($konusma['durum'] ?? '') === 'cozuldu' && !Auth::isAdmin()) {
            throw new RuntimeException('Çözülen konuya yalnız Moex yazabilir.');
        }
        $metin = trim($metin);
        if ($metin === '') {
            throw new RuntimeException('Mesaj boş olamaz.');
        }
        $mesajId = self::mesajEkle($konusmaId, $userId, $metin);
        if (($konusma['durum'] ?? '') === 'cozuldu') {
            Database::query("UPDATE konusmalar SET durum = 'acik', updated_at = NOW() WHERE id = :id", ['id' => $konusmaId]);
        }

        return $mesajId;
    }

    public static function durum(int $konusmaId, string $durum, ?int $sorumluId): void
    {
        if (!Auth::isAdmin()) {
            throw new RuntimeException('Durumu yalnız Moex değiştirir.');
        }
        if (!in_array($durum, ['acik', 'bekliyor', 'cozuldu'], true)) {
            throw new RuntimeException('Durum geçersiz.');
        }
        $konusma = self::getir($konusmaId);
        Database::query(
            'UPDATE konusmalar SET durum = :d, sorumlu_id = :s, updated_at = NOW() WHERE id = :id',
            ['d' => $durum, 's' => $sorumluId, 'id' => $konusmaId]
        );
        if ((int) $konusma['acan_id'] !== (int) Auth::id()) {
            BildirimMerkezi::yaz(
                (int) $konusma['acan_id'],
                'ticket',
                $konusma['kod'] . ' durumu değişti',
                self::durumAdi($durum),
                '/mesaj/' . $konusmaId
            );
        }
    }

    /** @return list<array<string,mixed>> */
    public static function liste(): array
    {
        [$sql, $p] = self::erisimSql('k');

        return Database::fetchAll(
            'SELECT k.*, u.name AS acan_ad, s.name AS sorumlu_ad, b.ad AS bayi_ad, mu.ad AS musteri_ad,
                    (SELECT metin FROM konusma_mesajlari m WHERE m.konusma_id = k.id ORDER BY m.id DESC LIMIT 1) AS son_metin
             FROM konusmalar k
             JOIN users u ON u.id = k.acan_id
             LEFT JOIN users s ON s.id = k.sorumlu_id
             LEFT JOIN bayiler b ON b.id = k.bayi_id
             LEFT JOIN musteriler mu ON mu.id = k.musteri_id
             WHERE ' . $sql . '
             ORDER BY k.updated_at DESC
             LIMIT 200',
            $p
        );
    }

    /** @return array<string,mixed> */
    public static function getir(int $id): array
    {
        [$sql, $p] = self::erisimSql('k');
        $p['id'] = $id;
        $row = Database::fetch(
            'SELECT k.*, u.name AS acan_ad, s.name AS sorumlu_ad, b.ad AS bayi_ad, mu.ad AS musteri_ad
             FROM konusmalar k
             JOIN users u ON u.id = k.acan_id
             LEFT JOIN users s ON s.id = k.sorumlu_id
             LEFT JOIN bayiler b ON b.id = k.bayi_id
             LEFT JOIN musteriler mu ON mu.id = k.musteri_id
             WHERE k.id = :id AND ' . $sql,
            $p
        );
        if (!$row) {
            throw new RuntimeException('Konuşma bulunamadı.');
        }
        $row['mesajlar'] = Database::fetchAll(
            'SELECT m.*, u.name AS yazan FROM konusma_mesajlari m JOIN users u ON u.id = m.user_id WHERE m.konusma_id = :id ORDER BY m.id',
            ['id' => $id]
        );

        return $row;
    }

    private static function mesajEkle(int $konusmaId, int $userId, string $metin): int
    {
        $mesajId = (int) Database::fetch(
            'INSERT INTO konusma_mesajlari (konusma_id, user_id, metin) VALUES (:k, :u, :m) RETURNING id',
            ['k' => $konusmaId, 'u' => $userId, 'm' => $metin]
        )['id'];
        Database::query('UPDATE konusmalar SET updated_at = NOW() WHERE id = :id', ['id' => $konusmaId]);
        $konusma = Database::fetch('SELECT * FROM konusmalar WHERE id = :id', ['id' => $konusmaId]);
        if (!$konusma) {
            return $mesajId;
        }
        foreach (self::haberdar($konusma, $userId) as $hedef) {
            BildirimMerkezi::yaz(
                $hedef,
                $konusma['tur'] === 'ticket' ? 'ticket' : 'mesaj',
                $konusma['kod'] . ' · ' . $konusma['konu'],
                mb_substr($metin, 0, 180),
                '/mesaj/' . $konusmaId
            );
        }

        return $mesajId;
    }

    /** @param array<string,mixed> $konusma
     *  @return list<int>
     */
    private static function haberdar(array $konusma, int $yazarId): array
    {
        $idler = [];
        $ekle = static function (int $id) use (&$idler, $yazarId): void {
            if ($id > 0 && $id !== $yazarId) {
                $idler[$id] = $id;
            }
        };
        $ekle((int) ($konusma['acan_id'] ?? 0));
        $ekle((int) ($konusma['sorumlu_id'] ?? 0));
        if (!Auth::isAdmin()) {
            $sorumlu = (int) ($konusma['sorumlu_id'] ?? 0);
            if ($sorumlu > 0) {
                $ekle($sorumlu);
            } else {
                foreach (Database::fetchAll("SELECT id FROM users WHERE is_active AND role = 'admin'") as $admin) {
                    $ekle((int) $admin['id']);
                }
            }
        } else {
            if (!empty($konusma['bayi_id']) && empty($konusma['musteri_id'])) {
                foreach (Database::fetchAll(
                    "SELECT id FROM users WHERE is_active AND role = 'bayi' AND bayi_id = :b",
                    ['b' => $konusma['bayi_id']]
                ) as $kisi) {
                    $ekle((int) $kisi['id']);
                }
            }
            if (!empty($konusma['musteri_id'])) {
                foreach (Database::fetchAll(
                    "SELECT id FROM users WHERE is_active AND role = 'musteri' AND musteri_id = :m",
                    ['m' => $konusma['musteri_id']]
                ) as $kisi) {
                    $ekle((int) $kisi['id']);
                }
            }
            if (empty($konusma['bayi_id']) && empty($konusma['musteri_id'])) {
                foreach (Database::fetchAll("SELECT id FROM users WHERE is_active AND role = 'admin'") as $admin) {
                    $ekle((int) $admin['id']);
                }
            }
        }

        return array_values($idler);
    }

    private static function tarafKontrol(?int $bayiId, ?int $musteriId): void
    {
        if (Auth::isMusteri()) {
            if ($musteriId !== Auth::musteriId() || $bayiId) {
                throw new RuntimeException('Müşteri yalnız Moex merkezine yazar.');
            }

            return;
        }
        if (Auth::bayiId()) {
            if ($bayiId !== Auth::bayiId() || $musteriId) {
                throw new RuntimeException('Bayi yalnız kendi lokasyonu adına Moex merkezine yazar.');
            }

            return;
        }
        if ($bayiId && $musteriId) {
            throw new RuntimeException('Konuşma ya bir lokasyona ya da bir firmaya bağlanır.');
        }
        if ($bayiId && !Database::fetch('SELECT 1 FROM bayiler WHERE id = :id', ['id' => $bayiId])) {
            throw new RuntimeException('Lokasyon bulunamadı.');
        }
        if ($musteriId && !Database::fetch('SELECT 1 FROM musteriler WHERE id = :id', ['id' => $musteriId])) {
            throw new RuntimeException('Firma bulunamadı.');
        }
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private static function erisimSql(string $alias): array
    {
        if (Auth::isAdmin()) {
            return ['TRUE', []];
        }
        if ($bayiId = Auth::bayiId()) {
            return [
                "({$alias}.acan_id = :uid OR {$alias}.sorumlu_id = :uid2 OR ({$alias}.bayi_id = :bayi AND {$alias}.musteri_id IS NULL))",
                ['uid' => Auth::id(), 'uid2' => Auth::id(), 'bayi' => $bayiId],
            ];
        }
        if ($musteriId = Auth::musteriId()) {
            return [
                "({$alias}.acan_id = :uid OR {$alias}.musteri_id = :musteri)",
                ['uid' => Auth::id(), 'musteri' => $musteriId],
            ];
        }

        return ["{$alias}.acan_id = :uid", ['uid' => Auth::id()]];
    }

    public static function durumAdi(string $durum): string
    {
        return match ($durum) {
            'bekliyor' => 'Bekliyor',
            'cozuldu' => 'Çözüldü',
            default => 'Açık',
        };
    }
}
