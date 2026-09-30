<?php

declare(strict_types=1);

use App\Core\Database;

require dirname(__DIR__) . '/bootstrap.php';

$pdo = Database::connection();
$pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
    name VARCHAR(255) PRIMARY KEY,
    ran_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
)');

$ran = array_column(Database::fetchAll('SELECT name FROM migrations'), 'name');
$files = glob(BASE_PATH . '/database/migrations/*.sql');
sort($files);

$count = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $ran, true)) {
        continue;
    }

    Database::transaction(function (PDO $pdo) use ($file, $name) {
        $pdo->exec(file_get_contents($file));
        Database::query('INSERT INTO migrations (name) VALUES (:name)', ['name' => $name]);
    });

    echo "Çalıştırıldı: {$name}\n";
    $count++;
}

echo $count === 0 ? "Yeni migration yok.\n" : "{$count} migration tamamlandı.\n";
