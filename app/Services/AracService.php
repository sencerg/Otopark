<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Log;
use RuntimeException;

final class AracService
{
    public const ARAC_ALANLARI = [
        'plaka', 'onceki_plaka', 'park_kodu', 'arac_durumu', 'arac_tipi_id', 'kasa_tipi_id', 'renk_id', 'marka_id', 'seri_id',
        'model_id', 'model_yili_id', 'motor_hacmi', 'motor_gucu', 'yakit_tipi_id', 'vites_tipi_id', 'km', 'yakit_durumu',
        'musteri_id', 'bayi_id', 'lokasyon_turu', 'lokasyon_detay', 'proje_adi', 'konsinye', 'sigorta_tarihi', 'kasko_tarihi', 'muayene_tarihi',
    ];

    public const HAREKET_ALANLARI = [
        'hareket_nedeni_id', 'musteri_id', 'bayi_id', 'lokasyon_turu', 'lokasyon_detay', 'km', 'yakit_durumu',
        'teslim_eden', 'teslim_eden_telefon', 'teslim_eden_eposta', 'teslim_alan', 'teslim_alan_telefon', 'teslim_alan_eposta',
        'teslim_alan_personel_id', 'sevkiyat_tipi', 'sevkiyat_durumu', 'sofor_adi_soyadi', 'sofor_telefon', 'cekici_plakasi',
        'sevkiyat_kodu', 'irsaliye_kodu', 'aciklama',
    ];

    public static function normalizeSase(?string $sase): string
    {
        return mb_strtoupper(preg_replace('/\s+/', '', (string) $sase));
    }

    public static function bayiKontrol(?int $bayiId): int
    {
        $kendi = Auth::bayiId();
        if ($kendi !== null) {
            return $kendi;
        }
        if (!$bayiId) {
            throw new RuntimeException('Lokasyon seçilmelidir.');
        }

        return $bayiId;
    }

    /** Araç bu kullanıcının erişebileceği lokasyonda mı? */
    public static function bul(int $aracId): array
    {
        $arac = Database::fetch('SELECT * FROM araclar WHERE id = :id AND ' . Auth::bayiKosulu('bayi_id'), ['id' => $aracId]);
        if (!$arac) {
            throw new RuntimeException('Araç bulunamadı veya bu araca erişim yetkiniz yok.');
        }

        return $arac;
    }

    public static function markaSeriModelKontrol(array $arac): void
    {
        if (!empty($arac['seri_id']) && !Database::fetch(
            'SELECT 1 FROM seriler WHERE id = :s AND (:m1::int IS NULL OR marka_id = :m2::int)',
            ['s' => $arac['seri_id'], 'm1' => ($arac['marka_id'] ?? null) ?: null, 'm2' => ($arac['marka_id'] ?? null) ?: null]
        )) {
            throw new RuntimeException('Seçilen seri bu markaya ait değil.');
        }
        if (!empty($arac['model_id']) && !Database::fetch(
            'SELECT 1 FROM modeller WHERE id = :o AND (:s1::int IS NULL OR seri_id = :s2::int)',
            ['o' => $arac['model_id'], 's1' => ($arac['seri_id'] ?? null) ?: null, 's2' => ($arac['seri_id'] ?? null) ?: null]
        )) {
            throw new RuntimeException('Seçilen model bu seriye ait değil.');
        }
    }

