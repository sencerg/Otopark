<?php

declare(strict_types=1);

return [
    ['title' => 'Saha Operasyon Dashboard', 'icon' => 'mdi-view-dashboard-outline', 'url' => '/'],
    ['title' => 'Saha Operasyon Araç Yönetimi', 'icon' => 'mdi-car-multiple', 'children' => [
        ['title' => 'Araç Yönetimi Dashboard', 'url' => '/saha_dashboard'],
        ['title' => 'Stoktaki Araçlar', 'url' => '/arac_hareketleri/giris'],
        ['title' => 'Stoktan Çıkanlar', 'url' => '/arac_hareketleri/cikis'],
        ['title' => 'Tüm Araçlar', 'url' => '/arac_yonetimi'],
        ['title' => 'Araç Ekle', 'url' => '/arac_yonetimi/ekle'],
        ['title' => 'Toplu İşlemler', 'children' => [
            ['title' => 'Toplu Stok Girişi', 'url' => '/arac_yonetimi/toplu_stok_girisi'],
            ['title' => 'Toplu Ek Maliyet Girişi', 'url' => '/arac_yonetimi/toplu_ek_maliyet_girisi'],
            ['title' => 'Adres (Lokasyon) Düzenle', 'url' => '/arac_yonetimi/toplu_kayit_guncelleme_lokasyon'],
        ]],
        ['title' => 'Araç Sayımları', 'url' => '/sayim_kayitlari'],
        ['title' => 'Saha Operasyon İş Takibi', 'url' => '/is_takibi'],
        ['title' => 'Raporlar', 'children' => [
            ['title' => 'Stok Araç Depolama Lokasyon Raporu', 'url' => '/depolama_raporu'],
            ['title' => 'Saha Operasyon Ek Hizmetler Lokasyon Raporu', 'url' => '/ek_hizmet_raporu'],
        ]],
        ['title' => 'Saha Operasyon Tanımları', 'children' => [
            ['title' => 'Araç Tanımları', 'url' => '/arac_yonetimi/tanimlar'],
        ]],
    ]],
    ['title' => 'Operasyon İş Emri Yönetimi', 'icon' => 'mdi-clipboard-text-outline', 'children' => [
        ['title' => 'İş Emri Oluştur', 'url' => '/gorev_yonetimi/ekle'],
        ['title' => 'Bekleyen İş Emri Talepleri', 'url' => '/gorev_yonetimi?durum=1'],
        ['title' => 'Tamamlanan İş Emri Talepleri', 'url' => '/gorev_yonetimi?durum=4'],
        ['title' => 'Tüm İş Emirleri', 'url' => '/gorev_yonetimi'],
    ]],
    ['title' => 'İK ve Satın Alma Yönetimi', 'icon' => 'mdi-account-group-outline', 'children' => [
        ['title' => 'Dashboard', 'url' => '/ik_dashboard'],
        ['title' => 'Destek Talepleri', 'url' => '/destek_talepleri'],
    ]],
    ['title' => 'Tanımlamalar', 'icon' => 'mdi-cog-outline', 'url' => '/tanimlamalar'],
    ['title' => 'Proje Notları', 'icon' => 'mdi-notebook-outline', 'children' => [
        ['title' => 'BİA ↔ MD Karşılaştırma', 'url' => '/bia_md_karsilastirma'],
        ['title' => 'Tema Önizleme', 'url' => '/tema_onizleme'],
    ]],
];
