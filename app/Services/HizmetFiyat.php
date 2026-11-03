<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

/**
 * Hizmet ücreti: müşteri × otopark (bayi) × hizmet.
 * Genel / varsayılan fiyat yoktur.
 *
 * maliyet_tipleri.bayi_fiyat_degistirebilir = true ise bayi müşteri+otopark
 * fiyatını uygulama anında değiştirebilir; aksi halde katalog tutarı kilitlidir.
 */
final class HizmetFiyat
{
    public static function tanimli(?int $musteriId, int $maliyetTipiId, ?int $bayiId = null): bool
    {
        if (!$musteriId || !$bayiId) {
            return false;
        }

        return Database::fetch(
            'SELECT 1 FROM hizmet_fiyatlari
             WHERE musteri_id = :m AND maliyet_tipi_id = :t AND bayi_id = :b',
            ['m' => $musteriId, 't' => $maliyetTipiId, 'b' => $bayiId]
        ) !== null;
    }

    /**
     * Firmaya (müşteri × otopark) fiyatı tanımlanmış aktif hizmetler.
     * Tanımsız hizmet araç ekstresinde listelenmez.
     *
     * @return list<array{id:int,ad:string,bayi_fiyat_degistirebilir:mixed,tekrar_eklenebilir:mixed}>
     */
    public static function firmaHizmetleri(?int $musteriId, ?int $bayiId = null): array
    {
        if (!$musteriId || !$bayiId) {
            return [];
        }

        return Database::fetchAll(
            'SELECT mt.id, mt.ad, mt.bayi_fiyat_degistirebilir, mt.tekrar_eklenebilir
             FROM maliyet_tipleri mt
             INNER JOIN hizmet_fiyatlari f
                ON f.maliyet_tipi_id = mt.id AND f.musteri_id = :m AND f.bayi_id = :b
             WHERE mt.aktif
             ORDER BY mt.ad',
            ['m' => $musteriId, 'b' => $bayiId]
        );
    }

    /** @return ?array{musteri_fiyat:float,bayi_fiyat:float} */
    public static function cift(?int $musteriId, int $maliyetTipiId, ?int $bayiId = null): ?array
    {
        if (!$musteriId || !$bayiId) {
            return null;
        }
        $row = Database::fetch(
            'SELECT musteri_fiyat, bayi_fiyat, tutar FROM hizmet_fiyatlari
             WHERE musteri_id = :m AND maliyet_tipi_id = :t AND bayi_id = :b',
            ['m' => $musteriId, 't' => $maliyetTipiId, 'b' => $bayiId]
        );
        if (!$row) {
            return null;
        }
        $musteri = $row['musteri_fiyat'] !== null ? (float) $row['musteri_fiyat'] : (float) $row['tutar'];
        $bayi = $row['bayi_fiyat'] !== null ? (float) $row['bayi_fiyat'] : $musteri;

        return [
            'musteri_fiyat' => round($musteri, 2),
            'bayi_fiyat' => round($bayi, 2),
        ];
    }

    /** Moex'in müşteriye kestiği katalog fiyatı. */
    public static function tutar(?int $musteriId, int $maliyetTipiId, ?int $bayiId = null): float
    {
        return self::cift($musteriId, $maliyetTipiId, $bayiId)['musteri_fiyat'] ?? 0.0;
    }

    /** Otopark anlaşma fiyatı. */
    public static function bayiTutar(?int $musteriId, int $maliyetTipiId, ?int $bayiId = null): float
    {
        return self::cift($musteriId, $maliyetTipiId, $bayiId)['bayi_fiyat'] ?? 0.0;
    }

    public static function sayi(mixed $v): ?float
    {
        if ($v === null || $v === '' || !is_numeric($v)) {
            return null;
        }
        $n = round((float) $v, 2);

        return $n < 0 ? null : $n;
    }

