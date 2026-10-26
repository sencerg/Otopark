<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Detaylı Araç — ortak alan kataloğu.
 *
 * Kaynaklar:
 *  - basit: checkpoint/detayli-arac-basit-v1 (mevcut UI)
 *  - excel: docs/detayli_arac/Teslim_Formu_Alan_Listesi.xlsx (teslim / handover)
 *
 * UI genişlemesi bu şemaya göre yapılır. Eski akışa dönüş:
 *   git checkout checkpoint/detayli-arac-basit-v1 -- \
 *     app/Services/DetayliAracStore.php \
 *     app/Controllers/DetayliAracController.php \
 *     views/detayli_arac public/assets/js/detayli-ekspertiz.js
 *
 * @phpstan-type Bolum array{kod:string,ad:string,akis:string,kaynak:string}
 * @phpstan-type Alan array{
 *   kod:string,ad:string,bolum:string,tip:string,zorunlu:bool,
 *   secenek_set:?string,kaynak:string,eslestirme:?string,not?:string
 * }
 */
final class DetayliAracAlanSema
{
    public const CHECKPOINT = 'checkpoint/detayli-arac-basit-v1';

    /** Ortak seçenek setleri (excel + basit). */
    public static function secenekSetleri(): array
    {
        return [
            'yes_no' => ['var' => 'Var', 'yok' => 'Yok'],
            'bool' => ['evet' => 'Evet', 'hayir' => 'Hayır'],
            'condition' => [
                'standard_ok' => 'Standarda Uygun',
                'not_standard' => 'Standart Değil',
                'damaged' => 'Hasarlı',
            ],
            'seatbelt_condition' => ['standard_ok' => 'Uygun', 'damaged' => 'Hasarlı'],
            'fuel_type' => [
                'gasoline' => 'Benzin',
                'diesel' => 'Dizel',
                'lpg' => 'LPG',
                'electric' => 'Elektrik',
            ],
            'tire_class' => ['summer' => 'Yaz', 'winter' => 'Kış'],
            'transmission' => ['auto' => 'Otomatik', 'manual' => 'Manuel'],
            'fuel_level' => [
                'empty' => 'Boş',
                'quarter' => 'Çeyrek',
                'half' => 'Yarım',
                'three_quarter' => 'Dörte Üç',
                'full' => 'Dolu (Full)',
            ],
            'hasar_seviye' => [
                'yok' => 'Yok',
                'cizik' => 'Çizik',
                'gocuk' => 'Göçük',
                'boyali' => 'Boyalı',
                'degisen' => 'Değişen',
                'hasarli' => 'Hasarlı',
            ],
            'mekanik_seviye' => [
                'iyi' => 'İyi',
                'orta' => 'Orta',
                'kotu' => 'Kötü',
                'bilinmiyor' => 'Bilinmiyor',
            ],
            'kondisyon' => [
                'cok_iyi' => 'Çok iyi',
                'iyi' => 'İyi',
                'orta' => 'Orta',
                'kotu' => 'Kötü',
            ],
        ];
    }

    /**
     * Akış bölümleri — ortak iskelet (excel adımları + basit ekspertiz).
     *
     * @return list<Bolum>
     */
    public static function bolumler(): array
    {
        return [
            ['kod' => 'kontrol', 'ad' => 'Kontrol', 'akis' => 'kontrol', 'kaynak' => 'basit'],
            ['kod' => 'taraflar', 'ad' => 'Taraflar', 'akis' => 'taraflar', 'kaynak' => 'basit'],
            ['kod' => 'vehicle_info', 'ad' => 'Araç Bilgileri', 'akis' => 'arac', 'kaynak' => 'ortak'],
            ['kod' => 'front_section', 'ad' => 'Ön Kısım', 'akis' => 'ekspertiz', 'kaynak' => 'excel'],
            ['kod' => 'right_section', 'ad' => 'Sağ Orta', 'akis' => 'ekspertiz', 'kaynak' => 'excel'],
            ['kod' => 'rear_section', 'ad' => 'Arka Kısım', 'akis' => 'ekspertiz', 'kaynak' => 'excel'],
            ['kod' => 'left_section', 'ad' => 'Sol Orta', 'akis' => 'ekspertiz', 'kaynak' => 'excel'],
            ['kod' => 'hasar_sema', 'ad' => 'Hasar şeması (özet paneller)', 'akis' => 'ekspertiz', 'kaynak' => 'basit'],
            ['kod' => 'mekanik', 'ad' => 'Mekanik / elektrik', 'akis' => 'ekspertiz', 'kaynak' => 'basit'],
            ['kod' => 'interior_section', 'ad' => 'Araç İçi', 'akis' => 'ekspertiz', 'kaynak' => 'excel'],
            ['kod' => 'tires_section', 'ad' => 'Lastik', 'akis' => 'ekspertiz', 'kaynak' => 'excel'],
            ['kod' => 'documents_section', 'ad' => 'Belge & yüklemeler', 'akis' => 'ekspertiz', 'kaynak' => 'excel'],
            ['kod' => 'final_photos_section', 'ad' => 'Son aşama fotoğraflar', 'akis' => 'ekspertiz', 'kaynak' => 'excel'],
            ['kod' => 'foto_slot', 'ad' => 'Foto slotları (basit)', 'akis' => 'ekspertiz', 'kaynak' => 'basit'],
            ['kod' => 'lokasyon', 'ad' => 'Lokasyon / park', 'akis' => 'ekspertiz', 'kaynak' => 'basit'],
            ['kod' => 'kondisyon', 'ad' => 'Kondisyon', 'akis' => 'ekspertiz', 'kaynak' => 'basit'],
            ['kod' => 'signoff_section', 'ad' => 'İbra & imza', 'akis' => 'ozet', 'kaynak' => 'excel'],
            ['kod' => 'audit_section', 'ad' => 'Denetim formu', 'akis' => 'ozet', 'kaynak' => 'excel'],
        ];
    }

