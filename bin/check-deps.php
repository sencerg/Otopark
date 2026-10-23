<?php

declare(strict_types=1);

/**
 * Canlı / sunucu bağımlılık kontrolü.
 * Kullanım: php bin/check-deps.php
 */
require dirname(__DIR__) . '/bootstrap.php';

$ok = true;
$checks = [
    'PHP >= 8.2' => version_compare(PHP_VERSION, '8.2.0', '>='),
    'ext-pdo' => extension_loaded('pdo'),
    'ext-pdo_pgsql' => extension_loaded('pdo_pgsql'),
    'ext-mbstring' => extension_loaded('mbstring'),
    'ext-zip (XLSX için)' => extension_loaded('zip'),
    'ext-xml' => extension_loaded('xml'),
    'vendor/autoload.php' => is_file(BASE_PATH . '/vendor/autoload.php'),
    'phpoffice/phpspreadsheet' => class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class),
];

foreach ($checks as $ad => $pass) {
    echo ($pass ? '[OK] ' : '[EKSIK] ') . $ad . PHP_EOL;
    $ok = $ok && $pass;
}

if (!$ok) {
    echo PHP_EOL . 'Eksikler var. Proje kökünde çalıştırın:' . PHP_EOL;
    echo '  composer install --no-dev --optimize-autoloader' . PHP_EOL;
    exit(1);
}

echo PHP_EOL . 'Tüm bağımlılıklar hazır.' . PHP_EOL;
exit(0);
