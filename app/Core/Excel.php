<?php

declare(strict_types=1);

namespace App\Core;

/** Excel'in Türkçe ayarlarda doğrudan açtığı, noktalı virgül ayraçlı UTF-8 CSV. */
final class Excel
{
    public static function download(string $fileName, array $headers, iterable $rows): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '-' . date('Ymd-Hi') . '.csv"');

        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array_values($headers), ';', '"', '\\');
        foreach ($rows as $row) {
            $line = [];
            foreach (array_keys($headers) as $key) {
                $line[] = strip_tags((string) ($row[$key] ?? ''));
            }
            fputcsv($out, $line, ';', '"', '\\');
        }
        fclose($out);
        exit;
    }

    /** CSV veya noktalı virgül/virgül ayraçlı dosyayı başlık satırına göre okur. */
    public static function read(string $path): array
    {
        $handle = fopen($path, 'rb');
        $first = fgets($handle);
        if ($first === false) {
            return [];
        }
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
        $delimiter = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ',';
        $headers = array_map(fn ($h) => mb_strtolower(trim($h)), str_getcsv($first, $delimiter, '"', '\\'));

        $rows = [];
        while (($data = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $rows[] = array_combine($headers, array_pad(array_map('trim', $data), count($headers), ''));
        }
        fclose($handle);

        return $rows;
    }
}