    /**
     * Satıra yazılacak iki tutar.
     * Müşteri tutarı Moex kataloğudur; bayi bunu değiştiremez.
     * Bayi tutarı anlaşma fiyatıdır; yalnızca bayi fiyatı değişebilir işaretliyse bayi kendi tutarını yazar.
     *
     * @param array{musteri?:mixed,bayi?:mixed,tek?:mixed,ayri?:bool} $gelen
     * @return array{musteri:float,bayi:float}
     */
    public static function satirTutarlari(
        ?int $musteriId,
        int $maliyetTipiId,
        array $gelen,
        bool $admin,
        ?int $bayiId,
        bool $tekrarOzel
    ): array {
        $katalogM = self::tutar($musteriId, $maliyetTipiId, $bayiId);
        $katalogB = self::bayiTutar($musteriId, $maliyetTipiId, $bayiId);
        $ayri = !empty($gelen['ayri']);
        $hamM = self::sayi($gelen['musteri'] ?? null);
        $hamB = self::sayi($gelen['bayi'] ?? null);
        $hamTek = self::sayi($gelen['tek'] ?? null);
        if (!$ayri) {
            if ($admin) {
                $hamM = $hamTek;
                if ($tekrarOzel) {
                    $hamB = $hamTek;
                }
            } else {
                $hamB = $hamTek;
            }
        }
        $deg = self::bayiDegistirebilir($maliyetTipiId);

        if ($tekrarOzel) {
            if ($hamM === null || $hamM <= 0) {
                throw new \RuntimeException('Ek kayıt için müşteri fiyatı giriniz.');
            }

            return ['musteri' => $hamM, 'bayi' => $hamB ?? $hamM];
        }

        $musteri = $katalogM;
        if ($admin && $hamM !== null) {
            $musteri = $hamM;
        }
        $bayi = $katalogB;
        if (($admin || $deg) && $hamB !== null) {
            $bayi = $hamB;
        }
        if (!$admin && !$deg && $hamB !== null && abs($hamB - $katalogB) > 0.009) {
            throw new \RuntimeException('Bu hizmetin bayi fiyatı kilitlidir; tutar değiştirilemez.');
        }

        return ['musteri' => $musteri, 'bayi' => $bayi];
    }

    public static function bayiDegistirebilir(int $maliyetTipiId): bool
    {
        $row = Database::fetch(
            'SELECT bayi_fiyat_degistirebilir FROM maliyet_tipleri WHERE id = :id',
            ['id' => $maliyetTipiId]
        );

        return $row ? (bool) $row['bayi_fiyat_degistirebilir'] : false;
    }

    /** false = bayi aynı araca yalnızca 1 kez ekler; admin 2+ kez özel fiyatla ekleyebilir. */
    public static function tekrarEklenebilir(int $maliyetTipiId): bool
    {
        $row = Database::fetch(
            'SELECT tekrar_eklenebilir FROM maliyet_tipleri WHERE id = :id',
            ['id' => $maliyetTipiId]
        );

        return $row ? (bool) $row['tekrar_eklenebilir'] : true;
    }

    public static function aracHizmetAdedi(int $aracId, int $maliyetTipiId): int
    {
        return (int) (Database::fetch(
            'SELECT COUNT(*)::int AS c FROM arac_ekstreleri
             WHERE arac_id = :a AND maliyet_tipi_id = :t',
            ['a' => $aracId, 't' => $maliyetTipiId]
        )['c'] ?? 0);
    }

    /**
     * Bayi ekranında tutar metni.
     * Kilitli → "Anlaşmalı Fiyat"; değiştirilebilir → "Özel fiyat: X ₺".
     */
    public static function bayiTutarMetni(bool $degistirebilir, mixed $tutar = null): string
    {
        if (!$degistirebilir) {
            return 'Anlaşmalı Fiyat';
        }
        if ($tutar === null || $tutar === '') {
            return 'Özel fiyat';
        }

        return 'Özel fiyat: ' . number_format((float) $tutar, 2, ',', '.') . ' ₺';
    }

    /** HTML badge / düz metin (liste hücreleri). */
    public static function bayiTutarHtml(bool $degistirebilir, mixed $tutar = null): string
    {
        if (!$degistirebilir) {
            return '<span class="badge badge-soft-secondary">Anlaşmalı Fiyat</span>';
        }

        return '<span class="text-nowrap"><span class="badge badge-soft-info me-1">Özel fiyat</span>'
            . number_format((float) ($tutar ?? 0), 2, ',', '.') . ' ₺</span>';
    }

    /**
     * Liste satırı: müşteri tutarı, bayi tutarı ve kâr birbirine karışmaz.
     * Bayi yanıtında müşteri tutarı yoktur.
     *
     * @param array<string,mixed> $r
     * @return array<string,mixed>
     */
    public static function gorunenEkstre(array $r): array
    {
        $musteri = round((float) ($r['tutar'] ?? 0), 2);
        $bayi = round((float) ($r['bayi_tutar'] ?? $musteri), 2);
        if (Auth::isAdmin()) {
            $r['tutar'] = $musteri;
            $r['bayi_tutar'] = $bayi;
            $r['kar'] = round($musteri - $bayi, 2);

            return $r;
        }
        if (Auth::fiyatMaskeli()) {
            $r['tutar'] = $bayi;
            $r['tutar_etiket'] = number_format($bayi, 2, ',', '.') . ' ₺';
            unset($r['bayi_tutar'], $r['kar']);

            return $r;
        }
        $r['tutar'] = $musteri;
        unset($r['bayi_tutar'], $r['kar']);

        return $r;
    }

