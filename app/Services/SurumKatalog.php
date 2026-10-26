<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Proje sürüm geçmişi — Proje Notları sayfasının tek kaynağı.
 * Yeni özellik sonrası buraya gün + saat ile kayıt eklenir.
 *
 * @phpstan-type Surum array{surum:string,tarih:string,baslik:string,maddeler:list<string>}
 */
final class SurumKatalog
{
    /** @return list<Surum> En yeni önce */
    public static function liste(): array
    {
        $rows = self::kayitlar();
        usort($rows, static fn ($a, $b) => strcmp($b['tarih'], $a['tarih']) ?: strcmp($b['surum'], $a['surum']));

        return $rows;
    }

    public static function guncel(): ?array
    {
        return self::liste()[0] ?? null;
    }

    /** @return list<Surum> */
    private static function kayitlar(): array
    {
        return [
            [
                'surum' => 'v0.36',
                'tarih' => '2026-10-26 11:25',
                'baslik' => 'Ekspertiz şema + foto yükleme',
                'maddeler' => [
                    'Hasar: tıklanabilir araç şeması; durumlar şema üzerinde renk/etiket.',
                    'Foto adımı: her kare için dosya + not; ek belge yükleme.',
                    'Özette hasar şeması işaretleriyle gösterilir.',
                ],
            ],
            [
                'surum' => 'v0.35',
                'tarih' => '2026-10-26 11:15',
                'baslik' => 'Ekspertiz alt adımları',
                'maddeler' => [
                    '4. aşama: Hasar panelleri → Mekanik → Foto → Lokasyon → Kondisyon.',
                    'VIS bilgi seti; ekranlar ana kart/form tasarımında.',
                ],
            ],
            [
                'surum' => 'v0.34',
                'tarih' => '2026-10-26 10:55',
                'baslik' => 'Detaylı ekleme stoka yazılır',
                'maddeler' => [
                    'Araç adımında gerçek stok + giriş hareketi oluşur; listede «Detaylı» rozeti.',
                    'Liste varsayılan sıra: en yeni kayıt başta (id DESC).',
                    'Müşterinin müşterisi alanı araç kartında tutulur.',
                ],
            ],
            [
                'surum' => 'v0.33',
                'tarih' => '2026-10-26 10:45',
                'baslik' => 'Detaylı Araç Ekle · ana tasarım',
                'maddeler' => [
                    'VIS kabuğu kaldırıldı; menü + kart/form tasarımıyla adım adım ilerleme.',
                    'Kontrol → Taraflar → Araç → Ekspertiz → Özet (doldur, kaydet, devam).',
                ],
            ],
            [
                'surum' => 'v0.32',
                'tarih' => '2026-10-26 09:33',
                'baslik' => 'Detaylı Araç Ekle (ön kontrol)',
                'maddeler' => [
                    'Araç Ekspertiz → Detaylı Araç Ekle olarak yeniden adlandırıldı.',
                    'Önce şasi/plaka kontrolü ve koşullar; mevcut Araç Ekle akışına dokunulmadı.',
                ],
            ],
            [
                'surum' => 'v0.31',
                'tarih' => '2026-10-26 09:28',
                'baslik' => 'Toplu araç silme kapatıldı',
                'maddeler' => [
                    'Toplu araç silme kaldırıldı; yalnızca tek tek silme (admin) mümkün.',
                ],
            ],
            [
                'surum' => 'v0.30',
                'tarih' => '2026-10-26 09:20',
                'baslik' => 'Toplu stok: araç tipi / durumu isteğe bağlı',
                'maddeler' => [
                    'Excel toplu stok girişinde Araç Tipi ve Araç Durumu zorunlu olmaktan çıkarıldı; boş bırakılabilir.',
                ],
            ],
            [
                'surum' => 'v0.29',
                'tarih' => '2026-10-26 09:12',
                'baslik' => 'Firma hizmet filtresi',
                'maddeler' => [
                    'Firmaya (müşteri × otopark) fiyatı tanımlı olmayan hizmetler araç ekstresinde görünmez.',
                    'Hızlı maliyet, stoktan çıkış ve araç maliyeti listeleri yalnızca tanımlı hizmetleri gösterir.',
                ],
            ],
            [
                'surum' => 'v0.28',
                'tarih' => '2026-10-24 16:18',
                'baslik' => 'Proje Notları → sürüm geçmişi',
                'maddeler' => [
                    'Proje Notları menüsü yalnızca sürüm listesine dönüştürüldü.',
                    'Başından beri özellikler gün ve saat ile sürümlendi.',
                ],
            ],
            [
                'surum' => 'v0.27',
                'tarih' => '2026-10-24 16:12',
                'baslik' => 'Giriş ekranı marka tutarlılığı',
                'maddeler' => [
                    'Hero markası APP_NAME’den bağımsız: Moex / Otopark (APP_BRAND, APP_PRODUCT).',
                    'Canlıdaki “Otopark Yönetim Paneli” split’inin local’den farklı görünmesi giderildi.',
                ],
            ],
            [
                'surum' => 'v0.26',
                'tarih' => '2026-10-24 16:05',
                'baslik' => 'Yetki: Admin / Otopark / Otopark personel',
                'maddeler' => [
                    'Kullanıcı ekleme: yetki tipi Admin, Otopark, Otopark personel, Müşteri.',
                    'Kullanıcı adı, e-posta, telefon ve şifre yönetimi.',
                    'Giriş: e-posta veya kullanıcı adı.',
                    'Sistem profilleri: Admin, Otopark, Otopark Personel.',
                ],
            ],
            [
                'surum' => 'v0.25',
                'tarih' => '2026-10-23 15:10',
                'baslik' => 'Araç Ekspertiz (VIS) — bağımsız modül',
                'maddeler' => [
                    'Autorola VIS tarzı wizard: tanım → hasar → mekanik → foto → lokasyon → kondisyon → çıkış.',
                    'Kayıtlar storage/vis altında; stok / hareket / maliyet akışına bağlanmaz.',
                    'Menü: Araç Ekspertiz · izin menu.vis (admin).',
                ],
            ],
            [
                'surum' => 'v0.24',
                'tarih' => '2026-10-23 14:40',
                'baslik' => 'Raporlar hub',
                'maddeler' => [
                    'Raporlar ana sayfa: interaktif + seçerek hazırla grupları.',
                    'Stok özet ve giriş–çıkış özeti hazırlama ekranları.',
                    'Bayi İK/destek menü dışı; rapor izinleri netleştirildi.',
                ],
            ],
            [
                'surum' => 'v0.23',
                'tarih' => '2026-10-23 14:10',
                'baslik' => 'Hizmet fiyatı ve tekrar eklenebilir',
                'maddeler' => [
                    'Bayi anlaşmalı / özel fiyat düzenleme kuralları.',
                    'Hizmetlerde tekrar_eklenebilir: bayi 1×, admin N× özel fiyat.',
                ],
            ],
            [
                'surum' => 'v0.22',
                'tarih' => '2026-10-23 12:22',
                'baslik' => 'Hizmet fiyatı müşteri × otopark',
                'maddeler' => [
                    'Hizmet fiyatı müşteri ve otopark bazında tanımlanır.',
                ],
            ],
            [
                'surum' => 'v0.21',
                'tarih' => '2026-10-23 12:19',
                'baslik' => 'Müşteri bazlı fiyat checkpoint',
                'maddeler' => [
                    'Bayi–hizmet ayrımı öncesi müşteri bazlı otopark/hizmet fiyat altyapısı.',
                ],
            ],
            [
                'surum' => 'v0.20',
                'tarih' => '2026-10-23 11:33',
                'baslik' => 'Yetki yönetimi + Excel XLSX',
                'maddeler' => [
                    'Yetki profilleri ve sayfa/işlem izinleri.',
                    'PhpSpreadsheet vendor ile Excel XLSX desteği.',
                ],
            ],
            [
                'surum' => 'v0.19',
                'tarih' => '2026-10-21 21:50',
                'baslik' => 'Tarifeler (20 Ekim)',
                'maddeler' => [
                    'Tarife ve fiyatlandırma güncellemeleri.',
                ],
            ],
            [
                'surum' => 'v0.18',
                'tarih' => '2026-10-01 22:13',
                'baslik' => 'Canlı / paket hazırlığı',
                'maddeler' => [
                    'Otopark ilk dağıtım paketi.',
                ],
            ],
            [
                'surum' => 'v0.17',
                'tarih' => '2026-10-01 21:50',
                'baslik' => 'Dashboard koyu tema uyumu',
                'maddeler' => [
                    'Veri seti özeti kutuları koyu modda temaya uyumlu.',
                ],
            ],
            [
                'surum' => 'v0.16',
                'tarih' => '2026-10-01 21:48',
                'baslik' => 'Tema seçimi',
                'maddeler' => [
                    'Klasik ve Vuexy tarzı temalar; açık / koyu / sistem.',
                    'Tema önizleme sayfası.',
                ],
            ],
            [
                'surum' => 'v0.15',
                'tarih' => '2026-10-01 21:33',
                'baslik' => 'Araç sayımı',
                'maddeler' => [
                    'Sayım kaydı, şasi/plaka okutma.',
                    'Okutulan / lokasyonda değil / okutulmayan listeleri, Stoğa Al, Excel.',
                ],
            ],
            [
                'surum' => 'v0.14',
                'tarih' => '2026-10-01 21:24',
                'baslik' => 'Stok listesi toplu işlemler',
                'maddeler' => [
                    'Stoktaki Araçlar: BİA tarzı toplu işlem düğmeleri ve Konsinye filtresi.',
                ],
            ],
            [
                'surum' => 'v0.13',
                'tarih' => '2026-10-01 21:19',
                'baslik' => 'Hareket hizmet ücreti düzenleme',
                'maddeler' => [
                    'Kayıtlı hizmet ücretleri düzenlenebilir; 0 ₺ kayıtlarda tanım fiyatı gelir.',
                ],
            ],
            [
                'surum' => 'v0.12',
                'tarih' => '2026-10-01 21:14',
                'baslik' => 'Ek hizmet raporu iyileştirmeleri',
                'maddeler' => [
                    'Özet kartları tablo filtreleriyle hizalı; 0 ₺ hizmet engeli ve işareti.',
                ],
            ],
            [
                'surum' => 'v0.11',
                'tarih' => '2026-10-01 21:06',
                'baslik' => 'Çıkış sonrası hizmet / maliyet',
                'maddeler' => [
                    'Hareket düzenlemede ekleme; renkli işaret ve notla raporlara yansıma.',
                ],
            ],
            [
                'surum' => 'v0.10',
                'tarih' => '2026-10-01 20:48',
                'baslik' => 'Demo verisi sıfırlama',
                'maddeler' => [
                    'Geliştirme ortamı için /demo/sifirla.',
                ],
            ],
            [
                'surum' => 'v0.9',
                'tarih' => '2026-10-01 20:45',
                'baslik' => 'Tanımlamalar sayfası',
                'maddeler' => [
                    'Müşteri, otopark (günlük fiyat + çarpan), hizmet, marka/seri/model.',
                    'Stoktan Çıkar’da hizmet ücreti.',
                ],
            ],
            [
                'surum' => 'v0.8',
                'tarih' => '2026-10-01 19:56',
                'baslik' => 'Müşteri oturumu öncesi kontroller',
                'maddeler' => [
                    'İndeksler, marka/seri/model doğrulaması, fiyatsız depolama uyarısı, demo veri.',
                ],
            ],
            [
                'surum' => 'v0.7',
                'tarih' => '2026-09-30 22:38',
                'baslik' => 'Karşılaştırma, demo giriş, test paketi',
                'maddeler' => [
                    'BİA ↔ MD karşılaştırma dokümanı.',
                    'Girişte demo hızlı giriş; sıkı test paketi (bin/test.sh).',
                ],
            ],
            [
                'surum' => 'v0.6',
                'tarih' => '2026-09-30 22:21',
                'baslik' => 'BİA akışına göre çekirdek sistem',
                'maddeler' => [
                    'Araç yönetimi, hareketler, ek hizmet/maliyet, depolama raporu.',
                    'İş emirleri, dashboardlar, tanımlar, destek, hesabım.',
                ],
            ],
            [
                'surum' => 'v0.5',
                'tarih' => '2026-09-30 21:52',
                'baslik' => 'Composer kilit dosyası',
                'maddeler' => [
                    'composer.lock eklendi.',
                ],
            ],
            [
                'surum' => 'v0.4',
                'tarih' => '2026-09-30 21:37',
                'baslik' => 'Proje iskeleti',
                'maddeler' => [
                    'PHP + PostgreSQL iskelet, auth, routing, temel şema.',
                ],
            ],
        ];
    }
}
