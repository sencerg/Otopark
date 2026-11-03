<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Tanim;

$headerActions = '<a href="/arac_yonetimi/sablon/stok" class="btn btn-success"><i class="mdi mdi-microsoft-excel me-1"></i>Örnek Şablon İndir</a>';
$markaOptions = Tanim::options(Tanim::liste('markalar'));
?>
<form method="post" action="/arac_yonetimi/excel_toplu_giris" enctype="multipart/form-data" id="form-toplu-stok">
    <?= Csrf::field() ?>
    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h5>Ortak Bilgiler</h5></div>
                <div class="card-body">
                    <p class="small text-muted">Aşağıdaki bilgiler aktarılan tüm araçlara uygulanır.</p>
                    <div class="mb-2"><label class="form-label required">Müşteri Adı</label><select name="musteri_id" class="form-select select2" required><?= Tanim::options(Tanim::liste('musteriler'), $_SESSION['_old']['musteri_id'] ?? null) ?></select></div>
                    <div class="mb-2"><label class="form-label required">Lokasyon</label><select name="bayi_id" class="form-select" required><?= Tanim::options(Tanim::liste('bayiler'), Auth::bayiId(), Auth::bayiId() ? null : 'Seçiniz') ?></select></div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label">Lokasyon Türü</label><select name="lokasyon_turu" class="form-select"><?= Tanim::options(Tanim::LOKASYON_TURU) ?></select></div>
                        <div class="col-6"><label class="form-label">Araç Tipi</label><select name="arac_tipi" class="form-select"><?= Tanim::options(Tanim::liste('arac_tipleri')) ?></select></div>
                        <div class="col-6"><label class="form-label">Araç Durumu</label><select name="arac_durumu" class="form-select"><?= Tanim::options(Tanim::ARAC_DURUMU) ?></select></div>
                        <div class="col-6"><label class="form-label">Hareket Nedeni</label><select name="hareket_nedeni" class="form-select"><?= Tanim::options(Tanim::liste('hareket_nedenleri')) ?></select></div>
                    </div>
                    <label class="form-label">Giriş Tarihi</label>
                    <div class="input-group mb-2"><input type="date" name="hareket_tarihi" class="form-control" data-default-today><input type="time" name="hareket_saati" class="form-control" value="<?= date('H:i') ?>"></div>
                    <div class="mb-2"><label class="form-label">Teslim Eden</label><input type="text" name="teslim_eden" class="form-control"></div>
                    <div class="mb-2"><label class="form-label">Personel</label><select name="teslim_alan_personel" class="form-select select2"><?= Tanim::options(Tanim::liste('personeller')) ?></select></div>
                    <label class="form-label">Araç Envanteri</label>
                    <div class="check-grid">
                        <?php foreach (Tanim::liste('envanterler') as $id => $ad): ?>
                            <label class="form-check"><input class="form-check-input" type="checkbox" name="arac_envanterleri[]" value="<?= $id ?>"> <span class="form-check-label"><?= e($ad) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5>Excel ile Yükle</h5></div>
                <div class="card-body">
                    <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls,.csv,.txt">
                    <div class="form-text">
                        “Örnek Şablon İndir” ile XLSX alın. <b>MARKA</b>, <b>Model</b> ve <b>Seri</b> adları şablondaki “Araç Markaları”, “Araç Modelleri” ve “Araç Serileri” sayfalarındaki kayıtlarla aynı olmalıdır. Seri boş bırakılabilir.
                        <b>Araç Tipi</b> ve <b>Araç Durumu</b> zorunlu değildir; boş bırakılabilir.
                        Dosya seçilirse aşağıdaki yapıştırma alanı ve manuel satırlar dikkate alınmaz.
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5>Toplu yapıştır</h5></div>
                <div class="card-body">
                    <p class="small text-muted">Önce marka ve modeli seçin. Liste, yapıştırılan şasi ve plakalardan bu seçimle oluşur. Satırda marka, model ve seriyi değiştirebilirsiniz.</p>
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label required" for="giris-marka">Marka</label>
                            <select id="giris-marka" class="form-select select2" data-placeholder="Marka seçin"><?= $markaOptions ?></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required" for="giris-model">Model</label>
                            <select id="giris-model" class="form-select select2" data-placeholder="Önce marka"><option value="">Önce marka</option></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="giris-seri">Seri</label>
                            <select id="giris-seri" class="form-select select2" data-placeholder="Önce model"><option value="">Önce model</option></select>
                        </div>
                    </div>
                    <label class="form-label" for="giris-yapiskan">Şasi veya plaka listesi</label>
                    <textarea id="giris-yapiskan" class="form-control font-monospace" rows="10" placeholder="Tek sütun ya da yan yana iki sütun yapıştırın.&#10;Şase	Plaka&#10;VF1ZUTPNT8GNVDCWJ	06TX584&#10;06 ERHAN 640"></textarea>
                    <div class="form-text">Tek sütun alt alta yeter. Şasi ve plaka yan yanaysa iki sütunu birlikte kopyalayın. Stokta olan, şasisi geçersiz ve plakası dolu satırlar listeye yazılmaz.</div>
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-outline-primary" id="giris-listele"><i class="mdi mdi-clipboard-list-outline me-1"></i>Listeye al</button>
                    </div>
                    <div id="giris-onizleme" class="mt-3"></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5>Manuel Araç Listesi</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="#stok-satir-tpl" data-target="#stok-satirlari"><i class="mdi mdi-plus"></i> Satır Ekle</button></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Şasi No</th><th>Plaka</th><th style="min-width:150px">Marka</th><th style="min-width:150px">Model</th><th style="min-width:150px">Seri</th><th>Lokasyon Detay</th><th>Sevkiyat Kodu</th><th>İrsaliye Kodu</th><th></th></tr></thead>
                            <tbody id="stok-satirlari"></tbody>
                        </table>
                    </div>
                    <template id="stok-satir-tpl">
                        <tr class="row-item">
                            <td><input type="text" name="satir[sase][]" class="form-control form-control-sm text-uppercase"></td>
                            <td><input type="text" name="satir[plaka][]" class="form-control form-control-sm text-uppercase"></td>
                            <td><select name="satir[marka_id][]" class="form-select form-select-sm satir-marka"><?= $markaOptions ?></select></td>
                            <td><select name="satir[model_id][]" class="form-select form-select-sm satir-model"><option value="">Önce Marka</option></select></td>
                            <td><select name="satir[seri_id][]" class="form-select form-select-sm satir-seri"><option value="">Önce Model</option></select></td>
                            <td><input type="text" name="satir[lokasyon_detay][]" class="form-control form-control-sm"></td>
                            <td><input type="text" name="satir[sevkiyat_kodu][]" class="form-control form-control-sm"></td>
                            <td><input type="text" name="satir[irsaliye_kodu][]" class="form-control form-control-sm"></td>
                            <td><input type="hidden" name="satir[proje_adi][]"><button type="button" class="btn btn-sm btn-light text-danger" data-remove-row><i class="mdi mdi-delete-outline"></i></button></td>
                        </tr>
                    </template>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100"><i class="mdi mdi-database-import me-1"></i> Stoğa Aktar</button>
        </div>
    </div>
