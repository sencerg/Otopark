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
            <?php
            $depTutar = $konaklama ? (float) $konaklama['tutar'] : 0.0;
            $eklenenToplam = array_sum(array_map(fn ($x) => (float) $x['tutar'], $eklenenler));
            ?>
            <div class="card">
                <div class="card-header"><h5>Hizmetler ve Ücret</h5><a href="/tanimlamalar" class="small" target="_blank"><i class="mdi mdi-cog-outline"></i> Hizmet tanımları</a></div>
                <div class="card-body">
                    <table class="table table-sm align-middle mb-3">
                        <tbody>
                        <tr>
                            <td><i class="mdi mdi-parking text-primary me-1"></i><b>Depolama</b>
                                <?php if ($konaklama): ?>
                                    <span class="text-muted small">— <?= (int) $konaklama['gun'] ?> gün ×
                                        <?php if ($konaklama['fiyat_yok']): ?><span class="badge badge-soft-warning">Fiyat tanımlı değil</span>
                                        <?php else: ?><?= number_format((float) $konaklama['gunluk_fiyat'], 2, ',', '.') ?> ₺
                                            <?php if ((float) $konaklama['carpan'] !== 1.0): ?>(<?= number_format((float) $konaklama['taban_fiyat'], 2, ',', '.') ?> ₺ × <?= number_format((float) $konaklama['carpan'], 2, ',', '.') ?> otopark çarpanı)<?php endif; ?>
                                        <?php endif; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-semibold"><?= number_format($depTutar, 2, ',', '.') ?> ₺</td>
                        </tr>
                        <?php foreach ($eklenenler as $x): ?>
                            <tr><td><i class="mdi mdi-check-circle-outline text-success me-1"></i><?= e($x['ad']) ?> <span class="text-muted small">— <?= date('d.m.Y', strtotime($x['islem_tarihi'])) ?></span></td>
                                <td class="text-end"><?= number_format((float) $x['tutar'], 2, ',', '.') ?> ₺</td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="form-section mt-0">Çıkışta eklenecek hizmetler</div>
                    <table class="table table-sm align-middle mb-2">
                        <thead><tr><th>Hizmet</th><th style="width: 180px">Ücret (₺)</th><th style="width: 40px"></th></tr></thead>
                        <tbody id="cikis-hizmetleri"></tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="#hizmet-satir" data-target="#cikis-hizmetleri"><i class="mdi mdi-plus me-1"></i>Hizmet Ekle</button>

                    <div class="d-flex justify-content-between align-items-center border-top mt-3 pt-3">
                        <span class="text-muted">Toplam ücret (depolama + hizmetler)</span>
                        <span class="fs-4 fw-bold text-primary" id="toplam-ucret" data-sabit="<?= $depTutar + $eklenenToplam ?>"><?= number_format($depTutar + $eklenenToplam, 2, ',', '.') ?> ₺</span>
                    </div>
                </div>
            </div>
            <template id="hizmet-satir">
                <tr>
                    <td><select name="hizmet_id[]" class="form-select form-select-sm select2" required>
                        <option value="">Seçiniz</option>
                        <?php foreach ($hizmetler as $h): ?><option value="<?= $h['id'] ?>" data-tutar="<?= e($h['varsayilan_tutar']) ?>"><?= e($h['ad']) ?></option><?php endforeach; ?>
                    </select></td>
                    <td><input type="number" name="hizmet_tutar[]" class="form-control form-control-sm text-end" step="0.01" min="0" required></td>
                    <td><button type="button" class="btn btn-sm btn-light text-danger" data-remove-row><i class="mdi mdi-close"></i></button></td>
                </tr>
            </template>
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
<?php ob_start(); ?>
<script>
$(function () {
    const $toplam = $('#toplam-ucret');
    const hesapla = () => {
        let t = Number($toplam.data('sabit') || 0);
        $('#cikis-hizmetleri [name="hizmet_tutar[]"]').each(function () { t += Number(this.value || 0); });
        $toplam.text(App.money(t));
    };
    $('#cikis-hizmetleri').on('change', '[name="hizmet_id[]"]', function () {
        $(this).closest('tr').find('[name="hizmet_tutar[]"]').val($(this).find(':selected').data('tutar') ?? '');
        hesapla();
    }).on('input', '[name="hizmet_tutar[]"]', hesapla);
    $(document).on('click', '#cikis-hizmetleri [data-remove-row]', () => setTimeout(hesapla));
});
</script>
<?php $scripts = ob_get_clean(); ?>
