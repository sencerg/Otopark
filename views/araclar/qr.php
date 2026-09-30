<?php
$pageTitle = 'QR Etiket Yazdır';
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
?>
<style>
    .qr-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6mm; }
    .qr-label { border: 1px dashed #9aa5b8; border-radius: 4px; padding: 3mm; text-align: center; page-break-inside: avoid; }
    .qr-label .qr svg, .qr-label .qr img { width: 32mm; height: 32mm; }
    .qr-label .sase { font-weight: 800; font-size: 12px; letter-spacing: .5px; margin-top: 2mm; word-break: break-all; }
    .qr-label .meta { font-size: 11px; color: #4a5a6b; }
</style>
<div class="sheet">
    <?php if (!$araclar): ?>
        <p class="text-muted">Seçili araç bulunamadı.</p>
    <?php endif; ?>
    <div class="qr-grid">
        <?php foreach ($araclar as $a): ?>
            <div class="qr-label">
                <div class="qr" data-qr="<?= e($baseUrl . '/arac_yonetimi/duzenle/' . $a['id']) ?>"></div>
                <div class="sase"><?= e($a['sase']) ?></div>
                <div class="meta"><?= e($a['plaka'] ?: '') ?> <?= e(trim(($a['marka'] ?? '') . ' ' . ($a['seri'] ?? ''))) ?></div>
                <div class="meta"><?= e($a['musteri'] ?: '') ?><?= $a['lokasyon_detay'] ? ' · ' . e($a['lokasyon_detay']) : '' ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php ob_start(); ?>
<script src="/vendor/qrcode/qrcode.min.js"></script>
<script>
document.querySelectorAll('[data-qr]').forEach(function (el) {
    var qr = qrcode(0, 'M');
    qr.addData(el.dataset.qr);
    qr.make();
    el.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
});
</script>
<?php $scripts = ob_get_clean(); ?>
