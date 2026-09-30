<?php
use App\Core\Auth;
use App\Core\Tanim;

$tl = fn ($v) => number_format((float) $v, 0, ',', '.');
$maliyetToplam = array_sum(array_column($maliyetler, 'tutar'));
$stokToplam = array_sum(array_column($firmalar, 'adet'));
?>
<?php if (!Auth::bayiId()): ?>
    <form class="d-flex gap-2 align-items-end mb-3" method="get">
        <div><label class="form-label small mb-1">Lokasyon</label><select name="bayi_id" class="form-select form-select-sm" onchange="this.form.submit()"><?= Tanim::options(Tanim::liste('bayiler'), $bayiId, 'Tüm Lokasyonlar') ?></select></div>
    </form>
<?php endif; ?>

<div class="row g-3 mb-3">
    <?php foreach ([['0-30 Gün', $sureler['a'], '#10b981'], ['31-90 Gün', $sureler['b'], '#2563eb'], ['91-180 Gün', $sureler['c'], '#f59e0b'], ['180+ Gün', $sureler['d'], '#ef4444']] as [$ad, $n, $renk]): ?>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <div class="kpi-head"><span class="kpi-icon" style="background:<?= $renk ?>"><i class="mdi mdi-timer-sand"></i></span>Stokta <?= $ad ?></div>
                <div class="kpi-value"><?= $tl($n) ?></div>
                <div class="kpi-unit">Araç</div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header"><h5>Yıllık Giriş / Çıkış</h5></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>Dönem</th><th class="text-end">Yıllık Giren (Adet)</th><th class="text-end">Yıllık Çıkan (Adet)</th><th class="text-end">Günlük Giren (Ort.)</th><th class="text-end">Günlük Çıkan (Ort.)</th><th class="text-end">Anlık Stok (Adet)</th></tr></thead>
            <tbody>
            <?php foreach ($yillar as $y): ?>
                <tr>
                    <td class="fw-semibold"><?= $y['yil'] ?></td>
                    <td class="text-end"><?= $tl($y['giren']) ?></td>
                    <td class="text-end"><?= $tl($y['cikan']) ?></td>
                    <td class="text-end"><?= number_format($y['gunluk_giren'], 1, ',', '.') ?></td>
                    <td class="text-end"><?= number_format($y['gunluk_cikan'], 1, ',', '.') ?></td>
                    <td class="text-end fw-semibold"><?= $tl($y['stok']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header"><h5>Maliyet Kalemleri (<?= date('Y') ?>)</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Maliyet Kalemi</th><th class="text-end">Tutar (₺)</th><th style="width:35%">Toplam Maliyete Oranı</th></tr></thead>
                    <tbody>
                    <?php foreach ($maliyetler as $m): $oran = $maliyetToplam > 0 ? $m['tutar'] / $maliyetToplam * 100 : 0; ?>
                        <tr>
                            <td><?= e($m['ad']) ?></td>
                            <td class="text-end fw-semibold">₺<?= $tl($m['tutar']) ?></td>
                            <td><div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:6px"><div class="progress-bar" style="width:<?= round($oran, 1) ?>%"></div></div><span class="small text-muted">%<?= number_format($oran, 1, ',', '.') ?></span></div></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$maliyetler): ?><tr><td colspan="3" class="text-center text-muted py-4">Bu yıl maliyet kaydı yok.</td></tr><?php endif; ?>
                    </tbody>
                    <?php if ($maliyetler): ?><tfoot><tr><th>Toplam</th><th class="text-end">₺<?= $tl($maliyetToplam) ?></th><th></th></tr></tfoot><?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header"><h5>Firma Bazında Stok</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Firma</th><th class="text-end">Stok Adedi</th><th class="text-end">Oran (%)</th></tr></thead>
                    <tbody>
                    <?php foreach ($firmalar as $f): ?>
                        <tr>
                            <td><?= e($f['ad']) ?></td>
                            <td class="text-end fw-semibold"><?= $tl($f['adet']) ?></td>
                            <td class="text-end">%<?= number_format($stokToplam ? $f['adet'] / $stokToplam * 100 : 0, 1, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$firmalar): ?><tr><td colspan="3" class="text-center text-muted py-4">Stokta araç yok.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
