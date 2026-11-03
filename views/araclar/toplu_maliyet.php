<?php
use App\Core\Auth;
use App\Core\Csrf;

$secili = $secili ?? [];
$yapiskan = $yapiskan ?? '';
$hizmetIds = $hizmetIds ?? [];
$eski = $_SESSION['_old'] ?? [];
$admin = Auth::isAdmin();
?>
<form method="post" action="/arac_yonetimi/toplu_ek_maliyet_girisi" id="form-toplu-maliyet">
    <?= Csrf::field() ?>
    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h5>Maliyet bilgileri</h5></div>
                <div class="card-body">
                    <p class="small text-muted">Müşteri şasi veya plakadan bulunur. Seçilen hizmet, o aracın firmasına ve otoparkına tanımlı fiyatla yazılır.</p>
                    <div class="mb-2">
                        <label class="form-label required" for="maliyet-hizmet">Hizmet</label>
                        <select name="maliyet_tipi_id[]" id="maliyet-hizmet" class="form-select select2" multiple data-placeholder="Hizmet seçin"></select>
                        <div class="form-text">Birden fazla hizmet seçilebilir. Fiyat, bulunan aracın müşterisine göre uygulanır.</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="maliyet-tarih">İşlem tarihi</label>
                        <input type="date" name="islem_tarihi" id="maliyet-tarih" class="form-control" value="<?= e((string) ($eski['islem_tarihi'] ?? date('Y-m-d'))) ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Açıklama</label>
                        <input type="text" name="aciklama" class="form-control" value="<?= e((string) ($eski['aciklama'] ?? '')) ?>">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Fatura no</label>
                            <input type="text" name="fatura_no" class="form-control" value="<?= e((string) ($eski['fatura_no'] ?? '')) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Fatura tarihi</label>
                            <input type="date" name="fatura_tarihi" class="form-control" value="<?= e((string) ($eski['fatura_tarihi'] ?? '')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">İrsaliye</label>
                            <input type="text" name="irsaliye" class="form-control" value="<?= e((string) ($eski['irsaliye'] ?? '')) ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5>Toplu yapıştır</h5></div>
                <div class="card-body">
                    <?php if ($secili && ($eski['yapiskan'] ?? '') === ''): ?>
                        <div class="alert alert-info small">Listeden <?= count($secili) ?> araç buraya alındı. Kontrol edip maliyeti ekleyebilirsiniz.</div>
                    <?php endif; ?>
                    <label class="form-label" for="maliyet-yapiskan">Şasi veya plaka listesi</label>
                    <textarea id="maliyet-yapiskan" name="yapiskan" class="form-control font-monospace" rows="12" placeholder="Tek sütun ya da yan yana iki sütun yapıştırın.&#10;Şase	Plaka&#10;VF1ZUTPNT8GNVDCWJ	06TX584&#10;06 ERHAN 640"><?= e($yapiskan) ?></textarea>
                    <div class="form-text">Tek sütun alt alta yeter. Şasi ve plaka yan yanaysa iki sütunu birlikte kopyalayın. Kayıtlı olmayan ve fiyatı olmayan satırlar eklenmez. Firma, kayıttaki müşteridir.</div>
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-outline-primary" id="maliyet-kontrol"><i class="mdi mdi-clipboard-search-outline me-1"></i>Kontrol et</button>
                    </div>
                    <div id="maliyet-onizleme" class="mt-3"></div>
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-lg w-100" id="maliyet-kaydet"><i class="mdi mdi-cash-plus me-1"></i> Maliyeti ekle</button>
        </div>
    </div>
</form>
<?php unset($_SESSION['_old']); ?>

