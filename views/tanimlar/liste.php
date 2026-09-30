<?php
use App\Core\Csrf;
use App\Core\Tanim;

if ($yazabilir) {
    $headerActions = '<button class="btn btn-primary" data-yeni><i class="mdi mdi-plus me-1"></i>Yeni Kayıt</button>';
}
$gorunen = array_filter($t['alanlar'], fn ($a) => $a[1] !== 'password');
?>
<div class="card">
    <div class="card-header"><h5><?= e($t['baslik']) ?></h5>
        <?php if (!$yazabilir): ?><span class="badge badge-soft-secondary">Sadece görüntüleme</span><?php endif; ?></div>
    <div class="card-body">
        <div class="d-flex gap-2 mb-2">
            <input type="search" id="dt-search" class="form-control" placeholder="Ara...">
        </div>
        <div class="table-responsive">
            <table id="liste" class="table table-hover w-100">
                <thead><tr><th>#</th><?php foreach ($gorunen as $a): ?><th><?= e($a[0]) ?></th><?php endforeach; ?><?php if ($yazabilir): ?><th>İşlem</th><?php endif; ?></tr></thead>
            </table>
        </div>
    </div>
</div>

<?php if ($yazabilir): ?>
<div class="modal fade" id="tanimModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="post" action="/arac_yonetimi/tanimlar/<?= $tip ?>/kaydet" data-ajax>
            <?= Csrf::field() ?>
            <input type="hidden" name="id">
            <div class="modal-header"><h5 class="modal-title"><?= e($t['baslik']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <?php foreach ($t['alanlar'] as $alan => [$etiket, $tur, $zorunlu]): ?>
                    <div class="mb-3">
                        <?php if ($tur === 'bool'): ?>
                            <div class="form-check form-switch"><input type="checkbox" class="form-check-input" name="<?= $alan ?>" value="1" id="f-<?= $alan ?>" data-bool checked><label class="form-check-label" for="f-<?= $alan ?>"><?= e($etiket) ?></label></div>
                        <?php else: ?>
                            <label class="form-label <?= $zorunlu ? 'required' : '' ?>"><?= e($etiket) ?></label>
                            <?php if ($tur === 'select'): ?>
                                <select name="<?= $alan ?>" class="form-select <?= count($secenekler[$alan]) > 15 ? 'select2' : '' ?>" <?= $zorunlu ? 'required' : '' ?>><?= Tanim::options($secenekler[$alan]) ?></select>
                            <?php elseif ($tur === 'textarea'): ?>
                                <textarea name="<?= $alan ?>" class="form-control" rows="2"></textarea>
                            <?php else: ?>
                                <input type="<?= $tur === 'password' ? 'password' : (in_array($tur, ['number', 'decimal'], true) ? 'number' : 'text') ?>" <?= $tur === 'decimal' ? 'step="0.01" min="0"' : '' ?> name="<?= $alan ?>" class="form-control" <?= $zorunlu ? 'required' : '' ?> autocomplete="new-password">
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Kaydet</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php ob_start(); ?>
<script>
$(function () {
    const alanlar = <?= json_encode(array_map(fn ($a) => ['tur' => $a[1], 'secim' => isset($a[3])], $gorunen), JSON_UNESCAPED_UNICODE) ?>;
    const columns = [{ data: 'id' }];
    Object.entries(alanlar).forEach(([alan, a]) => columns.push({
        data: alan,
        render: (v, _, r) => a.tur === 'bool' ? (v ? App.badge('Evet', 'success') : App.badge('Hayır', 'secondary'))
            : a.secim ? App.esc(r[alan + '_ad']) : a.tur === 'decimal' ? App.money(v) : App.esc(v ?? '-'),
    }));
    <?php if ($yazabilir): ?>
    columns.push({ data: 'id', orderable: false, render: (id) => App.actions([
        { url: '#', icon: 'mdi-pencil', color: 'primary', title: 'Düzenle', attrs: `data-duzenle="${id}"` },
        { url: '#', icon: 'mdi-delete', color: 'danger', title: 'Sil', attrs: `data-sil="${id}"` },
    ]) });
    <?php endif; ?>
    App.table('#liste', { url: '/arac_yonetimi/tanimlar/<?= $tip ?>/data', columns, order: [] });

    const $m = $('#tanimModal');
    const $f = $m.find('form');
    const ac = () => bootstrap.Modal.getOrCreateInstance($m[0]).show();
    $('[data-yeni]').on('click', () => {
        $f[0].reset();
        $f.find('[name=id]').val('');
        $f.find('[data-bool]').prop('checked', true);
        $f.find('select').val('').trigger('change.select2');
        ac();
    });
    $(document).on('click', '[data-duzenle]', function (e) {
        e.preventDefault();
        $.getJSON('/arac_yonetimi/tanimlar/<?= $tip ?>/getir/' + this.dataset.duzenle, (r) => {
            $f[0].reset();
            Object.entries(r.data).forEach(([k, v]) => {
                const $el = $f.find(`[name="${k}"]`);
                if ($el.is(':checkbox')) $el.prop('checked', !!v);
                else $el.val(v ?? '').trigger('change.select2');
            });
            ac();
        });
    });
    $(document).on('click', '[data-sil]', function (e) {
        e.preventDefault();
        const id = this.dataset.sil;
        Swal.fire({ icon: 'warning', title: 'Kayıt silinsin mi?', showCancelButton: true, confirmButtonText: 'Sil', cancelButtonText: 'Vazgeç', confirmButtonColor: '#dc2626' }).then((r) => {
            if (!r.isConfirmed) return;
            $.post('/arac_yonetimi/tanimlar/<?= $tip ?>/sil/' + id).done((res) => { App.toast(res.message); $('#liste').DataTable().ajax.reload(null, false); })
                .fail((x) => App.error(x.responseJSON?.message));
        });
    });
});
</script>
<?php $scripts = ob_get_clean(); ?>
