<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giriş Yap | <?= e(\App\Core\Env::get('APP_NAME', 'Moex Otopark')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/vendor/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/app.css') ?>">
    <link rel="stylesheet" href="/assets/css/auth.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/auth.css') ?>">
</head>
<body class="auth-body">
<?= $content ?>
</body>
</html>
