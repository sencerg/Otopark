<?php
use App\Core\Auth;
use App\Core\Tanim;
?>
<div class="card">
    <div class="card-header">
        <h5>Depolama Raporu</h5>
        <div class="dt-toolbar">
            <div class="total-box">Toplam Gün: <b id="toplam-gun">0</b></div>
            <div class="total-box">Toplam Tutar: <b id="toplam-tutar">0,00 ₺</b></div>
            <button id="dt-export" class="btn btn-sm btn-success"><i class="mdi mdi-microsoft-excel me-1"></i>Excel İndir</button>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-light border small mb-3">
            <i class="mdi mdi-information-outline me-1"></i>Seçilen tarih aralığında stokta kalınan günler, giriş günü dahil sayılır ve müşteri / lokasyon / araç tipi için tanımlı <a href="/arac_yonetimi/tanimlar/depolama_fiyatlari">günlük depolama fiyatı</a> ile çarpılır.
        </div>
        <div class="filter-bar filter-scope">
            <div><label class="form-label">Başlangıç</label><input type="date" data-filter="baslangic" class="form-control" value="<?= date('Y-m-01') ?>"></div>
            <div><label class="form-label">Bitiş</label><input type="date" data-filter="bitis" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div><label class="form-label">Müşteri</label><select data-filter="musteri_id" class="form-select select2"><?= Tanim::options(Tanim::liste('musteriler'), null, 'Tüm Müşteriler') ?></select></div>
            <?php if (!Auth::bayiId()): ?>
                <div><label class="form-label">Lokasyon</label><select data-filter="bayi_id" class="form-select"><?= Tanim::options(Tanim::liste('bayiler'), null, 'Tüm Lokasyonlar') ?></select></div>
            <?php endif; ?>
            <div><label class="form-label">Araç Tipi</label><select data-filter="arac_tipi_id" class="form-select"><?= Tanim::options(Tanim::liste('arac_tipleri'), null, 'Tümü') ?></select></div>
            <div><label class="form-label">Durum</label><select data-filter="durum" class="form-select"><option value="">Tümü</option><option value="stokta">Halen Stokta</option><option value="cikti">Stoktan Çıkmış</option></select></div>
        </div>
        <div class="d-flex gap-2 mt-3 mb-2">
            <input type="search" id="dt-search" class="form-control" placeholder="Şasi, plaka, firma, lokasyon ile arayın...">
            <button id="dt-clear" class="btn btn-light text-nowrap"><i class="mdi mdi-filter-remove-outline"></i> Temizle</button>
        </div>
        <div class="table-responsive">
            <table id="liste" class="table table-hover w-100">
                <thead><tr><th>Şase</th><th>Plaka</th><th>Firma</th><th>Marka / Seri</th><th>Araç Tipi</th><th>Lokasyon</th><th>Giriş</th><th>Çıkış</th><th class="text-end">Gün</th><th class="text-end">Günlük Fiyat</th><th class="text-end">Tutar</th></tr></thead>
            </table>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(function () {
    App.table('#liste', {
        url: '/depolama_raporu/liste', order: [[6, 'desc']],
        onData: (json) => { $('#toplam-tutar').text(App.money(json.toplam)); $('#toplam-gun').text(App.num(json.toplam_gun)); },
        columns: [
            { data: 'sase', render: (v, _, r) => `<a href="/arac_yonetimi/duzenle/${r.arac_id}" class="fw-semibold">${App.esc(v)}</a>` },
            { data: 'plaka', render: (v) => App.esc(v || '-') },
            { data: 'firma', render: (v) => App.esc(v || '-') },
            { data: 'marka', render: (v, _, r) => App.esc([v, r.seri].filter(Boolean).join(' ') || '-') },
            { data: 'arac_tipi', render: (v) => App.esc(v || '-') },
            { data: 'lokasyon', render: (v) => App.esc(v || '-') },
            { data: 'giris_tarihi' },
            { data: 'cikis_tarihi', render: (v) => v || App.badge('Stokta', 'success') },
            { data: 'gun', className: 'text-end' },
            { data: 'gunluk_fiyat', className: 'text-end', render: (v, _, r) => r.fiyat_yok ? App.badge('Fiyat tanımlı değil', 'warning') : App.money(v) },
            { data: 'tutar', className: 'text-end fw-semibold', render: (v) => App.money(v) },
        ],
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
