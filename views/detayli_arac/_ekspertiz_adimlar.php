<?php
/** @var list<array{kod:string,ad:string}> $ekspertizAdimlari */
/** @var string $eksAlt */
/** @var array $kayit */
$sira = array_column($ekspertizAdimlari, 'kod');
$aktifIx = array_search($eksAlt, $sira, true);
if ($aktifIx === false) {
    $aktifIx = 0;
}
?>
<nav class="detayli-alt-adimlar mb-3" aria-label="Ekspertiz alt adımları">
    <?php foreach ($ekspertizAdimlari as $i => $a):
        $durum = $i < $aktifIx ? 'done' : ($i === $aktifIx ? 'active' : 'locked');
        $href = '/detayli-arac/kayit/' . e($kayit['id']) . '?adim=ekspertiz&alt=' . e($a['kod']);
        ?>
        <?php if ($durum === 'locked'): ?>
            <span class="detayli-alt-adim locked" aria-disabled="true" title="Önce önceki adımı tamamlayın">
                <span class="no"><?= $i + 1 ?></span>
                <span class="ad"><?= e($a['ad']) ?></span>
            </span>
        <?php else: ?>
            <a class="detayli-alt-adim <?= $durum ?>" href="<?= $href ?>"<?= $durum === 'active' ? ' aria-current="step"' : '' ?>>
                <span class="no"><?= $durum === 'done' ? '✓' : ($i + 1) ?></span>
                <span class="ad"><?= e($a['ad']) ?></span>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
