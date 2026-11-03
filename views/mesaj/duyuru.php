<?php
use App\Core\Csrf;

$paket = [];
foreach ($surumler as $s) {
    $paket[$s['surum']] = [
        'baslik' => $s['baslik'],
        'metin' => implode("\n", array_map(static fn ($m) => '• ' . $m, $s['maddeler'])),
    ];
}
$aktif = $paket[$secili] ?? ['baslik' => '', 'metin' => ''];
?>
<div class="row g-3">
    <div class="col-lg-7">
        <form method="post" action="/mesaj/duyuru" class="card" id="duyuru-form">
            <div class="card-header"><h5 class="mb-0">Sürüm duyurusu</h5></div>
            <div class="card-body row g-3">
                <?= Csrf::field() ?>
                <div class="col-md-4">
                    <label class="form-label">Sürüm</label>
                    <select name="surum" class="form-select" id="duyuru-surum">
                        <?php foreach ($surumler as $s): ?>
                            <option value="<?= e($s['surum']) ?>" <?= $s['surum'] === $secili ? 'selected' : '' ?>><?= e($s['surum']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Başlık</label>
                    <input type="text" name="baslik" id="duyuru-baslik" class="form-control" value="<?= e($aktif['baslik']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Metin</label>
                    <textarea name="metin" id="duyuru-metin" class="form-control" rows="6"><?= e($aktif['metin']) ?></textarea>
                </div>
                <div class="col-12">
                    <div class="form-label">Alıcılar</div>
                    <?php foreach (['moex' => 'Moex içi', 'bayi' => 'Bayiler', 'musteri' => 'Müşteriler', 'herkes' => 'Tüm paydaşlar'] as $kod => $ad): ?>
                        <label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="hedef[]" value="<?= $kod ?>"> <?= $ad ?></label>
                    <?php endforeach; ?>
                </div>
                <div class="col-12">
                    <label class="form-label">Özel grup</label>
                    <select name="grup_id[]" class="form-select" multiple size="4">
                        <?php foreach ($gruplar as $g): if (!empty($g['sistem_kod'])) continue; ?>
                            <option value="<?= (int) $g['id'] ?>"><?= e($g['ad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Kişiler</label>
                    <select name="user_id[]" class="form-select select2" multiple>
                        <?php foreach ($kullanicilar as $u): ?>
                            <option value="<?= (int) $u['id'] ?>"><?= e($u['name']) ?> · <?= e($u['email']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Lokasyon ile daralt</label>
                    <select name="bayi_id" class="form-select">
                        <option value="">Tümü</option>
                        <?php foreach ($bayiler as $b): ?><option value="<?= (int) $b['id'] ?>"><?= e($b['ad']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Firma ile daralt</label>
                    <select name="musteri_id" class="form-select">
                        <option value="">Tümü</option>
                        <?php foreach ($musteriler as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['ad']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-check"><input class="form-check-input" type="checkbox" name="zorunlu" value="1"> Zorunlu e-posta (sürüm e-postası kapalı olanlara da gider)</label>
                    <label class="form-check"><input class="form-check-input" type="checkbox" name="yeniden" value="1"> Bu sürüm daha önce gittiyse yeniden gönder</label>
                </div>
                <div class="col-12"><button class="btn btn-primary" type="submit">Gönder</button></div>
            </div>
        </form>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Gönderilenler</h5></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Sürüm</th><th>Alıcı</th><th>E-posta</th><th>Zaman</th></tr></thead>
                    <tbody>
                    <?php foreach ($gecmis as $g): ?>
                        <tr>
                            <td><?= e($g['surum']) ?><div class="small text-muted"><?= e($g['baslik']) ?></div></td>
                            <td><?= (int) $g['alici_sayisi'] ?></td>
                            <td><?= (int) $g['eposta_sayisi'] ?></td>
                            <td class="small"><?= e(date('d.m.Y H:i', strtotime((string) $g['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$gecmis): ?><tr><td colspan="4" class="text-muted">Kayıt yok.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
const duyuruPaket = <?= json_encode($paket, JSON_UNESCAPED_UNICODE) ?>;
document.getElementById('duyuru-surum')?.addEventListener('change', function () {
    const paket = duyuruPaket[this.value];
    if (!paket) return;
    document.getElementById('duyuru-baslik').value = paket.baslik;
    document.getElementById('duyuru-metin').value = paket.metin;
});
</script>
