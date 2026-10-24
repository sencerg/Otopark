<?php
use App\Core\Env;

$appName = Env::get('APP_NAME', 'Moex Otopark');
// Hero markası APP_NAME'den bağımsız — canlıda "Otopark Yönetim Paneli" gibi isimler split edilince Moex kayboluyordu.
$marka = Env::get('APP_BRAND', 'Moex');
$urun = Env::get('APP_PRODUCT', 'Otopark');
?>
<div class="auth-shell">
    <section class="auth-hero" aria-label="<?= e($marka . ' ' . $urun) ?>">
        <div class="auth-hero-inner">
            <div class="auth-brand-mark" aria-hidden="true"><i class="mdi mdi-parking"></i></div>
            <h1 class="auth-brand-name"><?= e($marka) ?></h1>
            <span class="auth-brand-product"><?= e($urun) ?></span>
            <p class="auth-tagline">Lokasyon stok, hareket ve operasyon yönetimi — tek panelde.</p>
        </div>
    </section>

    <section class="auth-panel">
        <div class="auth-card">
            <div class="auth-card-kicker"><?= e($marka . ' ' . $urun) ?></div>
            <h2 class="auth-card-title">Panele giriş</h2>
            <?php if ($msg = flash('error')): ?><div class="alert alert-danger py-2 small"><?= e($msg) ?></div><?php endif; ?>
            <?php if ($msg = flash('success')): ?><div class="alert alert-success py-2 small"><?= e($msg) ?></div><?php endif; ?>
            <form method="post" action="/login">
                <?= \App\Core\Csrf::field() ?>
                <div class="mb-3">
                    <label for="email" class="form-label">E-posta veya kullanıcı adı</label>
                    <input id="email" name="email" type="text" class="form-control form-control-lg" placeholder="ornek@firma.com veya kullanici_adi" value="<?= old('email') ?>" required autofocus autocomplete="username">
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Şifre</label>
                    <input id="password" name="password" type="password" class="form-control form-control-lg" placeholder="Şifreniz" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
            </form>
            <?php if (!empty($demoHesaplar)): ?>
                <div class="demo-login mt-4">
                    <div class="small text-muted text-center mb-2">Hızlı giriş (sadece geliştirme ortamı)</div>
                    <div class="d-grid gap-2">
                        <?php foreach ($demoHesaplar as [$ad, $aciklama, $email, $sifre, $ikon]): ?>
                            <button type="button" class="btn btn-outline-secondary btn-sm text-start d-flex align-items-center gap-2" data-demo-email="<?= e($email) ?>" data-demo-sifre="<?= e($sifre) ?>">
                                <i class="mdi <?= e($ikon) ?> fs-5"></i>
                                <span><b><?= e($ad) ?></b><small class="d-block text-muted"><?= e($aciklama) ?> · <?= e($email) ?></small></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <script>
                    document.querySelectorAll('[data-demo-email]').forEach((btn) => btn.addEventListener('click', () => {
                        const form = document.querySelector('form[action="/login"]');
                        form.email.value = btn.dataset.demoEmail;
                        form.password.value = btn.dataset.demoSifre;
                        form.submit();
                    }));
                </script>
                <div class="mt-3"><?php require __DIR__ . '/_sifirla_form.php'; ?></div>
            <?php endif; ?>
        </div>
    </section>
</div>
