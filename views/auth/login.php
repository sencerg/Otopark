<div class="auth-card">
    <h2><?= e(\App\Core\Env::get('APP_NAME')) ?></h2>
    <?php if ($msg = flash('error')): ?><div class="alert alert-error"><?= e($msg) ?></div><?php endif; ?>
    <form method="post" action="/login">
        <?= \App\Core\Csrf::field() ?>
        <label for="email">Kullanıcı Adı</label>
        <input id="email" name="email" type="email" value="<?= old('email') ?>" required autofocus>
        <label for="password">Şifre</label>
        <input id="password" name="password" type="password" required>
        <button type="submit" class="btn btn-primary">Giriş Yap</button>
    </form>
</div>
