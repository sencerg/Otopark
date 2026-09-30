<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Yazdır') ?> | <?= e(\App\Core\Env::get('APP_NAME', 'Otopark')) ?></title>
    <link rel="stylesheet" href="/vendor/fonts/fonts.css">
    <link rel="stylesheet" href="/vendor/bootstrap/bootstrap.min.css">
    <style>
        body { font-family: 'Public Sans', sans-serif; color: #111c44; background: #eaeef7; font-size: 13px; }
        .sheet { background: #fff; max-width: 210mm; margin: 20px auto; padding: 14mm; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .print-toolbar { max-width: 210mm; margin: 16px auto 0; display: flex; justify-content: flex-end; gap: 8px; }
        h1, h2, h3, h4 { font-family: 'Inter', sans-serif; font-weight: 800; }
        table.doc { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.doc th, table.doc td { border: 1px solid #cfd6e4; padding: 5px 8px; vertical-align: top; }
        table.doc th { background: #f3f6fb; width: 22%; font-weight: 600; }
        .doc-title { background: #111c44; color: #fff; padding: 6px 10px; font-weight: 700; margin: 14px 0 0; font-size: 13px; }
        .imza { height: 70px; }
        @media print {
            body { background: #fff; }
            .sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; }
            .print-toolbar { display: none !important; }
            @page { size: A4; margin: 10mm; }
        }
    </style>
</head>
<body>
<div class="print-toolbar">
    <button class="btn btn-primary btn-sm" onclick="window.print()">Yazdır</button>
    <button class="btn btn-light btn-sm" onclick="window.close()">Kapat</button>
</div>
<?= $content ?>
<?= $scripts ?? '' ?>
</body>
</html>
