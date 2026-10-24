<?php
use App\Core\Auth;
use App\Core\Tanim;

$f = $filtreler ?? [];
$alanlar = $alanlar ?? [];
$rapor = $rapor ?? [];
?>
<div class="card mb-3">
    <div class="card-header">
        <h5><?= e($rapor['ad'] ?? $pageTitle) ?></h5>
        <a href="/raporlar" class="btn btn-sm btn-light"><i class="mdi mdi-arrow-left me-1"></i>Raporlar</a>
    </div>
    <div class="card-body">
        <?php if (!empty($rapor['aciklama'])): ?>
            <p class="text-muted small mb-3"><?= e($rapor['aciklama']) ?></p>
        <?php endif; ?>

        <form method="get" action="<?= e($formAction) ?>" class="filter-bar filter-scope align-items-end">
            <?php if (in_array('tarih', $alanlar, true)): ?>
                <div><label class="form-label">Başlangıç</label><input type="date" name="baslangic" class="form-control" value="<?= e($f['baslangic'] ?? date('Y-m-01')) ?>" required></div>
                <div><label class="form-label">Bitiş</label><input type="date" name="bitis" class="form-control" value="<?= e($f['bitis'] ?? date('Y-m-d')) ?>" required></div>
            <?php endif; ?>
            <?php if (in_array('musteri', $alanlar, true)): ?>
                <div><label class="form-label">Müşteri</label><select name="musteri_id" class="form-select select2"><?= Tanim::options(Tanim::liste('musteriler'), $f['musteri_id'] ?? null, 'Tüm Müşteriler') ?></select></div>
            <?php endif; ?>
            <?php if (in_array('lokasyon', $alanlar, true) && !Auth::bayiId()): ?>
                <div><label class="form-label">Lokasyon</label><select name="bayi_id" class="form-select"><?= Tanim::options(Tanim::liste('bayiler'), $f['bayi_id'] ?? null, 'Tüm Lokasyonlar') ?></select></div>
            <?php endif; ?>
            <div class="d-flex gap-2">
                <button type="submit" name="hazirla" value="1" class="btn btn-primary"><i class="mdi mdi-file-chart-outline me-1"></i>Raporu hazırla</button>
                <?php if (!empty($hazir)): ?>
                    <button type="submit" name="export" value="1" class="btn btn-success"><i class="mdi mdi-microsoft-excel me-1"></i>Excel indir</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($hazir)): ?>
    <div class="row g-3 mb-3">
        <?php
        $kutular = match (true) {
            isset($ozet['adet']) => [
                ['Araç', $ozet['adet'] ?? 0, '#2563eb'],
                ['Firma', $ozet['firma'] ?? 0, '#10b981'],
                ['Lokasyon', $ozet['lokasyon'] ?? 0, '#f59e0b'],
            ],
            isset($ozet['giren']) => [
                ['Giren', $ozet['giren'] ?? 0, '#10b981'],
                ['Çıkan', $ozet['cikan'] ?? 0, '#ef4444'],
            ],
            default => [],
        };
        foreach ($kutular as [$ad, $n, $renk]): ?>
            <div class="col-6 col-md-3">
                <div class="mini-stat" style="background:var(--hover);box-shadow:none">
                    <span><?= e($ad) ?></span>
                    <strong style="color:<?= e($renk) ?>"><?= number_format((float) $n, 0, ',', '.') ?></strong>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="card-header"><h5>Sonuç</h5><span class="badge badge-soft-secondary"><?= count($satirlar) ?> satır</span></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                    <tr>
                        <?php foreach ($kolonlar as $etiket): ?>
                            <th><?= e($etiket) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($satirlar as $row): ?>
                        <tr>
                            <?php foreach (array_keys($kolonlar) as $alan): ?>
                                <td><?= e((string) ($row[$alan] ?? '—')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$satirlar): ?>
                        <tr><td colspan="<?= count($kolonlar) ?>" class="text-center text-muted py-4">Seçilen kriterlere uygun kayıt yok.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="mdi mdi-filter-cog-outline display-6 d-block mb-2"></i>
        Filtreleri seçip <b>Raporu hazırla</b> ile sonucu oluşturun.
    </div></div>
<?php endif; ?>