    /**
     * Basit UI panelleri ↔ excel alan kodları (ortak hasar çekirdeği).
     *
     * @return array<string,string> basit_kod => excel_kod
     */
    public static function hasarEslestirme(): array
    {
        return [
            'on_tampon' => 'front_bumper_body',
            'kaput' => 'hood',
            'tavan' => 'headliner',
            'bagaj' => 'trunk_lid',
            'arka_tampon' => 'rear_bumper_body',
            'sol_on_kapi' => 'left_front_door_body',
            'sol_arka_kapi' => 'left_rear_door_body',
            'sag_on_kapi' => 'right_front_door_body',
            'sag_arka_kapi' => 'right_rear_door_body',
            'sol_camurluk' => 'front_left_fender',
            'sag_camurluk' => 'front_right_fender',
            'sol_on_cam' => 'left_mirror_complete',
            'sag_on_cam' => 'right_mirror_complete',
            'far_sol' => 'front_left_headlamp',
            'far_sag' => 'front_right_headlamp',
            'stop_sol' => 'rear_left_taillamp',
            'stop_sag' => 'rear_right_taillamp',
            'jant_sol_on' => 'front_left_wheel',
            'jant_sag_on' => 'front_right_wheel',
            'jant_sol_arka' => 'rear_left_wheel',
            'jant_sag_arka' => 'rear_right_wheel',
        ];
    }

    /**
     * Basit akış alanları (checkpoint v1).
     *
     * @return list<Alan>
     */
    public static function basitAlanlar(): array
    {
        $out = [];
        foreach ([
            ['musteri_id', 'Müşteri (firma)', 'taraflar', 'select', true],
            ['musteri_musterisi', 'Müşterinin müşterisi', 'taraflar', 'text', true],
            ['bayi_id', 'Otopark / lokasyon', 'taraflar', 'otopark', true],
            ['teslim_eden', 'Teslim eden', 'taraflar', 'text', false],
            ['teslim_eden_telefon', 'Teslim eden telefon', 'taraflar', 'text', false],
            ['teslim_alan', 'Teslim alan', 'taraflar', 'text', false],
            ['teslim_alan_telefon', 'Teslim alan telefon', 'taraflar', 'text', false],
            ['not', 'Not', 'taraflar', 'textarea', false],
            ['sase', 'Şasi', 'vehicle_info', 'text', true],
            ['plaka', 'Plaka', 'vehicle_info', 'text', false],
            ['arac_tipi_id', 'Araç tipi', 'vehicle_info', 'select', true],
            ['marka_id', 'Marka', 'vehicle_info', 'select', true],
            ['seri_id', 'Seri', 'vehicle_info', 'select', true],
            ['model_id', 'Model', 'vehicle_info', 'select', false],
            ['model_yili_id', 'Model yılı', 'vehicle_info', 'select', false],
            ['renk_id', 'Renk', 'vehicle_info', 'select', false],
            ['yakit_tipi_id', 'Yakıt', 'vehicle_info', 'select', false],
            ['vites_tipi_id', 'Vites', 'vehicle_info', 'select', false],
            ['km', 'KM', 'vehicle_info', 'sayi', false],
            ['yakit_durumu', 'Yakıt (%)', 'vehicle_info', 'sayi', false],
            ['lokasyon_detay', 'Lokasyon detay', 'vehicle_info', 'text', false],
        ] as [$kod, $ad, $bolum, $tip, $zorunlu]) {
            $out[] = [
                'kod' => $kod,
                'ad' => $ad,
                'bolum' => $bolum,
                'tip' => $tip,
                'zorunlu' => $zorunlu,
                'secenek_set' => null,
                'kaynak' => 'basit',
                'eslestirme' => null,
            ];
        }

        foreach (DetayliAracStore::hasarPanelleri() as $kod => $ad) {
            $out[] = [
                'kod' => 'hasar_' . $kod,
                'ad' => $ad,
                'bolum' => 'hasar_sema',
                'tip' => 'select',
                'zorunlu' => false,
                'secenek_set' => 'hasar_seviye',
                'kaynak' => 'basit',
                'eslestirme' => self::hasarEslestirme()[$kod] ?? null,
            ];
        }

        foreach (DetayliAracStore::mekanikAlanlari() as $kod => $ad) {
            $out[] = [
                'kod' => 'mek_' . $kod,
                'ad' => $ad,
                'bolum' => 'mekanik',
                'tip' => 'select',
                'zorunlu' => false,
                'secenek_set' => 'mekanik_seviye',
                'kaynak' => 'basit',
                'eslestirme' => null,
            ];
        }

        foreach (DetayliAracStore::fotoEtiketleri() as $i => $ad) {
            $out[] = [
                'kod' => 'foto_slot_' . $i,
                'ad' => $ad,
                'bolum' => 'foto_slot',
                'tip' => 'foto',
                'zorunlu' => false,
                'secenek_set' => null,
                'kaynak' => 'basit',
                'eslestirme' => null,
            ];
        }

        $out[] = [
            'kod' => 'park_alani',
            'ad' => 'Park alanı',
            'bolum' => 'lokasyon',
            'tip' => 'text',
            'zorunlu' => false,
            'secenek_set' => null,
            'kaynak' => 'basit',
            'eslestirme' => null,
        ];
        $out[] = [
            'kod' => 'kondisyon_genel',
            'ad' => 'Genel kondisyon',
            'bolum' => 'kondisyon',
            'tip' => 'select',
            'zorunlu' => false,
            'secenek_set' => 'kondisyon',
            'kaynak' => 'basit',
            'eslestirme' => null,
        ];

        return $out;
    }

