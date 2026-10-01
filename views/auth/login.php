<div class="auth-card">
    <div class="brand">
        <span class="brand-mark"><i class="mdi mdi-parking"></i></span>
        <h4 class="mb-0 fw-bold"><?= e(\App\Core\Env::get('APP_NAME')) ?></h4>
    </div>
    <?php if ($msg = flash('error')): ?><div class="alert alert-danger py-2 small"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash('success')): ?><div class="alert alert-success py-2 small"><?= e($msg) ?></div><?php endif; ?>
    <form method="post" action="/login">
        <?= \App\Core\Csrf::field() ?>
        <div class="mb-3">
            <label for="email" class="form-label">Kullanıcı Adı</label>
            <input id="email" name="email" type="email" class="form-control form-control-lg" placeholder="E-posta adresiniz" value="<?= old('email') ?>" required autofocus>
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
