<?php
$tl = static fn ($v) => number_format((float) $v, 2, ',', '.') . ' ₺';
$gunFmt = static fn ($d) => $d ? date('d.m.Y H:i', strtotime((string) $d)) : '—';
$mod = $mod ?? 'tumu';
$tumuMod = $mod === 'tumu';
$cikanMod = $mod === 'cikan';
$stokMod = $mod === 'stok';
$filtreler ??= ['markalar' => [], 'seriler' => [], 'modeller' => [], 'otoparklar' => [], 'parklar' => []];
$excelTip = match ($mod) {
    'stok' => 'stok',
    'cikan' => 'cikanlar',
    default => 'tumu',
};
$aciklama = match ($mod) {
    'stok' => 'Stoğunuzdaki araçların otopark, kalan gün ve size özel günlük ücretle hesaplanan depolama tutarları.',
    'cikan' => 'Stoktan çıkmış araçlarınızın otoparkta kaldığı gün ve size özel ücretle hesaplanan depolama tutarları.',
    default => 'Stoktaki ve stoktan çıkan tüm araçlarınız bir arada; filtre ile ayırabilirsiniz.',
};
$baslikTablo = match ($mod) {
    'stok' => 'Stoktaki Araçlar',
    'cikan' => 'Stoktan Çıkan Araçlar',
    default => 'Tüm Araçlar',
};
$colspan = $tumuMod ? 11 : ($cikanMod ? 10 : 9);
$orderCol = $tumuMod ? 6 : ($cikanMod ? 5 : 4);
$opt = static function (array $items, string $hepsi = 'Tümü'): string {
    $html = '<option value="">' . e($hepsi) . '</option>';
    foreach ($items as $v) {
        $html .= '<option value="' . e($v) . '">' . e($v) . '</option>';
    }

    return $html;
};
?>
<p class="text-muted mb-3">Hoş geldiniz — <strong><?= e($musteriAdi) ?></strong>. <?= e($aciklama) ?></p>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="/musteri" class="btn btn-sm <?= $tumuMod ? 'btn-primary' : 'btn-outline-primary' ?>">
        <i class="mdi mdi-view-list me-1"></i>Tümü (<?= (int) $kpi['toplam'] ?>)
    </a>
    <a href="/musteri/stok" class="btn btn-sm <?= $stokMod ? 'btn-primary' : 'btn-outline-primary' ?>">
        <i class="mdi mdi-garage me-1"></i>Stoktaki (<?= (int) $kpi['stok'] ?>)
    </a>
    <a href="/musteri/cikanlar" class="btn btn-sm <?= $cikanMod ? 'btn-primary' : 'btn-outline-primary' ?>">
        <i class="mdi mdi-exit-run me-1"></i>Stoktan çıkan (<?= (int) $kpi['cikan'] ?>)
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-head"><span class="kpi-icon" style="background:#2563eb"><i class="mdi mdi-car-multiple"></i></span><?= $tumuMod ? 'Toplam Araç' : ($cikanMod ? 'Çıkan Araç' : 'Stoktaki Araç') ?></div>
            <div class="kpi-value" id="kpi-adet"><?= $tumuMod ? (int) $kpi['toplam'] : ($cikanMod ? (int) $kpi['cikan'] : (int) $kpi['stok']) ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-head"><span class="kpi-icon" style="background:#0ea5e9"><i class="mdi mdi-calendar-clock"></i></span>Ort. Gün</div>
            <div class="kpi-value" id="kpi-gun"><?= (int) $kpi['gun_ort'] ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-head"><span class="kpi-icon" style="background:#10b981"><i class="mdi mdi-cash"></i></span>Depolama</div>
            <div class="kpi-value" style="font-size:1.35rem" id="kpi-dep"><?= $tl($kpi['dep_tutar']) ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <a href="/musteri/hizmetler" class="kpi-card">
            <div class="kpi-head"><span class="kpi-icon" style="background:#7c3aed"><i class="mdi mdi-wrench"></i></span>Ek Hizmet</div>
            <div class="kpi-value" style="font-size:1.35rem"><?= $tl($kpi['hizmet_tutar']) ?></div>
            <div class="text-muted small"><?= (int) $kpi['hizmet_adet'] ?> kayıt</div>
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="filter-bar" id="musteri-arac-filtre">
            <div>
                <label class="form-label">Ara</label>
                <input type="search" id="f-q" class="form-control" placeholder="Plaka / şasi" autocomplete="off">
            </div>
            <div>
                <label class="form-label">Marka</label>
                <select id="f-marka" class="form-select"><?= $opt($filtreler['markalar'], 'Tüm Markalar') ?></select>
            </div>
            <div>
                <label class="form-label">Seri</label>
                <select id="f-seri" class="form-select"><option value="">Tüm Seriler</option></select>
            </div>
            <div>
                <label class="form-label">Model</label>
                <select id="f-model" class="form-select"><?= $opt($filtreler['modeller'], 'Tüm Modeller') ?></select>
            </div>
            <div>
                <label class="form-label">Otopark</label>
                <select id="f-otopark" class="form-select"><?= $opt($filtreler['otoparklar'], 'Tüm Otoparklar') ?></select>
            </div>
            <div>
                <label class="form-label">Park / Lokasyon</label>
                <select id="f-park" class="form-select"><?= $opt($filtreler['parklar'], 'Tümü') ?></select>
            </div>
            <div class="d-flex align-items-end">
                <button type="button" class="btn btn-light w-100" id="f-temizle"><i class="mdi mdi-filter-off me-1"></i>Temizle</button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0"><?= e($baslikTablo) ?></h5>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary"><span id="liste-adet"><?= count($araclar) ?></span> araç</span>
            <a href="/musteri/excel/<?= e($excelTip) ?>" class="btn btn-sm btn-success">
                <i class="mdi mdi-microsoft-excel me-1"></i>Excel İndir
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="musteri-arac-tablo">
                <thead>
                <tr>
                    <th>Plaka / Şasi</th>
                    <th>Marka / Seri / Model</th>
                    <th>Otopark</th>
                    <?php if ($tumuMod): ?><th>Durum</th><?php endif; ?>
                    <th>Giriş</th>
                    <?php if ($tumuMod || $cikanMod): ?><th>Çıkış</th><?php endif; ?>
                    <th class="text-end">Gün</th>
                    <th class="text-end">Günlük</th>
                    <th class="text-end">Depolama</th>
                    <th class="text-end">Hizmet</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$araclar): ?>
                    <tr><td colspan="<?= $colspan ?>" class="text-center text-muted py-4">Kayıt yok.</td></tr>
                <?php else: foreach ($araclar as $r):
                    $stokta = !empty($r['stokta']) || (($r['durum'] ?? '') === 'Stokta');
                    $markaSeri = trim(($r['marka'] ?? '') . ' ' . ($r['seri'] ?? '') . (trim((string) ($r['model'] ?? '')) !== '' ? ' / ' . $r['model'] : ''));
                    ?>
                    <tr
                        data-marka="<?= e($r['marka'] ?? '') ?>"
                        data-seri="<?= e($r['seri'] ?? '') ?>"
                        data-model="<?= e($r['model'] ?? '') ?>"
                        data-otopark="<?= e($r['otopark'] ?? '') ?>"
                        data-park="<?= e($r['park_kodu'] ?? '') ?>"
                        data-lokasyon="<?= e($r['lokasyon_detay'] ?? '') ?>"
                        data-q="<?= e(mb_strtolower(trim(($r['plaka'] ?? '') . ' ' . ($r['sase'] ?? '')), 'UTF-8')) ?>"
                        data-gun="<?= (int) $r['gun'] ?>"
                        data-dep="<?= (float) $r['tutar'] ?>"
                    >
                        <td>
                            <strong><?= e($r['plaka'] ?: '—') ?></strong>
                            <div class="small text-muted font-monospace"><?= e($r['sase']) ?></div>
                            <?php if (!empty($r['park_kodu'])): ?><div class="small text-muted">Park: <?= e($r['park_kodu']) ?></div><?php endif; ?>
                        </td>
                        <td><?= e($markaSeri ?: '—') ?></td>
                        <td>
                            <?= e($r['otopark']) ?>
                            <?php if ($r['lokasyon_detay']): ?><div class="small text-muted"><?= e($r['lokasyon_detay']) ?></div><?php endif; ?>
                        </td>
                        <?php if ($tumuMod): ?>
                            <td>
                                <?php if ($stokta): ?>
                                    <span class="badge bg-success-subtle text-success">Stokta</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary">Çıkmış</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <td class="small"><?= $gunFmt($r['giris_tarihi']) ?></td>
                        <?php if ($tumuMod || $cikanMod): ?>
                            <td class="small"><?= $stokta ? '—' : $gunFmt($r['cikis_tarihi'] ?? null) ?></td>
                        <?php endif; ?>
                        <td class="text-end"><span class="badge bg-secondary-subtle text-secondary"><?= (int) $r['gun'] ?></span></td>
                        <td class="text-end small">
                            <?php if (!empty($r['fiyat_yok'])): ?>
                                <span class="text-warning">Fiyat belirlenmedi</span>
                            <?php elseif (!empty($r['fiyat_degisti'])): ?>
                                <?= e(\App\Services\DepolamaHesap::fiyatAciklama($r)) ?>
                            <?php else: ?>
                                <?= $tl($r['gunluk_fiyat']) ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-semibold"><?= $tl($r['tutar']) ?></td>
                        <td class="text-end small">
                            <?php if ((int) $r['hizmet_adet'] > 0): ?>
                                <?= $tl($r['hizmet_tutar']) ?>
                                <div class="text-muted"><?= (int) $r['hizmet_adet'] ?> adet</div>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="/musteri/arac/<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-primary">Detay</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(function () {
    const seriMap = <?= json_encode($filtreler['seriler'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const $marka = $('#f-marka'), $seri = $('#f-seri'), $model = $('#f-model'), $otopark = $('#f-otopark'), $park = $('#f-park'), $q = $('#f-q');
    const tl = (n) => Number(n).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺';

    function doldurSeri(marka) {
        const list = marka && seriMap[marka] ? seriMap[marka] : [];
        let html = '<option value="">Tüm Seriler</option>';
        list.forEach((s) => { html += `<option value="${App.esc(s)}">${App.esc(s)}</option>`; });
        if (!marka) {
            const all = new Set();
            Object.values(seriMap).forEach((arr) => arr.forEach((s) => all.add(s)));
            [...all].sort((a, b) => a.localeCompare(b, 'tr')).forEach((s) => {
                html += `<option value="${App.esc(s)}">${App.esc(s)}</option>`;
            });
        }
        $seri.html(html);
    }
    doldurSeri('');

    let dt = null;
    if ($('#musteri-arac-tablo tbody tr').length && !$('#musteri-arac-tablo td[colspan]').length) {
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'musteri-arac-tablo') return true;
            const row = settings.aoData[dataIndex].nTr;
            if (!row) return true;
            const $r = $(row);
            const marka = $marka.val(), seri = $seri.val(), model = $model.val(), otopark = $otopark.val(), park = $park.val();
            const q = ($q.val() || '').toString().trim().toLocaleLowerCase('tr');
            if (marka && $r.data('marka') !== marka) return false;
            if (seri && $r.data('seri') !== seri) return false;
            if (model && $r.data('model') !== model) return false;
            if (otopark && $r.data('otopark') !== otopark) return false;
            if (park && $r.data('park') !== park && $r.data('lokasyon') !== park) return false;
            if (q && !(($r.data('q') || '').toString().includes(q))) return false;
            return true;
        });

        dt = $('#musteri-arac-tablo').DataTable({
            order: [[<?= (int) $orderCol ?>, 'desc']],
            pageLength: 25,
            dom: 'lrtip'
        });

        function ozetGuncelle() {
            let adet = 0, gunToplam = 0, dep = 0;
            dt.rows({ search: 'applied' }).every(function () {
                const $r = $(this.node());
                adet++;
                gunToplam += Number($r.data('gun') || 0);
                dep += Number($r.data('dep') || 0);
            });
            $('#liste-adet').text(adet);
            $('#kpi-adet').text(adet);
            $('#kpi-gun').text(adet ? Math.round(gunToplam / adet) : 0);
            $('#kpi-dep').text(tl(dep));
        }

        const uygula = () => { dt.draw(); ozetGuncelle(); };
        $marka.on('change', function () { doldurSeri(this.value); uygula(); });
        $seri.add($model).add($otopark).add($park).on('change', uygula);
        $q.on('input', uygula);
        $('#f-temizle').on('click', function () {
            $marka.val(''); $model.val(''); $otopark.val(''); $park.val(''); $q.val('');
            doldurSeri('');
            uygula();
        });
        ozetGuncelle();
    }
});
</script>
<?php $scripts = ob_get_clean(); ?>
