<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Tanim;

$t = strtotime($h['hareket_tarihi']);
$cikis = (int) $h['hareket_tipi'] === 2;
$fg = Auth::fiyatGorebilir();
if ($readonly) {
    $headerActions = '<a href="/arac_hareketleri/hareket_duzenle/' . $h['id'] . '" class="btn btn-primary"><i class="mdi mdi-pencil me-1"></i>Düzenle</a>';
}
$headerActions = ($headerActions ?? '') . '<a href="/arac_yonetimi/tesellum_formu/' . $h['arac_id'] . '" class="btn btn-outline-primary"><i class="mdi mdi-file-document-outline me-1"></i>Tesellüm Formu</a>'
    . '<a href="/arac_yonetimi/duzenle/' . $h['arac_id'] . '" class="btn btn-light"><i class="mdi mdi-car me-1"></i>Araç Kartı</a>';
?>
<form method="post" action="/arac_hareketleri/hareket_update/<?= $h['id'] ?>" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <input type="hidden" name="arac_id" value="<?= (int) $h['arac_id'] ?>">
    <fieldset <?= $readonly ? 'disabled' : '' ?>>
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5>Hareket Kaydı Detay — <span class="text-primary"><?= e($h['sase']) ?></span></h5>
                    <span class="badge <?= $cikis ? 'badge-soft-danger' : 'badge-soft-success' ?>"><?= Tanim::HAREKET_TIPI[$h['hareket_tipi']] ?> #<?= $h['id'] ?></span></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Plaka No</label><input type="text" name="plaka" class="form-control text-uppercase" value="<?= e($h['arac_plaka']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Şasi Numarası</label><input type="text" class="form-control" value="<?= e($h['sase']) ?>" disabled></div>
                        <div class="col-md-4"><label class="form-label required">Hareket Tipi</label><select name="hareket_tipi" class="form-select" required><?= Tanim::options(Tanim::HAREKET_TIPI, $h['hareket_tipi']) ?></select></div>
                        <div class="col-md-4"><label class="form-label">Hareket Nedeni</label><select name="hareket_nedeni" class="form-select"><?= Tanim::options(Tanim::liste('hareket_nedenleri'), $h['hareket_nedeni_id']) ?></select></div>
                        <div class="col-md-4"><label class="form-label">Güncel KM</label><input type="number" name="km" class="form-control" value="<?= e($h['km']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Yakıt Durumu (%)</label><input type="number" name="yakit_durumu" min="0" max="100" class="form-control" value="<?= e($h['yakit_durumu']) ?>"></div>
                    </div>

                    <div class="form-section">Detay Bilgiler</div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Marka</label><select name="marka_id" class="form-select select2"><?= Tanim::options(Tanim::liste('markalar'), $h['marka_id']) ?></select></div>
                        <div class="col-md-4"><label class="form-label">Seri</label><select name="seri_id" class="form-select select2"><?= Tanim::options($h['marka_id'] ? Tanim::seriler((int) $h['marka_id']) : [], $h['seri_id']) ?></select></div>
                        <div class="col-md-4"><label class="form-label">Model</label><select name="model_id" class="form-select select2"><?= Tanim::options($h['seri_id'] ? Tanim::modeller((int) $h['seri_id']) : [], $h['model_id']) ?></select></div>
                        <div class="col-md-3"><label class="form-label">Model Yılı</label><select name="yil" class="form-select"><?= Tanim::options(Tanim::liste('model_yillari'), $h['model_yili_id']) ?></select></div>
                        <div class="col-md-3"><label class="form-label">Araç Durumu</label><select name="arac_durumu" class="form-select"><?= Tanim::options(Tanim::ARAC_DURUMU, $h['arac_durumu']) ?></select></div>
                        <div class="col-md-3"><label class="form-label">Araç Tipi</label><select name="arac_tipi" class="form-select"><?= Tanim::options(Tanim::liste('arac_tipleri'), $h['arac_tipi_id']) ?></select></div>
                        <div class="col-md-3"><label class="form-label">Kasa Tipi</label><select name="kasa_tipi" class="form-select"><?= Tanim::options(Tanim::liste('kasa_tipleri'), $h['kasa_tipi_id']) ?></select></div>
                        <div class="col-md-4"><label class="form-label">Renk</label><select name="renk" class="form-select select2"><?= Tanim::options(Tanim::liste('renkler'), $h['renk_id']) ?></select></div>
                        <div class="col-md-4"><label class="form-label">Yakıt Tipi</label><select name="yakit_tipi" class="form-select"><?= Tanim::options(Tanim::liste('yakit_tipleri'), $h['yakit_tipi_id']) ?></select></div>
                        <div class="col-md-4"><label class="form-label">Vites Tipi</label><select name="vites_tipi" class="form-select"><?= Tanim::options(Tanim::liste('vites_tipleri'), $h['vites_tipi_id']) ?></select></div>
                    </div>

                    <div class="form-section">Araç Envanteri</div>
                    <div class="check-grid">
                        <?php foreach (Tanim::liste('envanterler') as $id => $ad): ?>
                            <label class="form-check"><input class="form-check-input" type="checkbox" name="arac_envanterleri[]" value="<?= $id ?>" <?= in_array($id, $envanter) ? 'checked' : '' ?>> <span class="form-check-label"><?= e($ad) ?></span></label>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-section">Müşteri ve Lokasyon</div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label required">Müşteri Adı</label><select name="musteri_id" class="form-select select2" required><?= Tanim::options(Tanim::liste('musteriler'), $h['musteri_id']) ?></select></div>
                        <div class="col-md-6"><label class="form-label required">Lokasyon</label><select name="bayi_id" class="form-select" required><?= Tanim::options(Tanim::liste('bayiler'), $h['bayi_id']) ?></select></div>
                        <div class="col-md-4"><label class="form-label">Lokasyon Türü</label><select name="lokasyon_turu" class="form-select"><?= Tanim::options(Tanim::LOKASYON_TURU, $h['lokasyon_turu']) ?></select></div>
                        <div class="col-md-4"><label class="form-label">Lokasyon Detay</label><input type="text" name="lokasyon_detay" class="form-control" value="<?= e($h['lokasyon_detay']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Konsinye Araç Mı?</label><select name="konsinye" class="form-select"><?= Tanim::options([1 => 'Evet', 0 => 'Hayır'], $h['konsinye'] ? 1 : 0, null) ?></select></div>
                        <div class="col-md-4"><label class="form-label">Teslim Eden</label><input type="text" name="teslim_eden" class="form-control" value="<?= e($h['teslim_eden']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Teslim Eden Telefon</label><input type="text" name="teslim_eden_telefon" class="form-control" value="<?= e($h['teslim_eden_telefon']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Teslim Eden E-posta</label><input type="email" name="teslim_eden_eposta" class="form-control" value="<?= e($h['teslim_eden_eposta']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Teslim Alan</label><input type="text" name="teslim_alan" class="form-control" value="<?= e($h['teslim_alan']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Teslim Alan Telefon</label><input type="text" name="teslim_alan_telefon" class="form-control" value="<?= e($h['teslim_alan_telefon']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Teslim Alan E-posta</label><input type="email" name="teslim_alan_eposta" class="form-control" value="<?= e($h['teslim_alan_eposta']) ?>"></div>
                    </div>

                    <div class="form-section">Sevkiyat Bilgileri</div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Sevkiyat Durumu</label><select name="sevkiyat_durumu" class="form-select"><?= Tanim::options(Tanim::SEVKIYAT_DURUMU, $h['sevkiyat_durumu']) ?></select></div>
                        <div class="col-md-4"><label class="form-label">Çekici Plakası</label><input type="text" name="cekici_plakasi" class="form-control text-uppercase" value="<?= e($h['cekici_plakasi']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Şoför Adı Soyadı</label><input type="text" name="sofor_adi_soyadi" class="form-control" value="<?= e($h['sofor_adi_soyadi']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Şoför Telefon</label><input type="text" name="sofor_telefon" class="form-control" value="<?= e($h['sofor_telefon']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Sevkiyat Kodu</label><input type="text" name="sevkiyat_kodu" class="form-control" value="<?= e($h['sevkiyat_kodu']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">İrsaliye Kodu</label><input type="text" name="irsaliye_kodu" class="form-control" value="<?= e($h['irsaliye_kodu']) ?>"></div>
                    </div>
                </div>
            </div>
            <?php if ($cikis):
                $maske = Auth::fiyatMaskeli();
                $depTutar = $konaklama ? (float) $konaklama['tutar'] : 0.0;
                $hizmetToplam = array_sum(array_map(fn ($x) => (float) $x['tutar'], $eklenenler));
                $ozelSabit = array_sum(array_map(
                    fn ($x) => !empty($x['bayi_fiyat_degistirebilir']) ? (float) $x['tutar'] : 0,
                    $eklenenler
                ));
                $anlasmaliSabit = count(array_filter($eklenenler, fn ($x) => empty($x['bayi_fiyat_degistirebilir'])));
                $sonradanAdet = count(array_filter($eklenenler, fn ($x) => $x['cikis_sonrasi']));
            ?>
            <div class="card">
                <div class="card-header"><h5>Hizmetler ve Ücret</h5>
                    <?php if ($sonradanAdet): ?><span class="badge bg-warning text-dark"><i class="mdi mdi-clock-alert-outline me-1"></i><?= $sonradanAdet ?> hizmet çıkış sonrası eklendi</span><?php endif; ?>
                </div>
                <div class="card-body">
                    <table class="table table-sm align-middle mb-3">
                        <tbody>
                        <tr>
                            <td><i class="mdi mdi-parking text-primary me-1"></i><b>Depolama</b>
                                <?php if ($konaklama && $fg): ?>
                                    <span class="text-muted small">— <?php
                                        if (!empty($konaklama['fiyat_yok'])): ?><span class="badge badge-soft-warning">Fiyat belirlenmedi</span><?php
                                        elseif (!empty($konaklama['fiyat_degisti'])): ?><?= e(\App\Services\DepolamaHesap::fiyatAciklama($konaklama)) ?><?php
                                        else: ?><?= (int) $konaklama['gun'] ?> gün × <?= number_format((float) $konaklama['gunluk_fiyat'], 2, ',', '.') ?> ₺<?php endif; ?></span>
                                <?php elseif ($konaklama): ?>
                                    <span class="text-muted small">— <?= (int) $konaklama['gun'] ?> gün</span>
                                <?php endif; ?>
                            </td>
                            <td></td>
                            <?php if ($fg): ?><td class="text-end fw-semibold"><?= !empty($konaklama['fiyat_yok']) ? '<span class="badge badge-soft-warning">Fiyat belirlenmedi</span>' : number_format($depTutar, 2, ',', '.') . ' ₺' ?></td>
                            <?php else: ?><td></td><?php endif; ?>
                            <td style="width: 40px"></td>
                        </tr>
                        <?php foreach ($eklenenler as $x):
                            $deg = !empty($x['bayi_fiyat_degistirebilir']);
                            ?>
                            <tr class="<?= $x['cikis_sonrasi'] ? 'table-warning' : '' ?>">
                                <td><?php if ($x['cikis_sonrasi']): ?><i class="mdi mdi-clock-alert-outline text-warning me-1"></i><?php else: ?><i class="mdi mdi-check-circle-outline text-success me-1"></i><?php endif; ?><?= e($x['ad']) ?>
                                    <span class="text-muted small">— <?= date('d.m.Y', strtotime($x['islem_tarihi'])) ?></span>
                                    <?php if ($x['cikis_sonrasi']): ?><span class="badge bg-warning text-dark ms-1">Çıkış sonrası eklendi</span><?php endif; ?>
                                </td>
                                <td class="small <?= $x['cikis_sonrasi'] ? '' : 'text-muted' ?>">
                                    <?php if ($x['aciklama']): ?><i class="mdi mdi-note-text-outline me-1"></i><?= e($x['aciklama']) ?><?php endif; ?>
                                    <?php if ($x['cikis_sonrasi']): ?><div class="text-muted"><?= e($x['kullanici'] ?? '-') ?>, <?= date('d.m.Y H:i', strtotime($x['created_at'])) ?></div><?php endif; ?>
                                </td>
                                <?php if ($readonly || $maske): ?>
                                    <td class="text-end"><?= $maske
                                        ? \App\Services\HizmetFiyat::bayiTutarHtml($deg, $x['tutar'])
                                        : number_format((float) $x['tutar'], 2, ',', '.') . ' ₺' ?></td>
                                <?php else:
                                    $deger = (float) $x['tutar'] > 0 ? $x['tutar'] : '';
                                ?>
                                    <td class="text-end" style="width: 150px">
                                        <input type="number" name="mevcut_tutar[<?= $x['id'] ?>]" value="<?= e($deger) ?>" step="0.01" min="0"
                                               class="form-control form-control-sm text-end" placeholder="Fiyat belirlenmedi"
                                               <?= Auth::isAdmin() ? '' : 'readonly' ?>>
                                    </td>
                                <?php endif; ?>
                                <td><?php if (!$readonly && $x['cikis_sonrasi']): ?><button type="button" class="btn btn-sm btn-light text-danger" data-hizmet-sil="<?= $x['id'] ?>" title="Sil"><i class="mdi mdi-delete-outline"></i></button><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (!$readonly): ?>
                        <div class="form-section mt-0">Çıkış sonrası hizmet / maliyet ekle</div>
                        <p class="small text-muted mb-2">Buradan eklenenler bugünün tarihiyle raporlara yansır ve "Çıkış sonrası eklendi" olarak işaretlenir. Kaydet'e basınca yazılır.</p>
                        <table class="table table-sm align-middle mb-2">
                            <thead><tr><th>Hizmet</th><th style="width: 150px">Ücret</th><th>Not</th><th style="width: 40px"></th></tr></thead>
                            <tbody id="cikis-hizmetleri"></tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="#hizmet-satir" data-target="#cikis-hizmetleri"><i class="mdi mdi-plus me-1"></i>Hizmet Ekle</button>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center border-top mt-3 pt-3">
                        <span class="text-muted">Toplam ücret<?= $fg ? ' (depolama + hizmetler)' : ' (hizmetler)' ?></span>
                        <span class="fs-5 fw-bold text-primary" id="toplam-ucret"
                              data-maske="<?= $maske ? '1' : '0' ?>"
                              data-sabit="<?= $readonly ? (($fg ? $depTutar : 0) + $hizmetToplam) : ($fg ? $depTutar : 0) ?>"
                              data-ozel-sabit="<?= $ozelSabit ?>"
                              data-anlasmali-sabit="<?= $anlasmaliSabit ?>"><?php
                            if ($maske) {
                                echo e(\App\Services\HizmetFiyat::bayiToplamOzeti($eklenenler));
                            } else {
                                echo number_format(($fg ? $depTutar : 0) + $hizmetToplam, 2, ',', '.') . ' ₺';
                            }
                        ?></span>
                    </div>
                </div>
            </div>
            <template id="hizmet-satir">
                <tr class="table-warning">
                    <td><select name="hizmet_id[]" class="form-select form-select-sm select2" required>
                        <option value="">Seçiniz</option>
                        <?php foreach ($hizmetler as $hz): ?><option value="<?= $hz['id'] ?>" data-bayi-degistirebilir="<?= !empty($hz['bayi_fiyat_degistirebilir']) ? '1' : '0' ?>" data-tekrar-eklenebilir="<?= !empty($hz['tekrar_eklenebilir']) ? '1' : '0' ?>"><?= e($hz['ad']) ?><?= empty($hz['bayi_fiyat_degistirebilir']) ? ' (kilitli)' : '' ?><?= empty($hz['tekrar_eklenebilir']) ? ' (tek sefer)' : '' ?></option><?php endforeach; ?>
                    </select></td>
                    <td><input type="number" name="hizmet_tutar[]" class="form-control form-control-sm text-end" step="0.01" min="0" placeholder="Fiyat belirlenmedi" required></td>
                    <td><input type="text" name="hizmet_not[]" class="form-control form-control-sm" maxlength="500" placeholder="Neden sonradan eklendi?"></td>
                    <td><button type="button" class="btn btn-sm btn-light text-danger" data-remove-row><i class="mdi mdi-close"></i></button></td>
                </tr>
            </template>
            <?php endif; ?>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h5>İşlem Bilgileri</h5></div>
                <div class="card-body">
                    <label class="form-label required"><?= $cikis ? 'Çıkış' : 'Giriş' ?> Tarihi</label>
                    <div class="input-group mb-3">
                        <input type="date" name="hareket_tarihi" class="form-control" value="<?= date('Y-m-d', $t) ?>" required>
                        <input type="time" name="hareket_saati" class="form-control" value="<?= date('H:i', $t) ?>">
                    </div>
                    <label class="form-label">Personel</label>
                    <select name="teslim_alan_personel" class="form-select select2 mb-3"><?= Tanim::options(Tanim::liste('personeller'), $h['teslim_alan_personel_id']) ?></select>
                    <label class="form-label mt-3">Açıklama / Not</label>
                    <textarea name="aciklama" class="form-control mb-3" rows="3"><?= e($h['aciklama']) ?></textarea>
                    <div class="row g-2">
                        <div class="col-12"><label class="form-label">Trafik Sigortası Tarihi</label><input type="date" name="sigorta_tarihi" class="form-control" value="<?= e($h['sigorta_tarihi']) ?>"></div>
                        <div class="col-12"><label class="form-label">Kasko Tarihi</label><input type="date" name="kasko_tarihi" class="form-control" value="<?= e($h['kasko_tarihi']) ?>"></div>
                        <div class="col-12"><label class="form-label">Muayene Tarihi</label><input type="date" name="muayene_tarihi" class="form-control" value="<?= e($h['muayene_tarihi']) ?>"></div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5>Kayıt Belgeleri</h5></div>
                <div class="card-body"><?php require BASE_PATH . '/views/partials/dosyalar.php'; ?></div>
            </div>
            <?php if (!$readonly): ?>
                <button type="submit" class="btn btn-primary w-100 btn-lg"><i class="mdi mdi-content-save me-1"></i> Kaydet</button>
            <?php endif; ?>
        </div>
    </div>
    </fieldset>
</form>
<?php require BASE_PATH . '/views/partials/dosya_sil_formlari.php'; ?>
<?php if ($cikis && !$readonly): ob_start(); ?>
<script>
$(function () {
    const $toplam = $('#toplam-ucret');
    const hesapla = () => {
        const maske = $toplam.data('maske') === 1 || $toplam.data('maske') === '1';
        if (!maske) {
            let t = Number($toplam.data('sabit') || 0);
            $('#cikis-hizmetleri [name="hizmet_tutar[]"], [name^="mevcut_tutar["]').each(function () { t += Number(this.value || 0); });
            $toplam.text(App.money(t));
            return;
        }
        let anlasmali = Number($toplam.data('anlasmaliSabit') || 0);
        let ozel = Number($toplam.data('ozelSabit') || 0);
        $('#cikis-hizmetleri tr').each(function () {
            const deg = $(this).find('[name="hizmet_id[]"] option:selected').data('bayiDegistirebilir');
            const degOk = !(deg === false || deg === 0 || deg === '0');
            const tip = $(this).find('[name="hizmet_id[]"]').val();
            if (!tip) return;
            if (degOk) ozel += Number($(this).find('[name="hizmet_tutar[]"]').val() || 0);
            else anlasmali += 1;
        });
        const parca = [];
        if (anlasmali > 0) parca.push(anlasmali === 1 ? 'Anlaşmalı Fiyat' : (anlasmali + ' × Anlaşmalı Fiyat'));
        if (ozel > 0) parca.push('Özel: ' + App.money(ozel));
        $toplam.text(parca.length ? parca.join(' + ') : '—');
    };
    $('[name^="mevcut_tutar["]').on('input', function () {
        this.classList.toggle('is-invalid', !Number(this.value));
        hesapla();
    });
    hesapla();
    $('#cikis-hizmetleri').on('change', '[name="hizmet_id[]"]', function () {
        hesapla();
    }).on('input', '[name="hizmet_tutar[]"]', hesapla);
    $(document).on('click', '#cikis-hizmetleri [data-remove-row]', () => setTimeout(hesapla));
    $(document).on('ajax-form-saved change', () => setTimeout(hesapla, 50));

    $('[data-hizmet-sil]').on('click', function () {
        const id = this.dataset.hizmetSil;
        Swal.fire({ icon: 'warning', title: 'Çıkış sonrası eklenen hizmet silinsin mi?', showCancelButton: true, confirmButtonText: 'Sil', cancelButtonText: 'Vazgeç', confirmButtonColor: '#dc2626' })
            .then((r) => {
                if (!r.isConfirmed) return;
                $.post('/arac_ekstreleri/maliyet_sil/' + id, { _csrf: $('meta[name="csrf-token"]').attr('content') })
                    .done((res) => { App.toast(res.message); setTimeout(() => location.reload(), 700); })
                    .fail((x) => App.error(x.responseJSON?.message));
            });
    });
});
</script>
<?php $scripts = ob_get_clean(); endif; ?>
