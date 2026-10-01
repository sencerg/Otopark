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
use App\Controllers\RaporController;
use App\Controllers\TanimController;
use App\Controllers\TanimlamaController;
use App\Core\Auth;

/** @var \App\Core\Router $router */

$guest = [[Auth::class, 'requireGuest']];
$auth = [[Auth::class, 'requireLogin']];

$router->get('/login', [AuthController::class, 'showLogin'], $guest);
$router->post('/login', [AuthController::class, 'login'], $guest);
$router->post('/logout', [AuthController::class, 'logout'], $auth);
$router->get('/demo/sifirla', [DemoController::class, 'onay']);
$router->post('/demo/sifirla', [DemoController::class, 'sifirla']);

// Dashboardlar
$router->get('/', [DashboardController::class, 'index'], $auth);
$router->get('/ana_dashboard', [DashboardController::class, 'index'], $auth);
$router->get('/saha_dashboard', [DashboardController::class, 'saha'], $auth);
$router->get('/ik_dashboard', [DashboardController::class, 'ik'], $auth);

// Yardımcı uçlar
$router->get('/api/seriler', [ApiController::class, 'seriler'], $auth);
$router->get('/api/modeller', [ApiController::class, 'modeller'], $auth);
$router->get('/api/personeller', [ApiController::class, 'personeller'], $auth);
$router->get('/api/araclar', [ApiController::class, 'araclar'], $auth);
$router->get('/arama', [ApiController::class, 'arama'], $auth);

// Araç hareketleri (Stoktaki Araçlar / Stoktan Çıkanlar)
$router->get('/arac_hareketleri/giris', [HareketController::class, 'giris'], $auth);
$router->get('/arac_hareketleri/giris/liste', [HareketController::class, 'girisListe'], $auth);
$router->get('/arac_hareketleri/cikis', [HareketController::class, 'cikis'], $auth);
$router->get('/arac_hareketleri/cikis/liste', [HareketController::class, 'cikisListe'], $auth);
$router->get('/arac_hareketleri/hareket_view/{id}', [HareketController::class, 'goruntule'], $auth);
$router->get('/arac_hareketleri/hareket_duzenle/{id}', [HareketController::class, 'duzenle'], $auth);
$router->post('/arac_hareketleri/hareket_update/{id}', [HareketController::class, 'guncelle'], $auth);
$router->get('/arac_hareketleri/stoktan_cikar/{aracId}', [HareketController::class, 'stoktanCikarForm'], $auth);
$router->post('/arac_hareketleri/stoktan_cikar/{aracId}', [HareketController::class, 'stoktanCikar'], $auth);
$router->post('/arac_hareketleri/etiket_fiyati', [HareketController::class, 'etiketFiyati'], $auth);
$router->post('/arac_hareketleri/multiple_arsiv', [HareketController::class, 'topluArsiv'], $auth);

// Araç yönetimi
$router->get('/arac_yonetimi', [AracController::class, 'index'], $auth);
$router->get('/arac_yonetimi/liste', [AracController::class, 'liste'], $auth);
$router->get('/arac_yonetimi/ekle', [AracController::class, 'ekle'], $auth);
$router->post('/arac_yonetimi/save', [AracController::class, 'save'], $auth);
$router->get('/arac_yonetimi/duzenle/{id}', [AracController::class, 'duzenle'], $auth);
$router->post('/arac_yonetimi/update/{id}', [AracController::class, 'update'], $auth);
$router->post('/arac_yonetimi/hizli_arac_save', [AracController::class, 'hizliSave'], $auth);
$router->get('/arac_yonetimi/tesellum_formu/{id}', [AracController::class, 'tesellum'], $auth);
$router->get('/arac_yonetimi/qr_toplu', [AracController::class, 'qrToplu'], $auth);
$router->post('/arac_yonetimi/multiple_arsiv', [AracController::class, 'topluArsiv'], $auth);
$router->post('/arac_yonetimi/multiple_delete', [AracController::class, 'topluSil'], $auth);
$router->get('/arac_yonetimi/toplu_stok_girisi', [AracController::class, 'topluStokGirisi'], $auth);
$router->post('/arac_yonetimi/excel_toplu_giris', [AracController::class, 'excelTopluGiris'], $auth);
$router->get('/arac_yonetimi/toplu_ek_maliyet_girisi', [AracController::class, 'topluEkMaliyet'], $auth);
$router->post('/arac_yonetimi/excel_toplu_ek_maliyet_giris', [AracController::class, 'excelTopluMaliyet'], $auth);
$router->get('/arac_yonetimi/toplu_kayit_guncelleme_lokasyon', [AracController::class, 'topluLokasyon'], $auth);
$router->post('/arac_yonetimi/excel_toplu_kayit_guncelleme_lokasyon', [AracController::class, 'excelTopluLokasyon'], $auth);
$router->get('/arac_yonetimi/sablon/{tip}', [AracController::class, 'sablon'], $auth);
$router->post('/dosya/sil/{id}', [AracController::class, 'dosyaSil'], $auth);

