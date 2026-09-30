<?php
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Tanim;

$yeni = $ie === null;
$o = $_SESSION['_old'] ?? [];
$v = fn (string $key, ?string $col = null) => $o[$key] ?? ($ie[$col ?? $key] ?? null);
$departmanlar = isset($o['sorumlu_departman']) ? (array) $o['sorumlu_departman'] : $departmanlar;
$personeller = isset($o['sorumlu_personel']) ? (array) $o['sorumlu_personel'] : $personeller;
$personelListe = $departmanlar
    ? array_column(Database::fetchAll('SELECT id, ad_soyad FROM personeller WHERE aktif AND departman_id = ANY(:d::int[]) ORDER BY ad_soyad', ['d' => '{' . implode(',', array_map('intval', $departmanlar)) . '}']), 'ad_soyad', 'id')
    : [];
$aracDurumlari = array_slice(Tanim::IS_EMRI_DURUM, 0, 4, true);
?>
<form method="post" action="<?= $yeni ? '/gorev_yonetimi/save' : '/gorev_yonetimi/update/' . $ie['id'] ?>" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5>İş Emri Notu</h5>
                    <?php if (!$yeni): ?><span class="badge badge-soft-<?= Tanim::IS_EMRI_DURUM_RENK[$ie['durum']] ?>"><?= Tanim::IS_EMRI_DURUM[$ie['durum']] ?></span><?php endif; ?></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label">İş Emri Kodu</label><input type="text" name="gorev_kodu" class="form-control" value="<?= e($v('gorev_kodu', 'kod') ?? $kod) ?>"></div>
                        <div class="col-md-3"><label class="form-label required">İş Emri Türü</label>
                            <select name="gorev_turu" class="form-select select2" required><?= Tanim::options(Tanim::liste('maliyet_tipleri'), $v('gorev_turu', 'maliyet_tipi_id')) ?></select></div>
                        <div class="col-md-3"><label class="form-label required">Müşteri</label>
                            <select name="musteri_id" class="form-select select2" required><?= Tanim::options(Tanim::liste('musteriler'), $v('musteri_id')) ?></select></div>
                        <div class="col-md-3"><label class="form-label">İş Emri Talep Tarihi</label><input type="date" name="talep_tarihi" class="form-control" value="<?= e($v('talep_tarihi') ?? date('Y-m-d')) ?>"></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5>Excel İle Veri Aktarımı</h5><a href="/gorev_yonetimi/sablon" class="btn btn-sm btn-success"><i class="mdi mdi-microsoft-excel me-1"></i>Şablon</a></div>
                <div class="card-body">
                    <input type="file" name="import_file" class="form-control" accept=".csv,.txt">
                    <div class="form-text">Tek sütunlu (<code>sase</code>) CSV dosyasındaki araçlar kayıt sırasında listeye eklenir.</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5>Hazırlanacak Araç Listesi</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="#arac-satir-tpl" data-target="#arac-satirlari"><i class="mdi mdi-plus"></i> Araç Ekle</button></div>
                <div class="card-body">
                    <table class="table align-middle">
                        <thead><tr><th style="width:50%">Şase</th><th>Durum</th><th>Bayi</th><th></th></tr></thead>
                        <tbody id="arac-satirlari">
                        <?php foreach ($araclar as $a): ?>
                            <tr class="row-item">
                                <td>
                                    <select name="arac_id[]" class="form-select arac-select"><option value="<?= $a['arac_id'] ?>" selected><?= e($a['sase'] . ' — ' . ($a['plaka'] ?: '-') . ' — ' . trim(($a['marka'] ?? '') . ' ' . ($a['seri'] ?? ''))) ?></option></select>
                                    <?php if ($a['maliyet_yazildi']): ?><div class="small text-success mt-1"><i class="mdi mdi-check-circle"></i> Maliyet yazıldı</div><?php endif; ?>
                                </td>
                                <td><select name="a_durum[]" class="form-select"><?= Tanim::options($aracDurumlari, $a['durum']) ?></select></td>
                                <td><?= e($a['bayi'] ?: '-') ?></td>
                                <td><button type="button" class="btn btn-sm btn-light text-danger" data-remove-row><i class="mdi mdi-delete-outline"></i></button></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <template id="arac-satir-tpl">
                        <tr class="row-item">
                            <td><select name="arac_id[]" class="form-select arac-select"></select></td>
                            <td><select name="a_durum[]" class="form-select"><?= Tanim::options($aracDurumlari, 1) ?></select></td>
                            <td class="text-muted small">Araç seçiniz</td>
                            <td><button type="button" class="btn btn-sm btn-light text-danger" data-remove-row><i class="mdi mdi-delete-outline"></i></button></td>
                        </tr>
                    </template>
                    <?php if (!$araclar): ?><p class="text-muted small mb-0" id="arac-bos">Araç eklemek için "Araç Ekle" butonunu kullanın veya Excel ile aktarın.</p><?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5>Temel Bilgiler</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Sorumlu Departman</label>
                            <select name="sorumlu_departman[]" id="sorumlu-departman" class="form-select select2" multiple><?= Tanim::options(Tanim::liste('departmanlar'), $departmanlar, null) ?></select></div>
                        <div class="col-md-6"><label class="form-label">Sorumlu Personel</label>
                            <select name="sorumlu_personel[]" id="sorumlu-personel" class="form-select select2" multiple data-placeholder="Önce Departman Seçiniz"><?= Tanim::options($personelListe, $personeller, null) ?></select></div>
                        <div class="col-12"><label class="form-label">Detaylar</label><textarea name="detaylar" class="form-control" rows="4"><?= e($v('detaylar')) ?></textarea></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h5>Detaylar</h5></div>
                <div class="card-body">
                    <div class="mb-2"><label class="form-label">İstenen Tarih</label><input type="date" name="istenen_tarih" class="form-control" value="<?= e($v('istenen_tarih')) ?>"></div>
                    <div class="mb-2"><label class="form-label">Durumu</label><select name="durum" class="form-select"><?= Tanim::options(Tanim::IS_EMRI_DURUM, $v('durum') ?? 1, null) ?></select></div>
                    <div class="mb-2"><label class="form-label">Tamamlanma Tarihi</label><input type="date" name="tamamlanma_tarihi" class="form-control" value="<?= e($v('tamamlanma_tarihi')) ?>"></div>
                    <div class="mb-2"><label class="form-label">Yorum</label><textarea name="yorum" class="form-control" rows="3"><?= e($v('yorum')) ?></textarea></div>
                    <div class="alert alert-info small mb-0"><i class="mdi mdi-information-outline"></i> Durumu <b>Tamamlandı</b> olan araçlara, iş emri türünün varsayılan tutarı kadar ek maliyet otomatik yazılır (her araç için bir kez).</div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5>Belgeler</h5></div>
                <div class="card-body"><?php $readonly = false; require BASE_PATH . '/views/partials/dosyalar.php'; ?></div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 mb-4"><i class="mdi mdi-content-save me-1"></i> <?= $yeni ? 'Kaydet' : 'Güncelle' ?></button>
        </div>
    </div>
</form>
<?php require BASE_PATH . '/views/partials/dosya_sil_formlari.php'; ?>

<?php ob_start(); ?>
<script>
$(function () {
    $('#sorumlu-departman').on('change', function () {
        const $p = $('#sorumlu-personel');
        const secili = ($p.val() || []).map(String);
        $.getJSON('/api/personeller', { departman: $(this).val() || [] }, (d) => {
            $p.empty();
            Object.entries(d).forEach(([id, ad]) => $p.append(new Option(ad, id, false, secili.includes(id))));
            $p.trigger('change.select2');
        });
    });
    $(document).on('click', '[data-add-row="#arac-satir-tpl"]', () => $('#arac-bos').remove());
    $('#arac-satirlari').on('select2:select', '.arac-select', function (e) {
        const dup = $('#arac-satirlari .arac-select').not(this).filter((_, el) => el.value === this.value).length;
        if (dup) { App.error('Bu araç listede zaten var.'); $(this).val(null).trigger('change'); }
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
