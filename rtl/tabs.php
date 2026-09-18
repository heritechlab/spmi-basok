<?php

declare(strict_types=1);

$currentUri = $_SERVER['REQUEST_URI'] ?? '';

$rtlTabs = [
    'rtl/plans/'          => ['label' => 'Penetapan RTL', 'icon' => 'bi-list-check'],
    'rtl/implementation/' => ['label' => 'Pelaksanaan RTL', 'icon' => 'bi-play-circle'],
    'rtl/monitoring/'     => ['label' => 'Monitoring RTL', 'icon' => 'bi-graph-up-arrow'],
];

?>

<div class="ami-tab-nav mb-3">
    <?php foreach ($rtlTabs as $path => $tab): ?>
        <a href="<?= BASE_URL . $path ?>" class="ami-tab <?= str_contains($currentUri, '/' . rtrim($path, '/')) ? 'active' : '' ?>">
            <i class="bi <?= $tab['icon'] ?>"></i>
            <span><?= $tab['label'] ?></span>
        </a>
    <?php endforeach; ?>
</div>