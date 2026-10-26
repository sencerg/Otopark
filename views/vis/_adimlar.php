<?php
/** @var list<array{kod:string,ad:string}> $adimlar */
/** @var string $adim */
$sira = array_column($adimlar, 'kod');
$aktifIx = array_search($adim, $sira, true);
if ($aktifIx === false) {
    $aktifIx = 0;
}
?>
<div class="detayli-adimlar mb-3">
    <?php foreach ($adimlar as $i => $a):
        $durum = $i < $aktifIx ? 'done' : ($i === $aktifIx ? 'active' : '');
        ?>
        <div class="detayli-adim <?= $durum ?>">
            <span class="no"><?= $i + 1 ?></span>
            <span class="ad"><?= e($a['ad']) ?></span>
        </div>
        <?php if ($i < count($adimlar) - 1): ?><div class="detayli-adim-cizgi <?= $i < $aktifIx ? 'done' : '' ?>"></div><?php endif; ?>
    <?php endforeach; ?>
</div>
