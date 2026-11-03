<?php
use App\Core\Csrf;

$rol = static fn (string $r): string => match ($r) {
    'admin' => 'Moex',
    'bayi' => 'Bayi',
    'musteri' => 'Müşteri',
    default => $r,
};
?>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Gruplar</h5></div>
            <div class="list-group list-group-flush">
                <?php foreach ($gruplar as $g): ?>
                    <a class="list-group-item" href="/mesaj/gruplar?grup=<?= (int) $g['id'] ?>">
                        <?= e($g['ad']) ?>
                        <span class="small text-muted"><?= !empty($g['sistem_kod']) ? 'sistem' : ((int) $g['uye_sayisi'] . ' üye') ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <form method="post" action="/mesaj/grup" class="card">
            <div class="card-header"><h5 class="mb-0">Yeni özel grup</h5></div>
            <div class="card-body">
                <?= Csrf::field() ?>
                <label class="form-label">Ad</label>
                <input type="text" name="ad" class="form-control mb-2" required>
                <label class="form-label">Açıklama</label>
                <input type="text" name="aciklama" class="form-control mb-3">
                <button class="btn btn-primary" type="submit">Kaydet</button>
            </div>
        </form>
    </div>
    <div class="col-lg-7">
        <?php if ($grup): ?>
            <div class="card">
                <div class="card-header"><h5 class="mb-0"><?= e($grup['ad']) ?></h5></div>
                <div class="card-body">
                    <?php if (!empty($grup['sistem_kod'])): ?>
                        <p class="mb-0">Bu grup sistem grubudur. Alıcılar <?= e((string) $grup['aciklama']) ?> olarak seçilir. Üye listesi tutulmaz.</p>
                    <?php else: ?>
                        <form method="post" action="/mesaj/grup_uyeleri">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $grup['id'] ?>">
                            <label class="form-label">Üyeler</label>
                            <select name="user_id[]" class="form-select select2" multiple>
                                <?php foreach ($kullanicilar as $u): ?>
                                    <option value="<?= (int) $u['id'] ?>" <?= in_array((int) $u['id'], $uyeler, true) ? 'selected' : '' ?>><?= e($u['name']) ?> · <?= e($rol((string) $u['role'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary mt-3" type="submit">Üyeleri kaydet</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card"><div class="card-body text-muted">Soldan bir grup seçin. Sistem grupları role göredir. Özel gruba kişi eklenir.</div></div>
        <?php endif; ?>
    </div>
</div>
