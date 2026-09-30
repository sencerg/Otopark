<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Tanim;

$headerActions = '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#destekModal"><i class="mdi mdi-plus me-1"></i>Yeni Destek Talebi</button>';
?>
<div class="card">
    <div class="card-header">
        <h5>DESTEK TALEPLERİ</h5>
        <div class="dt-toolbar">
            <div class="bulk-bar" id="bulk-bar">
                <span class="text-muted small"><b class="bulk-count">0</b> seçili</span>
                <button class="btn btn-sm btn-outline-danger" data-bulk="/destek_talepleri/multiple_arsiv" data-confirm="Seçili talepler arşivlensin mi?"><i class="mdi mdi-archive-outline me-1"></i>Arşivle</button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="d-flex gap-2 mb-2">
            <select data-filter="durum" class="form-select" style="max-width:200px"><?= Tanim::options(Tanim::DESTEK_DURUM, null, 'Tüm Durumlar') ?></select>
            <input type="search" id="dt-search" class="form-control" placeholder="Talep kodu, konu, talep eden ile arayın...">
        </div>
        <div class="table-responsive">
            <table id="liste" class="table table-hover w-100">
                <thead><tr><th><input type="checkbox" class="form-check-input check-all"></th><th>Talep Kodu</th><th>Talep Konusu</th><th>Talep Tarihi</th><th>Talep Türü</th><th>Talep Eden</th><th>Durumu</th><th>İşlem</th></tr></thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="destekModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="post" action="/destek_talepleri/save" enctype="multipart/form-data" data-ajax>
            <?= Csrf::field() ?>
            <div class="modal-header"><h5 class="modal-title">Yeni Destek Talebi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label required">Talep Konusu</label><input type="text" name="konu" class="form-control" maxlength="200" required></div>
                <div class="mb-3"><label class="form-label">Talep Türü</label><select name="tur" class="form-select"><?= Tanim::options(Tanim::DESTEK_TUR, 1, null) ?></select></div>
                <div class="mb-3"><label class="form-label">Açıklama</label><textarea name="aciklama" class="form-control" rows="4"></textarea></div>
                <div><label class="form-label">Ek Dosya</label><input type="file" name="dosya[]" class="form-control" multiple></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Gönder</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="detayModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="post" data-ajax>
            <?= Csrf::field() ?>
            <div class="modal-header"><h5 class="modal-title" data-f="baslik"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row small mb-3">
                    <div class="col-md-4"><b>Talep Eden:</b> <span data-f="talep_eden"></span></div>
                    <div class="col-md-4"><b>Tür:</b> <span data-f="tur_ad"></span></div>
                    <div class="col-md-4"><b>Tarih:</b> <span data-f="tarih"></span></div>
                </div>
                <div class="p-3 bg-light rounded mb-3" data-f="aciklama" style="white-space:pre-wrap"></div>
                <ul class="file-list mb-3" data-f="dosyalar"></ul>
                <?php if (Auth::isAdmin()): ?>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Durum</label><select name="durum" class="form-select"><?= Tanim::options(Tanim::DESTEK_DURUM, null, null) ?></select></div>
                        <div class="col-12"><label class="form-label">Cevap</label><textarea name="cevap" class="form-control" rows="3"></textarea></div>
                    </div>
                <?php else: ?>
                    <label class="form-label fw-semibold">Yanıt</label>
                    <div class="p-3 border rounded" data-f="cevap" style="white-space:pre-wrap"></div>
                <?php endif; ?>
            </div>
            <?php if (Auth::isAdmin()): ?><div class="modal-footer"><button type="submit" class="btn btn-primary">Güncelle</button></div><?php endif; ?>
        </form>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(function () {
    const renk = { 1: 'danger', 2: 'warning', 3: 'success', 4: 'secondary' };
    App.table('#liste', {
        url: '/destek_talepleri/liste', bulk: true, order: [[1, 'desc']],
        columns: [
            { data: 'kod', render: (v, _, r) => `<a href="#" data-detay="${r.id}" class="fw-semibold">${App.esc(v)}</a>` },
            { data: 'konu', render: (v) => App.esc(v) },
            { data: 'tarih' },
            { data: 'tur', render: (_, __, r) => App.esc(r.tur_ad) },
            { data: 'talep_eden', render: (v) => App.esc(v || '-') },
            { data: 'durum', render: (_, __, r) => App.badge(r.durum_ad, renk[r.durum]) },
            { data: 'id', orderable: false, render: (id) => App.actions([{ url: '#', icon: 'mdi-eye', color: 'success', title: 'Detay', attrs: `data-detay="${id}"` }]) },
        ],
    });
    $(document).on('click', '[data-detay]', function (e) {
        e.preventDefault();
        $.getJSON('/destek_talepleri/getir/' + this.dataset.detay, (r) => {
            const d = r.data, $m = $('#detayModal');
            $m.find('form').attr('action', '/destek_talepleri/cevapla/' + d.id);
            $m.find('[data-f=baslik]').text(d.kod + ' — ' + d.konu);
            ['talep_eden', 'tur_ad', 'tarih'].forEach((k) => $m.find(`[data-f=${k}]`).text(d[k] || '-'));
            $m.find('[data-f=aciklama]').text(d.aciklama || 'Açıklama girilmemiş.');
            $m.find('[data-f=cevap]').text(d.cevap || 'Henüz yanıtlanmadı.');
            $m.find('[data-f=dosyalar]').html(d.dosyalar.map((f) => `<li><i class="mdi mdi-paperclip"></i><a href="${App.esc(f.dosya_yolu)}" target="_blank">${App.esc(f.orijinal_ad)}</a></li>`).join(''));
            $m.find('[name=durum]').val(d.durum);
            $m.find('[name=cevap]').val(d.cevap || '');
            bootstrap.Modal.getOrCreateInstance($m[0]).show();
        }).fail((x) => App.error(x.responseJSON?.message));
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
