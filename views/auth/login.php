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
</div>
