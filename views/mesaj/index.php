<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Services\MesajMerkezi;

$okundu = static function (array $b): bool {
    return !empty($b['okundu_at']);
};
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="page-title mb-1">Mesaj Merkezi</h4>
        <p class="text-muted mb-0 small">Bildirimler, Moex yazışmaları ve talepler.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if (Auth::isAdmin()): ?>
            <a class="btn btn-outline-primary" href="/mesaj/duyuru"><i class="mdi mdi-bullhorn-outline me-1"></i> Sürüm duyurusu</a>
            <a class="btn btn-outline-secondary" href="/mesaj/gruplar">Duyuru grupları</a>
        <?php endif; ?>
        <a class="btn btn-primary" href="/mesaj/yeni"><i class="mdi mdi-plus me-1"></i> Yeni konu</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Bildirimler <?php if ($okunmamis): ?><span class="badge bg-danger"><?= (int) $okunmamis ?></span><?php endif; ?></h5>
                <?php if ($okunmamis): ?>
                    <form method="post" action="/mesaj/okundu" class="m-0"><?= Csrf::field() ?><button class="btn btn-sm btn-link">Tümünü okundu say</button></form>
                <?php endif; ?>
            </div>
            <div class="list-group list-group-flush">
                <?php if (!$bildirimler): ?>
                    <div class="list-group-item text-muted">Bildirim yok.</div>
                <?php endif; ?>
                <?php foreach ($bildirimler as $b): ?>
                    <a class="list-group-item <?= $okundu($b) ? '' : 'fw-semibold' ?>" href="<?= e($b['url'] ?: '/mesaj') ?>">
                        <div class="small text-muted"><?= e($b['tur']) ?> · <?= e(date('d.m.Y H:i', strtotime((string) $b['created_at']))) ?></div>
                        <?= e($b['baslik']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Konuşmalar</h5></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Kod</th><th>Konu</th><th>Taraf</th><th>Durum</th><th>Güncelleme</th></tr></thead>
                    <tbody>
                    <?php if (!$konusmalar): ?>
                        <tr><td colspan="5" class="text-muted">Henüz konuşma yok.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($konusmalar as $k): ?>
                        <tr>
                            <td><a href="/mesaj/<?= (int) $k['id'] ?>"><?= e($k['kod']) ?></a><div class="small text-muted"><?= $k['tur'] === 'ticket' ? 'Talep' : 'Mesaj' ?></div></td>
                            <td><?= e($k['konu']) ?><div class="small text-muted text-truncate" style="max-width:280px"><?= e((string) $k['son_metin']) ?></div></td>
                            <td><?= e($k['musteri_ad'] ?: ($k['bayi_ad'] ?: 'Moex içi')) ?></td>
                            <td><?= e(MesajMerkezi::durumAdi((string) $k['durum'])) ?></td>
                            <td class="small"><?= e(date('d.m.Y H:i', strtotime((string) $k['updated_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