    /**
     * Aracı oluşturur veya şasiye göre bulup günceller, ardından hareket kaydı atar.
     * Giriş hareketi aracı stoğa alır, çıkış hareketi stoktan düşer.
     */
    public static function kaydetVeHareket(array $arac, ?array $hareket, array $envanterler = [], array $donanimlar = []): array
    {
        $sase = self::normalizeSase($arac['sase'] ?? '');
        if (strlen($sase) < 5) {
            throw new RuntimeException('Geçerli bir şasi numarası giriniz.');
        }
        if (empty($arac['musteri_id'])) {
            throw new RuntimeException('Müşteri seçilmelidir.');
        }
        $arac['bayi_id'] = self::bayiKontrol($arac['bayi_id'] ?? null);
        $arac['plaka'] = isset($arac['plaka']) ? mb_strtoupper(trim((string) $arac['plaka'])) : null;
        self::markaSeriModelKontrol($arac);

        return Database::transaction(function () use ($sase, $arac, $hareket, $envanterler, $donanimlar) {
            $mevcut = Database::fetch('SELECT * FROM araclar WHERE sase = :s FOR UPDATE', ['s' => $sase]);

            if ($mevcut && Auth::bayiId() && (int) $mevcut['bayi_id'] !== Auth::bayiId()) {
                if ($mevcut['stokta']) {
                    throw new RuntimeException('Bu araç başka bir lokasyonun stoğunda.');
                }
                if (!$hareket || (int) $hareket['hareket_tipi'] !== 1) {
                    throw new RuntimeException('Bu araç başka bir lokasyona kayıtlı. Kendi lokasyonunuza almak için giriş hareketi yapın.');
                }
            }
            if ($mevcut && $hareket && (int) $hareket['hareket_tipi'] === 1 && $mevcut['stokta']) {
                throw new RuntimeException("{$sase} şasi numaralı araç zaten stokta.");
            }
            if (!$mevcut && $hareket && (int) $hareket['hareket_tipi'] === 2) {
                throw new RuntimeException('Stokta olmayan araç için çıkış yapılamaz.');
            }

            $alanlar = array_intersect_key($arac, array_flip(self::ARAC_ALANLARI));
            if ($mevcut) {
                $alanlar = array_filter($alanlar, fn ($v) => $v !== null && $v !== '');
                self::guncelle((int) $mevcut['id'], $alanlar);
                $aracId = (int) $mevcut['id'];
            } else {
                $alanlar['sase'] = $sase;
                $alanlar['olusturan_id'] = Auth::id();
                $alanlar['stoga_giris_tarihi'] = $arac['stoga_giris_tarihi'] ?? null;
                $aracId = self::insert('araclar', $alanlar);
            }

            if ($envanterler) {
                Database::query('DELETE FROM arac_envanterleri WHERE arac_id = :a', ['a' => $aracId]);
                foreach ($envanterler as $id) {
                    Database::query('INSERT INTO arac_envanterleri VALUES (:a, :e) ON CONFLICT DO NOTHING', ['a' => $aracId, 'e' => $id]);
                }
            }
            if ($donanimlar) {
                Database::query('DELETE FROM arac_donanimlari WHERE arac_id = :a', ['a' => $aracId]);
                foreach ($donanimlar as $id) {
                    Database::query('INSERT INTO arac_donanimlari VALUES (:a, :d) ON CONFLICT DO NOTHING', ['a' => $aracId, 'd' => $id]);
                }
            }

            $hareketId = null;
            if ($hareket) {
                $hareket['musteri_id'] ??= $arac['musteri_id'];
                $hareket['bayi_id'] = $arac['bayi_id'];
                $hareketId = self::hareketEkle($aracId, $hareket);
            }

            Log::islem('arac', ($mevcut ? 'Araç güncellendi: ' : 'Araç eklendi: ') . $sase, $aracId);

            return ['arac_id' => $aracId, 'hareket_id' => $hareketId, 'yeni' => !$mevcut];
        });
    }

