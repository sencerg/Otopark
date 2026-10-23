<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Tanim;

$headerActions = '<a href="/arac_yonetimi/sablon/cikis" class="btn btn-success"><i class="mdi mdi-microsoft-excel me-1"></i>Örnek Şablon İndir</a>';
?>
<form method="post" action="/arac_yonetimi/excel_toplu_cikis" enctype="multipart/form-data" data-confirm="Listedeki stoktaki araçlar stoktan çıkarılsın mı?">
    <?= Csrf::field() ?>
    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h5>Ortak Çıkış Bilgileri</h5></div>
                <div class="card-body">
                    <p class="small text-muted">Bu alanlar aktarılan tüm araçlara uygulanır. Excel satırında dolu alan varsa satır değeri önceliklidir.</p>
                    <label class="form-label required">Çıkış Tarihi</label>
                    <div class="input-group mb-2">
                        <input type="date" name="hareket_tarihi" class="form-control" data-default-today required>
                        <input type="time" name="hareket_saati" class="form-control" value="<?= date('H:i') ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label required">Hareket Nedeni</label>
                        <select name="hareket_nedeni" class="form-select" required><?= Tanim::options(Tanim::liste('hareket_nedenleri'), $_SESSION['_old']['hareket_nedeni'] ?? null) ?></select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Personel</label>
                        <select name="teslim_alan_personel" class="form-select select2"><?= Tanim::options(Tanim::liste('personeller'), $_SESSION['_old']['teslim_alan_personel'] ?? null) ?></select>
                    </div>
                    <div class="mb-2"><label class="form-label">Teslim Alan</label><input type="text" name="teslim_alan" class="form-control" value="<?= old('teslim_alan') ?>"></div>
                    <div class="mb-2"><label class="form-label">Teslim Alan Telefon</label><input type="text" name="teslim_alan_telefon" class="form-control" value="<?= old('teslim_alan_telefon') ?>"></div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label">Sevkiyat Kodu</label><input type="text" name="sevkiyat_kodu" class="form-control" value="<?= old('sevkiyat_kodu') ?>"></div>
                        <div class="col-6"><label class="form-label">İrsaliye Kodu</label><input type="text" name="irsaliye_kodu" class="form-control" value="<?= old('irsaliye_kodu') ?>"></div>
                    </div>
                    <div class="mb-2"><label class="form-label">Şoför Adı Soyadı</label><input type="text" name="sofor_adi_soyadi" class="form-control" value="<?= old('sofor_adi_soyadi') ?>"></div>
                    <div class="mb-2"><label class="form-label">Şoför Telefon</label><input type="text" name="sofor_telefon" class="form-control" value="<?= old('sofor_telefon') ?>"></div>
                    <div class="mb-2"><label class="form-label">Çekici Plakası</label><input type="text" name="cekici_plakasi" class="form-control text-uppercase" value="<?= old('cekici_plakasi') ?>"></div>
                    <div class="mb-0"><label class="form-label">Açıklama</label><textarea name="aciklama" class="form-control" rows="2"><?= old('aciklama') ?></textarea></div>
                    <?php if (Auth::bayiId()): ?>
                        <div class="form-text mt-2">Çıkışlar yalnızca kendi otoparkınızdaki stoktaki araçlar için yapılır.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5>Excel ile Yükle</h5></div>
                <div class="card-body">
                    <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls,.csv,.txt">
                    <div class="form-text">
                        Şablon: <b>Plaka</b> (opsiyonel) + <b>Şase Numarası</b>. Plaka boş olabilir; yalnızca şasi yapıştırmanız yeterli.
                        Plaka sütununa şasi yazılmışsa da kabul edilir. Araç stokta ve erişiminiz olan otoparkta olmalıdır.
                        Dosya seçilirse aşağıdaki manuel satırlar dikkate alınmaz.
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h5>Manuel Araç Listesi</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="#cikis-satir-tpl" data-target="#cikis-satirlari"><i class="mdi mdi-plus"></i> Satır Ekle</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                            <tr>
                                <th>Plaka <span class="text-muted fw-normal">(opsiyonel)</span></th>
                                <th>Şase Numarası</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody id="cikis-satirlari"></tbody>
                        </table>
                    </div>
                    <template id="cikis-satir-tpl">
                        <tr class="row-item">
                            <td><input type="text" name="satir[plaka][]" class="form-control form-control-sm text-uppercase" placeholder="Boş bırakılabilir"></td>
                            <td><input type="text" name="satir[sase][]" class="form-control form-control-sm text-uppercase" placeholder="Şasi yapıştırın"></td>
                            <td><button type="button" class="btn btn-sm btn-light text-danger" data-remove-row><i class="mdi mdi-delete-outline"></i></button></td>
                        </tr>
                    </template>
                </div>
            </div>
            <button type="submit" class="btn btn-danger btn-lg w-100"><i class="mdi mdi-car-off me-1"></i> Toplu Stoktan Çıkar</button>
        </div>
    </div>
</form>

<?php ob_start(); ?>
<script>
$(function () {
    $('[data-add-row]').first().trigger('click');
});
</script>
<?php $scripts = ob_get_clean(); ?>