</form>

<?php ob_start(); ?>
<script>
$(function () {
    const $tbody = $('#stok-satirlari');
    const tpl = $('#stok-satir-tpl').html();
    const $marka = $('#giris-marka');
    const $model = $('#giris-model');
    const $seri = $('#giris-seri');
    const modelOnbellek = {};
    const seriOnbellek = {};

    const secenek = (d, bos) => '<option value="">' + bos + '</option>' + Object.entries(d || {})
        .sort((a, b) => a[1].localeCompare(b[1], 'tr'))
        .map(([id, ad]) => `<option value="${id}">${App.esc(ad)}</option>`).join('');

    function selectDoldur($el, html, deger) {
        if ($el.data('select2')) $el.select2('destroy');
        $el.html(html);
        if (deger) $el.val(String(deger));
        App.initSelect2($el.parent());
    }

    function modeller(markaId) {
        if (!modelOnbellek[markaId]) modelOnbellek[markaId] = $.getJSON('/api/modeller', { marka_id: markaId });
        return modelOnbellek[markaId];
    }

    function seriler(modelId) {
        if (!seriOnbellek[modelId]) seriOnbellek[modelId] = $.getJSON('/api/seriler', { model_id: modelId });
        return seriOnbellek[modelId];
    }

    $marka.on('change', function () {
        selectDoldur($seri, '<option value="">Önce model</option>');
        if (!this.value) {
            selectDoldur($model, '<option value="">Önce marka</option>');
            return;
        }
        modeller(this.value).done((d) => selectDoldur($model, secenek(d, 'Model seçin')));
    });
    $model.on('change', function () {
        if (!this.value) {
            selectDoldur($seri, '<option value="">Önce model</option>');
            return;
        }
        seriler(this.value).done((d) => selectDoldur($seri, secenek(d, 'Seri (isteğe bağlı)')));
    });

    $tbody.on('change', '.satir-marka', function () {
        const $tr = $(this).closest('tr');
        const $satirModel = $tr.find('.satir-model');
        $tr.find('.satir-seri').html('<option value="">Önce Model</option>');
        if (!this.value) { $satirModel.html('<option value="">Önce Marka</option>'); return; }
        $satirModel.html('<option value="">Yükleniyor...</option>');
        modeller(this.value).done((d) => $satirModel.html(secenek(d, 'Seçiniz')));
    });
    $tbody.on('change', '.satir-model', function () {
        const $satirSeri = $(this).closest('tr').find('.satir-seri');
        if (!this.value) { $satirSeri.html('<option value="">Önce Model</option>'); return; }
        $satirSeri.html('<option value="">Yükleniyor...</option>');
        seriler(this.value).done((d) => $satirSeri.html(secenek(d, 'Seçiniz')));
    });

    function satirYaz(s, markaId, modelId, seriId) {
        const $tr = $(tpl);
        $tr.find('[name="satir[sase][]"]').val(s.sase);
        $tr.find('[name="satir[plaka][]"]').val(s.plaka || '');
        $tr.find('.satir-marka').val(String(markaId));
        $tbody.append($tr);
        const $satirModel = $tr.find('.satir-model');
        const $satirSeri = $tr.find('.satir-seri');
        modeller(markaId).done((d) => {
            $satirModel.html(secenek(d, 'Seçiniz')).val(String(modelId));
            if (!modelId) return;
            seriler(modelId).done((sd) => $satirSeri.html(secenek(sd, 'Seçiniz')).val(seriId ? String(seriId) : ''));
        });
    }

    $('#giris-listele').on('click', function () {
        const markaId = $marka.val();
        const modelId = $model.val();
        const seriId = $seri.val();
        if (!markaId) { App.error('Marka seçilmelidir.'); return; }
        if (!modelId) { App.error('Model seçilmelidir.'); return; }
        if (!$.trim($('#giris-yapiskan').val())) { App.error('Şasi veya plaka yapıştırılmalıdır.'); return; }
        const $btn = $(this).prop('disabled', true);
        $.post('/arac_yonetimi/toplu_stok_girisi/kontrol', {
            yapiskan: $('#giris-yapiskan').val(),
            _csrf: $('meta[name="csrf-token"]').attr('content'),
        }).done((res) => {
            const varOlan = {};
            $tbody.find('[name="satir[sase][]"]').each(function () {
                const deger = $.trim(this.value).toUpperCase();
                if (deger) varOlan[deger] = true;
            });
            let eklenen = 0;
            (res.satirlar || []).forEach((s) => {
                if (s.durum !== 'uygun' && s.durum !== 'stok_disi') return;
                if (varOlan[s.sase]) return;
                varOlan[s.sase] = true;
                satirYaz(s, markaId, modelId, seriId);
                eklenen++;
            });
            const renk = { uygun: 'success', stok_disi: 'info', stokta: 'warning', tekrar: 'secondary', gecersiz: 'danger', plaka_dolu: 'danger' };
            const ozet = `${eklenen} satır listeye alındı. ${res.stokta || 0} zaten stokta, ${res.gecersiz || 0} geçersiz, ${res.tekrar || 0} tekrar, ${res.plaka_dolu || 0} plakası dolu.`;
            let html = `<div class="alert alert-light border small mb-2">${App.esc(ozet)} Marka ve modeli satırdan değiştirip stoğa aktarın.</div>`;
            html += '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Şasi</th><th>Plaka</th><th>Durum</th></tr></thead><tbody>';
            (res.satirlar || []).forEach((s) => {
                html += `<tr><td class="font-monospace">${App.esc(s.sase || '—')}</td><td class="font-monospace">${App.esc(s.plaka || '—')}</td><td>${App.badge(s.mesaj, renk[s.durum] || 'secondary')}</td></tr>`;
            });
            html += '</tbody></table></div>';
            $('#giris-onizleme').html(html);
            if (eklenen) $('#giris-yapiskan').val('');
        }).fail((x) => App.error(x.responseJSON?.message || 'Liste hazırlanamadı'))
            .always(() => $btn.prop('disabled', false));
    });

    $('#form-toplu-stok').on('submit', function (e) {
        if ($.trim($('#giris-yapiskan').val()) && !$('input[name="import_file"]').val()) {
            e.preventDefault();
            App.error('Yapıştırılan liste henüz tabloya alınmadı. Önce listeye alın.');
        }
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
