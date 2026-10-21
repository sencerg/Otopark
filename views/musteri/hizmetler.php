<?php
$tl = static fn ($v) => number_format((float) $v, 2, ',', '.') . ' ₺';
$gunFmt = static fn ($d) => $d ? date('d.m.Y', strtotime((string) $d)) : '—';
$filtreler ??= ['hizmetler' => [], 'otoparklar' => [], 'markalar' => []];
$opt = static function (array $items, string $hepsi = 'Tümü'): string {
    $html = '<option value="">' . e($hepsi) . '</option>';
    foreach ($items as $v) {
        $html .= '<option value="' . e($v) . '">' . e($v) . '</option>';
    }

    return $html;
};
?>
<p class="text-muted mb-3">Araçlarınıza uygulanan ek hizmetler. Tutarlar size özel hizmet fiyatlarınız üzerinden kaydedilmiştir.</p>

<div class="card mb-3">
    <div class="card-body">
        <div class="filter-bar" id="musteri-hizmet-filtre">
            <div>
                <label class="form-label">Ara</label>
                <input type="search" id="hf-q" class="form-control" placeholder="Plaka / şasi" autocomplete="off">
            </div>
            <div>
                <label class="form-label">Hizmet</label>
                <select id="hf-hizmet" class="form-select"><?= $opt($filtreler['hizmetler'], 'Tüm Hizmetler') ?></select>
            </div>
            <div>
                <label class="form-label">Otopark</label>
                <select id="hf-otopark" class="form-select"><?= $opt($filtreler['otoparklar'], 'Tüm Otoparklar') ?></select>
            </div>
            <div>
                <label class="form-label">Marka</label>
                <select id="hf-marka" class="form-select"><?= $opt($filtreler['markalar'], 'Tüm Markalar') ?></select>
            </div>
            <div class="d-flex align-items-end">
                <button type="button" class="btn btn-light w-100" id="hf-temizle"><i class="mdi mdi-filter-off me-1"></i>Temizle</button>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="text-muted small"><span id="hf-adet"><?= count($satirlar) ?></span> kayıt</div>
    <div class="d-flex align-items-center gap-3">
        <div class="fs-5 fw-semibold">Toplam: <span id="hf-toplam"><?= $tl($toplam) ?></span></div>
        <a href="/musteri/excel/hizmetler" class="btn btn-sm btn-success">
            <i class="mdi mdi-microsoft-excel me-1"></i>Excel İndir
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="musteri-hizmet-tablo">
                <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Hizmet</th>
                    <th>Araç</th>
                    <th>Otopark</th>
                    <th>Açıklama</th>
                    <th class="text-end">Tutar</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$satirlar): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Hizmet kaydı yok.</td></tr>
                <?php else: foreach ($satirlar as $r): ?>
                    <tr
                        data-hizmet="<?= e($r['hizmet'] ?? '') ?>"
                        data-otopark="<?= e($r['otopark'] ?? '') ?>"
                        data-marka="<?= e($r['marka'] ?? '') ?>"
                        data-q="<?= e(mb_strtolower(trim(($r['plaka'] ?? '') . ' ' . ($r['sase'] ?? '')), 'UTF-8')) ?>"
                        data-tutar="<?= (float) $r['tutar'] ?>"
                    >
                        <td><?= $gunFmt($r['islem_tarihi']) ?></td>
                        <td><?= e($r['hizmet']) ?></td>
                        <td>
                            <strong><?= e($r['plaka'] ?: '—') ?></strong>
                            <div class="small text-muted font-monospace"><?= e($r['sase']) ?></div>
                            <?php if (!empty($r['marka'])): ?><div class="small text-muted"><?= e(trim($r['marka'] . ' ' . ($r['seri'] ?? ''))) ?></div><?php endif; ?>
                        </td>
                        <td><?= e($r['otopark'] ?? '—') ?></td>
                        <td class="small text-muted"><?= e($r['aciklama'] ?? '') ?></td>
                        <td class="text-end fw-semibold"><?= $tl($r['tutar']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(function () {
    const $hizmet = $('#hf-hizmet'), $otopark = $('#hf-otopark'), $marka = $('#hf-marka'), $q = $('#hf-q');
    const tl = (n) => Number(n).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺';

    if ($('#musteri-hizmet-tablo tbody tr').length && !$('#musteri-hizmet-tablo td[colspan]').length) {
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'musteri-hizmet-tablo') return true;
            const row = settings.aoData[dataIndex].nTr;
            if (!row) return true;
            const $r = $(row);
            const q = ($q.val() || '').toString().trim().toLocaleLowerCase('tr');
            if ($hizmet.val() && $r.data('hizmet') !== $hizmet.val()) return false;
            if ($otopark.val() && $r.data('otopark') !== $otopark.val()) return false;
            if ($marka.val() && $r.data('marka') !== $marka.val()) return false;
            if (q && !(($r.data('q') || '').toString().includes(q))) return false;
            return true;
        });

        const dt = $('#musteri-hizmet-tablo').DataTable({
            order: [[0, 'desc']],
            pageLength: 50,
            dom: 'lrtip'
        });

        function ozet() {
            let adet = 0, toplam = 0;
            dt.rows({ search: 'applied' }).every(function () {
                adet++;
                toplam += Number($(this.node()).data('tutar') || 0);
            });
            $('#hf-adet').text(adet);
            $('#hf-toplam').text(tl(toplam));
        }

        const uygula = () => { dt.draw(); ozet(); };
        $hizmet.add($otopark).add($marka).on('change', uygula);
        $q.on('input', uygula);
        $('#hf-temizle').on('click', function () {
            $hizmet.val(''); $otopark.val(''); $marka.val(''); $q.val('');
            uygula();
        });
        ozet();
    }
});
</script>
<?php $scripts = ob_get_clean(); ?>
