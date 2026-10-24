<?php
use App\Core\Auth;
use App\Core\Tanim;

$fiyat = Auth::fiyatGorebilir();
$maske = Auth::fiyatMaskeli();
?>
<div class="card">
    <div class="card-header">
        <h5>Ek Hizmet Raporu</h5>
        <div class="dt-toolbar">
            <?php if ($fiyat || $maske): ?><div class="total-box">Toplam: <b id="toplam-tutar"><?= $maske ? '—' : '0,00 ₺' ?></b></div><?php endif; ?>
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
            <div><label class="form-label">Maliyet Tipi</label><select data-filter="maliyet_tipi_id" class="form-select select2"><?= Tanim::options(Tanim::liste('maliyet_tipleri'), null, 'Tüm Hizmetler') ?></select></div>
            <div><label class="form-label">Marka</label><select name="marka_id" data-filter="marka_id" class="form-select select2"><?= Tanim::options(Tanim::liste('markalar'), null, 'Tüm Markalar') ?></select></div>
            <div><label class="form-label">Kayıt Türü</label><select data-filter="cikis_sonrasi" class="form-select"><option value="">Tüm Kayıtlar</option><option value="1">Çıkış sonrası eklenenler</option></select></div>
        </div>

        <div class="row g-3 my-2" id="ozet"></div>

        <div class="d-flex gap-2 mt-2 mb-2">
            <input type="search" id="dt-search" class="form-control" placeholder="Şasi, plaka, hizmet, açıklama, fatura no ile arayın...">
            <button id="dt-clear" class="btn btn-light text-nowrap"><i class="mdi mdi-filter-remove-outline"></i> Temizle</button>
        </div>
        <div class="table-responsive">
            <table id="liste" class="table table-hover w-100">
                <thead><tr><th>İşlem Tarihi</th><th>Şase</th><th>Plaka</th><th>Firma</th><th>Marka / Seri</th><th>Lokasyon</th><th>Hizmet</th><th>Açıklama</th><th>Fatura No</th><?php if ($fiyat || $maske): ?><th class="text-end">Tutar</th><?php endif; ?></tr></thead>
            </table>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(function () {
    const fiyat = document.body.dataset.fiyat === '1';
    const maske = document.body.dataset.fiyatMaske === '1';
    const ozet = (rows) => {
        if (!fiyat) { $('#ozet').empty(); return; }
        $('#ozet').html((rows || []).map((r) => `
        <div class="col-md-4 col-xl-2"><div class="mini-stat">
            <span class="text-truncate" title="${App.esc(r.ad)}">${App.esc(r.ad)}</span>
            <strong>${App.money(r.tutar)}</strong>
            <span>${App.num(r.adet)} işlem</span>
        </div></div>`).join(''));
    };
    const cols = [
        { data: 'islem_tarihi' },
        { data: 'sase', render: (v, _, r) => `<a href="/arac_yonetimi/duzenle/${r.arac_id}" class="fw-semibold">${App.esc(v)}</a>` },
        { data: 'plaka', render: (v) => App.esc(v || '-') },
        { data: 'firma', render: (v) => App.esc(v || '-') },
        { data: 'marka', render: (v, _, r) => App.esc([v, r.seri].filter(Boolean).join(' ') || '-') },
        { data: 'lokasyon', render: (v) => App.esc(v || '-') },
        { data: 'maliyet_tipi', render: (v, _, r) => App.esc(v) + (r.cikis_sonrasi ? '<div><span class="badge bg-warning text-dark">Çıkış sonrası eklendi</span></div>' : '') },
        { data: 'aciklama', render: (v, _, r) => (r.cikis_sonrasi && v ? '<i class="mdi mdi-note-text-outline text-warning me-1"></i>' : '') + App.esc(v || '-') },
        { data: 'fatura_no', render: (v) => App.esc(v || '-') },
    ];
    if (fiyat) {
        cols.push({ data: 'tutar', className: 'text-end fw-semibold text-nowrap', render: (v) => App.money(v)
            + (Number(v) ? '' : '<div><span class="badge bg-danger">Fiyat belirlenmedi</span></div>') });
    } else if (maske) {
        cols.push({
            data: 'tutar_etiket',
            className: 'text-end fw-semibold text-nowrap',
            render: (v, _, r) => App.bayiTutarHtml(r.bayi_fiyat_degistirebilir, r.tutar),
        });
    }
    App.table('#liste', {
        url: '/ek_hizmet_raporu/liste', order: [[0, 'desc']],
        onData: (json) => {
            if (maske) $('#toplam-tutar').text(json.toplam_etiket || '—');
            else if (fiyat) $('#toplam-tutar').text(App.money(json.toplam));
            ozet(json.ozet);
        },
        columns: cols,
        createdRow: (row, r) => { if (r.cikis_sonrasi) row.classList.add('table-warning'); },
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
