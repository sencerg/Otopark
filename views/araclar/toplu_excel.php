<?php
use App\Core\Csrf;

$headerActions = '<a href="/arac_yonetimi/sablon/' . e($sablon) . '" class="btn btn-success"><i class="mdi mdi-microsoft-excel me-1"></i>Örnek Şablon İndir</a>';
?>
<div class="row justify-content-center">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header"><h5><?= e($pageTitle) ?></h5></div>
            <div class="card-body">
                <div class="alert alert-info small">
                    <i class="mdi mdi-information-outline me-1"></i><?= e($aciklama) ?><br>
                    Şablonu XLSX olarak indirip doldurun; aynı formatta yükleyin (CSV de kabul edilir).
                </div>
                <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
                    <?= Csrf::field() ?>
                    <label class="form-label required">Dosya</label>
                    <input type="file" name="import_file" class="form-control mb-3" accept=".xlsx,.xls,.csv,.txt" required>
                    <button type="submit" class="btn btn-primary w-100"><i class="mdi mdi-upload me-1"></i> Yükle ve Aktar</button>
                </form>
            </div>
        </div>
    </div>
</div>
