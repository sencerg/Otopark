<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Sayfa / işlem izin kataloğu ve profil yönetimi.
 * İzin kodları menü ve route kontrollerinde kullanılır.
 */
final class Yetki
{
    /** @return list<array{grup:string,izinler:list<array{kod:string,ad:string,aciklama?:string}}}> */
    public static function katalog(): array
    {
        return [
            [
                'grup' => 'Genel',
                'izinler' => [
                    ['kod' => 'menu.dashboard', 'ad' => 'Ana dashboard', 'aciklama' => 'Saha operasyon ana ekranı'],
                    ['kod' => 'menu.saha_dashboard', 'ad' => 'Araç yönetimi dashboard'],
                    ['kod' => 'menu.arama', 'ad' => 'Üst arama (şasi / plaka)'],
                    ['kod' => 'menu.hizli_arac', 'ad' => 'Hızlı araç ekle'],
                    ['kod' => 'menu.hizli_maliyet', 'ad' => 'Hızlı maliyet ekle'],
                ],
            ],
            [
                'grup' => 'Araç ve stok',
                'izinler' => [
                    ['kod' => 'menu.stok', 'ad' => 'Stoktaki araçlar'],
                    ['kod' => 'menu.cikis', 'ad' => 'Stoktan çıkanlar'],
                    ['kod' => 'menu.araclar', 'ad' => 'Tüm araçlar'],
                    ['kod' => 'arac.goruntule', 'ad' => 'Araç detay görüntüle'],
                    ['kod' => 'arac.ekle', 'ad' => 'Araç ekle / düzenle'],
                    ['kod' => 'arac.sil', 'ad' => 'Araç sil / arşivle'],
                    ['kod' => 'hareket.goruntule', 'ad' => 'Hareket görüntüle'],
                    ['kod' => 'hareket.duzenle', 'ad' => 'Hareket düzenle'],
                    ['kod' => 'hareket.stoktan_cikar', 'ad' => 'Stoktan çıkar'],
                    ['kod' => 'menu.toplu', 'ad' => 'Toplu işlemler (Excel)'],
                    ['kod' => 'menu.sayim', 'ad' => 'Araç sayımları'],
                    ['kod' => 'menu.is_takibi', 'ad' => 'İş takibi / ek maliyetler'],
                    ['kod' => 'maliyet.yonet', 'ad' => 'Ek maliyet ekle / düzenle / sil'],
                ],
            ],
            [
                'grup' => 'Detaylı Araç Ekle',
                'izinler' => [
                    ['kod' => 'menu.detayli_arac', 'ad' => 'Detaylı araç ekle', 'aciklama' => 'Kontrol + koşullar sonrası detaylı araç ekleme'],
                ],
            ],
            [
                'grup' => 'Raporlar',
                'izinler' => [
                    ['kod' => 'menu.raporlar', 'ad' => 'Raporlar ana sayfa', 'aciklama' => 'Menü: Raporlar hub'],
                    ['kod' => 'menu.depolama_raporu', 'ad' => 'Depolama raporu', 'aciklama' => 'Interaktif'],
                    ['kod' => 'menu.ek_hizmet_raporu', 'ad' => 'Ek hizmet raporu', 'aciklama' => 'Interaktif'],
                    ['kod' => 'menu.stok_ozet_raporu', 'ad' => 'Stok özet raporu', 'aciklama' => 'Seçerek hazırla'],
                    ['kod' => 'menu.giris_cikis_raporu', 'ad' => 'Giriş–çıkış özeti', 'aciklama' => 'Seçerek hazırla'],
                ],
            ],
            [
                'grup' => 'İş emri',
                'izinler' => [
                    ['kod' => 'menu.is_emri', 'ad' => 'İş emri listesi'],
                    ['kod' => 'is_emri.ekle', 'ad' => 'İş emri oluştur / düzenle'],
                ],
            ],
            [
                'grup' => 'İK ve Satın Alma Yönetimi',
                'izinler' => [
                    ['kod' => 'menu.ik_dashboard', 'ad' => 'İK dashboard', 'aciklama' => 'Menü: İK ve Satın Alma → Dashboard'],
                    ['kod' => 'menu.destek', 'ad' => 'Destek talepleri', 'aciklama' => 'Menü: İK ve Satın Alma → Destek Talepleri'],
                ],
            ],
            [
                'grup' => 'Tanımlar',
                'izinler' => [
                    ['kod' => 'menu.tanimlar', 'ad' => 'Araç tanımları'],
                    ['kod' => 'menu.tanimlamalar', 'ad' => 'Tanımlamalar (müşteri / otopark / hizmet)'],
                    ['kod' => 'tanimlama.yaz', 'ad' => 'Tanımlama kaydet / sil'],
                ],
            ],
            [
                'grup' => 'Yetki ve kullanıcı',
                'izinler' => [
                    ['kod' => 'menu.yetki', 'ad' => 'Yetki yönetimi sayfası'],
                    ['kod' => 'yetki.yonet', 'ad' => 'Profil ve izin düzenle'],
                    ['kod' => 'kullanici.yonet', 'ad' => 'Kullanıcı oluştur / düzenle'],
                ],
            ],
            [
                'grup' => 'Proje Notları',
                'izinler' => [
                    ['kod' => 'menu.surumler', 'ad' => 'Sürüm geçmişi', 'aciklama' => 'Menü: Proje Notları'],
                ],
            ],
            [
                'grup' => 'Müşteri portalı',
                'izinler' => [
                    ['kod' => 'musteri.dashboard', 'ad' => 'Portal — araçlarım'],
                    ['kod' => 'musteri.stok', 'ad' => 'Portal — stoktaki araçlar'],
                    ['kod' => 'musteri.cikanlar', 'ad' => 'Portal — stoktan çıkanlar'],
                    ['kod' => 'musteri.hizmetler', 'ad' => 'Portal — aldığım hizmetler'],
                    ['kod' => 'musteri.detay', 'ad' => 'Portal — araç detay'],
                    ['kod' => 'musteri.excel', 'ad' => 'Portal — Excel indir'],
                ],
            ],
        ];
    }

