<?php
use App\Core\Auth;
use App\Core\Csrf;
?>
<div class="card">
    <div class="card-header"><h5 class="mb-0">Yeni konu</h5></div>
    <div class="card-body">
        <form method="post" action="/mesaj/ac" enctype="multipart/form-data" class="row g-3">
            <?= Csrf::field() ?>
            <div class="col-md-4">
                <label class="form-label">Tür</label>
                <select name="tur" class="form-select" id="konu-tur">
                    <option value="mesaj">Mesaj</option>
                    <option value="ticket">Talep</option>
                </select>
            </div>
            <?php if (Auth::isAdmin()): ?>
                <div class="col-md-4">
                    <label class="form-label">Kime</label>
                    <select name="taraf" class="form-select" id="konu-taraf">
                        <option value="moex">Moex içi</option>
                        <option value="bayi">Bir lokasyon</option>
                        <option value="musteri">Bir firma</option>
                    </select>
                </div>
                <div class="col-md-4" id="konu-bayi" hidden>
                    <label class="form-label">Lokasyon</label>
                    <select name="bayi_id" class="form-select">
                        <option value="">Seçiniz</option>
                        <?php foreach ($bayiler as $b): ?><option value="<?= (int) $b['id'] ?>"><?= e($b['ad']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4" id="konu-musteri" hidden>
                    <label class="form-label">Firma</label>
                    <select name="musteri_id" class="form-select">
                        <option value="">Seçiniz</option>
                        <?php foreach ($musteriler as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['ad']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4" id="konu-sorumlu" hidden>
                    <label class="form-label">Sorumlu</label>
                    <select name="sorumlu_id" class="form-select">
                        <option value="">Atanmasın</option>
                        <?php foreach ($sorumlular as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4" id="konu-oncelik" hidden>
                    <label class="form-label">Öncelik</label>
                    <select name="oncelik" class="form-select">
                        <option value="dusuk">Düşük</option>
                        <option value="normal" selected>Normal</option>
                        <option value="yuksek">Yüksek</option>
                    </select>
                </div>
            <?php else: ?>
                <div class="col-md-8"><p class="text-muted mb-0 pt-4">Konu Moex merkezine açılır.</p></div>
            <?php endif; ?>
            <div class="col-12">
                <label class="form-label required">Konu</label>
                <input type="text" name="konu" class="form-control" required maxlength="200" value="<?= old('konu') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Şasi</label>
                <input type="text" name="sase" class="form-control" maxlength="17" value="<?= old('sase') ?>" placeholder="İsteğe bağlı">
            </div>
            <div class="col-md-6">
                <label class="form-label">Dosya</label>
                <input type="file" name="dosya" class="form-control">
            </div>
            <div class="col-12">
                <label class="form-label required">Mesaj</label>
                <textarea name="metin" class="form-control" rows="5" required><?= old('metin') ?></textarea>
            </div>
            <div class="col-12"><button class="btn btn-primary" type="submit">Gönder</button></div>
        </form>
    </div>
</div>
<script>
document.getElementById('konu-tur')?.addEventListener('change', function () {
    const talep = this.value === 'ticket';
    const sorumlu = document.getElementById('konu-sorumlu');
    const oncelik = document.getElementById('konu-oncelik');
    if (sorumlu) sorumlu.hidden = !talep;
    if (oncelik) oncelik.hidden = !talep;
});
document.getElementById('konu-taraf')?.addEventListener('change', function () {
    const bayi = document.getElementById('konu-bayi');
    const musteri = document.getElementById('konu-musteri');
    if (bayi) bayi.hidden = this.value !== 'bayi';
    if (musteri) musteri.hidden = this.value !== 'musteri';
});
</script>
