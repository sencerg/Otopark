<?php
/**
 * Üstten araç hasar şeması.
 * @var array<string,array{seviye?:string,not?:string,ad?:string}> $hasarData
 * @var bool $semaReadonly
 */
$semaReadonly = !empty($semaReadonly);
$sevClass = static function (?string $sev): string {
    return match ($sev) {
        'Çizik' => 'sev-cizik',
        'Göçük' => 'sev-gocuk',
        'Boyalı' => 'sev-boyali',
        'Değişen' => 'sev-degisen',
        'Hasarlı' => 'sev-hasarli',
        default => 'sev-yok',
    };
};
$panelSev = static function (string $kod) use ($hasarData): string {
    return (string) ($hasarData[$kod]['seviye'] ?? 'Yok');
};
?>
<div class="arac-sema-wrap <?= $semaReadonly ? 'readonly' : '' ?>" id="arac-sema">
    <div class="arac-sema-legend">
        <span class="sev-yok">Yok</span>
        <span class="sev-cizik">Çizik</span>
        <span class="sev-gocuk">Göçük</span>
        <span class="sev-boyali">Boyalı</span>
        <span class="sev-degisen">Değişen</span>
        <span class="sev-hasarli">Hasarlı</span>
    </div>
    <svg class="arac-sema" viewBox="0 0 280 420" role="img" aria-label="Araç hasar şeması">
        <!-- gövde -->
        <rect class="sema-govde" x="70" y="40" width="140" height="340" rx="36"/>
        <!-- camlar -->
        <rect class="sema-cam" x="88" y="130" width="104" height="70" rx="6"/>
        <rect class="sema-cam" x="88" y="220" width="104" height="55" rx="6"/>

        <?php
        $bolgeler = [
            // kod => [x,y,w,h, rx]
            'on_tampon' => [95, 42, 90, 22, 8],
            'far_sol' => [72, 55, 28, 18, 4],
            'far_sag' => [180, 55, 28, 18, 4],
            'kaput' => [88, 78, 104, 48, 6],
            'sol_camurluk' => [58, 78, 26, 55, 5],
            'sag_camurluk' => [196, 78, 26, 55, 5],
            'sol_on_cam' => [52, 120, 18, 40, 4],
            'sag_on_cam' => [210, 120, 18, 40, 4],
            'sol_on_kapi' => [58, 140, 26, 70, 5],
            'sag_on_kapi' => [196, 140, 26, 70, 5],
            'tavan' => [95, 145, 90, 55, 4],
            'sol_arka_kapi' => [58, 215, 26, 65, 5],
            'sag_arka_kapi' => [196, 215, 26, 65, 5],
            'bagaj' => [88, 280, 104, 48, 6],
            'stop_sol' => [72, 335, 28, 16, 4],
            'stop_sag' => [180, 335, 28, 16, 4],
            'arka_tampon' => [95, 352, 90, 22, 8],
            'jant_sol_on' => [48, 95, 14, 28, 4],
            'jant_sag_on' => [218, 95, 14, 28, 4],
            'jant_sol_arka' => [48, 300, 14, 28, 4],
            'jant_sag_arka' => [218, 300, 14, 28, 4],
        ];
        foreach ($bolgeler as $kod => [$x, $y, $w, $h, $rx]):
            $sev = $panelSev($kod);
            $cls = $sevClass($sev);
            $ad = $hasarPanelleri[$kod] ?? $kod;
            ?>
            <g class="sema-panel <?= e($cls) ?>" data-panel="<?= e($kod) ?>" <?= $semaReadonly ? '' : 'tabindex="0" role="button"' ?>>
                <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $w ?>" height="<?= $h ?>" rx="<?= $rx ?>"/>
                <?php if ($sev !== 'Yok'): ?>
                    <title><?= e($ad . ': ' . $sev) ?></title>
                    <text class="sema-etiket" x="<?= $x + $w / 2 ?>" y="<?= $y + $h / 2 + 3 ?>" text-anchor="middle"><?= e(mb_substr($sev, 0, 6)) ?></text>
                <?php else: ?>
                    <title><?= e($ad) ?></title>
                <?php endif; ?>
            </g>
        <?php endforeach; ?>
    </svg>
    <p class="text-muted small mb-0 mt-2"><?= $semaReadonly ? 'Hasar işaretleri şema üzerinde.' : 'Panele tıklayın → tabloda durum seçin; şema anında güncellenir.' ?></p>
</div>
