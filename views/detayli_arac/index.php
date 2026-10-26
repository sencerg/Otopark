<?php
use App\Core\Csrf;
require __DIR__ . '/_adimlar.php';
?>
<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h5>Teslim Alma · Kontrol</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Şasi kontrolü → taraflar → araç (stoğa kayıt) → ekspertiz.
                    Kayıt araç listesinde «Detaylı» olarak görünür. Standart Araç Ekle ayrı kalır.
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

                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-arrow-right me-1"></i>Devam et
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><h5>Taslaklar</h5></div>
            <div class="card-body p-0">
                <?php if (empty($taslaklar)): ?>
                    <div class="p-3 text-muted small">Açık taslak yok.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($taslaklar as $r): ?>
                            <a href="/detayli-arac/kayit/<?= e($r['id']) ?>?adim=<?= e($r['adim'] ?? 'taraflar') ?>" class="list-group-item list-group-item-action">
                                <div class="fw-semibold"><?= e($r['veri']['sase'] ?? $r['id']) ?></div>
                                <div class="small text-muted">
                                    <?= e($r['taraflar']['musteri_ad'] ?? '—') ?>
                                    <?php if (!empty($r['taraflar']['musteri_musterisi'])): ?>
                                        · <?= e($r['taraflar']['musteri_musterisi']) ?>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
