<?php
use App\Core\Csrf;

$parca = explode(' ', (string) $user['name']);
$soyad = count($parca) > 1 ? array_pop($parca) : '';
$ad = implode(' ', $parca);
?>
<form method="post" action="/kullanici/hesabim" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5>Hesap Bilgilerim</h5></div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <?php if ($user['resim']): ?>
                            <img src="<?= e($user['resim']) ?>" alt="" class="rounded-circle" style="width:72px;height:72px;object-fit:cover">
                        <?php else: ?>
                            <span class="rounded-circle bg-primary text-white d-grid" style="width:72px;height:72px;place-items:center;font-size:28px"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
                        <?php endif; ?>
                        <div><label class="form-label">Görsel Seç</label><input type="file" name="resim" class="form-control" accept="image/*"></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Kullanıcı Adı</label><input type="text" class="form-control" value="<?= e($user['email']) ?>" disabled></div>
                        <div class="col-md-6"><label class="form-label">Kullanıcı Grubu</label><input type="text" class="form-control" value="<?= e($user['grup_adi'] ?? ($user['role'] === 'admin' ? 'Yönetici' : '-')) ?>" disabled></div>
                        <div class="col-md-6"><label class="form-label required">Adı</label><input type="text" name="ad" class="form-control" value="<?= old('ad', $ad) ?>" required></div>
                        <div class="col-md-6"><label class="form-label required">Soyadı</label><input type="text" name="soyad" class="form-control" value="<?= old('soyad', $soyad) ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Telefon</label><input type="text" name="telefon" class="form-control" value="<?= old('telefon', (string) $user['telefon']) ?>"></div>
                        <div class="col-md-6"><label class="form-label required">E-mail</label><input type="email" name="mail" class="form-control" value="<?= old('mail', $user['email']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Lokasyon</label><input type="text" class="form-control" value="<?= e($user['bayi_adi'] ?? 'Tüm Lokasyonlar') ?>" disabled></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h5>Şifre Güncelle</h5></div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Şifre</label><input type="password" name="sifre" class="form-control" autocomplete="new-password"></div>
                    <div class="mb-3"><label class="form-label">Şifre Tekrarı</label><input type="password" name="sifre_tekrari" class="form-control" autocomplete="new-password"></div>
                    <div class="form-text">Değiştirmek istemiyorsanız boş bırakın.</div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100"><i class="mdi mdi-content-save me-1"></i> Kaydet</button>
        </div>
    </div>
</form>
