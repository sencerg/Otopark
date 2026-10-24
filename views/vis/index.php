<header class="vis-top">
    <a href="/" class="vis-icon-btn" title="Ana sistem"><i class="mdi mdi-arrow-left"></i></a>
    <h1>Araç ara</h1>
    <a href="/vis" class="vis-icon-btn"><i class="mdi mdi-cog-outline"></i></a>
</header>

<div class="vis-search-bar">
    <form method="get" action="/vis" class="vis-search-form">
        <i class="mdi mdi-magnify"></i>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="En az 2 karakter (plaka / şasi)" autocomplete="off">
    </form>
</div>

<div class="vis-toolbar">
    <button type="button" class="vis-chip" disabled>Grupları görüntüle</button>
    <button type="button" class="vis-chip" disabled>Filter location</button>
</div>

<main class="vis-main vis-home">
    <?php if ($q === '' && !$liste): ?>
        <div class="vis-empty">
            <i class="mdi mdi-check-circle-outline"></i>
            <div>Araç ara</div>
            <p class="small">Bağımsız ekspertiz modülü — kayıtlar stoka yazılmaz.</p>
        </div>
    <?php elseif ($liste): ?>
        <ul class="vis-list">
            <?php foreach ($liste as $r): ?>
                <li>
                    <a href="/vis/kayit/<?= e($r['id']) ?>">
                        <strong><?= e($r['veri']['plaka'] ?? 'Plakasız') ?></strong>
                        <span><?= e(trim(($r['veri']['marka'] ?? '') . ' ' . ($r['veri']['model'] ?? '')) ?: '—') ?></span>
                        <span class="muted"><?= e($r['veri']['sase'] ?? '') ?> · <?= e($r['durum'] ?? '') ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="vis-empty"><p>Sonuç yok.</p></div>
    <?php endif; ?>
</main>

<footer class="vis-bottom">
    <a href="/" class="vis-btn ghost">Geri</a>
    <a href="/vis/yeni" class="vis-btn primary">Yeni araç açıkla</a>
</footer>
