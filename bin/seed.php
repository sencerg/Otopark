<?php

declare(strict_types=1);

use App\Core\Database;

require dirname(__DIR__) . '/bootstrap.php';

Database::query(
    "INSERT INTO users (name, email, password_hash, role)
     VALUES (:name, :email, :hash, 'admin')
     ON CONFLICT (email) DO NOTHING",
    [
        'name' => 'Yönetici',
        'email' => 'admin@otopark.local',
        'hash' => password_hash('admin123', PASSWORD_DEFAULT),
    ]
);

echo "Yönetici kullanıcısı hazır: admin@otopark.local / admin123\n";
