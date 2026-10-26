<?php
/** @var list<array{kod:string,ad:string}> $adimlar */
/** @var string $adim */
/** @var array|null $kayit */
$sira = array_column($adimlar, 'kod');
$aktifIx = array_search($adim, $sira, true);
if ($aktifIx === false) {
    $aktifIx = 0;
}
$kayitId = isset($kayit['id']) ? (string) $kayit['id'] : '';
?>
<nav class="detayli-adimlar mb-3" aria-label="Teslim alma adımları">
    <?php foreach ($adimlar as $i => $a):
        $durum = $i < $aktifIx ? 'done' : ($i === $aktifIx ? 'active' : 'locked');
        $href = '';
        if ($kayitId !== '' && $durum === 'done') {
            $href = '/detayli-arac/kayit/' . rawurlencode($kayitId) . '?adim=' . rawurlencode($a['kod']);
            if ($a['kod'] === 'ekspertiz') {
                $href .= '&alt=' . rawurlencode((string) ($kayit['ekspertiz_alt'] ?? 'hasar'));
            }
        }
        $tag = $href !== '' ? 'a' : 'div';
        $attrs = $href !== '' ? ' href="' . e($href) . '"' : ($durum === 'locked' ? ' aria-disabled="true"' : ' aria-current="step"');
        ?>
        <<?= $tag ?> class="detayli-adim <?= $durum ?>"<?= $attrs ?>>
            <span class="no"><?= $durum === 'done' ? '✓' : ($i + 1) ?></span>
            <span class="ad"><?= e($a['ad']) ?></span>
        </<?= $tag ?>>
        <?php if ($i < count($adimlar) - 1): ?>
            <div class="detayli-adim-cizgi <?= $i < $aktifIx ? 'done' : '' ?>" aria-hidden="true"></div>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
