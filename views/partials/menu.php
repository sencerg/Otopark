<?php
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
$currentPath = parse_url($currentUri, PHP_URL_PATH) ?: '/';

$isActive = static function (array $item) use (&$isActive, $currentUri, $currentPath): bool {
    if (isset($item['url'])) {
        return str_contains($item['url'], '?') ? $item['url'] === $currentUri : $item['url'] === $currentPath;
    }
    foreach ($item['children'] ?? [] as $child) {
        if ($isActive($child)) {
            return true;
        }
    }
    return false;
};

$renderMenu = static function (array $items, int $level = 0) use (&$renderMenu, $isActive): void {
    foreach ($items as $i => $item) {
        $active = $isActive($item);
        if (!empty($item['children'])) {
            $id = 'm' . $level . '-' . md5($item['title']);
            ?>
            <li class="menu-item has-children <?= $active ? 'open' : '' ?>">
                <a href="#<?= $id ?>" class="menu-link" data-bs-toggle="collapse" aria-expanded="<?= $active ? 'true' : 'false' ?>">
                    <?php if (!empty($item['icon'])): ?><i class="mdi <?= $item['icon'] ?>"></i><?php endif; ?>
                    <span><?= e($item['title']) ?></span>
                    <i class="mdi mdi-chevron-down menu-arrow"></i>
                </a>
                <ul class="collapse submenu <?= $active ? 'show' : '' ?>" id="<?= $id ?>">
                    <?php $renderMenu($item['children'], $level + 1); ?>
                </ul>
            </li>
            <?php
        } else {
            ?>
            <li class="menu-item">
                <a href="<?= e($item['url']) ?>" class="menu-link <?= $active ? 'active' : '' ?>">
                    <?php if (!empty($item['icon'])): ?><i class="mdi <?= $item['icon'] ?>"></i><?php endif; ?>
                    <span><?= e($item['title']) ?></span>
                </a>
            </li>
            <?php
        }
    }
};
?>
<ul class="sidebar-menu">
    <?php $renderMenu(require BASE_PATH . '/app/menu.php'); ?>
</ul>
