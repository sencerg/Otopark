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
<div class="detayli-alt-adimlar mb-3">
    <?php foreach ($ekspertizAdimlari as $i => $a):
        $durum = $i < $aktifIx ? 'done' : ($i === $aktifIx ? 'active' : '');
        ?>
        <a class="detayli-alt-adim <?= $durum ?>"
           href="/detayli-arac/kayit/<?= e($kayit['id']) ?>?adim=ekspertiz&alt=<?= e($a['kod']) ?>">
            <span class="no"><?= $i + 1 ?></span>
            <span class="ad"><?= e($a['ad']) ?></span>
        </a>
    <?php endforeach; ?>
</div>
