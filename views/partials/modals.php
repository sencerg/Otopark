<?php
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Tanim;

$maliyetTipleri = Database::fetchAll('SELECT id, ad, varsayilan_tutar FROM maliyet_tipleri WHERE aktif ORDER BY id');
$bayiler = Tanim::liste('bayiler');
?>
<div class="modal fade" id="hizli-arac-ekle-modal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" method="post" action="/arac_yonetimi/hizli_arac_save" data-ajax>
            <?= Csrf::field() ?>
            <div class="modal-header">
                <h5 class="modal-title"><i class="mdi mdi-car-arrow-right me-1 text-primary"></i> Hızlı Araç Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label required">Şasi Numarası</label><input type="text" name="sase" class="form-control text-uppercase" maxlength="30" required></div>
                    <div class="col-md-4"><label class="form-label required">Plaka</label><input type="text" name="plaka" class="form-control text-uppercase" maxlength="20" required></div>
                    <div class="col-md-4"><label class="form-label required">Araç Tipi</label><select name="arac_tipi" class="form-select" required><?= Tanim::options(Tanim::liste('arac_tipleri')) ?></select></div>
                    <div class="col-md-4"><label class="form-label">Marka</label><select name="marka_id" class="form-select select2"><?= Tanim::options(Tanim::liste('markalar')) ?></select></div>
                    <div class="col-md-4"><label class="form-label">Seri</label><select name="seri_id" class="form-select select2"><option value="">Önce Marka Seçiniz</option></select></div>
                    <div class="col-md-4"><label class="form-label">Model</label><select name="model_id" class="form-select select2"><option value="">Önce Seri Seçiniz</option></select></div>
                    <div class="col-md-3"><label class="form-label">Araç Durumu</label><select name="arac_durumu" class="form-select"><?= Tanim::options(Tanim::ARAC_DURUMU) ?></select></div>
                    <div class="col-md-3"><label class="form-label">KM</label><input type="number" name="km" class="form-control" min="0"></div>
                    <div class="col-md-3"><label class="form-label">Model Yılı</label><select name="yil" class="form-select"><?= Tanim::options(Tanim::liste('model_yillari')) ?></select></div>
                    <div class="col-md-3"><label class="form-label">Yakıt Durumu (%)</label><input type="number" name="yakit_durumu" class="form-control" min="0" max="100"></div>
                    <div class="col-md-3"><label class="form-label">Yakıt Tipi</label><select name="yakit_tipi" class="form-select"><?= Tanim::options(Tanim::liste('yakit_tipleri')) ?></select></div>
                    <div class="col-md-3"><label class="form-label">Vites Tipi</label><select name="vites_tipi" class="form-select"><?= Tanim::options(Tanim::liste('vites_tipleri')) ?></select></div>
                </div>

                <div class="form-section">Araç Envanteri</div>
                <div class="check-grid">
                    <?php foreach (Tanim::liste('envanterler') as $id => $ad): ?>
                        <label class="form-check"><input class="form-check-input" type="checkbox" name="arac_envanterleri[]" value="<?= $id ?>"> <span class="form-check-label"><?= e($ad) ?></span></label>
                    <?php endforeach; ?>
                </div>

                <div class="form-section">Hareket Bilgileri</div>
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">Hareket Tipi</label><select name="hareket_tipi" class="form-select"><?= Tanim::options(Tanim::HAREKET_TIPI, 1, null) ?></select></div>
                    <div class="col-md-3"><label class="form-label">Hareket Nedeni</label><select name="hareket_nedeni" class="form-select"><?= Tanim::options(Tanim::liste('hareket_nedenleri')) ?></select></div>
                    <div class="col-md-6"><label class="form-label required">Müşteri Adı</label><select name="musteri_id" class="form-select select2" required><?= Tanim::options(Tanim::liste('musteriler')) ?></select></div>
                    <div class="col-md-3"><label class="form-label required">Lokasyon Türü</label><select name="lokasyon_turu" class="form-select" required><?= Tanim::options(Tanim::LOKASYON_TURU) ?></select></div>
                    <div class="col-md-3"><label class="form-label required">Lokasyon</label><select name="bayi_id" class="form-select" required><?= Tanim::options($bayiler, count($bayiler) === 1 ? array_key_first($bayiler) : null, count($bayiler) === 1 ? null : 'Seçiniz') ?></select></div>
                    <div class="col-md-6"><label class="form-label">Lokasyon Detay</label><input type="text" name="lokasyon_detay" class="form-control" placeholder="Blok / sıra / park yeri"></div>
                    <div class="col-md-4"><label class="form-label">Teslim Eden</label><input type="text" name="teslim_eden" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Teslim Eden Telefon</label><input type="text" name="teslim_eden_telefon" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Teslim Eden E-posta</label><input type="email" name="teslim_eden_eposta" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Teslim Alan</label><input type="text" name="teslim_alan" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Teslim Alan Telefon</label><input type="text" name="teslim_alan_telefon" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Teslim Alan E-posta</label><input type="email" name="teslim_alan_eposta" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Açıklama / Not</label><textarea name="aciklama" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-3"><label class="form-label required">Hareket Tarihi</label>
                        <div class="input-group"><input type="date" name="hareket_tarihi_tarih" class="form-control" required data-default-today><input type="time" name="hareket_tarihi_saat" class="form-control" value="<?= date('H:i') ?>"></div>
                    </div>
                    <div class="col-md-3"><label class="form-label">Teslim Alan Personel</label><select name="teslim_alan_personel" class="form-select select2"><?= Tanim::options(Tanim::liste('personeller')) ?></select></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i> Kaydet</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="hizli-maliyet-ekle-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="post" action="/arac_ekstreleri/ek_maliyet_save_modal" data-ajax>
            <?= Csrf::field() ?>
            <div class="modal-header">
                <h5 class="modal-title"><i class="mdi mdi-cash-plus me-1 text-primary"></i> Hızlı Maliyet Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-12"><label class="form-label required">Şasi Numarası</label><select name="arac_id" class="form-select arac-select" required></select></div>
                    <div class="col-md-8"><label class="form-label required">Maliyet Tipi</label>
                        <select name="maliyet_tipi_modal" class="form-select select2" required data-tutar-target="#hizli-maliyet-tutar">
                            <option value="">Seçiniz</option>
                            <?php foreach ($maliyetTipleri as $m): ?><option value="<?= $m['id'] ?>" data-tutar="<?= e($m['varsayilan_tutar']) ?>"><?= e($m['ad']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label required">Fiyat (₺)</label><input type="number" step="0.01" min="0" name="tutar_ek" id="hizli-maliyet-tutar" class="form-control" required></div>
                    <div class="col-md-12"><label class="form-label">Açıklama</label><textarea name="aciklama_ekstre" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-4"><label class="form-label">İrsaliye Bilgisi</label><input type="text" name="irsaliye" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Fatura No</label><input type="text" name="fatura_no" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">Fatura Tarihi</label><input type="date" name="fatura_tarihi" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">İşlem Tarihi</label><input type="date" name="islem_tarihi" class="form-control" data-default-today></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i> Kaydet</button>
            </div>
        </form>
    </div>
</div>
