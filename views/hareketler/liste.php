<?php
$giris = $tip === 'giris';
$headerActions = '<a href="/arac_yonetimi/ekle" class="btn btn-success"><i class="mdi mdi-plus me-1"></i>Yeni Araç Ekle</a>'
    . '<a href="/arac_yonetimi/toplu_stok_girisi" class="btn btn-primary"><i class="mdi mdi-upload me-1"></i>Toplu Stok Girişi</a>'
    . '<a href="/arac_yonetimi/toplu_ek_maliyet_girisi" class="btn text-white" style="background:#7c3aed"><i class="mdi mdi-cash-multiple me-1"></i>Toplu Maliyet Ekle</a>'
    . '<a href="/arac_yonetimi/toplu_kayit_guncelleme_lokasyon" class="btn btn-warning text-white"><i class="mdi mdi-map-marker-outline me-1"></i>Toplu Adres Güncelleme</a>';
?>
<div class="card">
    <div class="card-header">
        <h5><?= $giris ? 'Stoktaki Araçlar' : 'Stoktan Çıkanlar' ?></h5>
        <div class="dt-toolbar">
            <div class="bulk-bar" id="bulk-bar">
                <span class="text-muted small"><b class="bulk-count">0</b> seçili</span>
                <?php if ($giris): ?>
                    <button class="btn btn-sm btn-dark" data-bulk="/arac_yonetimi/qr_toplu" data-bulk-open="1" data-confirm="Seçili araçlar için QR etiketi yazdırılsın mı?"><i class="mdi mdi-qrcode me-1"></i>QR Yazdır</button>
                    <button class="btn btn-sm btn-outline-danger" data-bulk="/arac_yonetimi/multiple_arsiv" data-confirm="Seçili araçlar arşivlensin mi?"><i class="mdi mdi-archive-outline me-1"></i>Arşivle</button>
                <?php else: ?>
                    <button class="btn btn-sm btn-outline-danger" data-bulk="/arac_hareketleri/multiple_arsiv" data-confirm="Seçili hareketler arşivlensin mi?"><i class="mdi mdi-archive-outline me-1"></i>Arşivle</button>
                <?php endif; ?>
            </div>
            <button id="dt-export" class="btn btn-sm btn-success"><i class="mdi mdi-microsoft-excel me-1"></i>Excel İndir</button>
        </div>
    </div>
    <div class="card-body">
        <?php $lokasyonTuruFiltre = $giris; $konsinyeFiltre = true; $tarihEtiketi = $giris ? 'Giriş Tarihi' : 'Çıkış Tarihi'; require BASE_PATH . '/views/partials/arac_filtre.php'; ?>
        <div class="d-flex gap-2 mt-3 mb-2">
            <input type="search" id="dt-search" class="form-control" placeholder="Hareket Kodu, Plaka, Şasi, Lokasyon ile arayın...">
            <button id="dt-clear" class="btn btn-light text-nowrap"><i class="mdi mdi-filter-remove-outline"></i> Temizle</button>
        </div>
        <div class="table-responsive">
            <table id="liste" class="table table-hover w-100">
                <thead>
                <tr>
                    <th><input type="checkbox" class="form-check-input check-all"></th>
                    <th>Şase</th><th>Plaka</th><th>Firma</th><th>Marka</th><th>Seri</th><th>Lokasyon</th>
                    <?php if ($giris): ?>
                        <th>Giriş Tarihi</th><th>Depolama Süresi</th>
                    <?php else: ?>
                        <th>Hareket Tarihi</th><th>Depolama Süresi</th><th>Sevkiyat Tipi</th><th>Sevkiyat Durumu</th><th class="text-end">Hizmet Ücreti</th>
                    <?php endif; ?>
                    <th>İşlem</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<?php if ($giris): ?>
<div class="modal fade" id="iskontoModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form class="modal-content" method="post" action="/arac_hareketleri/etiket_fiyati" data-ajax>
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="arac_id">
            <div class="modal-header"><h5 class="modal-title">Etiket Fiyatı</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small text-muted mb-2" data-sase></p>
                <label class="form-label">Fiyat (₺)</label>
                <input type="number" step="0.01" min="0" name="fiyat" class="form-control" required>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary w-100">Kaydet</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php ob_start(); ?>
