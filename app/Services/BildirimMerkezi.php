<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

/** Bildirim, duyuru alıcısı ve sürüm mektubu. */
final class BildirimMerkezi
{
    public static function yaz(int $userId, string $tur, string $baslik, string $metin, ?string $url): void
    {
        if ($userId <= 0) {
            return;
        }
        Database::query(
            'INSERT INTO bildirimler (user_id, tur, baslik, metin, url) VALUES (:u, :t, :b, :m, :url)',
            [
                'u' => $userId,
                't' => $tur,
                'b' => mb_substr($baslik, 0, 200),
                'm' => $metin,
                'url' => $url,
            ]
        );
    }

    /**
     * @param array{hedef?:list<string>,grup_id?:list<int>,user_id?:list<int>,bayi_id?:?int,musteri_id?:?int} $secim
     * @return list<array{id:int,name:string,email:string,surum_eposta:bool}>
     */
    public static function alicilar(array $secim): array
    {
        $hedef = array_values(array_unique(array_filter(array_map('strval', $secim['hedef'] ?? []))));
        $gruplar = array_values(array_filter(array_map('intval', $secim['grup_id'] ?? [])));
        $kisiler = array_values(array_filter(array_map('intval', $secim['user_id'] ?? [])));
        $bayiId = (int) ($secim['bayi_id'] ?? 0);
        $musteriId = (int) ($secim['musteri_id'] ?? 0);
        $parca = [];
        $p = [];
        if (in_array('herkes', $hedef, true)) {
            $parca[] = 'TRUE';
        }
        if (in_array('moex', $hedef, true)) {
            $parca[] = "u.role = 'admin'";
        }
        if (in_array('bayi', $hedef, true)) {
            $parca[] = "u.role = 'bayi'";
        }
        if (in_array('musteri', $hedef, true)) {
            $parca[] = "u.role = 'musteri'";
        }
        if ($gruplar) {
            $parca[] = 'u.id IN (SELECT user_id FROM duyuru_grup_uyeleri WHERE grup_id = ANY(:grup::int[]))';
            $p['grup'] = '{' . implode(',', $gruplar) . '}';
        }
        if ($kisiler) {
            $parca[] = 'u.id = ANY(:kisiler::bigint[])';
            $p['kisiler'] = '{' . implode(',', $kisiler) . '}';
        }
        if (!$parca && $bayiId > 0) {
            $parca[] = 'u.bayi_id = :bayi_tek';
            $p['bayi_tek'] = $bayiId;
        }
        if (!$parca && $musteriId > 0) {
            $parca[] = 'u.musteri_id = :musteri_tek';
            $p['musteri_tek'] = $musteriId;
        }
        if (!$parca) {
            throw new RuntimeException('En az bir alıcı grubu, kişi, lokasyon veya firma seçilmelidir.');
        }
        $sql = 'SELECT u.id, u.name, u.email, u.surum_eposta FROM users u WHERE u.is_active AND (' . implode(' OR ', $parca) . ')';
        $genis = in_array('herkes', $hedef, true) || in_array('moex', $hedef, true);
        if ($bayiId > 0 && !isset($p['bayi_tek']) && !$genis && !in_array('musteri', $hedef, true)) {
            $sql .= ' AND u.bayi_id = :bayi_dar';
            $p['bayi_dar'] = $bayiId;
        }
        if ($musteriId > 0 && !isset($p['musteri_tek']) && !$genis && !in_array('bayi', $hedef, true)) {
            $sql .= ' AND u.musteri_id = :musteri_dar';
            $p['musteri_dar'] = $musteriId;
        }
        $sql .= ' ORDER BY u.name';
        $rows = Database::fetchAll($sql, $p);
        $gorulen = [];
        $sonuc = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            if (isset($gorulen[$id])) {
                continue;
            }
            $gorulen[$id] = true;
            $eposta = $row['surum_eposta'];
            $sonuc[] = [
                'id' => $id,
                'name' => (string) $row['name'],
                'email' => (string) $row['email'],
                'surum_eposta' => $eposta === true || $eposta === 1 || $eposta === '1' || $eposta === 't' || $eposta === 'true',
            ];
        }
        if (!$sonuc) {
            throw new RuntimeException('Seçilen alıcılarda aktif kullanıcı yok.');
        }

        return $sonuc;
    }

    /**
     * @param array{surum:string,baslik:string,metin:string,zorunlu:bool,gonderen_id:?int} $duyuru
     * @param array{hedef?:list<string>,grup_id?:list<int>,user_id?:list<int>,bayi_id?:?int,musteri_id?:?int} $secim
     */
    public static function surumDuyur(array $duyuru, array $secim): array
    {
        $alicilar = self::alicilar($secim);
        $metin = trim($duyuru['metin']);
        $baslik = trim($duyuru['baslik']);
        $surum = trim($duyuru['surum']);
        if ($surum === '' || $baslik === '' || $metin === '') {
            throw new RuntimeException('Sürüm, başlık ve metin zorunludur.');
        }
        $var = Database::fetch('SELECT id FROM surum_duyurulari WHERE surum = :s', ['s' => $surum]);
        if ($var && empty($duyuru['yeniden'])) {
            throw new RuntimeException($surum . ' daha önce duyurulmuş. Yeniden göndermek için kutuyu işaretleyin.');
        }
        $konu = $surum . ' · ' . $baslik;
        $govde = $konu . "\n\n" . $metin . "\n\nProje notları: " . rtrim((string) \App\Core\Env::get('APP_URL', ''), '/') . '/surumler';
        $eposta = 0;
        foreach ($alicilar as $kisi) {
            self::yaz($kisi['id'], 'surum', $konu, $metin, '/surumler');
            if ($duyuru['zorunlu'] || $kisi['surum_eposta']) {
                Eposta::kuyruga($kisi['id'], $kisi['email'], $konu, $govde);
                $eposta++;
            }
        }
        Database::query(
            'INSERT INTO surum_duyurulari (surum, baslik, metin, zorunlu, gonderen_id, alici_sayisi, eposta_sayisi)
             VALUES (:s, :b, :m, :z, :g, :a, :e)',
            [
                's' => $surum,
                'b' => mb_substr($baslik, 0, 200),
                'm' => $metin,
                'z' => $duyuru['zorunlu'] ? 'true' : 'false',
                'g' => $duyuru['gonderen_id'],
                'a' => count($alicilar),
                'e' => $eposta,
            ]
        );
        Eposta::isle(40);

        return ['alici' => count($alicilar), 'eposta' => $eposta];
    }
}
