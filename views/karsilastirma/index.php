<?php
use App\Core\Auth;

$admin = Auth::isAdmin();
$turler = $veri['turler'];
$kararlar = $veri['kararlar'];
$turSay = array_count_values(array_column($maddeler, 'tur'));

$headerActions = '<a href="/bia_md_karsilastirma/indir" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-download me-1"></i>Markdown indir</a>'
    . ' <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="mdi mdi-printer me-1"></i>Yazdır</button>';
?>
<div class="row g-3 mb-3">
    <?php foreach ($turler as $kod => [$ad, $renk]): ?>
        <div class="col-6 col-md-4 col-xl">
            <button type="button" class="kpi-card w-100 text-start border-0" data-tur-filtre="<?= $kod ?>">
                <div class="kpi-head"><span class="badge badge-soft-<?= $renk ?>"><?= e($ad) ?></span></div>
                <div class="kpi-value mt-2"><?= $turSay[$kod] ?? 0 ?> <span class="kpi-unit">madde</span></div>
            </button>
        </div>
    <?php endforeach; ?>
</div>

<div class="card mb-3">
    <div class="card-header"><h5>Özet</h5>
        <div class="d-flex flex-wrap gap-1" id="karar-ozet">
            <?php foreach ($kararlar as $kod => [$ad, $renk]): ?>
                <span class="badge badge-soft-<?= $renk ?>"><?= e($ad) ?>: <b data-karar-say="<?= $kod ?>">0</b></span>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-body">
        <ul class="mb-2">
            <?php foreach ($veri['ozet'] as $s): ?><li class="mb-1"><?= e($s) ?></li><?php endforeach; ?>
        </ul>
        <div class="small text-muted">
            <?php foreach (['bia' => 'BİA', 'md' => 'MD', 'sistem' => 'Sistem'] as $k => $ad): ?>
                <div><b><?= $ad ?>:</b> <?= e($veri['kaynaklar'][$k]) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3 d-print-none" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-farklar" type="button">Farklar ve Kararlar</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-akis" type="button">İş Akışı</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sozluk" type="button">Sözlük</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-farklar">
        <div class="card mb-3 d-print-none">
            <div class="card-body row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Konu</label>
                    <select class="form-select form-select-sm" id="f-kategori">
                        <option value="">Tümü</option>
                        <?php foreach ($veri['kategoriler'] as $k => $ad): ?><option value="<?= $k ?>"><?= e($ad) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Fark türü</label>
                    <select class="form-select form-select-sm" id="f-tur">
                        <option value="">Tümü</option>
                        <?php foreach ($turler as $k => [$ad]): ?><option value="<?= $k ?>"><?= e($ad) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Karar</label>
                    <select class="form-select form-select-sm" id="f-karar">
                        <option value="">Tümü</option>
                        <?php foreach ($kararlar as $k => [$ad]): ?><option value="<?= $k ?>"><?= e($ad) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Ara</label>
                    <input type="search" class="form-control form-control-sm" id="f-ara" placeholder="Kelime veya madde kodu">
                </div>
            </div>
        </div>

        <?php foreach ($veri['kategoriler'] as $katKod => $katAd):
            $grup = array_filter($maddeler, static fn ($m) => $m['kategori'] === $katKod);
            if (!$grup) continue; ?>
            <div class="kategori-blok" data-kategori-blok="<?= $katKod ?>">
                <h6 class="text-uppercase text-muted fw-bold small mb-2 mt-3"><?= e($katAd) ?></h6>
                <?php foreach ($grup as $m): [$turAd, $turRenk] = $turler[$m['tur']]; ?>
                    <div class="card mb-3 madde" id="madde-<?= $m['kod'] ?>" data-kod="<?= $m['kod'] ?>" data-kategori="<?= $m['kategori'] ?>" data-tur="<?= $m['tur'] ?>" data-karar="<?= e($m['karar']) ?>">
                        <div class="card-header">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-dark"><?= $m['kod'] ?></span>
                                <strong><?= e($m['baslik']) ?></strong>
                                <span class="badge badge-soft-<?= $turRenk ?>"><?= e($turAd) ?></span>
                            </div>
                            <span class="badge badge-soft-<?= $kararlar[$m['karar']][1] ?? 'secondary' ?>" data-karar-rozet><?= e($kararlar[$m['karar']][0] ?? $m['karar']) ?></span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-lg-4">
                                    <div class="small fw-bold text-primary mb-1"><i class="mdi mdi-web me-1"></i>BİA</div>
                                    <div class="small"><?= e($m['bia']) ?></div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="small fw-bold text-warning mb-1"><i class="mdi mdi-file-document-outline me-1"></i>MD</div>
                                    <div class="small"><?= e($m['md']) ?></div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="small fw-bold text-success mb-1"><i class="mdi mdi-check-decagram-outline me-1"></i>Sistemde şu an</div>
                                    <div class="small"><?= e($m['sistem']) ?>
                                        <?php if (!empty($m['url'])): ?> <a href="<?= e($m['url']) ?>" class="d-print-none">Sayfaya git <i class="mdi mdi-arrow-right"></i></a><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-3">
                            <?php if ($admin): ?>
                                <form class="row g-2 align-items-start karar-form" data-kod="<?= $m['kod'] ?>">
                                    <div class="col-md-3">
                                        <select name="karar" class="form-select form-select-sm">
                                            <?php foreach ($kararlar as $k => [$ad]): ?><option value="<?= $k ?>" <?= $k === $m['karar'] ? 'selected' : '' ?>><?= e($ad) ?></option><?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-7">
                                        <textarea name="notlar" rows="1" class="form-control form-control-sm" maxlength="2000" placeholder="Not: ne değişecek, kim istedi, ne zaman..."><?= e($m['notlar']) ?></textarea>
                                    </div>
                                    <div class="col-md-2 d-grid">
                                        <button type="submit" class="btn btn-sm btn-primary"><i class="mdi mdi-content-save-outline me-1"></i>Kaydet</button>
                                    </div>
                                    <div class="col-12 small text-muted" data-karar-bilgi><?= $m['karar_tarihi'] ? 'Son güncelleme: ' . e($m['guncelleyen'] ?? '-') . ', ' . date('d.m.Y H:i', strtotime($m['karar_tarihi'])) : '' ?></div>
                                </form>
                            <?php else: ?>
                                <div class="small"><b>Karar notu:</b> <?= $m['notlar'] !== '' ? e($m['notlar']) : '<span class="text-muted">—</span>' ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <p class="text-muted text-center py-4 d-none" id="bos-sonuc">Filtreye uyan madde yok.</p>
    </div>

    <div class="tab-pane fade" id="tab-akis">
        <div class="row g-3">
            <?php foreach (['bia' => ['BİA akışı', 'primary', 'Sistem şu an bu akışla çalışıyor.'], 'md' => ['MD akışı', 'warning', 'Kullanım rehberinde anlatılan akış.']] as $k => [$baslik, $renk, $alt]): ?>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header"><h5 class="text-<?= $renk ?>"><?= $baslik ?></h5><span class="small text-muted"><?= $alt ?></span></div>
                        <div class="card-body">
                            <ol class="akis-listesi mb-0">
                                <?php foreach ($veri['akis'][$k] as $adim): ?>
                                    <li class="mb-3"><b><?= e($adim['baslik']) ?></b><div class="small text-muted"><?= e($adim['detay']) ?></div></li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-sozluk">
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>MD'deki terim</th><th>BİA'daki karşılığı</th><th>Not</th></tr></thead>
                    <tbody>
                        <?php foreach ($veri['sozluk'] as $s): ?>
                            <tr><td class="fw-semibold"><?= e($s['md']) ?></td><td><?= e($s['bia']) ?></td><td class="small text-muted"><?= e($s['not']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
(function () {
    const kararlar = <?= json_encode($kararlar, JSON_UNESCAPED_UNICODE) ?>;

    function sayaclar() {
        const say = {};
        document.querySelectorAll('.madde').forEach((el) => { say[el.dataset.karar] = (say[el.dataset.karar] || 0) + 1; });
        document.querySelectorAll('[data-karar-say]').forEach((el) => { el.textContent = say[el.dataset.kararSay] || 0; });
    }

    function filtrele() {
        const kat = $('#f-kategori').val(), tur = $('#f-tur').val(), karar = $('#f-karar').val();
        const q = $('#f-ara').val().toLocaleLowerCase('tr');
        let gorunen = 0;
        $('.madde').each(function () {
            const ok = (!kat || this.dataset.kategori === kat) && (!tur || this.dataset.tur === tur)
                && (!karar || this.dataset.karar === karar) && (!q || this.textContent.toLocaleLowerCase('tr').includes(q)
                    || $(this).find('textarea').val()?.toLocaleLowerCase('tr').includes(q));
            $(this).toggle(ok);
            gorunen += ok ? 1 : 0;
        });
        $('.kategori-blok').each(function () { $(this).toggle($(this).find('.madde').filter(function () { return this.style.display !== 'none'; }).length > 0); });
        $('#bos-sonuc').toggleClass('d-none', gorunen > 0);
    }

    $('#f-kategori, #f-tur, #f-karar').on('change', filtrele);
    $('#f-ara').on('input', filtrele);
    $('[data-tur-filtre]').on('click', function () {
        const tur = this.dataset.turFiltre;
        $('#f-tur').val($('#f-tur').val() === tur ? '' : tur);
        bootstrap.Tab.getOrCreateInstance(document.querySelector('[data-bs-target="#tab-farklar"]')).show();
        filtrele();
    });

    $(document).on('submit', '.karar-form', function (e) {
        e.preventDefault();
        const form = this, kod = form.dataset.kod;
        const $btn = $(form).find('[type="submit"]').prop('disabled', true);
        $.post('/bia_md_karsilastirma/kaydet', { kod, karar: form.karar.value, notlar: form.notlar.value })
            .done((res) => {
                if (!res.success) { App.error(res.message); return; }
                const kart = document.getElementById('madde-' + kod);
                const [ad, renk] = kararlar[form.karar.value];
                kart.dataset.karar = form.karar.value;
                $(kart).find('[data-karar-rozet]').attr('class', 'badge badge-soft-' + renk).text(ad);
                $(form).find('[data-karar-bilgi]').text('Son güncelleme: ' + res.guncelleyen + ', ' + res.tarih);
                App.toast(res.message);
                sayaclar();
            })
            .fail((x) => App.error(x.responseJSON?.message))
            .always(() => $btn.prop('disabled', false));
    });

    if (location.hash.startsWith('#madde-')) document.querySelector(location.hash)?.scrollIntoView();
    sayaclar();
})();
</script>
<?php $scripts = ob_get_clean(); ?>