<script>
$(function () {
    const giris = <?= $giris ? 'true' : 'false' ?>;
    const saseLink = (r) => `<a href="/arac_yonetimi/duzenle/${r.<?= $giris ? 'id' : 'arac_id' ?>}" class="fw-semibold">${App.esc(r.sase)}</a>`;
    const gun = (v) => v === null ? '-' : App.num(v) + ' Gün';
    const sevkRenk = { 1: 'warning', 2: 'info', 3: 'success' };

    const columns = [
        { data: 'sase', render: (_, __, r) => saseLink(r) },
        { data: 'plaka', render: (v) => App.esc(v || '-') },
        { data: 'firma', render: (v) => App.esc(v || '-') },
        { data: 'marka', render: (v) => App.esc(v || '-') },
        { data: 'seri', render: (v) => App.esc(v || '-') },
        { data: 'lokasyon', render: (v, _, r) => App.esc(v || '-') + (r.lokasyon_detay ? `<div class="small text-muted">${App.esc(r.lokasyon_detay)}</div>` : '') },
    ];
    if (giris) {
        columns.push(
            { data: 'giris_tarihi' },
            { data: 'depolama_suresi', render: gun },
            { data: 'id', orderable: false, render: (id, _, r) => App.actions([
                r.hareket_id && { url: '/arac_hareketleri/hareket_view/' + r.hareket_id, icon: 'mdi-eye', color: 'success', title: 'Görüntüle' },
                r.hareket_id && { url: '/arac_hareketleri/hareket_duzenle/' + r.hareket_id, icon: 'mdi-pencil-box', color: 'primary', title: 'Düzenle' },
                { url: '/arac_yonetimi/tesellum_formu/' + id, icon: 'mdi-file-document-outline', color: 'info', title: 'Tesellüm Formu' },
                { url: '/arac_yonetimi/qr_toplu?ids[]=' + id, icon: 'mdi-qrcode', color: 'dark', title: 'QR Yazdır', attrs: 'target="_blank"' },
                { url: '/arac_hareketleri/stoktan_cikar/' + id, icon: 'mdi-car-off', color: 'danger', title: 'Stoktan Çıkar' },
                { url: '#', icon: 'mdi-tag-outline', color: 'warning', title: 'Etiket Fiyatı', attrs: `data-etiket="${id}" data-sase="${App.esc(r.sase)}" data-fiyat="${r.etiket_fiyati || ''}"` },
                { url: '#', icon: 'mdi-cash-plus', color: 'secondary', title: 'Maliyet Ekle', attrs: `data-maliyet="${id}" data-sase="${App.esc(r.sase + ' — ' + (r.plaka || ''))}"` },
            ]) },
        );
    } else {
        columns.push(
            { data: 'hareket_tarihi' },
            { data: 'depolama_suresi', render: gun },
            { data: 'sevkiyat_tipi_ad', orderable: false },
            { data: 'sevkiyat_durumu_ad', orderable: false, render: (v, _, r) => App.badge(v, sevkRenk[r.sevkiyat_durumu]) },
            { data: 'hizmet_tutari', className: 'text-end text-nowrap', render: (v, _, r) => (Number(v) ? App.money(v) : '<span class="text-muted">-</span>')
                + (Number(r.sonradan_adet) ? `<div><span class="badge bg-warning text-dark" title="Çıkıştan sonra eklenen hizmet">+${App.money(r.sonradan_tutar)} çıkış sonrası (${App.num(r.sonradan_adet)})</span></div>` : '') },
            { data: 'id', orderable: false, render: (id, _, r) => App.actions([
                { url: '/arac_yonetimi/tesellum_formu/' + r.arac_id, icon: 'mdi-file-document-outline', color: 'info', title: 'Tesellüm Formu' },
                { url: '/arac_hareketleri/hareket_view/' + id, icon: 'mdi-eye', color: 'success', title: 'Görüntüle' },
                { url: '/arac_hareketleri/hareket_duzenle/' + id, icon: 'mdi-pencil-box', color: 'primary', title: 'Düzenle' },
            ]) },
        );
    }

    App.table('#liste', {
        url: giris ? '/arac_hareketleri/giris/liste' : '/arac_hareketleri/cikis/liste',
        columns, bulk: true, order: [[7, 'desc']],
        createdRow: (row, r) => { if (Number(r.sonradan_adet)) row.classList.add('table-warning'); },
    });

    $(document).on('click', '[data-etiket]', function (e) {
        e.preventDefault();
        const $m = $('#iskontoModal');
        $m.find('[name=arac_id]').val(this.dataset.etiket);
        $m.find('[name=fiyat]').val(this.dataset.fiyat);
        $m.find('[data-sase]').text(this.dataset.sase);
        bootstrap.Modal.getOrCreateInstance($m[0]).show();
    });

    $(document).on('click', '[data-maliyet]', function (e) {
        e.preventDefault();
        const $m = $('#hizli-maliyet-ekle-modal');
        bootstrap.Modal.getOrCreateInstance($m[0]).show();
        const $sel = $m.find('[name=arac_id]');
        App.initSelect2($m);
        $sel.empty().append(new Option(this.dataset.sase, this.dataset.maliyet, true, true)).trigger('change');
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
