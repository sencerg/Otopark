<?php
use App\Core\Env;
use App\Core\Tanim;

$pageTitle = 'Tesellüm Formu - ' . $arac['sase'];
$tarih = $h ? date('d.m.Y H:i', strtotime($h['hareket_tarihi'])) : '-';
$cikis = $h && (int) $h['hareket_tipi'] === 2;
?>
<div class="sheet">
    <div class="d-flex justify-content-between align-items-start border-bottom pb-2 mb-2">
        <div>
            <h3 class="mb-0">ARAÇ TESELLÜM FORMU</h3>
            <div class="text-muted"><?= e(Env::get('APP_NAME')) ?></div>
        </div>
        <div class="text-end">
            <div><b>Form No:</b> <?= $h ? 'TF-' . str_pad((string) $h['id'], 6, '0', STR_PAD_LEFT) : '-' ?></div>
            <div><b>İşlem:</b> <?= $h ? ($cikis ? 'Araç Teslim (Çıkış)' : 'Araç Teslim Alma (Giriş)') : '-' ?></div>
            <div><b>Tarih:</b> <?= $tarih ?></div>
        </div>
    </div>

    <div class="doc-title">ARAÇ BİLGİLERİ</div>
    <table class="doc">
        <tr><th>Şasi No</th><td><?= e($arac['sase']) ?></td><th>Plaka</th><td><?= e($arac['plaka'] ?: '-') ?></td></tr>
        <tr><th>Marka / Seri</th><td><?= e(trim(($arac['marka'] ?? '') . ' ' . ($arac['seri'] ?? ''))) ?: '-' ?></td><th>Model</th><td><?= e($arac['model'] ?: '-') ?></td></tr>
        <tr><th>Model Yılı</th><td><?= e($arac['yil'] ?: '-') ?></td><th>Renk</th><td><?= e($arac['renk'] ?: '-') ?></td></tr>
        <tr><th>Araç Tipi</th><td><?= e($arac['arac_tipi'] ?: '-') ?></td><th>Yakıt / Vites</th><td><?= e(trim(($arac['yakit_tipi'] ?? '') . ' / ' . ($arac['vites_tipi'] ?? ''), ' /')) ?: '-' ?></td></tr>
        <tr><th>KM</th><td><?= $h && $h['km'] !== null ? number_format((int) $h['km'], 0, ',', '.') : ($arac['km'] !== null ? number_format((int) $arac['km'], 0, ',', '.') : '-') ?></td><th>Yakıt Durumu</th><td><?= e(($h['yakit_durumu'] ?? $arac['yakit_durumu']) !== null ? '%' . ($h['yakit_durumu'] ?? $arac['yakit_durumu']) : '-') ?></td></tr>
        <tr><th>Müşteri</th><td><?= e($arac['musteri'] ?: '-') ?></td><th>Lokasyon</th><td><?= e($arac['bayi'] ?: '-') ?><?= $arac['lokasyon_detay'] ? ' / ' . e($arac['lokasyon_detay']) : '' ?></td></tr>
        <?php if ($h): ?>
            <tr><th>Hareket Tipi</th><td><?= Tanim::HAREKET_TIPI[$h['hareket_tipi']] ?? '-' ?></td><th>Sevkiyat</th><td><?= e(Tanim::SEVKIYAT_TIPI[$h['sevkiyat_tipi'] ?? 0] ?? '-') ?><?= $h['cekici_plakasi'] ? ' / ' . e($h['cekici_plakasi']) : '' ?></td></tr>
        <?php endif; ?>
    </table>

    <div class="doc-title">ARAÇ ENVANTERİ</div>
    <table class="doc">
        <?php foreach (array_chunk($tumEnvanter, 3, true) as $satir): ?>
            <tr>
                <?php foreach ($satir as $ad): ?>
                    <td style="width:33%"><?= in_array($ad, $envanter, true) ? '&#9745;' : '&#9744;' ?> <?= e($ad) ?></td>
                <?php endforeach; ?>
                <?php for ($i = count($satir); $i < 3; $i++): ?><td></td><?php endfor; ?>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="doc-title">HASAR / AÇIKLAMA</div>
    <table class="doc"><tr><td style="height:80px"><?= nl2br(e($h['aciklama'] ?? '')) ?></td></tr></table>

    <div class="doc-title">TESLİM BİLGİLERİ</div>
    <table class="doc">
        <tr>
            <th style="width:50%;text-align:center">TESLİM EDEN</th>
            <th style="width:50%;text-align:center">TESLİM ALAN</th>
        </tr>
        <tr>
            <td>
                Ad Soyad: <?= e($h['teslim_eden'] ?? '') ?><br>
                Telefon: <?= e($h['teslim_eden_telefon'] ?? '') ?><br>
                <div class="imza">İmza:</div>
            </td>
            <td>
                Ad Soyad: <?= e(($h['teslim_alan'] ?? '') ?: ($h['personel'] ?? '')) ?><br>
                Telefon: <?= e($h['teslim_alan_telefon'] ?? '') ?><br>
                <div class="imza">İmza:</div>
            </td>
        </tr>
    </table>
    <p class="small text-muted mb-0">Yukarıda bilgileri yazılı araç, belirtilen envanter ve açıklamalar doğrultusunda eksiksiz olarak teslim edilmiş / teslim alınmıştır.</p>
</div>
