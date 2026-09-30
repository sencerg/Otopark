<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Tanim;

$headerActions = '<a href="/arac_yonetimi/sablon/stok" class="btn btn-success"><i class="mdi mdi-microsoft-excel me-1"></i>Örnek Şablon İndir</a>';
$markaOptions = Tanim::options(Tanim::liste('markalar'));
?>
<form method="post" action="/arac_yonetimi/excel_toplu_giris" enctype="multipart/form-data">
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
                    <div class="mb-2"><label class="form-label">Teslim Alan Personel</label><select name="teslim_alan_personel" class="form-select select2"><?= Tanim::options(Tanim::liste('personeller')) ?></select></div>
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
                <div class="card-header"><h5>Excel (CSV) ile Yükle</h5></div>
                <div class="card-body">
                    <input type="file" name="import_file" class="form-control" accept=".csv,.txt">
                    <div class="form-text">Sütunlar: <code>sase; plaka; marka; seri; lokasyon_detay; sevkiyat_kodu; irsaliye_kodu; proje_adi</code>. Dosya seçilirse aşağıdaki manuel satırlar dikkate alınmaz.</div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5>Manuel Araç Listesi</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="#stok-satir-tpl" data-target="#stok-satirlari"><i class="mdi mdi-plus"></i> Satır Ekle</button></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Şasi No</th><th>Plaka</th><th style="min-width:150px">Marka</th><th style="min-width:150px">Seri</th><th>Lokasyon Detay</th><th>Sevkiyat Kodu</th><th>İrsaliye Kodu</th><th></th></tr></thead>
                            <tbody id="stok-satirlari"></tbody>
                        </table>
                    </div>
                    <template id="stok-satir-tpl">
                        <tr class="row-item">
                            <td><input type="text" name="satir[sase][]" class="form-control form-control-sm text-uppercase"></td>
                            <td><input type="text" name="satir[plaka][]" class="form-control form-control-sm text-uppercase"></td>
                            <td><select name="satir[marka_id][]" class="form-select form-select-sm satir-marka"><?= $markaOptions ?></select></td>
                            <td><select name="satir[seri_id][]" class="form-select form-select-sm satir-seri"><option value="">Önce Marka</option></select></td>
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
    for (let i = 0; i < 3; i++) $tbody.append(tpl);

    $tbody.on('change', '.satir-marka', function () {
        const $seri = $(this).closest('tr').find('.satir-seri').html('<option value="">Yükleniyor...</option>');
        if (!this.value) { $seri.html('<option value="">Önce Marka</option>'); return; }
        $.getJSON('/api/seriler', { marka_id: this.value }, (d) => {
            $seri.html('<option value="">Seçiniz</option>' + Object.entries(d)
                .sort((a, b) => a[1].localeCompare(b[1], 'tr'))
                .map(([id, ad]) => `<option value="${id}">${App.esc(ad)}</option>`).join(''));
        });
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
