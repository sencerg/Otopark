<?php
use App\Core\Csrf;
use App\Core\Env;
$appName = Env::get('APP_NAME', 'Moex Otopark');
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="<?= Csrf::token() ?>">
    <title><?= e($pageTitle ?? 'Detaylı Araç Ekle') ?> | <?= e($appName) ?></title>
    <link rel="stylesheet" href="/vendor/fonts/fonts.css">
    <link rel="stylesheet" href="/vendor/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="/assets/css/vis.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/vis.css') ?>">
</head>
<body class="vis-body">
<?= $content ?>
<script src="/vendor/jquery/jquery.min.js"></script>
<script src="/assets/js/vis.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/vis.js') ?>"></script>
<?php if (!empty($scripts)): ?><?= $scripts ?><?php endif; ?>
</body>
</html>
