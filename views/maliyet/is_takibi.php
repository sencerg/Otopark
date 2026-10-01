<?php
use App\Core\Auth;
use App\Core\Database;
use App\Core\Tanim;

$kullanicilar = array_column(Database::fetchAll(
    'SELECT id, name FROM users WHERE is_active' . (Auth::bayiId() ? ' AND bayi_id = ' . Auth::bayiId() : '') . ' ORDER BY name'
), 'name', 'id');
?>
<div class="card">
    <div class="card-header">
        <h5>İş Takibi <small class="text-muted fw-normal">— araçlara yazılan hizmetler ve işlemi yapan kullanıcılar</small></h5>
        <div class="dt-toolbar">
            <div class="total-box">Toplam: <b id="toplam-tutar">0,00 ₺</b></div>
            <button id="dt-export" class="btn btn-sm btn-success"><i class="mdi mdi-microsoft-excel me-1"></i>Excel İndir</button>
        </div>
    </div>
    <div class="card-body">
        <div class="filter-bar filter-scope">
            <div><label class="form-label">Başlangıç</label><input type="date" data-filter="baslangic" class="form-control" value="<?= date('Y-m-01') ?>"></div>
            <div><label class="form-label">Bitiş</label><input type="date" data-filter="bitis" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div><label class="form-label">Müşteri</label><select data-filter="musteri_id" class="form-select select2"><?= Tanim::options(Tanim::liste('musteriler'), null, 'Tüm Müşteriler') ?></select></div>
            <?php if (!Auth::bayiId()): ?>
                <div><label class="form-label">Lokasyon</label><select data-filter="bayi_id" class="form-select"><?= Tanim::options(Tanim::liste('bayiler'), null, 'Tüm Lokasyonlar') ?></select></div>
            <?php endif; ?>
            <div><label class="form-label">Hizmet</label><select data-filter="maliyet_tipi_id" class="form-select select2"><?= Tanim::options(Tanim::liste('maliyet_tipleri'), null, 'Tüm Hizmetler') ?></select></div>
            <div><label class="form-label">İşlemi Yapan</label><select data-filter="kullanici_id" class="form-select select2"><?= Tanim::options($kullanicilar, null, 'Tüm Kullanıcılar') ?></select></div>
        </div>
        <div class="d-flex gap-2 mt-3 mb-2">
            <input type="search" id="dt-search" class="form-control" placeholder="Şasi, plaka, hizmet, açıklama, kullanıcı ile arayın...">
            <button id="dt-clear" class="btn btn-light text-nowrap"><i class="mdi mdi-filter-remove-outline"></i> Temizle</button>
        </div>
        <div class="table-responsive">
            <table id="liste" class="table table-hover w-100">
                <thead><tr><th>İşlem Tarihi</th><th>Şase</th><th>Plaka</th><th>Firma</th><th>Lokasyon</th><th>Hizmet</th><th>Açıklama</th><th>İşlemi Yapan</th><th>Kayıt Zamanı</th><th class="text-end">Tutar</th></tr></thead>
            </table>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(function () {
    App.table('#liste', {
        url: '/is_takibi/liste', order: [[0, 'desc']],
        onData: (json) => $('#toplam-tutar').text(App.money(json.toplam)),
        columns: [
            { data: 'islem_tarihi' },
            { data: 'sase', render: (v, _, r) => `<a href="/arac_yonetimi/duzenle/${r.arac_id}" class="fw-semibold">${App.esc(v)}</a>` },
            { data: 'plaka', render: (v) => App.esc(v || '-') },
            { data: 'firma', render: (v) => App.esc(v || '-') },
            { data: 'lokasyon', render: (v) => App.esc(v || '-') },
            { data: 'maliyet_tipi', render: (v, _, r) => App.esc(v) + (r.cikis_sonrasi ? '<div><span class="badge bg-warning text-dark">Çıkış sonrası eklendi</span></div>' : '') },
            { data: 'aciklama', render: (v) => App.esc(v || '-') },
            { data: 'kullanici', render: (v) => v ? `<i class="mdi mdi-account-circle text-primary"></i> ${App.esc(v)}` : '-' },
            { data: 'kayit_zamani' },
            { data: 'tutar', className: 'text-end fw-semibold', render: (v) => App.money(v) },
        ],
        createdRow: (row, r) => { if (r.cikis_sonrasi) row.classList.add('table-warning'); },
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
