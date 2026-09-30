<?php

declare(strict_types=1);

namespace App\Core;

final class Tanim
{
    public const HAREKET_TIPI = [1 => 'Giriş', 2 => 'Çıkış'];
    public const ARAC_DURUMU = [1 => 'Sıfır', 2 => 'İkinci El'];
    public const LOKASYON_TURU = [1 => 'Açık', 2 => 'Kapalı', 3 => 'Her İkisi De'];
    public const SEVKIYAT_TIPI = [1 => 'Bireysel', 2 => 'Vale', 3 => 'Çekici'];
    public const SEVKIYAT_DURUMU = [1 => 'Bekliyor', 2 => 'Devam Ediyor', 3 => 'Tamamlandı'];
    public const IS_EMRI_DURUM = [1 => 'Yapılacak', 2 => 'Onay Bekliyor', 3 => 'İşleme Alındı', 4 => 'Tamamlandı', 5 => 'Reddedildi', 6 => 'İptal Edildi'];
    public const IS_EMRI_DURUM_RENK = [1 => 'secondary', 2 => 'warning', 3 => 'info', 4 => 'success', 5 => 'danger', 6 => 'dark'];
    public const DESTEK_TUR = [1 => 'Teknik', 2 => 'Operasyon', 3 => 'Talep / Öneri'];
    public const DESTEK_DURUM = [1 => 'Açık', 2 => 'İşlemde', 3 => 'Çözüldü', 4 => 'Kapatıldı'];

    private static array $cache = [];

    /** @return array<int,string> */
    public static function liste(string $tablo): array
    {
        if (isset(self::$cache[$tablo])) {
            return self::$cache[$tablo];
        }

        $sql = match ($tablo) {
            'markalar', 'arac_tipleri', 'kasa_tipleri', 'yakit_tipleri', 'vites_tipleri', 'envanterler',
            'hareket_nedenleri', 'departmanlar', 'kullanici_gruplari' => "SELECT id, ad FROM {$tablo} ORDER BY " . (in_array($tablo, ['markalar', 'departmanlar'], true) ? 'ad' : 'id'),
            'renkler' => 'SELECT id, ad FROM renkler ORDER BY id',
            'model_yillari' => 'SELECT id, yil::text AS ad FROM model_yillari ORDER BY yil DESC',
            'musteriler' => 'SELECT id, ad FROM musteriler WHERE aktif ORDER BY ad',
            'maliyet_tipleri' => 'SELECT id, ad FROM maliyet_tipleri WHERE aktif ORDER BY id',
            'bayiler' => self::bayiSql(),
            'personeller' => 'SELECT id, ad_soyad AS ad FROM personeller WHERE aktif ORDER BY ad_soyad',
            default => throw new \InvalidArgumentException("Bilinmeyen tanım: {$tablo}"),
        };

        return self::$cache[$tablo] = array_column(Database::fetchAll($sql), 'ad', 'id');
    }

    private static function bayiSql(): string
    {
        $bayiId = Auth::bayiId();

        return 'SELECT id, ad FROM bayiler WHERE aktif' . ($bayiId ? ' AND id = ' . $bayiId : '') . ' ORDER BY id';
    }

    public static function seriler(int $markaId): array
    {
        return array_column(Database::fetchAll('SELECT id, ad FROM seriler WHERE marka_id = :m ORDER BY ad', ['m' => $markaId]), 'ad', 'id');
    }

    public static function modeller(int $seriId): array
    {
        return array_column(Database::fetchAll('SELECT id, ad FROM modeller WHERE seri_id = :s ORDER BY ad', ['s' => $seriId]), 'ad', 'id');
    }

    public static function donanimGruplari(): array
    {
        $gruplar = [];
        foreach (Database::fetchAll('SELECT id, grup, ad FROM donanimlar ORDER BY id') as $row) {
            $gruplar[$row['grup']][$row['id']] = $row['ad'];
        }

        return $gruplar;
    }

    public static function options(array $items, mixed $selected = null, ?string $placeholder = 'Seçiniz'): string
    {
        $selected = array_map('strval', (array) $selected);
        $html = $placeholder !== null ? '<option value="">' . e($placeholder) . '</option>' : '';
        foreach ($items as $value => $label) {
            $isSelected = in_array((string) $value, $selected, true) ? ' selected' : '';
            $html .= '<option value="' . e($value) . '"' . $isSelected . '>' . e($label) . '</option>';
        }

        return $html;
    }
}