    public static function hareketEkle(int $aracId, array $hareket): int
    {
        $tip = (int) ($hareket['hareket_tipi'] ?? 0);
        if (!in_array($tip, [1, 2], true)) {
            throw new RuntimeException('Hareket tipi seçilmelidir.');
        }
        $tarih = $hareket['hareket_tarihi'] ?? date('Y-m-d H:i:s');
        if ($tip === 2) {
            $durum = Database::fetch('SELECT stokta, stoga_giris_tarihi FROM araclar WHERE id = :id', ['id' => $aracId]);
            if (!$durum || !$durum['stokta']) {
                throw new RuntimeException('Stokta olmayan araç için çıkış yapılamaz.');
            }
            $giris = $durum['stoga_giris_tarihi'];
            if ($giris && strtotime($tarih) < strtotime($giris)) {
                throw new RuntimeException('Çıkış tarihi, aracın stoğa giriş tarihinden (' . date('d.m.Y H:i', strtotime($giris)) . ') önce olamaz.');
            }
        }

        $alanlar = array_intersect_key($hareket, array_flip(self::HAREKET_ALANLARI));
        $alanlar += ['arac_id' => $aracId, 'hareket_tipi' => $tip, 'hareket_tarihi' => $tarih, 'kullanici_id' => Auth::id()];
        $hareketId = self::insert('arac_hareketleri', $alanlar);

        $guncelle = $tip === 1
            ? ['stokta' => 'true', 'stoga_giris_tarihi' => $tarih, 'stoktan_cikis_tarihi' => null, 'bayi_id' => $hareket['bayi_id'] ?? null, 'musteri_id' => $hareket['musteri_id'] ?? null]
            : ['stokta' => 'false', 'stoktan_cikis_tarihi' => $tarih];
        foreach (['km', 'yakit_durumu', 'lokasyon_turu', 'lokasyon_detay'] as $alan) {
            if (isset($hareket[$alan]) && $hareket[$alan] !== '') {
                $guncelle[$alan] = $hareket[$alan];
            }
        }
        self::guncelle($aracId, array_filter($guncelle, fn ($v, $k) => $v !== null || $k === 'stoktan_cikis_tarihi', ARRAY_FILTER_USE_BOTH));

        return $hareketId;
    }

    public static function maliyetEkle(int $aracId, array $data): int
    {
        $arac = self::bul($aracId);
        if (empty($data['maliyet_tipi_id'])) {
            throw new RuntimeException('Maliyet tipi seçilmelidir.');
        }
        if (!isset($data['tutar']) || $data['tutar'] < 0) {
            throw new RuntimeException('Geçerli bir tutar giriniz.');
        }

        $id = self::insert('arac_ekstreleri', [
            'arac_id' => $aracId,
            'maliyet_tipi_id' => $data['maliyet_tipi_id'],
            'musteri_id' => $arac['musteri_id'],
            'bayi_id' => $arac['bayi_id'],
            'tutar' => $data['tutar'],
            'aciklama' => $data['aciklama'] ?? null,
            'irsaliye' => $data['irsaliye'] ?? null,
            'fatura_no' => $data['fatura_no'] ?? null,
            'fatura_tarihi' => $data['fatura_tarihi'] ?? null,
            'islem_tarihi' => $data['islem_tarihi'] ?? date('Y-m-d'),
            'is_emri_id' => $data['is_emri_id'] ?? null,
            'kullanici_id' => Auth::id(),
        ]);
        Log::islem('maliyet', 'Maliyet eklendi: ' . $arac['sase'], $id);

        return $id;
    }

    public static function insert(string $table, array $data): int
    {
        $data = array_filter($data, fn ($v) => $v !== null && $v !== '');
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) RETURNING id',
            $table,
            implode(', ', $cols),
            implode(', ', array_map(fn ($c) => ':' . $c, $cols))
        );

        return (int) Database::fetch($sql, self::bindable($data))['id'];
    }

    public static function guncelle(int $aracId, array $data, string $table = 'araclar'): void
    {
        if (!$data) {
            return;
        }
        $set = implode(', ', array_map(fn ($c) => "{$c} = :{$c}", array_keys($data)));
        $extra = $table === 'araclar' ? ', updated_at = NOW()' : '';
        Database::query("UPDATE {$table} SET {$set}{$extra} WHERE id = :_id", self::bindable($data) + ['_id' => $aracId]);
    }

    private static function bindable(array $data): array
    {
        return array_map(fn ($v) => is_bool($v) ? ($v ? 'true' : 'false') : $v, $data);
    }
}
