<?php
use App\Core\Tanim;

$headerActions = '<a href="/gorev_yonetimi/ekle" class="btn btn-primary"><i class="mdi mdi-plus me-1"></i>İş Emri Ekle</a>';
?>
<ul class="nav nav-pills mb-3 gap-1">
    <li class="nav-item"><a class="nav-link <?= !$durum ? 'active' : '' ?>" href="/gorev_yonetimi">Tümü</a></li>
    <?php foreach (Tanim::IS_EMRI_DURUM as $k => $ad): ?>
        <li class="nav-item"><a class="nav-link <?= $durum === $k ? 'active' : '' ?>" href="/gorev_yonetimi?durum=<?= $k ?>"><?= e($ad) ?></a></li>
    <?php endforeach; ?>
</ul>
<div class="card">
    <div class="card-header">
        <h5>İŞ EMRİ LİSTESİ <small class="text-muted fw-normal">— <?= e($baslik) ?></small></h5>
        <div class="dt-toolbar">
            <div class="bulk-bar" id="bulk-bar">
                <span class="text-muted small"><b class="bulk-count">0</b> seçili</span>
                <button class="btn btn-sm btn-info" data-bulk="/gorev_yonetimi/toplu_durum?durum=3" data-confirm="Seçili iş emirleri 'İşleme Alındı' yapılsın mı?">İşleme Al</button>
                <button class="btn btn-sm btn-success" data-bulk="/gorev_yonetimi/toplu_durum?durum=4" data-confirm="Seçili iş emirleri tamamlansın mı? Araçlara iş emri tutarı kadar maliyet yazılacak.">Tamamlandı</button>
                <button class="btn btn-sm btn-outline-dark" data-bulk="/gorev_yonetimi/toplu_durum?durum=6" data-confirm="Seçili iş emirleri iptal edilsin mi?">İptal Et</button>
                <button class="btn btn-sm btn-outline-danger" data-bulk="/gorev_yonetimi/multiple_arsiv" data-confirm="Seçili iş emirleri arşivlensin mi?"><i class="mdi mdi-archive-outline"></i></button>
            </div>
            <button id="dt-export" class="btn btn-sm btn-success"><i class="mdi mdi-microsoft-excel me-1"></i>Excel İndir</button>
        </div>
    </div>
    <div class="card-body">
        <div class="filter-bar filter-scope">
            <div><label class="form-label">Müşteri</label><select data-filter="musteri_id" class="form-select select2"><?= Tanim::options(Tanim::liste('musteriler'), null, 'Tüm Müşteriler') ?></select></div>
            <div><label class="form-label">İş Emri Türü</label><select data-filter="maliyet_tipi_id" class="form-select select2"><?= Tanim::options(Tanim::liste('maliyet_tipleri'), null, 'Tümü') ?></select></div>
            <div><label class="form-label">Talep Başlangıç</label><input type="date" data-filter="baslangic" class="form-control"></div>
            <div><label class="form-label">Talep Bitiş</label><input type="date" data-filter="bitis" class="form-control"></div>
        </div>
        <div class="d-flex gap-2 mt-3 mb-2">
            <input type="search" id="dt-search" class="form-control" placeholder="İş emri kodu, müşteri, tür ile arayın...">
            <button id="dt-clear" class="btn btn-light text-nowrap"><i class="mdi mdi-filter-remove-outline"></i> Temizle</button>
        </div>
        <div class="table-responsive">
            <table id="liste" class="table table-hover w-100">
                <thead><tr><th><input type="checkbox" class="form-check-input check-all"></th><th>İş Emri Kodu</th><th>Müşteri</th><th>İş Emri Türü</th><th>Araç</th><th>Talep Tarihi</th><th>İstenen Tarih</th><th>Durumu</th><th>İşlem</th></tr></thead>
            </table>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(function () {
    App.table('#liste', {
        url: '/gorev_yonetimi/liste<?= $durum ? '?durum=' . $durum : '' ?>', bulk: true, order: [[1, 'desc']],
        columns: [
            { data: 'kod', render: (v, _, r) => `<a href="/gorev_yonetimi/duzenle/${r.id}" class="fw-semibold">${App.esc(v)}</a>` },
            { data: 'musteri', render: (v) => App.esc(v) },
            { data: 'tur', render: (v) => App.esc(v) },
            { data: 'arac_sayisi', orderable: false, render: (v) => App.num(v) + ' araç' },
            { data: 'talep_tarihi' },
            { data: 'istenen_tarih', render: (v) => v || '-' },
            { data: 'durum', render: (_, __, r) => App.badge(r.durum_ad, r.durum_renk) },
            { data: 'id', orderable: false, render: (id) => App.actions([
                { url: '/gorev_yonetimi/duzenle/' + id, icon: 'mdi-pencil-box', color: 'primary', title: 'Düzenle' },
            ]) },
        ],
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
