<?php

declare(strict_types=1);

use App\Controllers\AracController;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\DemoController;
use App\Controllers\DestekController;
use App\Controllers\HareketController;
use App\Controllers\HesapController;
use App\Controllers\IsEmriController;
use App\Controllers\KarsilastirmaController;
use App\Controllers\MaliyetController;
use App\Controllers\MusteriPortalController;
use App\Controllers\RaporController;
use App\Controllers\SayimController;
use App\Controllers\SurumController;
use App\Controllers\TanimController;
use App\Controllers\TanimlamaController;
use App\Controllers\TemaController;
use App\Controllers\DetayliAracController;
use App\Controllers\YetkiController;
use App\Core\Auth;
use App\Core\View;

/** @var \App\Core\Router $router */

$guest = [[Auth::class, 'requireGuest']];
$auth = [[Auth::class, 'requireLogin']];
$staff = [[Auth::class, 'requireStaff']];
$musteri = [[Auth::class, 'requireMusteri']];

$router->get('/login', [AuthController::class, 'showLogin'], $guest);
$router->post('/login', [AuthController::class, 'login'], $guest);
$router->post('/logout', [AuthController::class, 'logout'], $auth);
$router->get('/demo/sifirla', [DemoController::class, 'onay']);
$router->post('/demo/sifirla', [DemoController::class, 'sifirla']);

// Müşteri portalı (salt görüntüleme)
$router->get('/musteri', [MusteriPortalController::class, 'dashboard'], $musteri);
$router->get('/musteri/stok', [MusteriPortalController::class, 'stok'], $musteri);
$router->get('/musteri/cikanlar', [MusteriPortalController::class, 'cikanlar'], $musteri);
$router->get('/musteri/hizmetler', [MusteriPortalController::class, 'hizmetler'], $musteri);
$router->get('/musteri/excel/arac/{id}', [MusteriPortalController::class, 'excelArac'], $musteri);
$router->get('/musteri/excel/{tip}', [MusteriPortalController::class, 'excel'], $musteri);
$router->get('/musteri/arac/{id}', [MusteriPortalController::class, 'arac'], $musteri);

// Dashboardlar
$router->get('/', [DashboardController::class, 'index'], $staff);
$router->get('/ana_dashboard', [DashboardController::class, 'index'], $staff);
$router->get('/saha_dashboard', [DashboardController::class, 'saha'], $staff);
$router->get('/ik_dashboard', [DashboardController::class, 'ik'], $staff);

// Yardımcı uçlar
$router->get('/api/seriler', [ApiController::class, 'seriler'], $staff);
$router->get('/api/modeller', [ApiController::class, 'modeller'], $staff);
$router->get('/api/personeller', [ApiController::class, 'personeller'], $staff);
$router->get('/api/araclar', [ApiController::class, 'araclar'], $staff);
$router->get('/api/hizmet_fiyat', [ApiController::class, 'hizmetFiyat'], $staff);
$router->get('/api/hizmetler', [ApiController::class, 'hizmetler'], $staff);
$router->get('/arama', [ApiController::class, 'arama'], $staff);

// Araç hareketleri (Stoktaki Araçlar / Stoktan Çıkanlar)
$router->get('/arac_hareketleri/giris', [HareketController::class, 'giris'], $staff);
$router->get('/arac_hareketleri/giris/liste', [HareketController::class, 'girisListe'], $staff);
$router->get('/arac_hareketleri/cikis', [HareketController::class, 'cikis'], $staff);
$router->get('/arac_hareketleri/cikis/liste', [HareketController::class, 'cikisListe'], $staff);
$router->get('/arac_hareketleri/hareket_view/{id}', [HareketController::class, 'goruntule'], $staff);
$router->get('/arac_hareketleri/hareket_duzenle/{id}', [HareketController::class, 'duzenle'], $staff);
$router->post('/arac_hareketleri/hareket_update/{id}', [HareketController::class, 'guncelle'], $staff);
$router->get('/arac_hareketleri/stoktan_cikar/{aracId}', [HareketController::class, 'stoktanCikarForm'], $staff);
$router->post('/arac_hareketleri/stoktan_cikar/{aracId}', [HareketController::class, 'stoktanCikar'], $staff);
$router->post('/arac_hareketleri/etiket_fiyati', [HareketController::class, 'etiketFiyati'], $staff);
$router->post('/arac_hareketleri/multiple_arsiv', [HareketController::class, 'topluArsiv'], $staff);