    /** Bayi ara/genel toplam metni (adet + özel tutar). */
    public static function bayiToplamMetni(int $anlasmaliAdet, float $ozelTutar): string
    {
        $parca = [];
        if ($anlasmaliAdet > 0) {
            $parca[] = $anlasmaliAdet === 1 ? 'Anlaşmalı Fiyat' : ($anlasmaliAdet . ' × Anlaşmalı Fiyat');
        }
        if ($ozelTutar > 0) {
            $parca[] = 'Özel: ' . number_format($ozelTutar, 2, ',', '.') . ' ₺';
        }

        return $parca !== [] ? implode(' + ', $parca) : '—';
    }

    /**
     * Bayi toplam özeti: kilitli satırlar + özel tutar toplamı.
     * @param list<array{bayi_fiyat_degistirebilir?:mixed,tutars?:mixed,tutar?:mixed}> $satirlar
     */
    public static function bayiToplamOzeti(array $satirlar): string
    {
        $anlasmali = 0;
        $ozel = 0.0;
        foreach ($satirlar as $s) {
            $deg = !empty($s['bayi_fiyat_degistirebilir']);
            if ($deg) {
                $ozel += (float) ($s['tutar'] ?? 0);
            } else {
                $anlasmali++;
            }
        }

        return self::bayiToplamMetni($anlasmali, $ozel);
    }

    /**
     * Müşteri+otopark fiyatı zorunlu (admin elle tutar girebilir).
     * Bayi: kilitli hizmette yalnızca katalog; değiştirilebilirde gelen tutar geçerli.
     */
    public static function tutarBayiIcin(
        ?int $musteriId,
        int $maliyetTipiId,
        mixed $gelenTutar,
        bool $admin = false,
        ?int $bayiId = null
    ): float {
        if (self::tanimli($musteriId, $maliyetTipiId, $bayiId)) {
            $katalog = self::tutar($musteriId, $maliyetTipiId, $bayiId);
            if (!$admin && !self::bayiDegistirebilir($maliyetTipiId)) {
                return $katalog;
            }
            if ($gelenTutar === null || $gelenTutar === '' || (is_numeric($gelenTutar) && (float) $gelenTutar < 0)) {
                return $katalog;
            }

            return round((float) $gelenTutar, 2);
        }

        if ($admin && $gelenTutar !== null && $gelenTutar !== '' && (float) $gelenTutar > 0) {
            return round((float) $gelenTutar, 2);
        }

        if (!$admin && self::bayiDegistirebilir($maliyetTipiId)
            && $gelenTutar !== null && $gelenTutar !== '' && (float) $gelenTutar > 0) {
            return round((float) $gelenTutar, 2);
        }

        return 0.0;
    }

    public static function aracIcin(int $aracId, int $maliyetTipiId): float
    {
        $arac = Database::fetch('SELECT musteri_id, bayi_id FROM araclar WHERE id = :id', ['id' => $aracId]);
        if (!$arac) {
            return 0.0;
        }

        return self::tutar(
            (int) ($arac['musteri_id'] ?? 0) ?: null,
            $maliyetTipiId,
            (int) ($arac['bayi_id'] ?? 0) ?: null
        );
    }

    /**
     * @return list<array{id:int,musteri_id:int,bayi_id:int,maliyet_tipi_id:int,tutar:float,musteri:?string,hizmet:?string,otopark:?string}>
     */
    public static function liste(?int $musteriId = null, ?int $maliyetTipiId = null, ?int $bayiId = null): array
    {
        $kosul = ['TRUE'];
        $p = [];
        if ($musteriId) {
            $kosul[] = 'f.musteri_id = :m';
            $p['m'] = $musteriId;
        }
        if ($maliyetTipiId) {
            $kosul[] = 'f.maliyet_tipi_id = :t';
            $p['t'] = $maliyetTipiId;
        }
        if ($bayiId) {
            $kosul[] = 'f.bayi_id = :b';
            $p['b'] = $bayiId;
        }

        return Database::fetchAll(
            'SELECT f.id, f.musteri_id, f.bayi_id, f.maliyet_tipi_id, f.musteri_fiyat AS tutar,
                    f.musteri_fiyat, f.bayi_fiyat,
                    mu.ad AS musteri, mt.ad AS hizmet, b.ad AS otopark
             FROM hizmet_fiyatlari f
             JOIN musteriler mu ON mu.id = f.musteri_id
             JOIN maliyet_tipleri mt ON mt.id = f.maliyet_tipi_id
             JOIN bayiler b ON b.id = f.bayi_id
             WHERE ' . implode(' AND ', $kosul) . '
             ORDER BY b.ad, mt.ad',
            $p
        );
    }
}
