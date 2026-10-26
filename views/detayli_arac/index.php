<?php
use App\Core\Csrf;

$adimAdlari = array_column($adimlar, 'ad', 'kod');
$eksAdimlari = [
    'hasar' => 'Hasar',
    'mekanik' => 'Mekanik',
    'foto' => 'Foto',
    'lokasyon' => 'Lokasyon',
    'kondisyon' => 'Kondisyon',
];
require __DIR__ . '/_adimlar.php';
?>
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h5>Teslim Alma · Kontrol</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Şasi kontrolü → taraflar → araç (stoğa kayıt) → ekspertiz.
                    Araç listesinde «Detaylı» rozetiyle görünür.
                </p>
                <form method="post" action="/detayli-arac/baslat">
                    <?= Csrf::field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Şasi Numarası</label>
                            <input type="text" name="sase" class="form-control text-uppercase sase-input" maxlength="17" minlength="17"
                                   pattern="[A-HJ-NPR-Za-hj-npr-z0-9]{17}" required autocomplete="off" spellcheck="false"
                                   value="<?= e(old('sase')) ?>" placeholder="17 haneli şasi">
                            <div class="form-text">17 hane · I, O ve Türkçe harf yok</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Plaka</label>
                            <input type="text" name="plaka" class="form-control text-uppercase" maxlength="20"
                                   value="<?= e(old('plaka')) ?>" placeholder="İsteğe bağlı">
                        </div>
                    </div>

                    <div class="form-section">Koşullar</div>
                    <div class="mb-2">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="kosul_sase" value="1" required>
                            <span class="form-check-label">Şasi numarasını araç üzerinden doğruladım</span>
                        </label>
                    </div>
                    <div class="mb-2">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="kosul_bilgi" value="1" required>
                            <span class="form-check-label">Teslim bilgilerinin doğru olduğunu onaylıyorum</span>
                        </label>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="kosul_sorumluluk" value="1" required>
                            <span class="form-check-label">Ekspertiz sorumluluğunu kabul ediyorum</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary detayli-baslat-btn">
                        <i class="mdi mdi-arrow-right me-1"></i>Devam et
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Açık taslaklar</h5>
                <?php if (!empty($taslaklar)): ?>
                    <span class="badge badge-soft-secondary"><?= count($taslaklar) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($taslaklar)): ?>
                    <div class="p-4 text-center text-muted small">
                        Açık taslak yok.<br>
                        Soldan yeni teslim alma başlatın.
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush detayli-taslak-listesi">
                        <?php foreach ($taslaklar as $r):
                            $sase = (string) ($r['veri']['sase'] ?? '');
                            $plaka = (string) ($r['veri']['plaka'] ?? '');
                            $adimKod = (string) ($r['adim'] ?? 'taraflar');
                            $adimEtiket = $adimAdlari[$adimKod] ?? $adimKod;
                            if ($adimKod === 'ekspertiz') {
                                $altKod = (string) ($r['ekspertiz_alt'] ?? 'hasar');
                                $adimEtiket .= ' · ' . ($eksAdimlari[$altKod] ?? $altKod);
                            }
                            $href = '/detayli-arac/kayit/' . e($r['id']) . '?adim=' . e($adimKod);
                            if ($adimKod === 'ekspertiz') {
                                $href .= '&alt=' . e($r['ekspertiz_alt'] ?? 'hasar');
                            }
                            $guncelleme = !empty($r['updated_at']) ? date('d.m.Y H:i', strtotime((string) $r['updated_at'])) : '';
                            ?>
                            <a href="<?= $href ?>" class="list-group-item list-group-item-action detayli-taslak-item">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate">
                                            <?= e($sase !== '' ? $sase : ('#' . substr((string) $r['id'], 0, 8))) ?>
                                        </div>
                                        <div class="small text-muted text-truncate">
                                            <?php if ($plaka !== ''): ?>
                                                <span class="me-1"><?= e($plaka) ?></span>·
                                            <?php endif; ?>
                                            <?= e($r['taraflar']['musteri_ad'] ?? 'Müşteri seçilmedi') ?>
                                            <?php if (!empty($r['taraflar']['musteri_musterisi'])): ?>
                                                · <?= e($r['taraflar']['musteri_musterisi']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($guncelleme !== ''): ?>
                                            <div class="small text-muted mt-1"><?= e($guncelleme) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <span class="badge badge-soft-warning mb-1">Taslak</span>
                                        <div class="small text-primary fw-semibold"><?= e($adimEtiket) ?></div>
                                        <span class="small text-primary">Devam et <i class="mdi mdi-arrow-right"></i></span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
