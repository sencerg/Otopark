<?php
$gruplar = [];
foreach ($tanimlar as $tip => $t) {
    $gruplar[$t['grup']][$tip] = $t;
}
?>
<?php foreach ($gruplar as $grup => $items): ?>
    <h6 class="text-uppercase text-muted fw-bold small mb-2 mt-2"><?= e($grup) ?> Tanımları</h6>
    <div class="row g-3 mb-3">
        <?php foreach ($items as $tip => $t): ?>
            <div class="col-6 col-md-4 col-xl-3">
                <a href="/arac_yonetimi/tanimlar/<?= $tip ?>" class="kpi-card">
                    <div class="kpi-head"><span class="kpi-icon" style="background:#2563eb"><i class="mdi <?= $t['icon'] ?>"></i></span><?= e($t['baslik']) ?></div>
                    <i class="mdi mdi-chevron-right kpi-arrow"></i>
                    <div class="kpi-sub mt-2"><?= number_format($sayilar[$tip], 0, ',', '.') ?> kayıt</div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
