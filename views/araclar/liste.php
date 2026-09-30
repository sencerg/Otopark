<?php
$headerActions = '<a href="/arac_yonetimi/ekle" class="btn btn-primary"><i class="mdi mdi-plus me-1"></i>Araç Ekle</a>'
    . '<a href="/arac_yonetimi/toplu_stok_girisi" class="btn btn-outline-primary"><i class="mdi mdi-table-arrow-down me-1"></i>Toplu Stok Girişi</a>'
    . '<a href="/arac_yonetimi/toplu_ek_maliyet_girisi" class="btn btn-outline-primary"><i class="mdi mdi-cash-multiple me-1"></i>Toplu Ek Maliyet</a>'
    . '<a href="/arac_yonetimi/toplu_kayit_guncelleme_lokasyon" class="btn btn-outline-primary"><i class="mdi mdi-map-marker-radius me-1"></i>Adres Düzenle</a>';
?>
<div class="card">
    <div class="card-header">
        <h5>Araç Listesi</h5>
        <div class="dt-toolbar">
            <div class="bulk-bar" id="bulk-bar">
                <span class="text-muted small"><b class="bulk-count">0</b> seçili</span>
                <button class="btn btn-sm btn-dark" data-bulk="/arac_yonetimi/qr_toplu" data-bulk-open="1"><i class="mdi mdi-qrcode me-1"></i>QR Yazdır</button>
                <button class="btn btn-sm btn-outline-danger" data-bulk="/arac_yonetimi/multiple_arsiv" data-confirm="Seçili araçlar arşivlensin mi?"><i class="mdi mdi-archive-outline me-1"></i>Arşivle</button>
                <?php if (\App\Core\Auth::isAdmin()): ?>
                    <button class="btn btn-sm btn-danger" data-bulk="/arac_yonetimi/multiple_delete" data-confirm="Seçili araçlar ve tüm hareket/maliyet kayıtları kalıcı olarak silinecek. Emin misiniz?"><i class="mdi mdi-delete me-1"></i>Sil</button>
                <?php endif; ?>
            </div>
            <button id="dt-export" class="btn btn-sm btn-success"><i class="mdi mdi-microsoft-excel me-1"></i>Excel İndir</button>
        </div>
    </div>
    <div class="card-body">
        <?php $tarihEtiketi = 'Giriş Tarihi'; require BASE_PATH . '/views/partials/arac_filtre.php'; ?>
        <div class="d-flex gap-2 mt-3 mb-2">
            <select data-filter="stok" class="form-select" style="max-width:190px">
                <option value="">Tüm Araçlar</option>
                <option value="stokta" <?= ($_GET['stok'] ?? '') === 'stokta' ? 'selected' : '' ?>>Stokta Olanlar</option>
                <option value="cikti" <?= ($_GET['stok'] ?? '') === 'cikti' ? 'selected' : '' ?>>Stoktan Çıkanlar</option>
            </select>
            <input type="search" id="dt-search" class="form-control" placeholder="Plaka, Şasi, Firma, Lokasyon ile arayın..." value="<?= e($_GET['q'] ?? '') ?>">
            <button id="dt-clear" class="btn btn-light text-nowrap"><i class="mdi mdi-filter-remove-outline"></i> Temizle</button>
        </div>
        <div class="table-responsive">
            <table id="liste" class="table table-hover w-100">
                <thead>
                <tr>
                    <th><input type="checkbox" class="form-check-input check-all"></th>
                    <th>Şase</th><th>Plaka</th><th>Firma</th><th>Marka</th><th>Seri</th><th>Lokasyon</th>
                    <th>Durum</th><th>Giriş Tarihi</th><th>Çıkış Tarihi</th><th>Depolama Süresi</th><th>İşlem</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(function () {
    App.table('#liste', {
        url: '/arac_yonetimi/liste', bulk: true, order: [[8, 'desc']],
        columns: [
            { data: 'sase', render: (v, _, r) => `<a href="/arac_yonetimi/duzenle/${r.id}" class="fw-semibold">${App.esc(v)}</a>` },
            { data: 'plaka', render: (v) => App.esc(v || '-') },
            { data: 'firma', render: (v) => App.esc(v || '-') },
            { data: 'marka', render: (v) => App.esc(v || '-') },
            { data: 'seri', render: (v) => App.esc(v || '-') },
            { data: 'lokasyon', render: (v) => App.esc(v || '-') },
            { data: 'stokta', render: (v) => v ? App.badge('Stokta', 'success') : App.badge('Stoktan Çıktı', 'danger') },
            { data: 'giris_tarihi', render: (v) => v || '-' },
            { data: 'cikis_tarihi', render: (v) => v || '-' },
            { data: 'depolama_suresi', render: (v) => v === null ? '-' : App.num(v) + ' Gün' },
            { data: 'id', orderable: false, render: (id, _, r) => App.actions([
                { url: '/arac_yonetimi/duzenle/' + id, icon: 'mdi-pencil-box', color: 'primary', title: 'Düzenle' },
                { url: '/arac_yonetimi/tesellum_formu/' + id, icon: 'mdi-file-document-outline', color: 'info', title: 'Tesellüm Formu', attrs: 'target="_blank"' },
                { url: '/arac_yonetimi/qr_toplu?ids[]=' + id, icon: 'mdi-qrcode', color: 'dark', title: 'QR Yazdır', attrs: 'target="_blank"' },
                r.stokta && { url: '/arac_hareketleri/stoktan_cikar/' + id, icon: 'mdi-car-off', color: 'danger', title: 'Stoktan Çıkar' },
            ]) },
        ],
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
