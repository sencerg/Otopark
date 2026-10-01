<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Env;

$user = Auth::user();
$appName = Env::get('APP_NAME');
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= Csrf::token() ?>">
    <title><?= e($pageTitle ?? '') ?> | <?= e($appName) ?></title>
    <?php require BASE_PATH . '/views/partials/tema_baslat.php'; ?>
    <link rel="stylesheet" href="/vendor/fonts/fonts.css">
    <link rel="stylesheet" href="/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/vendor/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="/vendor/datatables/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="/vendor/select2/select2.min.css">
    <link rel="stylesheet" href="/vendor/select2/select2-bootstrap-5-theme.min.css">
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/app.css') ?>">
    <link rel="stylesheet" href="/assets/css/themes.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/themes.css') ?>">
</head>
<body>
<div class="layout" id="layout">
    <aside class="sidebar" id="sidebar">
        <a href="/" class="sidebar-brand">
            <span class="brand-mark"><i class="mdi mdi-parking"></i></span>
            <span class="brand-text"><?= e($appName) ?></span>
        </a>
        <nav class="sidebar-scroll">
            <?php require BASE_PATH . '/views/partials/menu.php'; ?>
        </nav>
    </aside>
    <div class="sidebar-backdrop" data-toggle-sidebar></div>

    <div class="main">
        <header class="topbar">
            <button type="button" class="topbar-btn" data-toggle-sidebar aria-label="Menü"><i class="mdi mdi-menu"></i></button>
            <form class="topbar-search" action="/arama" method="get">
                <i class="mdi mdi-magnify"></i>
                <input type="search" name="q" placeholder="Arama Yap... (şasi, plaka)" autocomplete="off" value="<?= e($_GET['q'] ?? '') ?>">
            </form>
            <div class="topbar-actions">
                <button type="button" class="btn btn-sm btn-primary d-none d-md-inline-flex" data-bs-toggle="modal" data-bs-target="#hizli-arac-ekle-modal">
                    <i class="mdi mdi-car-arrow-right me-1"></i> Hızlı Araç Ekle
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary d-none d-md-inline-flex" data-bs-toggle="modal" data-bs-target="#hizli-maliyet-ekle-modal">
                    <i class="mdi mdi-cash-plus me-1"></i> Hızlı Maliyet Ekle
                </button>
                <div class="dropdown theme-switch">
                    <button class="topbar-btn fs-4" data-bs-toggle="dropdown" type="button" title="Tema ve görünüm" aria-label="Tema ve görünüm">
                        <i class="mdi mdi-weather-sunny" data-mod-ikon></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" style="min-width: 210px">
                        <h6 class="dropdown-header">Tema</h6>
                        <button class="dropdown-item" type="button" data-tema-sec="klasik"><i class="mdi mdi-palette-outline me-2"></i>Klasik</button>
                        <button class="dropdown-item" type="button" data-tema-sec="vuexy"><i class="mdi mdi-palette-swatch-outline me-2"></i>Vuexy</button>
                        <div class="dropdown-divider"></div>
                        <h6 class="dropdown-header">Görünüm</h6>
                        <button class="dropdown-item" type="button" data-mod-sec="light"><i class="mdi mdi-weather-sunny me-2"></i>Açık</button>
                        <button class="dropdown-item" type="button" data-mod-sec="dark"><i class="mdi mdi-weather-night me-2"></i>Koyu</button>
                        <button class="dropdown-item" type="button" data-mod-sec="system"><i class="mdi mdi-monitor me-2"></i>Sistem</button>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="/tema_onizleme"><i class="mdi mdi-eye-outline me-2"></i>Tema Önizleme</a>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="topbar-user" data-bs-toggle="dropdown" type="button">
                        <span class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'] ?? '?', 0, 1))) ?></span>
                        <span class="d-none d-md-inline text-start">
                            <strong><?= e($user['name'] ?? '') ?></strong>
                            <small><?= e($user['bayi_adi'] ?? 'Tüm Lokasyonlar') ?></small>
                        </span>
                        <i class="mdi mdi-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="/kullanici/hesabim"><i class="mdi mdi-account-circle-outline me-2"></i>Hesabım</a>
                        <div class="dropdown-divider"></div>
                        <form method="post" action="/logout">
                            <?= Csrf::field() ?>
                            <button class="dropdown-item text-danger" type="submit"><i class="mdi mdi-logout me-2"></i>Çıkış Yap</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="page-content">
            <?php if (!empty($pageTitle) && empty($hidePageHeader)): ?>
                <div class="page-header">
                    <div>
                        <h4 class="page-title"><?= e($pageTitle) ?></h4>
                        <?php if (!empty($breadcrumb)): ?>
                            <nav class="breadcrumb-nav">
                                <a href="/">Ana Sayfa</a>
                                <?php foreach ($breadcrumb as $label => $url): ?>
                                    <i class="mdi mdi-chevron-right"></i>
                                    <?php if (is_string($url)): ?><a href="<?= e($url) ?>"><?= e($label) ?></a><?php else: ?><span><?= e($label) ?></span><?php endif; ?>
                                <?php endforeach; ?>
                            </nav>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($headerActions)): ?><div class="page-actions"><?= $headerActions ?></div><?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($msg = flash('success')): ?><div class="alert alert-success alert-dismissible fade show"><i class="mdi mdi-check-circle me-1"></i><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <?php if ($msg = flash('error')): ?><div class="alert alert-danger alert-dismissible fade show"><i class="mdi mdi-alert-circle me-1"></i><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

            <?= $content ?>
        </main>
        <footer class="footer"><?= date('Y') ?> © <?= e($appName) ?></footer>
    </div>
</div>

<?php require BASE_PATH . '/views/partials/modals.php'; ?>

<script src="/vendor/jquery/jquery.min.js"></script>
<script src="/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="/vendor/datatables/dataTables.bootstrap5.min.js"></script>
<script src="/vendor/select2/select2.min.js"></script>
<script src="/vendor/select2/tr.js"></script>
<script src="/vendor/sweetalert2/sweetalert2.all.min.js"></script>
<script src="/vendor/chartjs/chart.umd.min.js"></script>
<script src="/assets/js/app.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/app.js') ?>"></script>
<?php if (!empty($scripts)): ?><?= $scripts ?><?php endif; ?>
</body>
</html>