// Araç yönetimi
$router->get('/arac_yonetimi', [AracController::class, 'index'], $staff);
$router->get('/arac_yonetimi/liste', [AracController::class, 'liste'], $staff);
$router->get('/arac_yonetimi/ekle', [AracController::class, 'ekle'], $staff);
$router->post('/arac_yonetimi/save', [AracController::class, 'save'], $staff);
$router->get('/arac_yonetimi/duzenle/{id}', [AracController::class, 'duzenle'], $staff);
$router->post('/arac_yonetimi/update/{id}', [AracController::class, 'update'], $staff);
$router->post('/arac_yonetimi/hizli_arac_save', [AracController::class, 'hizliSave'], $staff);
$router->get('/arac_yonetimi/tesellum_formu/{id}', [AracController::class, 'tesellum'], $staff);
$router->get('/arac_yonetimi/qr_toplu', [AracController::class, 'qrToplu'], $staff);
$router->post('/arac_yonetimi/multiple_arsiv', [AracController::class, 'topluArsiv'], $staff);
$router->post('/arac_yonetimi/multiple_delete', [AracController::class, 'topluSil'], $staff);
$router->post('/arac_yonetimi/sil/{id}', [AracController::class, 'sil'], $staff);
$router->get('/arac_yonetimi/toplu_stok_girisi', [AracController::class, 'topluStokGirisi'], $staff);
$router->post('/arac_yonetimi/excel_toplu_giris', [AracController::class, 'excelTopluGiris'], $staff);
$router->get('/arac_yonetimi/toplu_stok_cikisi', [AracController::class, 'topluStokCikisi'], $staff);
$router->post('/arac_yonetimi/excel_toplu_cikis', [AracController::class, 'excelTopluCikis'], $staff);
$router->get('/arac_yonetimi/toplu_ek_maliyet_girisi', [AracController::class, 'topluEkMaliyet'], $staff);
$router->post('/arac_yonetimi/excel_toplu_ek_maliyet_giris', [AracController::class, 'excelTopluMaliyet'], $staff);
$router->get('/arac_yonetimi/toplu_kayit_guncelleme_lokasyon', [AracController::class, 'topluLokasyon'], $staff);
$router->post('/arac_yonetimi/excel_toplu_kayit_guncelleme_lokasyon', [AracController::class, 'excelTopluLokasyon'], $staff);
$router->get('/arac_yonetimi/sablon/{tip}', [AracController::class, 'sablon'], $staff);
$router->post('/dosya/sil/{id}', [AracController::class, 'dosyaSil'], $staff);

// Araç sayımları
$router->get('/sayim_kayitlari', [SayimController::class, 'index'], $staff);
$router->get('/sayim_kayitlari/liste', [SayimController::class, 'liste'], $staff);
$router->get('/sayim_kayitlari/ekle', [SayimController::class, 'ekle'], $staff);
$router->post('/sayim_kayitlari/save', [SayimController::class, 'save'], $staff);
$router->get('/sayim_kayitlari/detay/{id}', [SayimController::class, 'detay'], $staff);
$router->get('/sayim_kayitlari/veri/{id}', [SayimController::class, 'veri'], $staff);
$router->post('/sayim_kayitlari/okut/{id}', [SayimController::class, 'okut'], $staff);
$router->post('/sayim_kayitlari/okutma_sil/{id}', [SayimController::class, 'okutmaSil'], $staff);
$router->post('/sayim_kayitlari/tamamla/{id}', [SayimController::class, 'tamamla'], $staff);
$router->get('/sayim_kayitlari/excel/{id}', [SayimController::class, 'excel'], $staff);
$router->post('/sayim_kayitlari/multiple_arsiv', [SayimController::class, 'topluArsiv'], $staff);

