<?php
$secenekler = [
    ['klasik', 'light', 'Klasik — Açık', '#fff', '#eaeef7', '#1d4ed8', '#e3e8f2'],
    ['klasik', 'dark', 'Klasik — Koyu', '#16213a', '#0f1729', '#4f7cf5', '#26324d'],
    ['vuexy', 'light', 'Vuexy — Açık', '#fff', '#f8f7fa', '#7367f0', '#e6e6e8'],
    ['vuexy', 'dark', 'Vuexy — Koyu', '#2f3349', '#25293c', '#7367f0', '#44485e'],
];
?>
<div class="card">
    <div class="card-header"><h5>Tema Seçimi</h5><span class="text-muted small">Seçim bu tarayıcıda saklanır; üst bardaki <i class="mdi mdi-weather-sunny"></i> düğmesinden de değiştirilebilir.</span></div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($secenekler as [$tema, $mod, $ad, $yuzey, $zemin, $ana, $cizgi]): ?>
                <div class="col-sm-6 col-xl-3">
                    <button type="button" class="tema-kart" data-onizleme-tema="<?= $tema ?>" data-onizleme-mod="<?= $mod ?>">
                        <div class="mini" style="background: <?= $zemin ?>">
                            <div class="mini-side" style="background: <?= $yuzey ?>; border-right: 1px solid <?= $cizgi ?>">
                                <i style="background: <?= $ana ?>; width: 70%"></i>
                                <i style="background: <?= $tema === 'vuexy' ? $ana : $cizgi ?>; <?= $tema === 'vuexy' ? 'height: 12px; opacity: .85' : '' ?>"></i>
                                <i style="background: <?= $cizgi ?>"></i><i style="background: <?= $cizgi ?>"></i><i style="background: <?= $cizgi ?>; width: 60%"></i>
                            </div>
                            <div class="mini-main">
                                <div class="bar" style="background: <?= $yuzey ?>; <?= $tema === 'vuexy' ? 'border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,.12)' : '' ?>"></div>
                                <div class="box" style="background: <?= $yuzey ?>"></div>
                            </div>
                        </div>
                        <div class="ad"><?= e($ad) ?> <i class="mdi mdi-check-circle text-primary d-none"></i></div>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="kpi-card"><div class="kpi-head"><span class="kpi-icon bg-blue"><i class="mdi mdi-car-multiple"></i></span>Stoktaki Araç</div><div class="kpi-value">165</div><div class="kpi-trend up"><i class="mdi mdi-arrow-up"></i> %12 geçen aya göre</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="kpi-card"><div class="kpi-head"><span class="kpi-icon bg-green"><i class="mdi mdi-cash-multiple"></i></span>Depolama Geliri</div><div class="kpi-value">891.270 ₺</div><div class="kpi-trend up"><i class="mdi mdi-arrow-up"></i> %4</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="kpi-card"><div class="kpi-head"><span class="kpi-icon bg-orange"><i class="mdi mdi-clipboard-text-outline"></i></span>Bekleyen İş Emri</div><div class="kpi-value">18</div><div class="kpi-trend down"><i class="mdi mdi-arrow-down"></i> %3</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="kpi-card"><div class="kpi-head"><span class="kpi-icon bg-purple"><i class="mdi mdi-clipboard-check-outline"></i></span>Devam Eden Sayım</div><div class="kpi-value">2</div><div class="kpi-trend flat">Değişim yok</div></div></div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header"><h5>Düğmeler ve Rozetler</h5></div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button class="btn btn-primary">Birincil</button>
                    <button class="btn btn-success">Başarılı</button>
                    <button class="btn btn-warning text-white">Uyarı</button>
                    <button class="btn btn-danger">Tehlike</button>
                    <button class="btn btn-info text-white">Bilgi</button>
                    <button class="btn btn-outline-primary">Çerçeveli</button>
                    <button class="btn btn-light">Açık</button>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge badge-soft-primary">Yeni</span>
                    <span class="badge badge-soft-success">Tamamlandı</span>
                    <span class="badge badge-soft-warning">Devam Ediyor</span>
                    <span class="badge badge-soft-danger">Lokasyonda Değil</span>
                    <span class="badge badge-soft-info">Stoğa Alındı</span>
                    <span class="badge badge-soft-secondary">Arşiv</span>
                </div>
                <ul class="nav nav-pills gap-1 mb-3">
                    <li class="nav-item"><a class="nav-link active" href="#!">Tümü</a></li>
                    <li class="nav-item"><a class="nav-link" href="#!">Yapılacak</a></li>
                    <li class="nav-item"><a class="nav-link" href="#!">Tamamlandı</a></li>
                </ul>
                <div class="alert alert-success mb-2"><i class="mdi mdi-check-circle me-1"></i> Araç stoğa eklendi.</div>
                <div class="alert alert-warning mb-0"><i class="mdi mdi-alert me-1"></i> Hizmet ücreti girilmemiş.</div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header"><h5>Form Alanları</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label required">Şasi Numarası</label><input type="text" class="form-control" value="VF1RJA00576606362"></div>
                    <div class="col-md-6"><label class="form-label">Lokasyon</label><select class="form-select"><option>BİA ANKARA</option><option>BİA SEYRANTEPE</option></select></div>
                    <div class="col-md-6"><label class="form-label">Hareket Tarihi</label><input type="date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Fiyat (₺)</label><input type="number" class="form-control" value="750.00"></div>
                    <div class="col-12"><label class="form-check"><input type="checkbox" class="form-check-input" checked> <span class="form-check-label">Yedek anahtar teslim alındı</span></label></div>
                    <div class="col-12"><button class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i>Kaydet</button> <button class="btn btn-light">Vazgeç</button></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header"><h5>Örnek Tablo</h5><button class="btn btn-sm btn-success"><i class="mdi mdi-microsoft-excel me-1"></i>Excel İndir</button></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover dataTable w-100 mb-0">
                        <thead><tr><th>Şase</th><th>Plaka</th><th>Marka</th><th>Lokasyon</th><th>Durum</th><th>İşlem</th></tr></thead>
                        <tbody>
                            <tr><td class="fw-semibold"><a href="#!">VF1RJA00576606362</a></td><td>06 CSV 01</td><td>Renault Clio</td><td>BİA ANKARA</td><td><span class="badge badge-soft-success">Stokta</span></td><td><div class="table-actions"><a class="btn btn-sm btn-success btn-icon" href="#!"><i class="mdi mdi-eye"></i></a><a class="btn btn-sm btn-primary btn-icon" href="#!"><i class="mdi mdi-pencil-box"></i></a></div></td></tr>
                            <tr class="table-warning"><td class="fw-semibold"><a href="#!">VF13GWVMJN2BZ813R</a></td><td>06 JN 138</td><td>BMW 1 Serisi</td><td>BİA ANKARA</td><td><span class="badge badge-soft-warning">Çıkış sonrası eklendi</span></td><td><div class="table-actions"><a class="btn btn-sm btn-success btn-icon" href="#!"><i class="mdi mdi-eye"></i></a><a class="btn btn-sm btn-primary btn-icon" href="#!"><i class="mdi mdi-pencil-box"></i></a></div></td></tr>
                            <tr><td class="fw-semibold"><a href="#!">VF1D15R0TRDP83MED</a></td><td>06 GI 180</td><td>Renault Duster</td><td>BİA SEYRANTEPE</td><td><span class="badge badge-soft-danger">Lokasyonda Değil</span></td><td><div class="table-actions"><a class="btn btn-sm btn-success btn-icon" href="#!"><i class="mdi mdi-eye"></i></a><a class="btn btn-sm btn-primary btn-icon" href="#!"><i class="mdi mdi-pencil-box"></i></a></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><h5>Grafik</h5></div>
            <div class="card-body"><div style="height: 220px"><canvas id="onizleme-grafik"></canvas></div></div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(function () {
    function isaretle() {
        const tema = App.tema(), mod = document.documentElement.getAttribute('data-bs-theme');
        $('.tema-kart').each(function () {
            const secili = this.dataset.onizlemeTema === tema && this.dataset.onizlemeMod === mod;
            $(this).toggleClass('secili', secili).find('.mdi-check-circle').toggleClass('d-none', !secili);
        });
    }
    $('.tema-kart').on('click', function () {
        localStorage.setItem('tema', this.dataset.onizlemeTema);
        App.setMod(this.dataset.onizlemeMod);
    });
    $(document).on('tema-degisti', isaretle);
    isaretle();

    new Chart(document.getElementById('onizleme-grafik'), {
        type: 'bar',
        data: { labels: ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt'], datasets: [
            { label: 'Giriş', data: [12, 19, 8, 15, 22, 9], backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--primary').trim(), borderRadius: 6 },
            { label: 'Çıkış', data: [7, 11, 13, 9, 14, 6], backgroundColor: '#28c76f', borderRadius: 6 },
        ] },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