    /** @return list<string> */
    public static function tumKodlar(): array
    {
        $kodlar = [];
        foreach (self::katalog() as $grup) {
            foreach ($grup['izinler'] as $izin) {
                $kodlar[] = $izin['kod'];
            }
        }

        return $kodlar;
    }

    /** Tip bazlı varsayılan izin setleri (sistem profilleri için). */
    public static function varsayilanKodlar(string $tip, string $profilAd = ''): array
    {
        $tum = self::tumKodlar();
        $musteri = array_values(array_filter($tum, static fn ($k) => str_starts_with($k, 'musteri.')));

        return match (true) {
            $tip === 'admin' => $tum,
            $tip === 'musteri' => $musteri,
            $tip === 'bayi' && self::otoparkPersonelMi($profilAd) => [
                'menu.dashboard', 'menu.saha_dashboard', 'menu.arama',
                'menu.stok', 'menu.cikis', 'menu.araclar',
                'arac.goruntule', 'hareket.goruntule', 'hareket.stoktan_cikar',
                'menu.is_takibi',
                'menu.detayli_arac',
                'menu.raporlar', 'menu.depolama_raporu', 'menu.stok_ozet_raporu', 'menu.giris_cikis_raporu',
            ],
            // Bayi / otopark: İK / destek / yetki yönetimi yok; detaylı araç ekleme sahada yapılır
            $tip === 'bayi' => array_values(array_filter(
                $tum,
                static fn ($k) => !str_starts_with($k, 'musteri.')
                    && !in_array($k, [
                        'menu.yetki', 'yetki.yonet', 'kullanici.yonet',
                        'menu.tanimlamalar', 'tanimlama.yaz',
                        'menu.ik_dashboard', 'menu.destek',
                    ], true)
            )),
            default => [],
        };
    }

    /** Boş sistem profillerine varsayılan izinleri yazar. */
    public static function varsayilanlariDoldur(): void
    {
        $profiller = Database::fetchAll('SELECT id, ad, tip FROM yetki_profilleri WHERE sistem_mi');
        foreach ($profiller as $p) {
            $var = (int) Database::fetch(
                'SELECT COUNT(*) c FROM yetki_profil_izinleri WHERE profil_id = :id',
                ['id' => $p['id']]
            )['c'];
            if ($var > 0) {
                continue;
            }
            self::izinleriKaydet((int) $p['id'], self::varsayilanKodlar($p['tip'], $p['ad']));
        }
        self::kullanicilariProfilleEslestir();
    }

