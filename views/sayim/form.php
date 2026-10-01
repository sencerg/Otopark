<?php
use App\Core\Csrf;
use App\Core\Tanim;

$o = $_SESSION['_old'] ?? [];
$bayiler = Tanim::liste('bayiler');
?>
<form method="post" action="/sayim_kayitlari/save">
    <?= Csrf::field() ?>
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5>Sayım Bilgileri</h5><span class="text-muted small"><?= e($kod) ?></span></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Sorumlu Personel</label>
                            <select name="sorumlu_personel" class="form-select select2"><?= Tanim::options(Tanim::liste('personeller'), $o['sorumlu_personel'] ?? null, 'Personel Seçiniz') ?></select></div>
                        <div class="col-md-6"><label class="form-label required">Lokasyon</label>
                            <select name="bayi_id" class="form-select" required><?= Tanim::options($bayiler, $o['bayi_id'] ?? (count($bayiler) === 1 ? array_key_first($bayiler) : null), count($bayiler) === 1 ? null : 'Lokasyon Seçiniz') ?></select></div>
                        <div class="col-md-8"><label class="form-label required">Sayım Başlığı</label>
                            <input type="text" name="baslik" class="form-control" maxlength="200" required value="<?= e($o['baslik'] ?? 'Stok Sayımı - ' . date('d.m.Y')) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Sayım Durumu</label>
                            <select class="form-select" disabled><option><?= Tanim::SAYIM_DURUM[1] ?></option></select></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <p class="small text-muted">Sayım kaydedildikten sonra okutma ekranı açılır. Lokasyondaki araçların şasi numarası veya plakası okutulur; sistem bunları otomatik olarak üç listeye ayırır:</p>
                    <ul class="small text-muted ps-3">
                        <li><b>Okutulanlar:</b> Lokasyonun stoğunda olan araçlar</li>
                        <li><b>Lokasyonda Değil:</b> Sahada olup bu lokasyonun stoğunda görünmeyen araçlar</li>
                        <li><b>Henüz Okutulmayanlar:</b> Stokta görünüp sahada okutulmamış araçlar</li>
                    </ul>
                    <button type="submit" class="btn btn-primary btn-lg w-100"><i class="mdi mdi-content-save me-1"></i> Kaydet ve Okutmaya Başla</button>
                </div>
            </div>
        </div>
    </div>
</form>