    /**
     * Excel alanlarını yükler (docs/detayli_arac/excel_alanlari.json).
     *
     * @return list<Alan>
     */
    public static function excelAlanlar(): array
    {
        $path = BASE_PATH . '/docs/detayli_arac/excel_alanlari.json';
        if (!is_file($path)) {
            return [];
        }
        $raw = json_decode((string) file_get_contents($path), true);
        if (!is_array($raw) || empty($raw['alanlar']) || !is_array($raw['alanlar'])) {
            return [];
        }
        $ters = array_flip(self::hasarEslestirme());
        $out = [];
        foreach ($raw['alanlar'] as $a) {
            $kod = (string) ($a['kod'] ?? '');
            if ($kod === '') {
                continue;
            }
            $out[] = [
                'kod' => $kod,
                'ad' => (string) ($a['ad'] ?? $kod),
                'bolum' => (string) ($a['bolum_kod'] ?? ''),
                'tip' => (string) ($a['tip'] ?? 'text'),
                'zorunlu' => !empty($a['zorunlu']),
                'secenek_set' => isset($a['secenek_set']) ? (string) $a['secenek_set'] : null,
                'kaynak' => 'excel',
                'eslestirme' => $ters[$kod] ?? null,
                'not' => (string) ($a['not'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * Birleşik katalog: excel öncelikli; basit-only alanlar eklenir.
     * Aynı excel koduna eşlenen hasar panelleri tekrarlanmaz (eslestirme ile bağlanır).
     *
     * @return list<Alan>
     */
    public static function ortakAlanlar(): array
    {
        $excel = self::excelAlanlar();
        $excelKod = [];
        foreach ($excel as $a) {
            $excelKod[$a['kod']] = true;
        }

        $out = $excel;
        foreach (self::basitAlanlar() as $a) {
            if (($a['eslestirme'] ?? null) && isset($excelKod[$a['eslestirme']])) {
                continue; // excel karşılığı var
            }
            if (isset($excelKod[$a['kod']])) {
                continue;
            }
            $out[] = $a;
        }

        return $out;
    }

    /** @return array{bolum:int,alan:int,excel:int,basit:int,ortak_eslesen:int} */
    public static function ozet(): array
    {
        $ortak = self::ortakAlanlar();
        $excel = 0;
        $basit = 0;
        $eslesen = 0;
        foreach ($ortak as $a) {
            if ($a['kaynak'] === 'excel') {
                $excel++;
                if (!empty($a['eslestirme'])) {
                    $eslesen++;
                }
            } else {
                $basit++;
            }
        }

        return [
            'bolum' => count(self::bolumler()),
            'alan' => count($ortak),
            'excel' => $excel,
            'basit' => $basit,
            'ortak_eslesen' => $eslesen,
        ];
    }
}