<?php ob_start(); ?>
<script>
$(function () {
    const admin = document.body.dataset.admin === '1';
    const seciliHizmet = <?= json_encode(array_values(array_map('intval', $hizmetIds)), JSON_UNESCAPED_UNICODE) ?>;
    const renk = {
        uygun: 'success', kismi: 'warning', kayitli_degil: 'danger', musteri_yok: 'warning',
        tekrar: 'secondary', belirsiz: 'warning', uyusmuyor: 'danger', hizmet_yok: 'danger',
        eklenecek: 'success', zaten_var: 'warning', tanimsiz: 'danger', otopark_yok: 'danger'
    };
    const $hizmet = $('#maliyet-hizmet');

    function hizmetKur(rows, secili) {
        if ($hizmet.data('select2')) $hizmet.select2('destroy');
        $hizmet.empty();
        rows.forEach((h) => {
            const opt = new Option(h.ad, String(h.id), false, secili.map(String).includes(String(h.id)));
            $hizmet.append(opt);
        });
        $hizmet.attr('data-placeholder', rows.length ? 'Hizmet seçin' : 'Fiyatı tanımlı hizmet yok');
        App.initSelect2($hizmet.parent());
    }

    $.get('/arac_yonetimi/toplu_ek_maliyet_girisi/hizmetler')
        .done((res) => hizmetKur(res.hizmetler || [], seciliHizmet))
        .fail((x) => App.error(x.responseJSON?.message || 'Hizmetler alınamadı'));

    function eksik() {
        if (!$hizmet.val() || !$hizmet.val().length) return 'En az bir hizmet seçilmelidir.';
        if (!$.trim($('#maliyet-yapiskan').val())) return 'Şasi veya plaka yapıştırılmalıdır.';
        return '';
    }

    function fiyatYaz(h) {
        if (h.durum !== 'eklenecek') return '';
        if (admin && h.musteri_fiyat != null) {
            return ' · bayi ' + App.money(h.bayi_fiyat) + ' · müşteri ' + App.money(h.musteri_fiyat);
        }
        return ' · anlaşmalı fiyat';
    }

    $('#maliyet-kontrol').on('click', function () {
        const mesaj = eksik();
        if (mesaj) { App.error(mesaj); return; }
        const $btn = $(this).prop('disabled', true);
        $.post('/arac_yonetimi/toplu_ek_maliyet_girisi/kontrol', {
            maliyet_tipi_id: $hizmet.val(),
            yapiskan: $('#maliyet-yapiskan').val(),
            _csrf: $('meta[name="csrf-token"]').attr('content'),
        }).done((res) => {
            const ozet = `${res.uygun || 0} eklenecek, ${res.kismi || 0} kısmen, ${res.musteri_yok || 0} müşterisiz, ${res.kayitli_degil || 0} kayıtlı değil`;
            let html = `<div class="alert alert-light border small mb-2">${App.esc(ozet)}. Maliyet yalnızca uygun satırlara yazılır.</div>`;
            html += '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Şasi</th><th>Plaka</th><th>Durum</th><th>Kayıt</th><th>Hizmet</th></tr></thead><tbody>';
            (res.satirlar || []).forEach((s) => {
                const kayit = s.sase
                    ? App.esc([s.sase, s.plaka, s.firma, s.otopark].filter(Boolean).join(' · ')) + (s.stokta ? '' : ' <span class="text-muted">(stok dışı)</span>')
                    : '—';
                const hizmet = (s.hizmetler || []).map((h) => App.badge(h.ad + ': ' + h.mesaj + fiyatYaz(h), renk[h.durum] || 'secondary')).join(' ') || '—';
                html += `<tr><td class="font-monospace">${App.esc(s.girilen_sase || '—')}</td><td class="font-monospace">${App.esc(s.girilen_plaka || '—')}</td><td>${App.badge(s.mesaj, renk[s.durum] || 'secondary')}</td><td>${kayit}</td><td>${hizmet}</td></tr>`;
            });
            html += '</tbody></table></div>';
            $('#maliyet-onizleme').html(html);
        }).fail((x) => App.error(x.responseJSON?.message || 'Kontrol edilemedi'))
            .always(() => $btn.prop('disabled', false));
    });

    $('#maliyet-kaydet').on('click', function () {
        const mesaj = eksik();
        if (mesaj) { App.error(mesaj); return; }
        Swal.fire({
            icon: 'warning',
            title: 'Uygun araçlara seçilen hizmetler eklensin mi?',
            showCancelButton: true,
            confirmButtonText: 'Evet',
            cancelButtonText: 'Vazgeç',
            confirmButtonColor: '#4f46e5',
        }).then((r) => {
            if (r.isConfirmed) document.getElementById('form-toplu-maliyet').submit();
        });
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