    /** Rol / kapsama göre profili olmayan kullanıcılara atar. */
    public static function kullanicilariProfilleEslestir(): void
    {
        $map = [];
        foreach (Database::fetchAll('SELECT id, ad, tip FROM yetki_profilleri WHERE sistem_mi AND aktif') as $p) {
            $map[$p['tip'] . ':' . mb_strtolower($p['ad'])] = (int) $p['id'];
            if (!isset($map[$p['tip']])) {
                $map[$p['tip']] = (int) $p['id'];
            }
        }
        $yonetici = null;
        $bayiGenel = null;
        $bayiKisisel = null;
        $musteri = null;
        foreach (Database::fetchAll('SELECT id, ad, tip FROM yetki_profilleri WHERE sistem_mi') as $p) {
            $ad = mb_strtolower($p['ad']);
            if ($p['tip'] === 'admin') {
                $yonetici = (int) $p['id'];
            } elseif ($p['tip'] === 'musteri') {
                $musteri = (int) $p['id'];
            } elseif ($p['tip'] === 'bayi' && self::otoparkPersonelMi($p['ad'])) {
                $bayiKisisel = (int) $p['id'];
            } elseif ($p['tip'] === 'bayi') {
                $bayiGenel = (int) $p['id'];
            }
        }

        foreach (Database::fetchAll('SELECT id, role, bayi_kapsam, yetki_profil_id FROM users') as $u) {
            if (!empty($u['yetki_profil_id'])) {
                continue;
            }
            $profil = match ($u['role']) {
                'admin' => $yonetici,
                'musteri' => $musteri,
                'bayi' => (($u['bayi_kapsam'] ?? 'genel') === 'kisisel' ? $bayiKisisel : $bayiGenel) ?? $bayiGenel,
                default => $bayiGenel,
            };
            if (!$profil) {
                continue;
            }
            $kapsam = $u['role'] === 'bayi' ? ($u['bayi_kapsam'] ?: 'genel') : null;
            Database::query(
                'UPDATE users SET yetki_profil_id = :p, bayi_kapsam = :k WHERE id = :id',
                ['p' => $profil, 'k' => $kapsam, 'id' => $u['id']]
            );
        }
    }

    /** Otopark personel (eski: Bayi Kişisel) profili mi? */
    public static function otoparkPersonelMi(string $profilAd): bool
    {
        $ad = mb_strtolower($profilAd);

        return str_contains($ad, 'kişisel')
            || str_contains($ad, 'kisisel')
            || str_contains($ad, 'personel');
    }

    /** @param list<string> $kodlar */
    public static function izinleriKaydet(int $profilId, array $kodlar): void
    {
        $gecerli = array_flip(self::tumKodlar());
        $kodlar = array_values(array_unique(array_filter(
            $kodlar,
            static fn ($k) => is_string($k) && isset($gecerli[$k])
        )));
        Database::query('DELETE FROM yetki_profil_izinleri WHERE profil_id = :id', ['id' => $profilId]);
        foreach ($kodlar as $kod) {
            Database::query(
                'INSERT INTO yetki_profil_izinleri (profil_id, izin_kodu) VALUES (:p, :k) ON CONFLICT DO NOTHING',
                ['p' => $profilId, 'k' => $kod]
            );
        }
        Database::query('UPDATE yetki_profilleri SET updated_at = NOW() WHERE id = :id', ['id' => $profilId]);
    }

    /** @return list<string> */
    public static function profilIzinleri(int $profilId): array
    {
        return array_column(
            Database::fetchAll(
                'SELECT izin_kodu FROM yetki_profil_izinleri WHERE profil_id = :id ORDER BY izin_kodu',
                ['id' => $profilId]
            ),
            'izin_kodu'
        );
    }

    /** @return list<array<string,mixed>> */
    public static function profiller(?string $tip = null): array
    {
        $sql = 'SELECT p.*, (SELECT COUNT(*) FROM yetki_profil_izinleri i WHERE i.profil_id = p.id) AS izin_sayisi,
                       (SELECT COUNT(*) FROM users u WHERE u.yetki_profil_id = p.id) AS kullanici_sayisi
                FROM yetki_profilleri p WHERE p.aktif';
        $params = [];
        if ($tip) {
            $sql .= ' AND p.tip = :t';
            $params['t'] = $tip;
        }

        return Database::fetchAll($sql . ' ORDER BY p.sistem_mi DESC, p.tip, p.ad', $params);
    }
}
