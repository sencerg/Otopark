<?php

declare(strict_types=1);

return [
    ['title' => 'Saha Operasyon Dashboard', 'icon' => 'mdi-view-dashboard-outline', 'url' => '/', 'izin' => 'menu.dashboard'],
    ['title' => 'Saha Operasyon Araç Yönetimi', 'icon' => 'mdi-car-multiple', 'children' => [
        ['title' => 'Araç Yönetimi Dashboard', 'url' => '/saha_dashboard', 'izin' => 'menu.saha_dashboard'],
        ['title' => 'Stoktaki Araçlar', 'url' => '/arac_hareketleri/giris', 'izin' => 'menu.stok'],
        ['title' => 'Stoktan Çıkanlar', 'url' => '/arac_hareketleri/cikis', 'izin' => 'menu.cikis'],
        ['title' => 'Tüm Araçlar', 'url' => '/arac_yonetimi', 'izin' => 'menu.araclar'],
        ['title' => 'Araç Ekle', 'url' => '/arac_yonetimi/ekle', 'izin' => 'arac.ekle'],
        ['title' => 'Toplu İşlemler', 'izin' => 'menu.toplu', 'children' => [
            ['title' => 'Toplu Stok Girişi', 'url' => '/arac_yonetimi/toplu_stok_girisi', 'izin' => 'menu.toplu'],
            ['title' => 'Toplu Ek Maliyet Girişi', 'url' => '/arac_yonetimi/toplu_ek_maliyet_girisi', 'izin' => 'menu.toplu'],
            ['title' => 'Adres (Lokasyon) Düzenle', 'url' => '/arac_yonetimi/toplu_kayit_guncelleme_lokasyon', 'izin' => 'menu.toplu'],
        ]],
        ['title' => 'Araç Sayımları', 'url' => '/sayim_kayitlari', 'izin' => 'menu.sayim'],
        ['title' => 'Saha Operasyon İş Takibi', 'url' => '/is_takibi', 'izin' => 'menu.is_takibi'],
        ['title' => 'Raporlar', 'children' => [
            ['title' => 'Stok Araç Depolama Lokasyon Raporu', 'url' => '/depolama_raporu', 'izin' => 'menu.depolama_raporu'],
            ['title' => 'Saha Operasyon Ek Hizmetler Lokasyon Raporu', 'url' => '/ek_hizmet_raporu', 'izin' => 'menu.ek_hizmet_raporu'],
        ]],
        ['title' => 'Saha Operasyon Tanımları', 'children' => [
            ['title' => 'Araç Tanımları', 'url' => '/arac_yonetimi/tanimlar', 'izin' => 'menu.tanimlar'],
        ]],
    ]],
    ['title' => 'Operasyon İş Emri Yönetimi', 'icon' => 'mdi-clipboard-text-outline', 'izin' => 'menu.is_emri', 'children' => [
        ['title' => 'İş Emri Oluştur', 'url' => '/gorev_yonetimi/ekle', 'izin' => 'is_emri.ekle'],
        ['title' => 'Bekleyen İş Emri Talepleri', 'url' => '/gorev_yonetimi?durum=1', 'izin' => 'menu.is_emri'],
        ['title' => 'Tamamlanan İş Emri Talepleri', 'url' => '/gorev_yonetimi?durum=4', 'izin' => 'menu.is_emri'],
        ['title' => 'Tüm İş Emirleri', 'url' => '/gorev_yonetimi', 'izin' => 'menu.is_emri'],
    ]],
    ['title' => 'İK ve Satın Alma Yönetimi', 'icon' => 'mdi-account-group-outline', 'children' => [
        ['title' => 'Dashboard', 'url' => '/ik_dashboard', 'izin' => 'menu.ik_dashboard'],
        ['title' => 'Destek Talepleri', 'url' => '/destek_talepleri', 'izin' => 'menu.destek'],
    ]],
    ['title' => 'Tanımlamalar', 'icon' => 'mdi-cog-outline', 'url' => '/tanimlamalar', 'izin' => 'menu.tanimlamalar'],
    ['title' => 'Yetki Yönetimi', 'icon' => 'mdi-shield-account-outline', 'url' => '/yetki_yonetimi', 'izin' => 'menu.yetki'],
    ['title' => 'Proje Notları', 'icon' => 'mdi-notebook-outline', 'children' => [
        ['title' => 'Referans ↔ MD Karşılaştırma', 'url' => '/referans_md_karsilastirma', 'izin' => 'menu.karsilastirma'],
        ['title' => 'Tema Önizleme', 'url' => '/tema_onizleme', 'izin' => 'menu.tema', 'admin' => true],
    ]],
];
