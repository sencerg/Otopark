<?php
use App\Core\Auth;
use App\Core\Tanim;

$tl = fn ($v) => number_format((float) $v, 0, ',', '.');
$trend = function (float $simdi, float $once): array {
    if ($once == 0) {
        return $simdi > 0 ? ['up', 'mdi-arrow-up', 'Yeni'] : ['flat', 'mdi-minus', '%0'];
    }
    $oran = ($simdi - $once) / $once * 100;

    return [$oran > 0 ? 'up' : ($oran < 0 ? 'down' : 'flat'), $oran > 0 ? 'mdi-arrow-up' : ($oran < 0 ? 'mdi-arrow-down' : 'mdi-minus'), '%' . number_format(abs($oran), 1, ',', '.')];
};
$tekGun = $bas === $bit;
$donem = $tekGun ? ($bas === date('Y-m-d') ? 'Bugün' : date('d.m.Y', strtotime($bas))) : date('d.m.Y', strtotime($bas)) . ' - ' . date('d.m.Y', strtotime($bit));
$renkler = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#64748b'];

$grafik = function (array $rows, string $alan, int $limit = 7) {
    $top = array_slice($rows, 0, $limit);
    $diger = array_sum(array_column(array_slice($rows, $limit), $alan));
    $labels = array_column($top, 'ad');
    $values = array_map('floatval', array_column($top, $alan));
    if ($diger > 0) {
        $labels[] = 'Diğer';
        $values[] = (float) $diger;
    }

    return [$labels, $values];
};
[$firmaLabels, $firmaValues] = $grafik($firmaDagilimi, 'adet');
[$hizmetLabels, $hizmetValues] = $grafik($hizmetDagilimi, 'tutar');
$hizmetToplam = array_sum($hizmetValues);
?>
<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
    <div>
        <h4 class="page-title mb-0">ANA DASHBOARD</h4>
        <div class="text-muted small">Hoş geldiniz, <?= e(Auth::user()['name'] ?? '') ?> · <?= e($donem) ?></div>
    </div>
    <form class="d-flex flex-wrap gap-2 align-items-end" method="get">
        <div><label class="form-label small mb-1">Başlangıç</label><input type="date" name="baslangic" class="form-control form-control-sm" value="<?= e($bas) ?>"></div>
        <div><label class="form-label small mb-1">Bitiş</label><input type="date" name="bitis" class="form-control form-control-sm" value="<?= e($bit) ?>"></div>
        <?php if (!Auth::bayiId()): ?>
            <div><label class="form-label small mb-1">Lokasyon</label><select name="bayi_id" class="form-select form-select-sm"><?= Tanim::options(Tanim::liste('bayiler'), $bayiId, 'Tüm Lokasyonlar') ?></select></div>
        <?php endif; ?>
        <button class="btn btn-sm btn-primary"><i class="mdi mdi-filter"></i> Uygula</button>
        <a href="/" class="btn btn-sm btn-light">Bugün</a>
    </form>
</div>

