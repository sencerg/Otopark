<?php

declare(strict_types=1);

namespace App\Core;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

/** CSV (Türkçe ; ayraç) ve XLSX okuma / indirme. */
final class Excel
{
    /** @param array<string,string> $headers key => başlık */
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

    /** Çok sayfalı XLSX indirir. sheets: [sayfaAdı => ['headers'=>[key=>label], 'rows'=>list<array>, 'list'=>?list<list>]] */
    public static function downloadXlsx(string $fileName, array $sheets): never
    {
        $ss = new Spreadsheet();
        $ss->removeSheetByIndex(0);
        $i = 0;
        foreach ($sheets as $title => $sheet) {
            $ws = $ss->createSheet($i++);
            $ws->setTitle(mb_substr((string) $title, 0, 31));
            if (isset($sheet['list'])) {
                foreach ($sheet['list'] as $r => $row) {
                    foreach (array_values($row) as $c => $val) {
                        $ws->setCellValue([$c + 1, $r + 1], $val);
                    }
                }
                self::styleHeader($ws, count($sheet['list'][0] ?? []));
            } else {
                $headers = $sheet['headers'] ?? [];
                $keys = array_keys($headers);
                $labels = array_values($headers);
                foreach ($labels as $c => $label) {
                    $ws->setCellValue([$c + 1, 1], $label);
                }
                $r = 2;
                foreach ($sheet['rows'] ?? [] as $row) {
                    foreach ($keys as $c => $key) {
                        $ws->setCellValue([$c + 1, $r], $row[$key] ?? '');
                    }
                    $r++;
                }
                self::styleHeader($ws, count($labels));
            }
            $colCount = max(1, Coordinate::columnIndexFromString($ws->getHighestColumn()));
            for ($col = 1; $col <= $colCount; $col++) {
                $ws->getColumnDimensionByColumn($col)->setAutoSize(true);
            }
        }
        $ss->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '-' . date('d-m-Y_H-i-s') . '.xlsx"');
        header('Cache-Control: max-age=0');
        (new Xlsx($ss))->save('php://output');
        exit;
    }

    private static function styleHeader($ws, int $colCount): void
    {
        if ($colCount < 1) {
            return;
        }
        $range = 'A1:' . Coordinate::stringFromColumnIndex($colCount) . '1';
        $ws->getStyle($range)->getFont()->setBold(true);
        $ws->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8EEF7');
        $ws->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    }

    /**
     * CSV veya XLSX okur. XLSX'te ilk çalışma sayfası veri satırlarıdır.
     * Başlıklar normalize edilip iç anahtarlara map edilir.
     * @return list<array<string,string>>
     */
    public static function read(string $path, ?string $originalName = null): array
    {
        $ext = strtolower(pathinfo($originalName ?: $path, PATHINFO_EXTENSION));
        if (in_array($ext, ['xlsx', 'xls'], true)) {
            return self::readXlsx($path);
        }

        return self::readCsv($path);
    }

    /** @return list<array<string,string>> */
    private static function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Dosya okunamadı.');
        }
        $first = fgets($handle);
        if ($first === false) {
            fclose($handle);

            return [];
        }
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first) ?? $first;
        $delimiter = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ',';
        $rawHeaders = str_getcsv($first, $delimiter, '"', '\\');
        $headers = array_map([self::class, 'mapHeader'], $rawHeaders);

        $rows = [];
        while (($data = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $assoc = [];
            foreach ($headers as $i => $key) {
                if ($key === '' || $key === null) {
                    continue;
                }
                $assoc[$key] = trim((string) ($data[$i] ?? ''));
            }
            $rows[] = $assoc;
        }
        fclose($handle);

        return $rows;
    }

    /** @return list<array<string,string>> */
    private static function readXlsx(string $path): array
    {
        $ss = IOFactory::load($path);
        $ws = $ss->getSheet(0);
        $grid = $ws->toArray(null, true, true, false);
        if (!$grid) {
            return [];
        }
        $rawHeaders = array_map(fn ($h) => trim((string) $h), $grid[0]);
        $headers = array_map([self::class, 'mapHeader'], $rawHeaders);
        $rows = [];
        for ($i = 1, $n = count($grid); $i < $n; $i++) {
            $data = $grid[$i];
            if (count(array_filter($data, fn ($v) => trim((string) ($v ?? '')) !== '')) === 0) {
                continue;
            }
            $assoc = [];
            foreach ($headers as $c => $key) {
                if ($key === '' || $key === null) {
                    continue;
                }
                $assoc[$key] = trim((string) ($data[$c] ?? ''));
            }
            $rows[] = $assoc;
        }

        return $rows;
    }

    /** Türkçe / eski şablon başlıklarını iç anahtarlara çevirir. */
    public static function mapHeader(string $header): string
    {
        $h = self::foldHeader($header);

        $map = [
            'plaka' => 'plaka',
            'sase numarasi' => 'sase',
            'sasi numarasi' => 'sase',
            'sase' => 'sase',
            'sasi' => 'sase',
            'marka' => 'marka',
            'seri' => 'seri',
            'arac durumu' => 'arac_durumu',
            'kasa tipi' => 'kasa_tipi',
            'renk' => 'renk',
            'onceki plaka' => 'onceki_plaka',
            'arac tipi' => 'arac_tipi',
            'yil' => 'yil',
            'yakit tipi' => 'yakit_tipi',
            'vites tipi' => 'vites_tipi',
            'km' => 'km',
            'yakit durumu' => 'yakit_durumu',
            'lokasyon detay' => 'lokasyon_detay',
            'sevkiyat kodu' => 'sevkiyat_kodu',
            'irsaliye kodu' => 'irsaliye_kodu',
            'proje adi' => 'proje_adi',
            'hizmet kalemi' => 'maliyet_tipi',
            'maliyet tipi' => 'maliyet_tipi',
            'fiyat' => 'tutar',
            'tutar' => 'tutar',
            'islem tarihi' => 'islem_tarihi',
            'aciklama' => 'aciklama',
            'fatura no' => 'fatura_no',
            'park kodu' => 'park_kodu',
        ];

        return $map[$h] ?? preg_replace('/[^a-z0-9_]/', '_', $h) ?? $h;
    }

    private static function foldHeader(string $header): string
    {
        $h = trim($header);
        $h = str_replace(['*', '：'], ['', ''], $h);
        $h = mb_strtolower($h, 'UTF-8');
        $h = strtr($h, [
            'ı' => 'i', 'İ' => 'i', 'i̇' => 'i',
            'ş' => 's', 'Ş' => 's',
            'ğ' => 'g', 'Ğ' => 'g',
            'ü' => 'u', 'Ü' => 'u',
            'ö' => 'o', 'Ö' => 'o',
            'ç' => 'c', 'Ç' => 'c',
        ]);
        $h = preg_replace('/\p{Mn}/u', '', $h) ?? $h;
        $h = preg_replace('/\s+/u', ' ', $h) ?? $h;

        return trim($h);
    }
}
