<?php
$fmt = static function (string $iso): string {
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $iso)
        ?: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $iso);
    if (!$dt) {
        return $iso;
    }

    return $dt->format('d.m.Y H:i');
};
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h4 class="page-title mb-1">Proje Notları</h4>
        <p class="text-muted mb-0 small">Özellik sürümleri — gün ve saat ile. Yeni özellik eklendikçe buraya yazılır.</p>
    </div>
    <?php if ($guncel): ?>
        <div class="text-end">
            <span class="badge badge-soft-primary fs-6 px-3 py-2"><?= e($guncel['surum']) ?></span>
            <div class="small text-muted mt-1">Güncel · <?= e($fmt($guncel['tarih'])) ?></div>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="surum-timeline">
            <?php foreach ($surumler as $i => $s): ?>
                <article class="surum-item <?= $i === 0 ? 'surum-item-guncel' : '' ?>">
                    <div class="surum-rail" aria-hidden="true"></div>
                    <div class="surum-meta">
                        <span class="surum-kod"><?= e($s['surum']) ?></span>
                        <time class="surum-tarih" datetime="<?= e(str_replace(' ', 'T', $s['tarih'])) ?>"><?= e($fmt($s['tarih'])) ?></time>
                    </div>
                    <div class="surum-body">
                        <h5 class="surum-baslik"><?= e($s['baslik']) ?></h5>
                        <?php if (!empty($s['maddeler'])): ?>
                            <ul class="surum-maddeler">
                                <?php foreach ($s['maddeler'] as $m): ?>
                                    <li><?= e($m) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.surum-timeline { padding: 8px 0; }
.surum-item {
    display: grid;
    grid-template-columns: 8px 160px 1fr;
    gap: 0 16px;
    padding: 16px 20px;
    border-bottom: 1px solid #eef1f6;
    position: relative;
}
.surum-item:last-child { border-bottom: 0; }
.surum-item-guncel { background: #f5f8ff; }
.surum-rail {
    width: 8px; border-radius: 8px; background: #dbe4f5; margin-top: 4px; min-height: 100%;
}
.surum-item-guncel .surum-rail { background: var(--primary, #1d4ed8); }
.surum-meta { padding-top: 2px; }
.surum-kod {
    display: inline-block; font-weight: 700; font-size: 0.95rem; color: #111827; font-variant-numeric: tabular-nums;
}
.surum-tarih { display: block; margin-top: 4px; font-size: 0.8rem; color: #6b7280; }
.surum-baslik { margin: 0 0 8px; font-size: 1rem; font-weight: 650; }
.surum-maddeler { margin: 0; padding-left: 1.1rem; color: #374151; font-size: 0.9rem; }
.surum-maddeler li { margin-bottom: 4px; }
@media (max-width: 700px) {
    .surum-item { grid-template-columns: 6px 1fr; }
    .surum-meta { grid-column: 2; display: flex; flex-wrap: wrap; gap: 8px; align-items: baseline; }
    .surum-body { grid-column: 2; }
    .surum-tarih { margin-top: 0; }
}
</style>
