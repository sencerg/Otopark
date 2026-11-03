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
                <button type="button" class="btn btn-sm btn-danger" data-bulk="/arac_yonetimi/toplu_stok_cikisi" data-bulk-nav="1"><i class="mdi mdi-car-off me-1"></i>Toplu stok çıkışı</button>
                <button type="button" class="btn btn-sm btn-primary" data-bulk="/arac_yonetimi/toplu_ek_maliyet_girisi" data-bulk-nav="1"><i class="mdi mdi-cash-multiple me-1"></i>Toplu maliyet</button>
                <?php if (\App\Core\Auth::isAdmin() && \App\Core\Auth::can('arac.sil')): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="bulk-sil"><i class="mdi mdi-delete me-1"></i>Sil</button>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-dark" data-bulk="/arac_yonetimi/qr_toplu" data-bulk-open="1"><i class="mdi mdi-qrcode me-1"></i>QR Yazdır</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bulk="/arac_yonetimi/multiple_arsiv" data-confirm="Seçili araçlar arşivlensin mi?"><i class="mdi mdi-archive-outline me-1"></i>Arşivle</button>
                <button type="button" class="btn btn-sm btn-light" data-bulk-clear>Seçimi temizle</button>
                <div class="bulk-secili" id="bulk-secili"></div>
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
            <div class="elastic-ara">
                <input type="search" id="dt-search" class="form-control" placeholder="Plaka veya şasi — başı, ortası veya son haneleri" value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
                <div class="elastic-menu" id="dt-search-menu" hidden></div>
            </div>
            <button id="dt-clear" class="btn btn-light text-nowrap"><i class="mdi mdi-filter-remove-outline"></i> Temizle</button>
        </div>
        <div class="elastic-chips" id="dt-search-chips"></div>
        <div class="table-responsive">
            <table id="liste" class="table table-hover w-100">
                <thead>
                <tr>
                    <th><input type="checkbox" class="form-check-input check-all"></th>
                    <th>Şase</th><th>Plaka</th><th>Firma</th><th>Marka</th><th>Model</th><th>Seri</th><th>Lokasyon</th>
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
    const canSil = <?= (\App\Core\Auth::isAdmin() && \App\Core\Auth::can('arac.sil')) ? 'true' : 'false' ?>;
    const pins = new Map();
    const chipCiz = () => {
        const $box = $('#dt-search-chips');
        $box.empty();
        pins.forEach((r) => {
            const ad = (r.sase || ('#' + r.id)) + (r.plaka ? ' · ' + r.plaka : '');
            $box.append(`<span class="elastic-chip">${App.esc(ad)}<button type="button" data-pin-sil="${App.esc(r.id)}" aria-label="Kaldır">&times;</button></span>`);
        });
    };
    const menuKapat = () => $('.elastic-menu').attr('hidden', true).empty();
    const menuAc = ($input) => {
        const q = String($input.val() || '').trim();
        const $menu = $input.siblings('.elastic-menu');
        if (!$menu.length || q.length < 2) { menuKapat(); return; }
        $('.elastic-menu').not($menu).attr('hidden', true).empty();
        $.getJSON('/api/araclar', { q }).done((res) => {
            if (String($input.val() || '').trim() !== q) return;
            const rows = res.results || [];
            $menu.empty();
            if (!rows.length) {
                $menu.append('<div class="elastic-empty">Eşleşen araç yok</div>');
            } else {
                rows.forEach((r) => {
                    const $b = $('<button type="button" class="elastic-opt"></button>').text(r.text || r.sase || '');
                    $b.on('mousedown', (e) => {
                        e.preventDefault();
                        pins.set(String(r.id), { id: String(r.id), sase: r.sase || '', plaka: r.plaka || '' });
                        $('#dt-search, .topbar-search input').val('');
                        chipCiz();
                        menuKapat();
                        dt.ajax.reload();
                    });
                    $menu.append($b);
                });
            }
            $menu.removeAttr('hidden');
        });
    };
    const dt = App.table('#liste', {
        url: '/arac_yonetimi/liste', bulk: true, bulkRemember: true, order: [[1, 'desc']],
        extraData() {
            const q = String($('#dt-search').val() || '').trim();
            const pin = [...pins.keys()];
            return !q && pin.length ? { pin } : {};
        },
        onClear() {
            pins.clear();
            chipCiz();
            menuKapat();
            $('.topbar-search input').val('');
        },
        onSelection(rows) {
            window.__seciliAraclar = rows;
            const $box = $('#bulk-secili');
            $box.empty();
            rows.forEach((r) => {
                const ad = (r.sase || ('#' + r.id)) + (r.plaka ? ' · ' + r.plaka : '');
                const cls = r.stokta ? 'bulk-chip' : 'bulk-chip is-out';
                $box.append(`<span class="${cls}">${App.esc(ad + (r.stokta ? '' : ' · stokta değil'))}</span>`);
            });
        },
        columns: [
            { data: 'sase', render: (v, _, r) => {
                const kaynak = r.kayit_kaynagi === 'detayli'
                    ? ' <span class="badge badge-soft-info" title="Detaylı Araç Ekle">Detaylı</span>'
                    : '';
                const alt = r.musteri_musterisi
                    ? `<div class="small text-muted">${App.esc(r.musteri_musterisi)}</div>`
                    : '';
                return `<a href="/arac_yonetimi/duzenle/${r.id}" class="fw-semibold">${App.esc(v)}</a>${kaynak}${alt}`;
            } },
            { data: 'plaka', render: (v) => App.esc(v || '-') },
            { data: 'firma', render: (v) => App.esc(v || '-') },
            { data: 'marka', render: (v) => App.esc(v || '-') },
            { data: 'model', render: (v) => App.esc(v || '-') },
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
                canSil && { url: '#', icon: 'mdi-delete', color: 'danger', title: 'Sil', attrs: 'data-arac-sil="' + id + '" data-sase="' + App.esc(r.sase || '') + '"' },
            ]) },
        ],
    });

    let araTimer;
    $('#dt-search').on('input search', function () {
        const aktif = document.activeElement;
        const input = aktif && (aktif.id === 'dt-search' || aktif.closest('.topbar-search')) ? aktif : this;
        if (input === this) $('.topbar-search input').val(this.value);
        clearTimeout(araTimer);
        araTimer = setTimeout(() => menuAc($(input)), 200);
    });
    $('.topbar-search').on('submit', (e) => { e.preventDefault(); });
    $('.topbar-search input').on('input', function () {
        $('#dt-search').val(this.value).trigger('input');
    });
    $(document).on('click', '[data-pin-sil]', function () {
        pins.delete(String(this.dataset.pinSil));
        chipCiz();
        dt.ajax.reload();
    });
    $(document).on('click', (e) => {
        if (!$(e.target).closest('.elastic-ara, .topbar-search').length) menuKapat();
    });

    $(document).on('click', '[data-arac-sil]', function (e) {
        e.preventDefault();
        const id = this.dataset.aracSil;
        const sase = this.dataset.sase || '';
        Swal.fire({
            icon: 'warning',
            title: 'Araç silinsin mi?',
            html: (sase ? '<b>' + App.esc(sase) + '</b> — ' : '') + 'Hareket ve maliyet kayıtları da silinir.',
            showCancelButton: true, confirmButtonText: 'Sil', cancelButtonText: 'Vazgeç', confirmButtonColor: '#dc2626',
        }).then((r) => {
            if (!r.isConfirmed) return;
            $.post('/arac_yonetimi/sil/' + id, { _csrf: $('meta[name="csrf-token"]').attr('content') })
                .done((res) => { App.toast(res.message || 'Silindi'); $('#liste').DataTable().ajax.reload(null, false); })
                .fail((x) => App.error(x.responseJSON?.message || 'Silinemedi'));
        });
    });

    $('#bulk-sil').on('click', function () {
        const rows = window.__seciliAraclar || [];
        if (!rows.length) return;
        const liste = rows.map((r) => App.esc((r.sase || ('#' + r.id)) + (r.plaka ? ' · ' + r.plaka : ''))).join('<br>');
        Swal.fire({
            icon: 'warning',
            title: rows.length + ' araç silinsin mi?',
            html: liste + '<div class="small text-muted mt-2">Hareket ve maliyet kayıtları da silinir.</div>',
            showCancelButton: true, confirmButtonText: 'Sil', cancelButtonText: 'Vazgeç', confirmButtonColor: '#dc2626',
        }).then((r) => {
            if (!r.isConfirmed) return;
            $.post('/arac_yonetimi/multiple_delete', {
                ids: rows.map((row) => row.id),
                _csrf: $('meta[name="csrf-token"]').attr('content'),
            }).done((res) => {
                App.toast(res.message || 'Silindi');
                $('#bulk-bar [data-bulk-clear]').trigger('click');
                dt.ajax.reload(null, false);
            }).fail((x) => App.error(x.responseJSON?.message || 'Silinemedi'));
        });
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
