<?php
use App\Core\Auth;
use App\Core\Csrf;

$secili = $secili ?? [];
$yapiskan = $yapiskan ?? '';
$seciliFiyat = $seciliFiyat ?? [];
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
                    <p class="small text-muted">Önce şasi veya plaka listesini yapıştırın. Müşteri kayıttan bulunur; fiyat listesi yalnız o firmaya ve otoparka ait tarifelerden gelir.</p>
                    <div class="mb-2">
                        <label class="form-label">Müşteri</label>
                        <div id="maliyet-firma-yazi" class="border rounded px-2 py-2 bg-light small text-muted">Listeyi yapıştırıp firmayı bulun.</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label required" for="maliyet-hizmet">Fiyat</label>
                        <select name="fiyat_anahtar[]" id="maliyet-hizmet" class="form-select select2" multiple data-placeholder="Önce firmayı bulun"></select>
                        <div class="form-text">Birden fazla fiyat seçilebilir. Seçilen fiyat, yalnızca aynı müşteri ve otoparktaki araçlara yazılır.</div>
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
                        <div class="alert alert-info small">Listeden <?= count($secili) ?> araç buraya alındı. Firmayı bulup fiyat seçebilirsiniz.</div>
                    <?php endif; ?>
                    <label class="form-label" for="maliyet-yapiskan">Şasi veya plaka listesi</label>
                    <textarea id="maliyet-yapiskan" name="yapiskan" class="form-control font-monospace" rows="12" placeholder="Tek sütun ya da yan yana iki sütun yapıştırın.&#10;Şase	Plaka&#10;VF1ZUTPNT8GNVDCWJ	06TX584&#10;06 ERHAN 640"><?= e($yapiskan) ?></textarea>
                    <div class="form-text">Tek sütun alt alta yeter. Şasi ve plaka yan yanaysa iki sütunu birlikte kopyalayın. Firma, şasideki kayıttan okunur.</div>
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-outline-primary" id="maliyet-firma"><i class="mdi mdi-account-search-outline me-1"></i>Firmayı bul</button>
                        <button type="button" class="btn btn-outline-secondary" id="maliyet-kontrol"><i class="mdi mdi-clipboard-search-outline me-1"></i>Kontrol et</button>
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
    const seciliFiyat = <?= json_encode(array_values($seciliFiyat), JSON_UNESCAPED_UNICODE) ?>;
    const renk = {
        uygun: 'success', kismi: 'warning', kayitli_degil: 'danger', musteri_yok: 'warning', fiyat_yok: 'warning',
        tekrar: 'secondary', belirsiz: 'warning', uyusmuyor: 'danger', hizmet_yok: 'danger', bulundu: 'info',
        eklenecek: 'success', zaten_var: 'warning', tanimsiz: 'danger', otopark_yok: 'danger'
    };
    const $hizmet = $('#maliyet-hizmet');
    const $firma = $('#maliyet-firma-yazi');
    let firmaHazir = false;

    function fiyatKur(musteriler, secili) {
        if ($hizmet.data('select2')) $hizmet.select2('destroy');
        $hizmet.empty();
        let adet = 0;
        musteriler.forEach((m) => {
            const grup = $('<optgroup>', { label: m.ad + ' · ' + m.adet + ' araç' });
            (m.fiyatlar || []).forEach((f) => {
                adet++;
                grup.append(new Option(f.etiket, f.anahtar, false, secili.includes(f.anahtar)));
            });
            $hizmet.append(grup);
        });
        $hizmet.prop('disabled', adet === 0);
        $hizmet.attr('data-placeholder', adet ? 'Fiyat seçin' : 'Bu firmalar için tanımlı fiyat yok');
        App.initSelect2($hizmet.parent());
    }

    function firmaYaz(musteriler) {
        if (!musteriler.length) {
            $firma.text('Listede müşterisi bulunan araç yok.');
            return;
        }
        $firma.removeClass('text-muted').text(musteriler.map((m) => m.ad + ' (' + m.adet + ')').join(', '));
    }

    function tablo(res, ozet) {
        let html = `<div class="alert alert-light border small mb-2">${App.esc(ozet)}</div>`;
        html += '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Şasi</th><th>Plaka</th><th>Durum</th><th>Kayıt</th><th>Fiyat</th></tr></thead><tbody>';
        (res.satirlar || []).forEach((s) => {
            const kayit = s.sase
                ? App.esc([s.sase, s.plaka, s.firma, s.otopark].filter(Boolean).join(' · ')) + (s.stokta ? '' : ' <span class="text-muted">(stok dışı)</span>')
                : '—';
            const hizmet = (s.hizmetler || []).map((h) => App.badge(h.ad + ': ' + h.mesaj + fiyatYaz(h), renk[h.durum] || 'secondary')).join(' ') || '—';
            html += `<tr><td class="font-monospace">${App.esc(s.girilen_sase || '—')}</td><td class="font-monospace">${App.esc(s.girilen_plaka || '—')}</td><td>${App.badge(s.mesaj, renk[s.durum] || 'secondary')}</td><td>${kayit}</td><td>${hizmet}</td></tr>`;
        });
        html += '</tbody></table></div>';
        $('#maliyet-onizleme').html(html);
    }

    function fiyatYaz(h) {
        if (h.durum !== 'eklenecek') return '';
        if (admin && h.musteri_fiyat != null) {
            return ' · bayi ' + App.money(h.bayi_fiyat) + ' · müşteri ' + App.money(h.musteri_fiyat);
        }
        return ' · anlaşmalı fiyat';
    }

    function firmaBul(secili) {
        if (!$.trim($('#maliyet-yapiskan').val())) {
            App.error('Şasi veya plaka yapıştırılmalıdır.');
            return;
        }
        const $btn = $('#maliyet-firma').prop('disabled', true);
        $.post('/arac_yonetimi/toplu_ek_maliyet_girisi/firmalar', {
            yapiskan: $('#maliyet-yapiskan').val(),
            _csrf: $('meta[name="csrf-token"]').attr('content'),
        }).done((res) => {
            const musteriler = res.musteriler || [];
            firmaHazir = true;
            firmaYaz(musteriler);
            fiyatKur(musteriler, secili || []);
            const adlar = musteriler.map((m) => m.ad).join(', ') || 'yok';
            tablo(res, `Firma: ${adlar}. ${res.bulundu || 0} kayıtlı araç, ${res.kayitli_degil || 0} kayıtlı değil. Fiyatı seçip kontrol edin.`);
        }).fail((x) => App.error(x.responseJSON?.message || 'Firma bulunamadı'))
            .always(() => $btn.prop('disabled', false));
    }

    $('#maliyet-yapiskan').on('input', function () {
        if (!firmaHazir) return;
        firmaHazir = false;
        fiyatKur([], []);
        $firma.addClass('text-muted').text('Liste değişti. Firmayı yeniden bulun.');
        $('#maliyet-onizleme').empty();
    });

    $('#maliyet-firma').on('click', () => firmaBul([]));
    if ($.trim($('#maliyet-yapiskan').val())) firmaBul(seciliFiyat);

    function eksik() {
        if (!$.trim($('#maliyet-yapiskan').val())) return 'Şasi veya plaka yapıştırılmalıdır.';
        if (!firmaHazir) return 'Önce firmayı bulun.';
        if (!$hizmet.val() || !$hizmet.val().length) return 'En az bir fiyat seçilmelidir.';
        return '';
    }

    $('#maliyet-kontrol').on('click', function () {
        const mesaj = eksik();
        if (mesaj) { App.error(mesaj); return; }
        const $btn = $(this).prop('disabled', true);
        $.post('/arac_yonetimi/toplu_ek_maliyet_girisi/kontrol', {
            fiyat_anahtar: $hizmet.val(),
            yapiskan: $('#maliyet-yapiskan').val(),
            _csrf: $('meta[name="csrf-token"]').attr('content'),
        }).done((res) => {
            tablo(res, `${res.uygun || 0} eklenecek, ${res.kismi || 0} kısmen, ${res.fiyat_yok || 0} fiyatı seçilmemiş, ${res.kayitli_degil || 0} kayıtlı değil. Maliyet yalnızca uygun satırlara yazılır.`);
        }).fail((x) => App.error(x.responseJSON?.message || 'Kontrol edilemedi'))
            .always(() => $btn.prop('disabled', false));
    });

    $('#maliyet-kaydet').on('click', function () {
        const mesaj = eksik();
        if (mesaj) { App.error(mesaj); return; }
        Swal.fire({
            icon: 'warning',
            title: 'Seçilen fiyatlar uygun araçlara eklensin mi?',
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
