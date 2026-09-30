<?php
/** @var array $dosyalar */
$readonly ??= false;
?>
<?php if ($dosyalar): ?>
    <ul class="file-list mb-3">
        <?php foreach ($dosyalar as $d): ?>
            <li>
                <i class="mdi <?= $d['tur'] === 'fotograf' ? 'mdi-image-outline' : 'mdi-file-document-outline' ?> fs-5 text-primary"></i>
                <a href="<?= e($d['dosya_yolu']) ?>" target="_blank"><?= e($d['tanim'] ?: $d['orijinal_ad']) ?></a>
                <small class="text-muted ms-auto"><?= date('d.m.Y H:i', strtotime($d['created_at'])) ?></small>
                <?php if (!$readonly): ?>
                    <button type="submit" form="dosya-sil-<?= $d['id'] ?>" class="btn btn-sm btn-light text-danger" title="Sil"><i class="mdi mdi-delete-outline"></i></button>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php elseif ($readonly): ?>
    <p class="text-muted small mb-0">Yüklenmiş belge yok.</p>
<?php endif; ?>

<?php if (!$readonly): ?>
    <table class="table table-sm align-middle mb-2">
        <thead><tr><th>Dosya Tanımı</th><th>Dosya</th><th style="width:50px"></th></tr></thead>
        <tbody id="dosya-satirlari">
        <tr>
            <td><input type="text" name="dosya_tanim[]" class="form-control form-control-sm" placeholder="Örn. Ruhsat fotokopisi"></td>
            <td><input type="file" name="dosya[]" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-sm btn-light text-danger" data-remove-row><i class="mdi mdi-delete-outline"></i></button></td>
        </tr>
        </tbody>
    </table>
    <button type="button" class="btn btn-sm btn-outline-primary" data-add-row="#dosya-satir-tpl" data-target="#dosya-satirlari"><i class="mdi mdi-plus"></i> Yeni Kayıt Ekle</button>
    <template id="dosya-satir-tpl">
        <tr>
            <td><input type="text" name="dosya_tanim[]" class="form-control form-control-sm"></td>
            <td><input type="file" name="dosya[]" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-sm btn-light text-danger" data-remove-row><i class="mdi mdi-delete-outline"></i></button></td>
        </tr>
    </template>
<?php endif; ?>
