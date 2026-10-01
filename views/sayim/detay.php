<?php
use App\Core\Tanim;

$devam = (int) $s['durum'] === 1;
$headerActions = '<a href="/sayim_kayitlari/excel/' . $s['id'] . '" class="btn btn-success"><i class="mdi mdi-microsoft-excel me-1"></i>Excel ile İndir</a>'
    . ($devam ? '<button type="button" id="tamamla" class="btn btn-primary"><i class="mdi mdi-check-all me-1"></i>Sayımı Tamamla</button>' : '');
?>
<div class="card">
    <div class="card-header">
        <h5><?= e($s['kod']) ?> — <?= e($s['baslik']) ?></h5>
        <span class="badge badge-soft-<?= Tanim::SAYIM_DURUM_RENK[$s['durum']] ?>"><?= Tanim::SAYIM_DURUM[$s['durum']] ?></span>
    </div>
    <div class="card-body">
        <div class="row g-3 small">
            <div class="col-md-3"><div class="text-muted">Lokasyon</div><div class="fw-semibold"><?= e($s['lokasyon']) ?></div></div>
            <div class="col-md-3"><div class="text-muted">Sorumlu Personel</div><div class="fw-semibold"><?= e($s['sorumlu'] ?: '-') ?></div></div>
            <div class="col-md-3"><div class="text-muted">Sayım Tarihi</div><div class="fw-semibold"><?= date('d.m.Y H:i', strtotime($s['created_at'])) ?> <span class="text-muted fw-normal">(<?= e($s['olusturan'] ?: '-') ?>)</span></div></div>
            <div class="col-md-3"><div class="text-muted">Tamamlanma</div><div class="fw-semibold"><?= $s['tamamlanma_tarihi'] ? date('d.m.Y H:i', strtotime($s['tamamlanma_tarihi'])) : '-' ?></div></div>
        </div>
    </div>
</div>

<?php if ($devam): ?>
<div class="card border-primary">
    <div class="card-body">
        <form id="okut-form" class="d-flex gap-2" autocomplete="off">
            <div class="input-group input-group-lg">
                <span class="input-group-text"><i class="mdi mdi-barcode-scan"></i></span>
                <input type="text" id="okut-sase" class="form-control text-uppercase" placeholder="Şasi numarası veya plaka okutun / yazıp Enter'a basın" autofocus maxlength="30">
            </div>
            <button type="submit" class="btn btn-primary btn-lg text-nowrap"><i class="mdi mdi-magnify-scan me-1"></i>Okut</button>
        </form>
        <div class="form-text">Barkod / QR okuyucu kullanıyorsanız alan seçiliyken okutmanız yeterli; her okutma otomatik kaydedilir.</div>
        <div id="son-okutma" class="mt-2"></div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="card h-100 mb-0"><div class="card-body"><div class="text-muted small">Stoktaki Araç (Beklenen)</div><div class="fs-3 fw-bold" id="say-beklenen">-</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100 mb-0"><div class="card-body"><div class="text-muted small">Okutulan</div><div class="fs-3 fw-bold text-success" id="say-okutulan">-</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100 mb-0"><div class="card-body"><div class="text-muted small">Lokasyonda Değil</div><div class="fs-3 fw-bold text-danger" id="say-disarida">-</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100 mb-0"><div class="card-body"><div class="text-muted small"><?= $devam ? 'Henüz Okutulmayan' : 'Bulunamayan' ?></div><div class="fs-3 fw-bold text-warning" id="say-okutulmayan">-</div></div></div></div>
