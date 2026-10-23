<?php
use App\Core\Csrf;

$m = $musteri ?? [];
$id = (int) ($m['id'] ?? 0);
$yaz = !empty($admin);
$hizmetJson = array_map(static fn ($h) => [
    'id' => (int) $h['id'],
    'ad' => $h['ad'],
], $hizmetler);
$bayiJson = array_map(static fn ($b) => [
    'id' => (int) $b['id'],
    'ad' => $b['ad'],
    'sehir' => $b['sehir'] ?? '',
], $bayiler ?? []);
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <a href="/tanimlamalar#sekme-musteri" class="btn btn-sm btn-light"><i class="mdi mdi-arrow-left me-1"></i>Müşteri listesi</a>
    </div>
    <?php if (!$yeni): ?>
        <div class="text-muted small">Firma ID: <?= $id ?></div>
    <?php endif; ?>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
        <button class="nav-link <?= $sekme === 'bilgi' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#sekme-bilgi" type="button">
            <i class="mdi mdi-domain me-1"></i>Firma bilgileri
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $sekme === 'otopark' ? 'active' : '' ?> <?= $yeni ? 'disabled' : '' ?>" data-bs-toggle="tab" data-bs-target="#sekme-otopark" type="button" <?= $yeni ? 'disabled title="Önce firmayı kaydedin"' : '' ?>>
            <i class="mdi mdi-parking me-1"></i>Otopark ücretleri
            <?php if (!$yeni): ?><span class="badge badge-soft-primary ms-1"><?= (int) ($otoparkOzelSayisi ?? 0) ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link <?= $sekme === 'fiyat' ? 'active' : '' ?> <?= $yeni ? 'disabled' : '' ?>" data-bs-toggle="tab" data-bs-target="#sekme-fiyat" type="button" <?= $yeni ? 'disabled title="Önce firmayı kaydedin"' : '' ?>>
            <i class="mdi mdi-cash-multiple me-1"></i>Hizmet fiyatları
            <?php if (!$yeni): ?><span class="badge badge-soft-primary ms-1" id="fiyat-adet"><?= count($fiyatlar) ?></span><?php endif; ?>
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade <?= $sekme === 'bilgi' ? 'show active' : '' ?>" id="sekme-bilgi">
        <div class="card">
            <div class="card-header"><h5><?= $yeni ? 'Yeni müşteri' : 'Firma bilgileri' ?></h5></div>
            <div class="card-body">
                <form method="post" action="/tanimlamalar/musteri/kaydet" data-ajax data-tip="musteri">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="sayfa" value="1">
                    <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Firma Adı</label>
                            <input type="text" name="ad" class="form-control" required value="<?= e($m['ad'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Vergi No</label>
                            <input type="text" name="vergi_no" class="form-control" value="<?= e($m['vergi_no'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Yetkili Kişi</label>
                            <input type="text" name="yetkili" class="form-control" value="<?= e($m['yetkili'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="telefon" class="form-control" value="<?= e($m['telefon'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="eposta" class="form-control" value="<?= e($m['eposta'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Adres</label>
                            <textarea name="adres" class="form-control" rows="2"><?= e($m['adres'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input type="checkbox" class="form-check-input" name="aktif" value="1" id="musteri-aktif" <?= !isset($m['aktif']) || $m['aktif'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="musteri-aktif">Aktif</label>
                            </div>
                        </div>
                    </div>
                    <?php if ($yaz): ?>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i><?= $yeni ? 'Kaydet ve devam et' : 'Bilgileri kaydet' ?></button>
                        </div>
                    <?php endif; ?>
                </form>
                <?php if ($yeni): ?>
                    <div class="alert alert-info py-2 small mt-3 mb-0"><i class="mdi mdi-information-outline me-1"></i>Firmayı kaydettikten sonra <b>Otopark ücretleri</b> ve <b>Hizmet fiyatları</b> sekmelerinden bu firmaya özel tarifeleri girebilirsiniz.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="tab-pane fade <?= $sekme === 'otopark' ? 'show active' : '' ?>" id="sekme-otopark">
        <?php if ($yeni): ?>
            <div class="alert alert-warning">Önce firma bilgilerini kaydedin.</div>
        <?php else: ?>
            <div class="card">
                <div class="card-header"><h5>Bu firmaya özel otopark (depolama) ücretleri</h5></div>
                <div class="card-body">
                    <div class="alert alert-info py-2 small"><i class="mdi mdi-information-outline me-1"></i>
                        Tüm aktif otoparklar listelenir. Günlük depolama ücreti <b>yalnızca bu firmaya</b> özeldir; boş bırakılan otoparklarda
                        <b>fiyat belirlenmedi</b> kabul edilir. Genel / standart otopark fiyatı yoktur.
                    </div>
                    <form method="post" action="/tanimlamalar/musteri/kaydet" data-ajax data-tip="musteri" id="form-otopark">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="sayfa" value="1">
                        <input type="hidden" name="otopark_senkron" value="1">
                        <input type="hidden" name="ad" value="<?= e($m['ad'] ?? '') ?>">
                        <input type="hidden" name="vergi_no" value="<?= e($m['vergi_no'] ?? '') ?>">
                        <input type="hidden" name="yetkili" value="<?= e($m['yetkili'] ?? '') ?>">
                        <input type="hidden" name="telefon" value="<?= e($m['telefon'] ?? '') ?>">
                        <input type="hidden" name="eposta" value="<?= e($m['eposta'] ?? '') ?>">
                        <input type="hidden" name="adres" value="<?= e($m['adres'] ?? '') ?>">
                        <?php if (!empty($m['aktif'])): ?><input type="hidden" name="aktif" value="1"><?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="tablo-otopark">
                                <thead>
                                    <tr>
                                        <th>Otopark</th>
                                        <th>Şehir</th>
                                        <th class="text-end" style="width:14rem">Firma günlük (₺)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($otoparkFiyatlari)): ?>
                                        <tr><td colspan="3" class="text-center text-muted py-4">Aktif otopark yok.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($otoparkFiyatlari as $o):
                                            $bid = (int) $o['id'];
                                            $firma = $o['firma_fiyat'];
                                            ?>
                                            <tr>
                                                <td><b><?= e($o['ad']) ?></b></td>
                                                <td class="text-muted"><?= e($o['sehir'] ?: '—') ?></td>
                                                <td>
                                                    <input type="hidden" name="otopark_bayi_id[]" value="<?= $bid ?>">
                                                    <input type="number" step="0.01" min="0" name="otopark_tutar[]"
                                                           class="form-control text-end"
                                                           value="<?= $firma !== null && $firma !== '' ? e((string) $firma) : '' ?>"
                                                           placeholder="Fiyat belirlenmedi"
                                                           <?= $yaz ? '' : 'disabled' ?>>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($yaz && !empty($otoparkFiyatlari)): ?>
                            <button type="submit" class="btn btn-primary mt-2"><i class="mdi mdi-content-save me-1"></i>Otopark ücretlerini kaydet</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="tab-pane fade <?= $sekme === 'fiyat' ? 'show active' : '' ?>" id="sekme-fiyat">
        <?php if ($yeni): ?>
            <div class="alert alert-warning">Önce firma bilgilerini kaydedin.</div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h5>Bu firmaya özel hizmet ücretleri</h5>
                    <?php if ($yaz): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="fiyat-satir-ekle"><i class="mdi mdi-plus me-1"></i>Satır ekle</button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="alert alert-info py-2 small"><i class="mdi mdi-information-outline me-1"></i>
                        Aynı hizmet farklı otoparklarda farklı ücret alabilir. Her satırda <b>otopark + hizmet</b> seçin.
                        Tanımsız çiftler için <b>fiyat belirlenmedi</b> kabul edilir.
                    </div>
                    <form method="post" action="/tanimlamalar/musteri/kaydet" data-ajax data-tip="musteri" id="form-fiyat">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="sayfa" value="1">
                        <input type="hidden" name="fiyat_senkron" value="1">
                        <input type="hidden" name="ad" value="<?= e($m['ad'] ?? '') ?>">
                        <input type="hidden" name="vergi_no" value="<?= e($m['vergi_no'] ?? '') ?>">
                        <input type="hidden" name="yetkili" value="<?= e($m['yetkili'] ?? '') ?>">
                        <input type="hidden" name="telefon" value="<?= e($m['telefon'] ?? '') ?>">
                        <input type="hidden" name="eposta" value="<?= e($m['eposta'] ?? '') ?>">
                        <input type="hidden" name="adres" value="<?= e($m['adres'] ?? '') ?>">
                        <?php if (!empty($m['aktif'])): ?><input type="hidden" name="aktif" value="1"><?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="tablo-fiyat">
                                <thead>
                                    <tr>
                                        <th style="min-width:14rem">Otopark</th>
                                        <th style="min-width:14rem">Hizmet</th>
                                        <th class="text-end" style="width:12rem">Firma ücreti (₺)</th>
                                        <?php if ($yaz): ?><th style="width:3rem"></th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!$fiyatlar): ?>
                                        <tr class="text-muted" data-bos-satir><td colspan="4" class="text-center py-4">Henüz fiyat yok. “Satır ekle” ile ekleyin.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($fiyatlar as $f):
                                        $hid = (int) $f['maliyet_tipi_id'];
                                        $bid = (int) ($f['bayi_id'] ?? 0);
                                        ?>
                                        <tr data-fiyat-satir>
                                            <td>
                                                <select name="fiyat_bayi_id[]" class="form-select" required <?= $yaz ? '' : 'disabled' ?>>
                                                    <option value="">Otopark seçiniz</option>
                                                    <?php foreach ($bayiler ?? [] as $b): ?>
                                                        <option value="<?= (int) $b['id'] ?>" <?= (int) $b['id'] === $bid ? 'selected' : '' ?>>
                                                            <?= e($b['ad']) ?><?= !empty($b['sehir']) ? ' — ' . e($b['sehir']) : '' ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="fiyat_hizmet_id[]" class="form-select" required <?= $yaz ? '' : 'disabled' ?>>
                                                    <option value="">Hizmet seçiniz</option>
                                                    <?php foreach ($hizmetler as $h): ?>
                                                        <option value="<?= (int) $h['id'] ?>" <?= (int) $h['id'] === $hid ? 'selected' : '' ?>>
                                                            <?= e($h['ad']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="fiyat_tutar[]"
                                                       class="form-control text-end" value="<?= e((string) $f['tutar']) ?>"
                                                       required <?= $yaz ? '' : 'disabled' ?>
                                                       placeholder="Firma ücreti">
                                            </td>
                                            <?php if ($yaz): ?>
                                                <td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger" data-fiyat-sil title="Kaldır"><i class="mdi mdi-close"></i></button></td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($yaz): ?>
                            <button type="submit" class="btn btn-primary mt-2"><i class="mdi mdi-content-save me-1"></i>Hizmet fiyatlarını kaydet</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$yeni && $yaz): ?>
<template id="tpl-fiyat-satir">
    <tr data-fiyat-satir>
        <td>
            <select name="fiyat_bayi_id[]" class="form-select" required>
                <option value="">Otopark seçiniz</option>
            </select>
        </td>
        <td>
            <select name="fiyat_hizmet_id[]" class="form-select" required>
                <option value="">Hizmet seçiniz</option>
            </select>
        </td>
        <td><input type="number" step="0.01" min="0" name="fiyat_tutar[]" class="form-control text-end" required placeholder="Firma ücreti"></td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger" data-fiyat-sil title="Kaldır"><i class="mdi mdi-close"></i></button></td>
    </tr>
</template>
<?php endif; ?>

<?php ob_start(); ?>
<script>
$(function () {
    const HIZMETLER = <?= json_encode($hizmetJson, JSON_UNESCAPED_UNICODE) ?>;
    const BAYILER = <?= json_encode($bayiJson, JSON_UNESCAPED_UNICODE) ?>;
    const yaz = <?= $yaz ? 'true' : 'false' ?>;
    if (!yaz || !HIZMETLER.length || !BAYILER.length) return;

    const $tbody = $('#tablo-fiyat tbody');
    const tpl = document.getElementById('tpl-fiyat-satir');

    function ciftAnahtar(bayiId, hizmetId) {
        return String(bayiId || '') + ':' + String(hizmetId || '');
    }

    function kullanilanCiftler(haricTr) {
        const ids = new Set();
        $tbody.find('[data-fiyat-satir]').each(function () {
            if (this === haricTr) return;
            const b = $(this).find('[name="fiyat_bayi_id[]"]').val();
            const h = $(this).find('[name="fiyat_hizmet_id[]"]').val();
            if (b && h) ids.add(ciftAnahtar(b, h));
        });
        return ids;
    }

    function bayiOptionDoldur(select, seciliId) {
        const $sel = $(select);
        const mevcut = seciliId || $sel.val() || '';
        $sel.empty().append('<option value="">Otopark seçiniz</option>');
        BAYILER.forEach((b) => {
            const id = String(b.id);
            const label = b.sehir ? (b.ad + ' — ' + b.sehir) : b.ad;
            const $opt = $('<option></option>').val(id).text(label);
            if (id === String(mevcut)) $opt.prop('selected', true);
            $sel.append($opt);
        });
        if (mevcut) $sel.val(mevcut);
    }

    function hizmetOptionDoldur(select, seciliId, bayiId, haricTr) {
        const kullanilan = kullanilanCiftler(haricTr);
        const $sel = $(select);
        const mevcut = seciliId || $sel.val() || '';
        $sel.empty().append('<option value="">Hizmet seçiniz</option>');
        HIZMETLER.forEach((h) => {
            const id = String(h.id);
            const key = ciftAnahtar(bayiId, id);
            if (bayiId && kullanilan.has(key) && id !== String(mevcut)) return;
            const $opt = $('<option></option>').val(id).text(h.ad);
            if (id === String(mevcut)) $opt.prop('selected', true);
            $sel.append($opt);
        });
        if (mevcut) $sel.val(mevcut);
    }

    function satirYenile($tr) {
        const bayiSel = $tr.find('[name="fiyat_bayi_id[]"]')[0];
        const hizmetSel = $tr.find('[name="fiyat_hizmet_id[]"]')[0];
        bayiOptionDoldur(bayiSel, bayiSel.value);
        hizmetOptionDoldur(hizmetSel, hizmetSel.value, bayiSel.value, $tr[0]);
    }

    function tumSelectleriYenile() {
        $tbody.find('[data-fiyat-satir]').each(function () {
            satirYenile($(this));
        });
        $('#fiyat-adet').text($tbody.find('[data-fiyat-satir]').length);
    }

    function satirEkle(bayiId, hizmetId, tutar) {
        if (!tpl) return;
        $tbody.find('[data-bos-satir]').remove();
        const node = tpl.content.cloneNode(true);
        $tbody.append(node);
        const $tr = $tbody.find('[data-fiyat-satir]').last();
        bayiOptionDoldur($tr.find('[name="fiyat_bayi_id[]"]')[0], bayiId || '');
        hizmetOptionDoldur($tr.find('[name="fiyat_hizmet_id[]"]')[0], hizmetId || '', bayiId || '', $tr[0]);
        if (tutar !== undefined && tutar !== null && tutar !== '') {
            $tr.find('[name="fiyat_tutar[]"]').val(tutar);
        }
        tumSelectleriYenile();
        return $tr;
    }

    $tbody.find('[data-fiyat-satir]').each(function () {
        satirYenile($(this));
    });

    $('#fiyat-satir-ekle').on('click', function () {
        const max = BAYILER.length * HIZMETLER.length;
        if ($tbody.find('[data-fiyat-satir]').length >= max) {
            App.toast('Tüm otopark × hizmet kombinasyonları eklendi.', 'warning');
            return;
        }
        const $tr = satirEkle();
        $tr.find('[name="fiyat_bayi_id[]"]').trigger('focus');
    });

    $(document).on('click', '#tablo-fiyat [data-fiyat-sil]', function () {
        $(this).closest('tr').remove();
        if (!$tbody.find('[data-fiyat-satir]').length) {
            $tbody.html('<tr class="text-muted" data-bos-satir><td colspan="4" class="text-center py-4">Henüz fiyat yok. “Satır ekle” ile ekleyin.</td></tr>');
            $('#fiyat-adet').text('0');
        } else {
            tumSelectleriYenile();
        }
    });

    $(document).on('change', '#tablo-fiyat select[name="fiyat_bayi_id[]"], #tablo-fiyat select[name="fiyat_hizmet_id[]"]', function () {
        tumSelectleriYenile();
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
