<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;

require dirname(__DIR__) . '/bootstrap.php';

if (Env::get('APP_ENV') !== 'local') {
    fwrite(STDERR, "reset sadece APP_ENV=local ortamında çalışır.\n");
    exit(1);
}

Database::connection()->exec('DROP SCHEMA public CASCADE; CREATE SCHEMA public;');
echo "Veritabanı temizlendi.\n";

passthru(PHP_BINARY . ' ' . escapeshellarg(BASE_PATH . '/bin/migrate.php'));
passthru(PHP_BINARY . ' ' . escapeshellarg(BASE_PATH . '/bin/seed.php') . ' ' . implode(' ', array_slice($argv, 1)));
