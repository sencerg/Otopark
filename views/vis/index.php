<?php use App\Core\Csrf; ?>
<header class="vis-top">
    <a href="/" class="vis-icon-btn" title="Ana sistem"><i class="mdi mdi-arrow-left"></i></a>
    <h1>Detaylı Araç Ekle</h1>
    <span class="vis-icon-btn" aria-hidden="true"><i class="mdi mdi-car-info"></i></span>
</header>

<main class="vis-main vis-kontrol">
    <div class="vis-kontrol-card">
        <p class="vis-hint mb-3">
            Bu ekran <b>Araç Ekle</b> ile aynı kaydı hazırlar; önce kontrol ve koşullar adımı gelir.
            Mevcut hızlı araç ekleme akışına dokunulmaz.
        </p>

        <?php if (!empty($hata)): ?>
            <div class="vis-alert"><?= e($hata) ?></div>
        <?php endif; ?>

        <form method="post" action="/vis/baslat" class="vis-form" id="vis-kontrol-form">
            <?= Csrf::field() ?>

            <div class="vis-step-title">1 · Kontrol</div>
            <label class="vis-label">Şasi numarası</label>
            <input type="text" name="sase" class="vis-input text-uppercase sase-input" maxlength="17" minlength="17"
                   value="<?= e(old('sase', (string) ($sase ?? ''))) ?>" placeholder="17 haneli şasi" required autocomplete="off" spellcheck="false"
                   title="17 hane; I, O ve Türkçe harf kullanılamaz">

            <label class="vis-label">Plaka <span class="vis-optional">(isteğe bağlı)</span></label>
            <input type="text" name="plaka" class="vis-input text-uppercase" maxlength="20"
                   value="<?= e(old('plaka', (string) ($plaka ?? ''))) ?>" placeholder="Plaka">

            <div class="vis-step-title">2 · Koşullar</div>
            <label class="vis-check">
                <input type="checkbox" name="kosul_sase" value="1" required>
                Şasi numarasını doğruladım; stokta aynı şasi olmadığını kontrol edeceğim.
            </label>
            <label class="vis-check">
                <input type="checkbox" name="kosul_bilgi" value="1" required>
                Araç bilgilerini eksiksiz ve doğru gireceğimi kabul ediyorum.
            </label>
            <label class="vis-check">
                <input type="checkbox" name="kosul_sorumluluk" value="1" required>
                Girişin operasyon kaydı olduğunu ve sorumluluğumu kabul ediyorum.
            </label>

            <button type="submit" class="vis-btn primary block">Devam et — detaylı ekleme</button>
        </form>

        <?php if (!empty($taslaklar)): ?>
            <div class="vis-step-title mt-4">Taslaklar</div>
            <ul class="vis-list">
                <?php foreach ($taslaklar as $r): ?>
                    <li>
                        <a href="/vis/kayit/<?= e($r['id']) ?>">
                            <strong><?= e($r['veri']['plaka'] ?? 'Plakasız') ?></strong>
                            <span><?= e($r['veri']['sase'] ?? '—') ?></span>
                            <span class="muted"><?= e($r['durum'] ?? '') ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>

<footer class="vis-bottom">
    <a href="/" class="vis-btn ghost">Geri</a>
    <a href="/arac_yonetimi/ekle" class="vis-btn ghost">Standart Araç Ekle</a>
</footer>
