<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Tanim;

$fg = Auth::fiyatGorebilir();

$dosyalar = [];
$girisTs = strtotime((string) $arac['stoga_giris_tarihi']);
$gun = (int) ((time() - $girisTs) / 86400) + 1;
$girisTarih = date('Y-m-d', $girisTs);
$girisSaat = date('H:i', $girisTs);
$bugun = date('Y-m-d');
$simdiSaat = date('H:i');
?>
<form method="post" action="/arac_hareketleri/stoktan_cikar/<?= $arac['id'] ?>" enctype="multipart/form-data" data-confirm="Araç stoktan çıkarılsın mı?">
    <?= Csrf::field() ?>
    <input type="hidden" name="arac_id" value="<?= (int) $arac['id'] ?>">
    <?php if (!empty($arac['musteri_id'])): ?><input type="hidden" name="musteri_id" value="<?= (int) $arac['musteri_id'] ?>"><?php endif; ?>
    <?php if (!empty($arac['bayi_id'])): ?><input type="hidden" name="bayi_id" value="<?= (int) $arac['bayi_id'] ?>"><?php endif; ?>
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
                        <div class="col-md-4"><label class="form-label">Çekici Plakası</label><input type="text" name="cekici_plakasi" class="form-control text-uppercase" value="<?= old('cekici_plakasi') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Şoför Adı Soyadı</label><input type="text" name="sofor_adi_soyadi" class="form-control" value="<?= old('sofor_adi_soyadi') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Şoför Telefon</label><input type="text" name="sofor_telefon" class="form-control" value="<?= old('sofor_telefon') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Sevkiyat Kodu</label><input type="text" name="sevkiyat_kodu" class="form-control" value="<?= old('sevkiyat_kodu') ?>"></div>
                        <div class="col-md-4"><label class="form-label">İrsaliye Kodu</label><input type="text" name="irsaliye_kodu" class="form-control" value="<?= old('irsaliye_kodu') ?>"></div>
                    </div>
                </div>
            </div>
            <?php
            $maske = Auth::fiyatMaskeli();
            $depTutar = $konaklama ? (float) $konaklama['tutar'] : 0.0;
            $eklenenToplam = array_sum(array_map(fn ($x) => (float) $x['tutar'], $eklenenler));
            $ozelSabit = array_sum(array_map(
                fn ($x) => !empty($x['bayi_fiyat_degistirebilir']) ? (float) $x['tutar'] : 0,
                $eklenenler
            ));
            ?>
            <div class="card">
                <div class="card-header"><h5>Hizmetler ve Ücret</h5><?php if (Auth::isAdmin()): ?><a href="/tanimlamalar" class="small" target="_blank"><i class="mdi mdi-cog-outline"></i> Hizmet tanımları</a><?php endif; ?></div>
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
                            <?php if ($fg): ?><td class="text-end fw-semibold"><?= !empty($konaklama['fiyat_yok']) ? '<span class="badge badge-soft-warning">Fiyat belirlenmedi</span>' : number_format($depTutar, 2, ',', '.') . ' ₺' ?></td>
                            <?php else: ?><td></td><?php endif; ?>
                        </tr>
                        <?php foreach ($eklenenler as $x): ?>
                            <tr><td><i class="mdi mdi-check-circle-outline text-success me-1"></i><?= e($x['ad']) ?> <span class="text-muted small">— <?= date('d.m.Y', strtotime($x['islem_tarihi'])) ?></span></td>
                                <td class="text-end"><?= $maske
                                    ? \App\Services\HizmetFiyat::bayiTutarHtml(!empty($x['bayi_fiyat_degistirebilir']), $x['tutar'])
                                    : number_format((float) $x['tutar'], 2, ',', '.') . ' ₺' ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="form-section mt-0">Çıkışta eklenecek hizmetler</div>
                    <table class="table table-sm align-middle mb-2">
                        <thead><tr><th>Hizmet</th><th style="width: 180px">Ücret</th><th style="width: 40px"></th></tr></thead>
                        <tbody id="cikis-hizmetleri"></tbody>
                    </table>
                    <?php if (empty($hizmetler)): ?>
                        <div class="alert alert-warning py-2 small mb-2">Bu firma / otopark için tanımlı hizmet yok.</div>
                    <?php else: ?>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="#hizmet-satir" data-target="#cikis-hizmetleri"><i class="mdi mdi-plus me-1"></i>Hizmet Ekle</button>
                    <?php endif; ?>
                    <?php require BASE_PATH . '/views/partials/hizmet_liste_notu.php'; ?>

                    <div class="d-flex justify-content-between align-items-center border-top mt-3 pt-3">
                        <span class="text-muted">Toplam ücret<?= $fg ? ' (depolama + hizmetler)' : ' (hizmetler)' ?></span>
                        <span class="fs-5 fw-bold text-primary" id="toplam-ucret"
                              data-maske="<?= $maske ? '1' : '0' ?>"
                              data-sabit="<?= $fg ? $depTutar : 0 ?>"
                              data-ozel-sabit="<?= $ozelSabit ?>"
                              data-anlasmali-sabit="<?= count(array_filter($eklenenler, fn ($x) => empty($x['bayi_fiyat_degistirebilir']))) ?>"><?php
                            if ($maske) {
                                echo e(\App\Services\HizmetFiyat::bayiToplamOzeti($eklenenler));
                            } else {
                                echo number_format(($fg ? $depTutar : 0) + $eklenenToplam, 2, ',', '.') . ' ₺';
                            }
                        ?></span>
                    </div>
                </div>
            </div>
            <template id="hizmet-satir">
                <tr>
                    <td><select name="hizmet_id[]" class="form-select form-select-sm select2" required>
                        <option value="">Seçiniz</option>
                        <?php foreach ($hizmetler as $h): ?><option value="<?= $h['id'] ?>" data-bayi-degistirebilir="<?= !empty($h['bayi_fiyat_degistirebilir']) ? '1' : '0' ?>" data-tekrar-eklenebilir="<?= !empty($h['tekrar_eklenebilir']) ? '1' : '0' ?>"><?= e($h['ad']) ?><?= empty($h['bayi_fiyat_degistirebilir']) ? ' (kilitli)' : '' ?><?= empty($h['tekrar_eklenebilir']) ? ' (tek sefer)' : '' ?></option><?php endforeach; ?>
                    </select></td>
                    <td><input type="number" name="hizmet_tutar[]" class="form-control form-control-sm text-end" step="0.01" min="0" placeholder="Fiyat belirlenmedi" required></td>
                    <td><button type="button" class="btn btn-sm btn-light text-danger" data-remove-row><i class="mdi mdi-close"></i></button></td>
                </tr>
            </template>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h5>İşlem</h5></div>
                <div class="card-body">
                    <label class="form-label required">Çıkış Tarihi</label>
                    <div class="input-group mb-1">
                        <input type="date" name="hareket_tarihi" id="cikis-tarih" class="form-control"
                               value="<?= e($bugun) ?>" min="<?= e($girisTarih) ?>" max="<?= e($bugun) ?>" required>
                        <input type="time" name="hareket_saati" id="cikis-saat" class="form-control" value="<?= e($simdiSaat) ?>">
                    </div>
                    <div class="form-text mb-3">Giriş: <?= date('d.m.Y H:i', $girisTs) ?> — çıkış bu aralıkta olmalı (ileri tarih yok).</div>
                    <label class="form-label">Personel</label>
                    <select name="teslim_alan_personel" class="form-select select2"><?= Tanim::options(Tanim::liste('personeller'), old('teslim_alan_personel'), 'Seçiniz') ?></select>
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
        const maske = $toplam.data('maske') === 1 || $toplam.data('maske') === '1';
        if (!maske) {
            let t = Number($toplam.data('sabit') || 0);
            $('#cikis-hizmetleri [name="hizmet_tutar[]"]').each(function () { t += Number(this.value || 0); });
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
    $('#cikis-hizmetleri').on('change', '[name="hizmet_id[]"]', function () {
        hesapla();
    }).on('input', '[name="hizmet_tutar[]"]', hesapla);
    $(document).on('click', '#cikis-hizmetleri [data-remove-row]', () => setTimeout(hesapla));
    $(document).on('ajax-form-saved change', () => setTimeout(hesapla, 50));

    const GIRIS = new Date(<?= json_encode(date('c', $girisTs)) ?>);
    const GIRIS_TARIH = <?= json_encode($girisTarih) ?>;
    const GIRIS_SAAT = <?= json_encode($girisSaat) ?>;
    const $tarih = $('#cikis-tarih');
    const $saat = $('#cikis-saat');

    function pad(n) { return String(n).padStart(2, '0'); }
    function simdi() {
        const d = new Date();
        return {
            tarih: d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()),
            saat: pad(d.getHours()) + ':' + pad(d.getMinutes()),
            ts: d.getTime(),
        };
    }
    function secilenTs() {
        const t = $tarih.val();
        const s = $saat.val() || '00:00';
        if (!t) return null;
        return new Date(t + 'T' + s + ':00').getTime();
    }
    function cikisTarihKontrol(sessiz) {
        const now = simdi();
        $tarih.attr('max', now.tarih);
        const ts = secilenTs();
        if (ts === null) return true;
        if (ts < GIRIS.getTime()) {
            if (!sessiz) {
                App.toast('Çıkış tarihi, stoğa girişten önce olamaz (' + GIRIS.toLocaleString('tr-TR') + ').', 'warning');
            }
            $tarih.val(GIRIS_TARIH);
            $saat.val(GIRIS_SAAT);
            return false;
        }
        if (ts > now.ts) {
            if (!sessiz) {
                App.toast('İleri bir tarih/saat seçilemez. En fazla şu anki zaman.', 'warning');
            }
            $tarih.val(now.tarih);
            $saat.val(now.saat);
            return false;
        }
        return true;
    }
    $tarih.add($saat).on('change input', () => cikisTarihKontrol(false));
    $('form[action*="stoktan_cikar"]').on('submit', function (e) {
        if (!cikisTarihKontrol(false)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });
    cikisTarihKontrol(true);
});
</script>
<?php $scripts = ob_get_clean(); ?>