// Tanımlar
$router->get('/arac_yonetimi/tanimlar', [TanimController::class, 'index'], $auth);
$router->get('/arac_yonetimi/tanimlar/{tip}', [TanimController::class, 'liste'], $auth);
$router->get('/arac_yonetimi/tanimlar/{tip}/data', [TanimController::class, 'data'], $auth);
$router->get('/arac_yonetimi/tanimlar/{tip}/getir/{id}', [TanimController::class, 'getir'], $auth);
$router->post('/arac_yonetimi/tanimlar/{tip}/kaydet', [TanimController::class, 'kaydet'], $auth);
$router->post('/arac_yonetimi/tanimlar/{tip}/sil/{id}', [TanimController::class, 'sil'], $auth);

// Tanımlamalar (müşteri, otopark, hizmet, marka/seri/model)
$router->get('/tanimlamalar', [TanimlamaController::class, 'index'], $auth);
$router->get('/tanimlamalar/{tip}/liste', [TanimlamaController::class, 'liste'], $auth);
$router->post('/tanimlamalar/{tip}/kaydet', [TanimlamaController::class, 'kaydet'], $auth);
$router->post('/tanimlamalar/{tip}/sil/{id}', [TanimlamaController::class, 'sil'], $auth);

// Ek maliyet (araç ekstresi)
$router->post('/arac_ekstreleri/ek_maliyet_save_modal', [MaliyetController::class, 'saveModal'], $auth);
$router->get('/arac_ekstreleri/maliyet_getir/{id}', [MaliyetController::class, 'getir'], $auth);
$router->post('/arac_ekstreleri/maliyet_guncelle/{id}', [MaliyetController::class, 'guncelle'], $auth);
$router->post('/arac_ekstreleri/maliyet_sil/{id}', [MaliyetController::class, 'sil'], $auth);
$router->get('/is_takibi', [MaliyetController::class, 'isTakibi'], $auth);
$router->get('/is_takibi/liste', [MaliyetController::class, 'isTakibiListe'], $auth);

// Raporlar
$router->get('/depolama_raporu', [RaporController::class, 'depolama'], $auth);
$router->get('/depolama_raporu/liste', [RaporController::class, 'depolamaListe'], $auth);
$router->get('/ek_hizmet_raporu', [RaporController::class, 'ekHizmet'], $auth);
$router->get('/ek_hizmet_raporu/liste', [RaporController::class, 'ekHizmetListe'], $auth);
$router->get('/ek_hizmet_raporu/ozet', [RaporController::class, 'ekHizmetOzet'], $auth);

// İş emri yönetimi
$router->get('/gorev_yonetimi', [IsEmriController::class, 'index'], $auth);
$router->get('/gorev_yonetimi/liste', [IsEmriController::class, 'liste'], $auth);
$router->get('/gorev_yonetimi/ekle', [IsEmriController::class, 'ekle'], $auth);
$router->post('/gorev_yonetimi/save', [IsEmriController::class, 'save'], $auth);
$router->get('/gorev_yonetimi/duzenle/{id}', [IsEmriController::class, 'duzenle'], $auth);
$router->post('/gorev_yonetimi/update/{id}', [IsEmriController::class, 'update'], $auth);
$router->post('/gorev_yonetimi/toplu_durum', [IsEmriController::class, 'topluDurum'], $auth);
$router->post('/gorev_yonetimi/multiple_arsiv', [IsEmriController::class, 'topluArsiv'], $auth);
$router->get('/gorev_yonetimi/sablon', [IsEmriController::class, 'sablon'], $auth);

// Destek talepleri
$router->get('/destek_talepleri', [DestekController::class, 'index'], $auth);
$router->get('/destek_talepleri/liste', [DestekController::class, 'liste'], $auth);
$router->post('/destek_talepleri/save', [DestekController::class, 'save'], $auth);
$router->get('/destek_talepleri/getir/{id}', [DestekController::class, 'getir'], $auth);
$router->post('/destek_talepleri/cevapla/{id}', [DestekController::class, 'cevapla'], $auth);
$router->post('/destek_talepleri/multiple_arsiv', [DestekController::class, 'topluArsiv'], $auth);

// Hesabım
$router->get('/kullanici/hesabim', [HesapController::class, 'index'], $auth);
$router->post('/kullanici/hesabim', [HesapController::class, 'update'], $auth);

// Proje notları
$router->get('/bia_md_karsilastirma', [KarsilastirmaController::class, 'index'], $auth);
$router->get('/bia_md_karsilastirma/indir', [KarsilastirmaController::class, 'indir'], $auth);
$router->post('/bia_md_karsilastirma/kaydet', [KarsilastirmaController::class, 'kaydet'], $auth);
