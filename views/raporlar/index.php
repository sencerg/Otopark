<?php
use App\Services\RaporKatalog;

$interaktif = array_values(array_filter($raporlar, fn ($r) => $r['tip'] === 'interaktif'));
$hazirla = array_values(array_filter($raporlar, fn ($r) => $r['tip'] === 'hazirla'));
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h4 class="page-title mb-1">Raporlar</h4>
        <p class="text-muted mb-0 small">Yetkinize açık raporlar burada listelenir. Interaktif raporlarda canlı filtre; «Seçerek hazırla» tipinde parametre seçip çıktı alırsınız.</p>
    </div>
</div>

<?php if (!$raporlar): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="mdi mdi-file-chart-outline display-6 d-block mb-2"></i>
        Görüntüleyebileceğiniz rapor yok. Yetki yöneticinizden ilgili rapor izinlerini isteyin.
    </div></div>
<?php else: ?>

<?php if ($interaktif): ?>
    <h6 class="text-uppercase text-muted mb-2" style="letter-spacing:.04em;font-size:12px">
        <i class="mdi mdi-monitor-dashboard me-1"></i>Interaktif raporlar
    </h6>
    <div class="row g-3 mb-4">
        <?php foreach ($interaktif as $r):
            [, $renk, $alt] = RaporKatalog::tipEtiket($r['tip']);
            ?>
            <div class="col-md-6 col-xl-4">
                <a href="<?= e($r['url']) ?>" class="card h-100 text-decoration-none rapor-kart">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <span class="kpi-icon" style="background:#2563eb"><i class="mdi <?= e($r['ikon']) ?>"></i></span>
                            <span class="badge badge-soft-<?= e($renk) ?>">Interaktif</span>
                        </div>
                        <h5 class="mb-1"><?= e($r['ad']) ?></h5>
                        <p class="small text-muted mb-2"><?= e($r['aciklama']) ?></p>
                        <div class="small text-primary"><?= e($alt) ?> <i class="mdi mdi-arrow-right"></i></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($hazirla): ?>
    <h6 class="text-uppercase text-muted mb-2" style="letter-spacing:.04em;font-size:12px">
        <i class="mdi mdi-file-cog-outline me-1"></i>Seçerek hazırla
    </h6>
    <div class="row g-3">
        <?php foreach ($hazirla as $r):
            [, $renk, $alt] = RaporKatalog::tipEtiket($r['tip']);
            ?>
            <div class="col-md-6 col-xl-4">
                <a href="<?= e($r['url']) ?>" class="card h-100 text-decoration-none rapor-kart">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <span class="kpi-icon" style="background:#f59e0b"><i class="mdi <?= e($r['ikon']) ?>"></i></span>
                            <span class="badge badge-soft-<?= e($renk) ?>">Seçerek hazırla</span>
                        </div>
                        <h5 class="mb-1"><?= e($r['ad']) ?></h5>
                        <p class="small text-muted mb-2"><?= e($r['aciklama']) ?></p>
                        <div class="small text-primary"><?= e($alt) ?> <i class="mdi mdi-arrow-right"></i></div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php endif; ?>

<style>
.rapor-kart { transition: box-shadow .15s, transform .15s; color: inherit; }
.rapor-kart:hover { box-shadow: 0 8px 24px rgba(15,23,42,.08); transform: translateY(-2px); }
.rapor-kart .kpi-icon { width: 40px; height: 40px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 1.25rem; }
</style>