<div class="section-banner">
    <div class="banner-icon"><i class="mdi mdi-car-multiple"></i></div>
    <div><h2>Saha Operasyon</h2><p>Stok, giriş-çıkış hareketleri ve ek hizmetler</p></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-3">
        <a href="/arac_hareketleri/giris" class="kpi-card">
            <div class="kpi-head"><span class="kpi-icon" style="background:#2563eb"><i class="mdi mdi-garage"></i></span>Anlık Stok</div>
            <i class="mdi mdi-chevron-right kpi-arrow"></i>
            <div class="kpi-value"><?= $tl($anlikStok) ?></div>
            <div class="kpi-unit">Araç</div>
            <div class="kpi-sub mt-2">Şu an stokta bulunan araçlar</div>
        </a>
    </div>
    <?php foreach ([
        ['giris', 'Günlük Giriş', 'mdi-login', '#10b981', '/arac_hareketleri/giris?baslangic=' . $bas . '&bitis=' . $bit],
        ['cikis', 'Günlük Çıkış', 'mdi-logout', '#ef4444', '/arac_hareketleri/cikis?baslangic=' . $bas . '&bitis=' . $bit],
    ] as [$key, $baslik, $icon, $renk, $url]): [$sinif, $ok, $yuzde] = $trend((float) $kpi[$key][0], (float) $kpi[$key][1]); ?>
        <div class="col-md-6 col-xl-3">
            <a href="<?= e($url) ?>" class="kpi-card">
                <div class="kpi-head"><span class="kpi-icon" style="background:<?= $renk ?>"><i class="mdi <?= $icon ?>"></i></span><?= $tekGun ? $baslik : str_replace('Günlük ', 'Dönem ', $baslik) ?></div>
                <i class="mdi mdi-chevron-right kpi-arrow"></i>
                <div class="kpi-value"><?= $tl($kpi[$key][0]) ?></div>
                <div class="kpi-unit">Araç</div>
                <div class="kpi-trend <?= $sinif ?>"><i class="mdi <?= $ok ?>"></i> <?= $yuzde ?> <span class="kpi-sub fw-normal">önceki döneme göre (<?= $tl($kpi[$key][1]) ?>)</span></div>
            </a>
        </div>
    <?php endforeach; ?>
    <?php [$sinif, $ok, $yuzde] = $trend((float) $kpi['maliyet'][0]['tutar'], (float) $kpi['maliyet'][1]['tutar']); ?>
    <div class="col-md-6 col-xl-3">
        <a href="/ek_hizmet_raporu" class="kpi-card">
            <div class="kpi-head"><span class="kpi-icon" style="background:#f59e0b"><i class="mdi mdi-cash-multiple"></i></span>Ek Hizmet Tutarı</div>
            <i class="mdi mdi-chevron-right kpi-arrow"></i>
            <div class="kpi-value">₺<?= $tl($kpi['maliyet'][0]['tutar']) ?></div>
            <div class="kpi-unit"><?= $tl($kpi['maliyet'][0]['adet']) ?> işlem</div>
            <div class="kpi-trend <?= $sinif ?>"><i class="mdi <?= $ok ?>"></i> <?= $yuzde ?> <span class="kpi-sub fw-normal">önceki döneme göre</span></div>
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header"><h5>FİRMA DAĞILIMI (STOKTAKİ ARAÇLAR)</h5></div>
            <div class="card-body">
                <?php if ($firmaValues): ?>
                    <div class="chart-wrap">
                        <div class="chart-box"><canvas id="firmaChart"></canvas><div class="chart-center"><strong><?= $tl($anlikStok) ?></strong><span>ARAÇ</span></div></div>
                        <ul class="chart-legend">
                            <?php foreach ($firmaLabels as $i => $l): ?>
                                <li><span class="dot" style="background:<?= $renkler[$i % 10] ?>"></span><?= e($l) ?><span class="pct"><?= $tl($firmaValues[$i]) ?> · %<?= number_format($firmaValues[$i] / max(1, array_sum($firmaValues)) * 100, 1, ',', '.') ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?><p class="text-muted text-center py-5 mb-0">Stokta araç bulunmuyor.</p><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header"><h5>HİZMET DAĞILIMI <small class="text-muted fw-normal">(<?= e($donem) ?>)</small></h5></div>
            <div class="card-body">
                <?php if ($hizmetValues): ?>
                    <div class="chart-wrap">
                        <div class="chart-box"><canvas id="hizmetChart"></canvas><div class="chart-center"><strong>₺<?= $tl($hizmetToplam) ?></strong><span>TOPLAM</span></div></div>
                        <ul class="chart-legend">
                            <?php foreach ($hizmetLabels as $i => $l): ?>
                                <li><span class="dot" style="background:<?= $renkler[$i % 10] ?>"></span><?= e($l) ?><span class="pct">%<?= number_format($hizmetValues[$i] / max(1, $hizmetToplam) * 100, 1, ',', '.') ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?><p class="text-muted text-center py-5 mb-0">Seçilen dönemde ek hizmet kaydı yok.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header"><h5>VERİ SETİ ÖZETİ (TÜM ZAMAN)</h5></div>
            <div class="card-body">
                <div class="row g-2">
                    <?php foreach ([
                        ['Toplam Araç', $tl($ozet['toplam_arac']), '/arac_yonetimi'],
                        ['Stokta', $tl($ozet['stokta']), '/arac_hareketleri/giris'],
                        ['Stoktan Çıkan', $tl($ozet['cikan']), '/arac_hareketleri/cikis'],
                        ['Hareket Kaydı', $tl($ozet['hareket']), null],
                        ['Firma', $tl($ozet['firma']), null],
                        ['Marka', $tl($ozet['marka']), null],
                        ['Ek Hizmet İşlemi', $tl($ozet['maliyet_adet']), '/is_takibi'],
                        ['Ek Hizmet Tutarı', '₺' . $tl($ozet['maliyet_tutar']), '/ek_hizmet_raporu'],
                    ] as [$ad, $deger, $url]): ?>
                        <div class="col-6">
                            <<?= $url ? 'a href="' . $url . '"' : 'div' ?> class="mini-stat" style="background:var(--hover);box-shadow:none"><span><?= $ad ?></span><strong><?= $deger ?></strong></<?= $url ? 'a' : 'div' ?>>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header"><h5>EK MALİYET KALEMLERİ <small class="text-muted fw-normal">(<?= e($donem) ?>)</small></h5><a href="/ek_hizmet_raporu" class="btn btn-sm btn-light">Rapor</a></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Kalem</th><th class="text-end">Adet</th><th class="text-end">Tutar (₺)</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($hizmetDagilimi, 0, 8) as $h): ?>
                        <tr><td><?= e($h['ad']) ?></td><td class="text-end"><?= $tl($h['adet']) ?></td><td class="text-end fw-semibold"><?= $tl($h['tutar']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$hizmetDagilimi): ?><tr><td colspan="3" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><h5>HIZLI İŞLEMLER</h5></div>
            <div class="card-body">
                <div class="quick-actions">
                    <button type="button" class="quick-action" data-bs-toggle="modal" data-bs-target="#hizli-arac-ekle-modal"><i class="mdi mdi-car-arrow-right"></i>Hızlı Araç Ekle</button>
                    <button type="button" class="quick-action" data-bs-toggle="modal" data-bs-target="#hizli-maliyet-ekle-modal"><i class="mdi mdi-cash-plus"></i>Hızlı Maliyet Ekle</button>
                    <a href="/arac_yonetimi/toplu_stok_girisi" class="quick-action"><i class="mdi mdi-table-arrow-down"></i>Toplu Stok Girişi</a>
                    <a href="/gorev_yonetimi/ekle" class="quick-action"><i class="mdi mdi-clipboard-plus-outline"></i>İş Emri Ekle</a>
                    <a href="/depolama_raporu" class="quick-action"><i class="mdi mdi-file-chart-outline"></i>Depolama Raporu</a>
                    <a href="/destek_talepleri" class="quick-action"><i class="mdi mdi-lifebuoy"></i>Destek Talebi</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><h5>DUYURULAR &amp; HATIRLATMALAR</h5></div>
            <div class="card-body">
                <?php foreach ($duyurular as $d): ?>
                    <div class="d-flex gap-2 mb-3">
                        <i class="mdi <?= $d['tur'] === 'hatirlatma' ? 'mdi-bell-ring-outline text-warning' : 'mdi-bullhorn-outline text-primary' ?> fs-5"></i>
                        <div><div class="fw-semibold"><?= e($d['baslik']) ?></div><div class="small text-muted"><?= e($d['icerik']) ?></div><div class="small text-muted"><?= date('d.m.Y', strtotime($d['created_at'])) ?></div></div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$duyurular): ?><p class="text-muted mb-0">Aktif duyuru yok.</p><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><h5>SON İŞLEMLER</h5></div>
            <div class="card-body">
                <?php foreach ($sonIslemler as $l): ?>
                    <div class="d-flex justify-content-between gap-2 small border-bottom py-2">
                        <div><div class="fw-semibold"><?= e($l['aciklama']) ?></div><div class="text-muted"><?= e($l['name'] ?? 'Sistem') ?></div></div>
                        <div class="text-muted text-nowrap"><?= date('d.m H:i', strtotime($l['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$sonIslemler): ?><p class="text-muted mb-0">Henüz işlem yok.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function () {
    const renkler = <?= json_encode($renkler) ?>;
    const doughnut = (id, labels, data, money) => {
        const el = document.getElementById(id);
        if (!el) return;
        new Chart(el, {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: renkler, borderWidth: 2, borderColor: '#fff' }] },
            options: { cutout: '72%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => ' ' + c.label + ': ' + (money ? App.money(c.raw) : App.num(c.raw)) } } } },
        });
    };
    doughnut('firmaChart', <?= json_encode($firmaLabels, JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($firmaValues) ?>, false);
    doughnut('hizmetChart', <?= json_encode($hizmetLabels, JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($hizmetValues) ?>, true);
})();
</script>
<?php $scripts = ob_get_clean(); ?>
