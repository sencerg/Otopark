<?php

declare(strict_types=1);

use App\Core\Database;

require dirname(__DIR__) . '/bootstrap.php';

$withDemo = !in_array('--no-demo', $argv, true);
$t = json_decode(file_get_contents(BASE_PATH . '/database/seeds/tanimlar.json'), true, 512, JSON_THROW_ON_ERROR);

$insertNames = static function (string $table, array $names, string $column = 'ad'): void {
    foreach ($names as $name) {
        Database::query("INSERT INTO {$table} ({$column}) VALUES (:v) ON CONFLICT DO NOTHING", ['v' => $name]);
    }
};
$idMap = static fn (string $table, string $column = 'ad'): array
    => array_column(Database::fetchAll("SELECT id, {$column} AS k FROM {$table}"), 'id', 'k');

Database::transaction(function () use ($t, $insertNames, $idMap) {
    $insertNames('kullanici_gruplari', $t['kullanici_gruplari']);
    foreach ($t['bayiler'] as [$ad, $sehir]) {
        Database::query('INSERT INTO bayiler (ad, sehir) VALUES (:a, :s) ON CONFLICT DO NOTHING', ['a' => $ad, 's' => $sehir]);
    }
    if (!Database::fetch('SELECT 1 FROM musteriler LIMIT 1')) {
        $insertNames('musteriler', $t['musteriler']);
    }
    foreach (['departmanlar', 'arac_tipleri', 'kasa_tipleri', 'renkler', 'yakit_tipleri', 'vites_tipleri', 'markalar', 'envanterler', 'hareket_nedenleri'] as $table) {
        $insertNames($table, $t[$table]);
    }
    $insertNames('model_yillari', $t['model_yillari'], 'yil');

    $markalar = $idMap('markalar');
    foreach ($t['seriler'] as $marka => $seriler) {
        if (!isset($markalar[$marka])) {
            continue;
        }
        foreach ($seriler as $seri) {
            Database::query('INSERT INTO seriler (marka_id, ad) VALUES (:m, :a) ON CONFLICT DO NOTHING', ['m' => $markalar[$marka], 'a' => $seri]);
        }
    }
    foreach ($t['donanimlar'] as [$grup, $ad]) {
        Database::query('INSERT INTO donanimlar (grup, ad) VALUES (:g, :a) ON CONFLICT DO NOTHING', ['g' => $grup, 'a' => $ad]);
    }
    foreach ($t['maliyet_tipleri'] as [$ad, $tutar]) {
        Database::query('INSERT INTO maliyet_tipleri (ad, varsayilan_tutar) VALUES (:a, :t) ON CONFLICT DO NOTHING', ['a' => $ad, 't' => $tutar]);
    }

    $grup = $idMap('kullanici_gruplari');
    $bayi = $idMap('bayiler');
    $users = [
        ['Yönetici', 'admin@otopark.local', 'admin123', 'admin', $grup['Yönetici'], null],
        ['Ankara Bayi', 'ankara@otopark.local', 'ankara123', 'bayi', $grup['Bayi Grubu'], $bayi['BİA ANKARA']],
    ];
    foreach ($users as [$name, $email, $pass, $role, $grupId, $bayiId]) {
        Database::query(
            'INSERT INTO users (name, email, password_hash, role, kullanici_grubu_id, bayi_id)
             VALUES (:n, :e, :h, :r, :g, :b) ON CONFLICT (email) DO NOTHING',
            ['n' => $name, 'e' => $email, 'h' => password_hash($pass, PASSWORD_DEFAULT), 'r' => $role, 'g' => $grupId, 'b' => $bayiId]
        );
    }
});

echo "Tanımlar ve kullanıcılar hazır.\n";
echo "  admin@otopark.local / admin123  (tüm lokasyonlar)\n";
echo "  ankara@otopark.local / ankara123 (sadece BİA ANKARA)\n";

if (!$withDemo || Database::fetch('SELECT 1 FROM araclar LIMIT 1')) {
    exit(0);
}

