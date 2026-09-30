<?php
use App\Core\Auth;
use App\Core\Tanim;

$tarihEtiketi ??= 'İşlem Tarihi';
$g = fn (string $k) => $_GET[$k] ?? null;
?>
<div class="filter-bar filter-scope">
    <div><label class="form-label">Marka</label><select name="marka_id" data-filter="marka_id" class="form-select select2"><?= Tanim::options(Tanim::liste('markalar'), $g('marka_id'), 'Tüm Markalar') ?></select></div>
    <div><label class="form-label">Seri</label><select name="seri_id" data-filter="seri_id" class="form-select select2"><?= $g('marka_id') ? Tanim::options(Tanim::seriler((int) $g('marka_id')), $g('seri_id'), 'Tüm Seriler') : '<option value="">Önce Marka Seçiniz</option>' ?></select></div>
    <div><label class="form-label">Müşteri</label><select data-filter="musteri_id" class="form-select select2"><?= Tanim::options(Tanim::liste('musteriler'), $g('musteri_id'), 'Tüm Müşteriler') ?></select></div>
    <div><label class="form-label">Araç Tipi</label><select data-filter="arac_tipi_id" class="form-select"><?= Tanim::options(Tanim::liste('arac_tipleri'), $g('arac_tipi_id'), 'Tümü') ?></select></div>
    <?php if (!empty($lokasyonTuruFiltre)): ?>
        <div><label class="form-label">Lokasyon Türü</label><select data-filter="lokasyon_turu" class="form-select"><?= Tanim::options(Tanim::LOKASYON_TURU, $g('lokasyon_turu'), 'Tümü') ?></select></div>
    <?php endif; ?>
    <?php if (!Auth::bayiId()): ?>
        <div><label class="form-label">Lokasyon</label><select data-filter="bayi_id" class="form-select"><?= Tanim::options(Tanim::liste('bayiler'), $g('bayi_id'), 'Tüm Lokasyonlar') ?></select></div>
    <?php endif; ?>
    <div><label class="form-label"><?= e($tarihEtiketi) ?> Başlangıç</label><input type="date" data-filter="baslangic" class="form-control" value="<?= e($g('baslangic') ?? $g('kayit_tarihi')) ?>"></div>
    <div><label class="form-label"><?= e($tarihEtiketi) ?> Bitiş</label><input type="date" data-filter="bitis" class="form-control" value="<?= e($g('bitis') ?? $g('kayit_tarihi')) ?>"></div>
</div>
