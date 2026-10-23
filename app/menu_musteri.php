<?php

declare(strict_types=1);

return [
    ['title' => 'Araçlarım', 'icon' => 'mdi-car-multiple', 'url' => '/musteri', 'izin' => 'musteri.dashboard'],
    ['title' => 'Stoktaki Araçlar', 'icon' => 'mdi-garage', 'url' => '/musteri/stok', 'izin' => 'musteri.stok'],
    ['title' => 'Stoktan Çıkanlar', 'icon' => 'mdi-exit-run', 'url' => '/musteri/cikanlar', 'izin' => 'musteri.cikanlar'],
    ['title' => 'Aldığım Hizmetler', 'icon' => 'mdi-cash-multiple', 'url' => '/musteri/hizmetler', 'izin' => 'musteri.hizmetler'],
    ['title' => 'Hesabım', 'icon' => 'mdi-account-circle-outline', 'url' => '/kullanici/hesabim'],
];
