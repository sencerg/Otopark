<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Services\MesajMerkezi;

$k = $konusma;
?>
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
    <div>
        <h4 class="page-title mb-1"><?= e($k['kod']) ?> · <?= e($k['konu']) ?></h4>
        <p class="text-muted mb-0 small">
            <?= $k['tur'] === 'ticket' ? 'Talep' : 'Mesaj' ?>
            · <?= e(MesajMerkezi::durumAdi((string) $k['durum'])) ?>
            · <?= e($k['musteri_ad'] ?: ($k['bayi_ad'] ?: 'Moex içi')) ?>
            · <?= e($k['acan_ad']) ?>
        </p>
    </div>
    <a class="btn btn-outline-secondary" href="/mesaj">Listeye dön</a>
</div>

<?php if (Auth::isAdmin() && $k['tur'] === 'ticket'): ?>
    <form method="post" action="/mesaj/<?= (int) $k['id'] ?>/durum" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            <?= Csrf::field() ?>
            <div class="col-md-3">
                <label class="form-label">Durum</label>
                <select name="durum" class="form-select">
                    <?php foreach (['acik' => 'Açık', 'bekliyor' => 'Bekliyor', 'cozuldu' => 'Çözüldü'] as $kod => $ad): ?>
                        <option value="<?= $kod ?>" <?= $k['durum'] === $kod ? 'selected' : '' ?>><?= $ad ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Sorumlu</label>
                <select name="sorumlu_id" class="form-select">
                    <option value="">Atanmasın</option>
                    <?php foreach ($sorumlular as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) $k['sorumlu_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-outline-primary" type="submit">Güncelle</button></div>
        </div>
    </form>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body d-flex flex-column gap-3">
        <?php foreach ($k['mesajlar'] as $m): ?>
            <div>
                <div class="small text-muted"><?= e($m['yazan']) ?> · <?= e(date('d.m.Y H:i', strtotime((string) $m['created_at']))) ?></div>
                <div style="white-space:pre-wrap"><?= e($m['metin']) ?></div>
                <?php foreach ($m['dosyalar'] as $d): ?>
                    <a class="small" href="<?= e($d['dosya_yolu']) ?>" target="_blank" rel="noopener"><?= e($d['orijinal_ad'] ?: 'Dosya') ?></a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<form method="post" action="/mesaj/<?= (int) $k['id'] ?>" enctype="multipart/form-data" class="card">
    <div class="card-body">
        <?= Csrf::field() ?>
        <label class="form-label">Yanıt</label>
        <textarea name="metin" class="form-control mb-2" rows="4" required></textarea>
        <input type="file" name="dosya" class="form-control mb-3">
        <button class="btn btn-primary" type="submit">Gönder</button>
    </div>
</form>