</div>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-pills gap-1" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-okutulan" type="button">Okutulanlar <span class="badge bg-success ms-1" id="b-okutulan">0</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-disarida" type="button">Lokasyonda Değil <span class="badge bg-danger ms-1" id="b-disarida">0</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-okutulmayan" type="button"><?= $devam ? 'Henüz Okutulmayanlar' : 'Bulunamayanlar' ?> <span class="badge bg-warning ms-1" id="b-okutulmayan">0</span></button></li>
        </ul>
        <input type="search" id="tablo-ara" class="form-control form-control-sm" style="max-width:260px" placeholder="Listede ara...">
    </div>
    <div class="card-body tab-content">
        <div class="tab-pane fade show active" id="tab-okutulan">
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr><th>Şasi</th><th>Plaka</th><th>Marka</th><th>Seri</th><th>Okutan</th><th>Okutma Tarihi</th><th>Stoğa Giriş</th><th>Durum</th><?= $devam ? '<th></th>' : '' ?></tr></thead>
                <tbody id="t-okutulan"></tbody>
            </table></div>
        </div>
        <div class="tab-pane fade" id="tab-disarida">
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr><th>Şasi</th><th>Okutan</th><th>Okutma Tarihi</th><th>Sistemdeki Durumu</th><th>Marka</th><th>Seri</th><?= $devam ? '<th>İşlem</th>' : '' ?></tr></thead>
                <tbody id="t-disarida"></tbody>
            </table></div>
        </div>
        <div class="tab-pane fade" id="tab-okutulmayan">
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr><th>Şasi</th><th>Plaka</th><th>Marka</th><th>Seri</th><th>Lokasyon Detay</th><th>Stoğa Giriş</th><th>Durum</th></tr></thead>
                <tbody id="t-okutulmayan"></tbody>
            </table></div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(function () {
    const ID = <?= (int) $s['id'] ?>, BAYI = <?= (int) $s['bayi_id'] ?>, DEVAM = <?= $devam ? 'true' : 'false' ?>;
    const bos = (n, mesaj) => `<tr><td colspan="${n}" class="text-center text-muted py-4">${mesaj}</td></tr>`;
    const sil = (r) => DEVAM ? `<td class="text-end"><button class="btn btn-sm btn-light text-danger okutma-sil" data-id="${r.id}" data-sase="${App.esc(r.sase)}" title="Okutmayı sil"><i class="mdi mdi-delete-outline"></i></button></td>` : '';

    function ciz(d) {
        const okutulan = d.okutulan.map((r) => `<tr class="${r.sonuc == 3 ? 'table-info' : ''}">
            <td class="fw-semibold">${App.esc(r.sase)}</td><td>${App.esc(r.plaka || '-')}</td><td>${App.esc(r.marka || '-')}</td><td>${App.esc(r.seri || '-')}</td>
            <td>${App.esc(r.okutan || '-')}</td><td>${r.okutma_tarihi || '-'}</td><td>${r.stoga_giris || '-'}</td>
            <td>${App.badge(r.sonuc_ad, r.sonuc == 3 ? 'info' : 'success')}</td>${sil(r)}</tr>`);
        const disarida = d.disarida.map((r) => `<tr>
            <td class="fw-semibold">${App.esc(r.sase)}</td><td>${App.esc(r.okutan || '-')}</td><td>${r.okutma_tarihi || '-'}</td>
            <td>${App.badge(r.sistem_durumu, 'danger')}</td><td>${App.esc(r.marka || '-')}</td><td>${App.esc(r.seri || '-')}</td>
            ${DEVAM ? `<td class="text-nowrap"><button class="btn btn-sm btn-primary stoga-al" data-sase="${App.esc(r.sase)}"><i class="mdi mdi-car-arrow-right me-1"></i>Stoğa Al</button>
                <button class="btn btn-sm btn-light text-danger okutma-sil" data-id="${r.id}" data-sase="${App.esc(r.sase)}" title="Okutmayı sil"><i class="mdi mdi-delete-outline"></i></button></td>` : ''}</tr>`);
        const okutulmayan = d.okutulmayan.map((r) => `<tr>
            <td class="fw-semibold">${r.arac_id ? `<a href="/arac_yonetimi/duzenle/${r.arac_id}">${App.esc(r.sase)}</a>` : App.esc(r.sase)}</td>
            <td>${App.esc(r.plaka || '-')}</td><td>${App.esc(r.marka || '-')}</td><td>${App.esc(r.seri || '-')}</td>
            <td>${App.esc(r.lokasyon_detay || '-')}</td><td>${r.stoga_giris || '-'}</td><td>${App.badge(r.sonuc_ad, 'warning')}</td></tr>`);

        $('#t-okutulan').html(okutulan.join('') || bos(9, 'Henüz araç okutulmadı.'));
        $('#t-disarida').html(disarida.join('') || bos(7, 'Lokasyon dışı araç okutulmadı.'));
        $('#t-okutulmayan').html(okutulmayan.join('') || bos(7, DEVAM ? 'Stoktaki tüm araçlar okutuldu.' : 'Bulunamayan araç yok.'));

        const stokOkutulan = d.okutulan.length;
        $('#say-beklenen').text(App.num(stokOkutulan + d.okutulmayan.length));
        $('#say-okutulan, #b-okutulan').text(App.num(stokOkutulan));
        $('#say-disarida, #b-disarida').text(App.num(d.disarida.length));
        $('#say-okutulmayan, #b-okutulmayan').text(App.num(d.okutulmayan.length));
        $('#tablo-ara').trigger('input');
    }

    const yukle = () => $.getJSON('/sayim_kayitlari/veri/' + ID).done(ciz).fail((x) => App.error(x.responseJSON?.message));
    yukle();

    $('#tablo-ara').on('input', function () {
        const q = this.value.toLocaleLowerCase('tr');
        $('.tab-content tbody tr').each(function () { $(this).toggle(!q || $(this).text().toLocaleLowerCase('tr').includes(q)); });
    });

    $('#okut-form').on('submit', function (e) {
        e.preventDefault();
        const $in = $('#okut-sase');
        const sase = $in.val().trim();
        if (!sase) return;
        $in.prop('disabled', true);
        $.post('/sayim_kayitlari/okut/' + ID, { sase }).done((res) => {
            const renk = res.tekrar ? 'warning' : (res.stokta ? 'success' : 'danger');
            const ikon = res.tekrar ? 'mdi-repeat' : (res.stokta ? 'mdi-check-circle' : 'mdi-alert-circle');
            $('#son-okutma').html(`<div class="alert alert-${renk} py-2 mb-0"><i class="mdi ${ikon} me-1"></i>${App.esc(res.message)}</div>`);
            if (!res.tekrar && !res.stokta) $('[data-bs-target="#tab-disarida"]').tab('show');
            yukle();
        }).fail((x) => {
            $('#son-okutma').html(`<div class="alert alert-danger py-2 mb-0">${App.esc(x.responseJSON?.message || 'Okutma kaydedilemedi.')}</div>`);
        }).always(() => { $in.prop('disabled', false).val('').trigger('focus'); });
    });

    $(document).on('click', '.okutma-sil', function () {
        const $b = $(this);
        Swal.fire({ icon: 'warning', title: $b.data('sase') + ' okutması silinsin mi?', showCancelButton: true, confirmButtonText: 'Evet', cancelButtonText: 'Vazgeç', confirmButtonColor: '#dc2626' })
            .then((r) => {
                if (!r.isConfirmed) return;
                $.post('/sayim_kayitlari/okutma_sil/' + $b.data('id')).done((res) => { App.toast(res.message); yukle(); }).fail((x) => App.error(x.responseJSON?.message));
            });
    });

    $(document).on('click', '.stoga-al', function () {
        const $m = $('#hizli-arac-ekle-modal');
        const $f = $m.find('form');
        $f.find('[name="sase"]').val($(this).data('sase'));
        $f.find('[name="bayi_id"]').val(BAYI);
        $f.find('[name="from_sayim"]').val(ID);
        bootstrap.Modal.getOrCreateInstance($m[0]).show();
    });
    $('#hizli-arac-ekle-modal').on('hidden.bs.modal', function () { $(this).find('[name="from_sayim"]').val(''); });
    $(document).on('ajax-form-saved', (_, form) => { if (form.action.includes('hizli_arac_save')) yukle(); });

    $('#tamamla').on('click', function () {
        const kalan = $('#b-okutulmayan').text();
        Swal.fire({
            icon: 'question', title: 'Sayım tamamlansın mı?',
            text: Number(kalan.replace(/\./g, '')) ? `Okutulmayan ${kalan} araç "Bulunamadı" olarak kaydedilecek. Tamamlanan sayımda değişiklik yapılamaz.` : 'Tamamlanan sayımda değişiklik yapılamaz.',
            showCancelButton: true, confirmButtonText: 'Tamamla', cancelButtonText: 'Vazgeç', confirmButtonColor: '#1d4ed8',
        }).then((r) => {
            if (!r.isConfirmed) return;
            $.post('/sayim_kayitlari/tamamla/' + ID).done((res) => {
                Swal.fire({ icon: 'success', title: res.message, confirmButtonColor: '#1d4ed8' }).then(() => location.reload());
            }).fail((x) => App.error(x.responseJSON?.message));
        });
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
