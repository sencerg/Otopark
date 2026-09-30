<?php $user = \App\Core\Auth::user(); ?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= \App\Core\Csrf::token() ?>">
    <title><?= e($pageTitle ?? '') ?> - <?= e(\App\Core\Env::get('APP_NAME')) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="sidebar-brand"><?= e(\App\Core\Env::get('APP_NAME')) ?></div>
        <nav class="sidebar-nav">
            <a href="/" class="active">Ana Dashboard</a>
        </nav>
    </aside>
    <div class="main">
        <header class="topbar">
            <h1><?= e($pageTitle ?? '') ?></h1>
            <div class="topbar-user">
                <span><?= e($user['name'] ?? '') ?></span>
                <form method="post" action="/logout">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="btn btn-link">Çıkış</button>
                </form>
            </div>
        </header>
        <main class="content">
            <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
            <?php if ($msg = flash('error')): ?><div class="alert alert-error"><?= e($msg) ?></div><?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</div>
</body>
</html>