mt_srand(42);
$pick = static fn (array $a) => $a[array_rand($a)];
$ids = static fn (string $table) => array_map('intval', array_column(Database::fetchAll("SELECT id FROM {$table} ORDER BY id"), 'id'));

Database::transaction(function () use ($pick, $ids, $idMap) {
    $bayi = $idMap('bayiler');
    $ankara = $bayi['BİA ANKARA'];
    $musteri = $idMap('musteriler');
    $maliyet = $idMap('maliyet_tipleri');
    $departman = $idMap('departmanlar');
    $admin = (int) Database::fetch("SELECT id FROM users WHERE email = 'admin@otopark.local'")['id'];

    $personeller = ['Ahmet Yılmaz', 'Mehmet Kaya', 'Ayşe Demir', 'Fatma Çelik', 'Mustafa Şahin', 'Emre Aydın', 'Burak Öztürk', 'Zeynep Arslan', 'Can Doğan', 'Elif Koç'];
    foreach ($personeller as $i => $ad) {
        Database::query('INSERT INTO personeller (ad_soyad, departman_id, bayi_id) VALUES (:a, :d, :b)', [
            'a' => $ad, 'd' => $departman[$i < 7 ? 'SAHA' : 'İDARİ İŞLER VE SATIN ALMA'], 'b' => $ankara,
        ]);
    }
    $personelIds = $ids('personeller');

    $firmaAgirlik = [
        'DOĞUŞ OTO (FİLO 0 ARAÇLAR)' => 60, 'ÇETAŞ OTOMOTİV (RENAULT)' => 10, 'GÜRSES' => 6, 'ÇETAŞ OTOMOTİV (PSA)' => 5,
        'NASCAR KİRALAMA' => 3, 'TEB ARVAL' => 5, 'Borusan Next' => 4, 'RENT GO' => 4, 'OTOKOÇ BURSA' => 3,
    ];
    $firmaHavuzu = [];
    foreach ($firmaAgirlik as $ad => $w) {
        $firmaHavuzu = array_merge($firmaHavuzu, array_fill(0, $w, $musteri[$ad]));
    }

    foreach (array_unique($firmaHavuzu) as $mId) {
        foreach ([1 => 95, 2 => 120, 3 => 180] as $aracTipi => $fiyat) {
            Database::query('INSERT INTO depolama_fiyatlari (musteri_id, bayi_id, arac_tipi_id, gunluk_fiyat) VALUES (:m, :b, :t, :f)', [
                'm' => $mId, 'b' => $ankara, 't' => $aracTipi, 'f' => $fiyat,
            ]);
        }
    }

    $seriler = Database::fetchAll('SELECT s.id, s.marka_id FROM seriler s');
    $renkler = array_slice($ids('renkler'), 0, 19);
    $yillar = array_column(Database::fetchAll('SELECT id FROM model_yillari WHERE yil >= 2022'), 'id');
    $envanterler = $ids('envanterler');
    $hizmetler = [
        $maliyet['Araç Dış Yıkama Hizmeti'] => 360, $maliyet['Araç Hazırlık'] => 560,
        $maliyet['Bant Altı Temizlik'] => 250, $maliyet['ARAÇ TAŞIMA'] => 1500,
    ];
    $teslimAlma = $maliyet['Araç Teslim Alma/Etme (Standart)'];
    $harfler = 'ABCDEFGHJKLMNPRSTUVWXYZ0123456789';

    for ($i = 1; $i <= 220; $i++) {
        $sase = 'VF1' . substr(str_shuffle(str_repeat($harfler, 3)), 0, 14);
        $seri = $pick($seriler);
        $mId = $pick($firmaHavuzu);
        $giris = (new DateTimeImmutable('today'))->modify('-' . mt_rand(0, 90) . ' days')->setTime(mt_rand(8, 18), mt_rand(0, 59));
        $cikti = mt_rand(1, 100) <= 35 && $giris < new DateTimeImmutable('-2 days');
        $cikis = $cikti ? $giris->modify('+' . mt_rand(1, max(1, (int) $giris->diff(new DateTimeImmutable())->days)) . ' days') : null;
        if ($cikis && $cikis > new DateTimeImmutable()) {
            $cikis = new DateTimeImmutable('-1 hour');
        }
        $plaka = '06 ' . chr(65 + mt_rand(0, 25)) . chr(65 + mt_rand(0, 25)) . ' ' . mt_rand(100, 999);

        $aracId = (int) Database::fetch(
            'INSERT INTO araclar (sase, plaka, arac_durumu, arac_tipi_id, kasa_tipi_id, renk_id, marka_id, seri_id, model_yili_id,
                yakit_tipi_id, vites_tipi_id, km, yakit_durumu, musteri_id, bayi_id, lokasyon_turu, lokasyon_detay,
                stokta, stoga_giris_tarihi, stoktan_cikis_tarihi, olusturan_id)
             VALUES (:sase, :plaka, :durum, :tip, :kasa, :renk, :marka, :seri, :yil, :yakit, :vites, :km, :yd, :m, :b, :lt, :ld,
                :stokta, :giris, :cikis, :u) RETURNING id',
            [
                'sase' => $sase, 'plaka' => $plaka, 'durum' => mt_rand(1, 10) <= 7 ? 1 : 2, 'tip' => mt_rand(1, 10) <= 8 ? 1 : 2,
                'kasa' => mt_rand(1, 5), 'renk' => $pick($renkler), 'marka' => $seri['marka_id'], 'seri' => $seri['id'],
                'yil' => $pick($yillar), 'yakit' => mt_rand(1, 5), 'vites' => mt_rand(1, 2), 'km' => mt_rand(5, 60000),
                'yd' => mt_rand(10, 100), 'm' => $mId, 'b' => $ankara, 'lt' => mt_rand(1, 2), 'ld' => 'Blok ' . chr(65 + mt_rand(0, 4)) . '-' . mt_rand(1, 40),
                'stokta' => $cikti ? 'false' : 'true', 'giris' => $giris->format('c'), 'cikis' => $cikis?->format('c'), 'u' => $admin,
            ]
        )['id'];

        foreach (array_rand(array_flip($envanterler), mt_rand(3, 7)) as $envId) {
            Database::query('INSERT INTO arac_envanterleri VALUES (:a, :e)', ['a' => $aracId, 'e' => $envId]);
        }

        Database::query(
            'INSERT INTO arac_hareketleri (arac_id, hareket_tipi, hareket_nedeni_id, musteri_id, bayi_id, lokasyon_turu, teslim_eden,
                teslim_alan_personel_id, hareket_tarihi, kullanici_id)
             VALUES (:a, 1, 1, :m, :b, 1, :te, :p, :t, :u)',
            ['a' => $aracId, 'm' => $mId, 'b' => $ankara, 'te' => 'Sevkiyat Şoförü', 'p' => $pick($personelIds), 't' => $giris->format('c'), 'u' => $admin]
        );

        $ekstre = static function (int $tipId, float $tutar, DateTimeImmutable $tarih) use ($aracId, $mId, $ankara, $admin): void {
            Database::query(
                'INSERT INTO arac_ekstreleri (arac_id, maliyet_tipi_id, musteri_id, bayi_id, tutar, islem_tarihi, kullanici_id, created_at)
                 VALUES (:a, :t, :m, :b, :tu, :d, :u, :c)',
                ['a' => $aracId, 't' => $tipId, 'm' => $mId, 'b' => $ankara, 'tu' => $tutar, 'd' => $tarih->format('Y-m-d'), 'u' => $admin, 'c' => $tarih->format('c')]
            );
        };
        $ekstre($teslimAlma, 135, $giris);
        foreach ($hizmetler as $tipId => $tutar) {
            if (mt_rand(1, 100) <= ($tutar > 1000 ? 8 : 45)) {
                $ekstre($tipId, $tutar, $giris->modify('+' . mt_rand(0, 3) . ' days'));
            }
        }

        if ($cikis) {
            Database::query(
                'INSERT INTO arac_hareketleri (arac_id, hareket_tipi, hareket_nedeni_id, musteri_id, bayi_id, teslim_alan, sevkiyat_tipi,
                    sevkiyat_durumu, hareket_tarihi, kullanici_id)
                 VALUES (:a, 2, :n, :m, :b, :ta, :st, 3, :t, :u)',
                ['a' => $aracId, 'n' => mt_rand(1, 3), 'm' => $mId, 'b' => $ankara, 'ta' => 'Müşteri Temsilcisi', 'st' => mt_rand(1, 3), 't' => $cikis->format('c'), 'u' => $admin]
            );
            $ekstre($teslimAlma, 135, $cikis);
        }
    }

    $stoktakiler = array_column(Database::fetchAll('SELECT id FROM araclar WHERE stokta ORDER BY random() LIMIT 40'), 'id');
    for ($i = 1; $i <= 18; $i++) {
        $durum = $pick([1, 1, 2, 3, 3, 4, 4, 4]);
        $talep = (new DateTimeImmutable())->modify('-' . mt_rand(0, 30) . ' days');
        $isEmriId = (int) Database::fetch(
            'INSERT INTO is_emirleri (kod, maliyet_tipi_id, musteri_id, talep_tarihi, istenen_tarih, durum, tamamlanma_tarihi, detaylar, olusturan_id)
             VALUES (:k, :t, :m, :tt, :it, :d, :tm, :det, :u) RETURNING id',
            [
                'k' => sprintf('IE-%s-%05d', date('Y'), $i), 't' => $pick(array_keys($hizmetler)), 'm' => $pick($firmaHavuzu),
                'tt' => $talep->format('Y-m-d'), 'it' => $talep->modify('+3 days')->format('Y-m-d'), 'd' => $durum,
                'tm' => $durum === 4 ? $talep->modify('+2 days')->format('Y-m-d') : null, 'det' => 'Müşteri talebi doğrultusunda araç hazırlığı.', 'u' => $admin,
            ]
        )['id'];
        foreach (array_slice($stoktakiler, ($i * 2) % 38, mt_rand(1, 3)) as $aracId) {
            Database::query('INSERT INTO is_emri_araclari (is_emri_id, arac_id, durum) VALUES (:i, :a, :d) ON CONFLICT DO NOTHING', ['i' => $isEmriId, 'a' => $aracId, 'd' => min($durum, 4)]);
        }
        Database::query('INSERT INTO is_emri_departmanlari VALUES (:i, :d)', ['i' => $isEmriId, 'd' => $departman['SAHA']]);
    }

    foreach ([['Kapalı otopark bakım çalışması', 'Cumartesi 08:00-12:00 arası B blok kapalı olacaktır.', 'duyuru'],
              ['Aylık sayım', 'Ay sonu stok sayımı için tüm araçların QR etiketleri kontrol edilmelidir.', 'hatirlatma']] as [$b, $ic, $tur]) {
        Database::query('INSERT INTO duyurular (baslik, icerik, tur) VALUES (:b, :i, :t)', ['b' => $b, 'i' => $ic, 't' => $tur]);
    }

    Database::query("INSERT INTO destek_talepleri (kod, konu, tur, durum, aciklama, talep_eden_id) VALUES
        ('DT-00001', 'QR etiket yazıcısı çalışmıyor', 1, 2, 'Saha ofisindeki etiket yazıcısı bağlantı hatası veriyor.', :u),
        ('DT-00002', 'Yeni müşteri tanımı', 3, 1, 'Yeni filo müşterisi için depolama fiyatı girilmesi gerekiyor.', :u)", ['u' => $admin]);
});

$ozet = Database::fetch('SELECT
    (SELECT COUNT(*) FROM araclar) AS arac,
    (SELECT COUNT(*) FROM araclar WHERE stokta) AS stok,
    (SELECT COUNT(*) FROM arac_hareketleri) AS hareket,
    (SELECT COUNT(*) FROM arac_ekstreleri) AS ekstre,
    (SELECT COUNT(*) FROM is_emirleri) AS is_emri');
echo "Demo veri: {$ozet['arac']} araç ({$ozet['stok']} stokta), {$ozet['hareket']} hareket, {$ozet['ekstre']} maliyet kaydı, {$ozet['is_emri']} iş emri.\n";
