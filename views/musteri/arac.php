<?php
$tl = static fn ($v) => number_format((float) $v, 2, ',', '.') . ' ₺';
$gunFmt = static fn ($d) => $d ? date('d.m.Y H:i', strtotime((string) $d)) : '—';
$depToplam = array_sum(array_map(fn ($k) => (float) ($k['tutar'] ?? 0), $konaklamalar));
$hizToplam = array_sum(array_map(fn ($h) => (float) $h['tutar'], $hizmetler));
?>
<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Araç Bilgisi</h5></div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5">Plaka</dt><dd class="col-7 fw-semibold"><?= e($arac['plaka'] ?: '—') ?></dd>
                    <dt class="col-5">Şasi</dt><dd class="col-7 font-monospace"><?= e($arac['sase']) ?></dd>
                    <dt class="col-5">Marka / Seri</dt><dd class="col-7"><?= e(trim(($arac['marka'] ?? '') . ' ' . ($arac['seri'] ?? '')) ?: '—') ?></dd>
                    <dt class="col-5">Tip</dt><dd class="col-7"><?= e($arac['arac_tipi'] ?? '—') ?></dd>
                    <dt class="col-5">Otopark</dt><dd class="col-7"><?= e($arac['otopark'] ?? '—') ?></dd>
                    <dt class="col-5">Lokasyon</dt><dd class="col-7"><?= e($arac['lokasyon_detay'] ?? '—') ?></dd>
                    <dt class="col-5">Durum</dt><dd class="col-7"><?= $arac['stokta'] ? '<span class="badge bg-success">Stokta</span>' : '<span class="badge bg-secondary">Çıkmış</span>' ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="row g-3">
            <div class="col-sm-6">
                <div class="kpi-card"><div class="kpi-head">Depolama toplam</div><div class="kpi-value" style="font-size:1.4rem"><?= $tl($depToplam) ?></div></div>
            </div>
            <div class="col-sm-6">
                <div class="kpi-card"><div class="kpi-head">Ek hizmet toplam</div><div class="kpi-value" style="font-size:1.4rem"><?= $tl($hizToplam) ?></div></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h5 class="mb-0">Depolama / Konaklama</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                <tr>
                    <th>Otopark</th>
                    <th>Giriş</th>
                    <th>Çıkış</th>
                    <th class="text-end">Gün</th>
                    <th class="text-end">Günlük</th>
                    <th class="text-end">Tutar</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$konaklamalar): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Kayıt yok</td></tr>
                <?php else: foreach ($konaklamalar as $k): ?>
                    <tr>
                        <td><?= e($k['bayi'] ?? '—') ?></td>
                        <td><?= $gunFmt($k['giris_tarihi']) ?></td>
                        <td><?= $k['cikis_tarihi'] ? $gunFmt($k['cikis_tarihi']) : '<span class="badge bg-primary-subtle text-primary">Devam ediyor</span>' ?></td>
                        <td class="text-end"><?= (int) $k['gun'] ?></td>
                        <td class="text-end">
                            <?php if (!empty($k['fiyat_yok'])): ?>
                                <span class="text-warning">Tanımsız</span>
                            <?php elseif (!empty($k['fiyat_degisti'])): ?>
                                <span class="small"><?= e(\App\Services\DepolamaHesap::fiyatAciklama($k)) ?></span>
                            <?php else: ?>
                                <?= $tl($k['gunluk_fiyat']) ?>
                                <?php if ((float) ($k['carpan'] ?? 1) != 1.0): ?>
                                    <div class="text-muted small"><?= number_format((float) $k['taban_fiyat'], 2, ',', '.') ?> × <?= number_format((float) $k['carpan'], 2, ',', '.') ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-semibold"><?= $tl($k['tutar']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Ek Hizmetler</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                <tr><th>Tarih</th><th>Hizmet</th><th>Açıklama</th><th class="text-end">Tutar</th></tr>
                </thead>
                <tbody>
                <?php if (!$hizmetler): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">Hizmet yok</td></tr>
                <?php else: foreach ($hizmetler as $h): ?>
                    <tr>
                        <td><?= $h['islem_tarihi'] ? date('d.m.Y', strtotime($h['islem_tarihi'])) : '—' ?></td>
                        <td><?= e($h['hizmet']) ?></td>
                        <td class="small text-muted"><?= e($h['aciklama'] ?? '') ?></td>
                        <td class="text-end fw-semibold"><?= $tl($h['tutar']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3 d-flex flex-wrap gap-2">
    <a href="/musteri" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i>Araç listesine dön</a>
    <a href="/musteri/excel/arac/<?= (int) $arac['id'] ?>" class="btn btn-success">
        <i class="mdi mdi-microsoft-excel me-1"></i>Excel İndir
    </a>
</div>