// Tanımlar
$router->get('/arac_yonetimi/tanimlar', [TanimController::class, 'index'], $staff);
$router->get('/arac_yonetimi/tanimlar/{tip}', [TanimController::class, 'liste'], $staff);
$router->get('/arac_yonetimi/tanimlar/{tip}/data', [TanimController::class, 'data'], $staff);
$router->get('/arac_yonetimi/tanimlar/{tip}/getir/{id}', [TanimController::class, 'getir'], $staff);
$router->post('/arac_yonetimi/tanimlar/{tip}/kaydet', [TanimController::class, 'kaydet'], $staff);
$router->post('/arac_yonetimi/tanimlar/{tip}/sil/{id}', [TanimController::class, 'sil'], $staff);

// Tanımlamalar (müşteri, otopark, hizmet, marka/seri/model)
$router->get('/tanimlamalar', [TanimlamaController::class, 'index'], $staff);
$router->get('/tanimlamalar/hizmet_fiyat/liste', [TanimlamaController::class, 'fiyatListe'], $staff);
$router->get('/tanimlamalar/musteri/yeni', [TanimlamaController::class, 'musteriForm'], $staff);
$router->get('/tanimlamalar/{tip}/liste', [TanimlamaController::class, 'liste'], $staff);
$router->get('/tanimlamalar/musteri/{id}', [TanimlamaController::class, 'musteriForm'], $staff);
$router->post('/tanimlamalar/{tip}/kaydet', [TanimlamaController::class, 'kaydet'], $staff);
$router->post('/tanimlamalar/{tip}/sil/{id}', [TanimlamaController::class, 'sil'], $staff);

// Ek maliyet (araç ekstresi)
$router->post('/arac_ekstreleri/ek_maliyet_save_modal', [MaliyetController::class, 'saveModal'], $staff);
$router->get('/arac_ekstreleri/maliyet_getir/{id}', [MaliyetController::class, 'getir'], $staff);
$router->post('/arac_ekstreleri/maliyet_guncelle/{id}', [MaliyetController::class, 'guncelle'], $staff);
$router->post('/arac_ekstreleri/maliyet_sil/{id}', [MaliyetController::class, 'sil'], $staff);
$router->get('/is_takibi', [MaliyetController::class, 'isTakibi'], $staff);
$router->get('/is_takibi/liste', [MaliyetController::class, 'isTakibiListe'], $staff);

// Detaylı Araç Ekle (eski /vis yolları yönlendirilir)
$router->get('/detayli-arac', [DetayliAracController::class, 'index'], $staff);
$router->get('/detayli-arac/yeni', [DetayliAracController::class, 'yeni'], $staff);
$router->post('/detayli-arac/baslat', [DetayliAracController::class, 'baslat'], $staff);
$router->get('/detayli-arac/kayit/{id}', [DetayliAracController::class, 'kayit'], $staff);
$router->post('/detayli-arac/kayit/{id}/kaydet', [DetayliAracController::class, 'kaydet'], $staff);
$router->get('/vis', static function () { View::redirect('/detayli-arac'); }, $staff);
$router->get('/vis/yeni', static function () { View::redirect('/detayli-arac'); }, $staff);
$router->get('/vis/kayit/{id}', static function (string $id) {
    $q = $_SERVER['QUERY_STRING'] ?? '';
    View::redirect('/detayli-arac/kayit/' . rawurlencode($id) . ($q !== '' ? '?' . $q : ''));
}, $staff);

