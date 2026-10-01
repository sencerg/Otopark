<?php
use App\Core\Auth;
use App\Core\Tanim;

$headerActions = '<a href="/sayim_kayitlari/ekle" class="btn btn-success"><i class="mdi mdi-plus me-1"></i>Yeni Araç Sayımı</a>';
?>
<div class="card">
    <div class="card-header">
        <h5>ARAÇ SAYIMLARI LİSTESİ</h5>
        <div class="dt-toolbar">
            <div class="bulk-bar" id="bulk-bar">
                <span class="text-muted small"><b class="bulk-count">0</b> seçili</span>
                <button class="btn btn-sm btn-outline-danger" data-bulk="/sayim_kayitlari/multiple_arsiv" data-confirm="Seçili sayımlar arşivlensin mi?"><i class="mdi mdi-archive-outline"></i></button>
            </div>
            <button id="dt-export" class="btn btn-sm btn-success"><i class="mdi mdi-microsoft-excel me-1"></i>Excel İndir</button>
        </div>
    </div>
    <div class="card-body">
        <div class="filter-bar filter-scope">
            <?php if (Auth::bayiId() === null): ?>
                <div><label class="form-label">Lokasyon</label><select data-filter="bayi_id" class="form-select"><?= Tanim::options(Tanim::liste('bayiler'), null, 'Tüm Lokasyonlar') ?></select></div>
            <?php endif; ?>
            <div><label class="form-label">Sorumlu</label><select data-filter="sorumlu_personel_id" class="form-select select2"><?= Tanim::options(Tanim::liste('personeller'), null, 'Tümü') ?></select></div>
            <div><label class="form-label">Durumu</label><select data-filter="durum" class="form-select"><?= Tanim::options(Tanim::SAYIM_DURUM, null, 'Tümü') ?></select></div>
            <div><label class="form-label">Başlangıç Tarihi</label><input type="date" data-filter="baslangic" class="form-control"></div>
            <div><label class="form-label">Bitiş Tarihi</label><input type="date" data-filter="bitis" class="form-control"></div>
        </div>
        <div class="d-flex gap-2 mt-3 mb-2">
            <input type="search" id="dt-search" class="form-control" placeholder="Sayım kodu, başlık, lokasyon ile arayın...">
            <button id="dt-clear" class="btn btn-light text-nowrap"><i class="mdi mdi-filter-remove-outline"></i> Temizle</button>
        </div>
        <div class="table-responsive">
            <table id="liste" class="table table-hover w-100">
                <thead><tr><th><input type="checkbox" class="form-check-input check-all"></th><th>Sayım Kodu</th><th>Başlık</th><th>Lokasyon</th><th>Sorumlu Personel</th><th>Sayım Tarihi</th><th>Okutulan</th><th>Lokasyonda Değil</th><th>Okutulmayan</th><th>Sayım Durumu</th><th>İşlem</th></tr></thead>
            </table>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(function () {
    App.table('#liste', {
        url: '/sayim_kayitlari/liste', bulk: true, order: [[1, 'desc']],
        columns: [
            { data: 'kod', render: (v, _, r) => `<a href="/sayim_kayitlari/detay/${r.id}" class="fw-semibold">${App.esc(v)}</a>` },
            { data: 'baslik', render: (v) => App.esc(v) },
            { data: 'lokasyon', render: (v) => App.esc(v) },
            { data: 'sorumlu', render: (v) => App.esc(v || '-') },
            { data: 'tarih' },
            { data: 'okutulan', orderable: false, render: (v) => App.badge(App.num(v), 'success') },
            { data: 'disarida', orderable: false, render: (v) => App.badge(App.num(v), Number(v) ? 'danger' : 'secondary') },
            { data: 'okutulmayan', orderable: false, render: (v, _, r) => App.badge(App.num(v) + (r.durum == 2 ? ' bulunamadı' : ''), Number(v) ? 'warning' : 'secondary') },
            { data: 'durum', render: (_, __, r) => App.badge(r.durum_ad, r.durum_renk) },
            { data: 'id', orderable: false, render: (id, _, r) => App.actions([
                { url: '/sayim_kayitlari/detay/' + id, icon: r.durum == 1 ? 'mdi-barcode-scan' : 'mdi-eye', color: r.durum == 1 ? 'primary' : 'success', title: r.durum == 1 ? 'Okutmaya Devam Et' : 'Görüntüle' },
                { url: '/sayim_kayitlari/excel/' + id, icon: 'mdi-microsoft-excel', color: 'success', title: 'Excel İndir' },
            ]) },
        ],
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
