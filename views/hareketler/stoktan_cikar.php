<?php
use App\Core\Csrf;
use App\Core\Tanim;

$dosyalar = [];
$gun = (int) ((time() - strtotime($arac['stoga_giris_tarihi'])) / 86400) + 1;
?>
<form method="post" action="/arac_hareketleri/stoktan_cikar/<?= $arac['id'] ?>" enctype="multipart/form-data" data-confirm="Araç stoktan çıkarılsın mı?">
    <?= Csrf::field() ?>
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5>Araç Bilgileri</h5><span class="badge badge-soft-success">Stokta — <?= $gun ?> gün</span></div>
                <div class="card-body">
                    <div class="detail-grid">
                        <div><div class="label">Şasi</div><div class="value"><?= e($arac['sase']) ?></div></div>
                        <div><div class="label">Plaka</div><div class="value"><?= e($arac['plaka'] ?: '-') ?></div></div>
                        <div><div class="label">Marka / Seri</div><div class="value"><?= e(trim(($arac['marka'] ?? '') . ' ' . ($arac['seri'] ?? '')) ?: '-') ?></div></div>
                        <div><div class="label">Müşteri</div><div class="value"><?= e($arac['musteri'] ?? '-') ?></div></div>
                        <div><div class="label">Lokasyon</div><div class="value"><?= e($arac['bayi'] ?? '-') ?> <?= $arac['lokasyon_detay'] ? '/ ' . e($arac['lokasyon_detay']) : '' ?></div></div>
                        <div><div class="label">Stoğa Giriş</div><div class="value"><?= date('d.m.Y H:i', strtotime($arac['stoga_giris_tarihi'])) ?></div></div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5>Çıkış Bilgileri</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label required">Hareket Nedeni</label><select name="hareket_nedeni" class="form-select" required><?= Tanim::options(Tanim::liste('hareket_nedenleri'), old('hareket_nedeni')) ?></select></div>
                        <div class="col-md-4"><label class="form-label">Güncel KM</label><input type="number" name="km" class="form-control" value="<?= e($arac['km']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Yakıt Durumu (%)</label><input type="number" name="yakit_durumu" min="0" max="100" class="form-control" value="<?= e($arac['yakit_durumu']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Teslim Alan</label><input type="text" name="teslim_alan" class="form-control" value="<?= old('teslim_alan') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Teslim Alan Telefon</label><input type="text" name="teslim_alan_telefon" class="form-control" value="<?= old('teslim_alan_telefon') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Teslim Alan E-posta</label><input type="email" name="teslim_alan_eposta" class="form-control" value="<?= old('teslim_alan_eposta') ?>"></div>
                    </div>
                    <div class="form-section">Sevkiyat Bilgileri</div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Sevkiyat Tipi</label><select name="sevkiyat_tipi" class="form-select"><?= Tanim::options(Tanim::SEVKIYAT_TIPI, old('sevkiyat_tipi')) ?></select></div>
                        <div class="col-md-4"><label class="form-label">Sevkiyat Durumu</label><select name="sevkiyat_durumu" class="form-select"><?= Tanim::options(Tanim::SEVKIYAT_DURUMU, old('sevkiyat_durumu', '3'), null) ?></select></div>
                        <div class="col-md-4"><label class="form-label">Çekici Plakası</label><input type="text" name="cekici_plakasi" class="form-control text-uppercase" value="<?= old('cekici_plakasi') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Şoför Adı Soyadı</label><input type="text" name="sofor_adi_soyadi" class="form-control" value="<?= old('sofor_adi_soyadi') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Şoför Telefon</label><input type="text" name="sofor_telefon" class="form-control" value="<?= old('sofor_telefon') ?>"></div>
                        <div class="col-md-2"><label class="form-label">Sevkiyat Kodu</label><input type="text" name="sevkiyat_kodu" class="form-control" value="<?= old('sevkiyat_kodu') ?>"></div>
                        <div class="col-md-2"><label class="form-label">İrsaliye Kodu</label><input type="text" name="irsaliye_kodu" class="form-control" value="<?= old('irsaliye_kodu') ?>"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h5>İşlem</h5></div>
                <div class="card-body">
                    <label class="form-label required">Çıkış Tarihi</label>
                    <div class="input-group mb-3">
                        <input type="date" name="hareket_tarihi" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        <input type="time" name="hareket_saati" class="form-control" value="<?= date('H:i') ?>">
                    </div>
                    <label class="form-label">Teslim Eden Personel</label>
                    <select name="teslim_eden_personel" class="form-select select2"><?= Tanim::options(Tanim::liste('personeller')) ?></select>
                    <label class="form-label mt-3">Açıklama / Not</label>
                    <textarea name="aciklama" class="form-control" rows="3"><?= old('aciklama') ?></textarea>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5>Kayıt Belgeleri</h5></div>
                <div class="card-body"><?php require BASE_PATH . '/views/partials/dosyalar.php'; ?></div>
            </div>
            <button type="submit" class="btn btn-danger w-100 btn-lg"><i class="mdi mdi-car-off me-1"></i> Stoktan Çıkar</button>
        </div>
    </div>
</form>
<?php unset($_SESSION['_old']); ?>
