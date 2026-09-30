<?php

declare(strict_types=1);

namespace App\Core;

final class Log
{
    public static function islem(string $modul, string $aciklama, ?int $ilgiliId = null): void
    {
        Database::query(
            'INSERT INTO islem_loglari (modul, aciklama, ilgili_id, kullanici_id) VALUES (:m, :a, :i, :u)',
            ['m' => $modul, 'a' => $aciklama, 'i' => $ilgiliId, 'u' => Auth::id()]
        );
    }
}