// Raporlar
$router->get('/raporlar', [RaporController::class, 'index'], $staff);
$router->get('/raporlar/stok_ozet', [RaporController::class, 'stokOzet'], $staff);
$router->get('/raporlar/giris_cikis', [RaporController::class, 'girisCikis'], $staff);
$router->get('/depolama_raporu', [RaporController::class, 'depolama'], $staff);
$router->get('/depolama_raporu/liste', [RaporController::class, 'depolamaListe'], $staff);
$router->get('/ek_hizmet_raporu', [RaporController::class, 'ekHizmet'], $staff);
$router->get('/ek_hizmet_raporu/liste', [RaporController::class, 'ekHizmetListe'], $staff);

// İş emri yönetimi
$router->get('/gorev_yonetimi', [IsEmriController::class, 'index'], $staff);
$router->get('/gorev_yonetimi/liste', [IsEmriController::class, 'liste'], $staff);
$router->get('/gorev_yonetimi/ekle', [IsEmriController::class, 'ekle'], $staff);
$router->post('/gorev_yonetimi/save', [IsEmriController::class, 'save'], $staff);
$router->get('/gorev_yonetimi/duzenle/{id}', [IsEmriController::class, 'duzenle'], $staff);
$router->post('/gorev_yonetimi/update/{id}', [IsEmriController::class, 'update'], $staff);
$router->post('/gorev_yonetimi/toplu_durum', [IsEmriController::class, 'topluDurum'], $staff);
$router->post('/gorev_yonetimi/multiple_arsiv', [IsEmriController::class, 'topluArsiv'], $staff);
$router->get('/gorev_yonetimi/sablon', [IsEmriController::class, 'sablon'], $staff);

// Destek talepleri
$router->get('/destek_talepleri', [DestekController::class, 'index'], $staff);
$router->get('/destek_talepleri/liste', [DestekController::class, 'liste'], $staff);
$router->post('/destek_talepleri/save', [DestekController::class, 'save'], $staff);
$router->get('/destek_talepleri/getir/{id}', [DestekController::class, 'getir'], $staff);
$router->post('/destek_talepleri/cevapla/{id}', [DestekController::class, 'cevapla'], $staff);
$router->post('/destek_talepleri/multiple_arsiv', [DestekController::class, 'topluArsiv'], $staff);

// Yetki yönetimi
$router->get('/yetki_yonetimi', [YetkiController::class, 'index'], $staff);
$router->get('/yetki_yonetimi/profil_izinleri', [YetkiController::class, 'profilIzinleri'], $staff);
$router->post('/yetki_yonetimi/profil_kaydet', [YetkiController::class, 'profilKaydet'], $staff);
$router->post('/yetki_yonetimi/izin_kaydet', [YetkiController::class, 'izinKaydet'], $staff);
$router->post('/yetki_yonetimi/profil_sil', [YetkiController::class, 'profilSil'], $staff);
$router->post('/yetki_yonetimi/kullanici_kaydet', [YetkiController::class, 'kullaniciKaydet'], $staff);

// Hesabım
$router->get('/kullanici/hesabim', [HesapController::class, 'index'], $auth);
$router->post('/kullanici/hesabim', [HesapController::class, 'update'], $auth);

$router->get('/tema_onizleme', [TemaController::class, 'index'], $staff);

// Proje Notları = sürüm geçmişi
$router->get('/surumler', [SurumController::class, 'index'], $staff);
$router->get('/proje_notlari', [SurumController::class, 'index'], $staff);

// Eski karşılaştırma rotaları (menüde yok; doğrudan URL ile erişim)
$router->get('/referans_md_karsilastirma', [KarsilastirmaController::class, 'index'], $staff);
$router->get('/referans_md_karsilastirma/indir', [KarsilastirmaController::class, 'indir'], $staff);
$router->post('/referans_md_karsilastirma/kaydet', [KarsilastirmaController::class, 'kaydet'], $staff);
