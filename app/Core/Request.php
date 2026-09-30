<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public static function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;

        return is_string($value) ? trim($value) : $value;
    }

    public static function str(string $key): ?string
    {
        $value = self::input($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function int(string $key): ?int
    {
        $value = self::input($key);

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    public static function decimal(string $key): ?float
    {
        $value = self::input($key);
        if (!is_string($value) || $value === '') {
            return null;
        }
        if (str_contains($value, ',')) {
            $value = str_replace(['.', ','], ['', '.'], $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    public static function date(string $key): ?string
    {
        $value = self::str($key);

        return $value !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    public static function dateTime(string $dateKey, string $timeKey): ?string
    {
        $date = self::date($dateKey);
        if ($date === null) {
            return null;
        }
        $time = self::str($timeKey);

        return $date . ' ' . ($time !== null && preg_match('/^\d{2}:\d{2}$/', $time) ? $time : '00:00') . ':00';
    }

    public static function bool(string $key): bool
    {
        return in_array(self::input($key), ['1', 'on', 'true', 1, true], true);
    }

    public static function ids(string $key): array
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? [];

        return array_values(array_unique(array_filter(array_map('intval', (array) $value), fn ($v) => $v > 0)));
    }

    public static function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }
}
