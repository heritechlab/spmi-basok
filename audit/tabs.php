<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Auth.php';

$currentUri = $_SERVER['REQUEST_URI'] ?? '';

if (Auth::isAuditee()) {

    $amiTabs = [
        'led_prodi/'                => ['label' => 'LED (Evaluasi Diri)', 'icon' => 'bi-file-earmark-text'],
        'master/auditor/'          => ['label' => 'Data Auditor', 'icon' => 'bi-person-badge'],
        'master/periods/'          => ['label' => 'Periode Audit', 'icon' => 'bi-calendar3'],
        'audit/assignments/'       => ['label' => 'Penugasan Audit', 'icon' => 'bi-clipboard-check'],
        'audit/findings/'          => ['label' => 'Temuan Audit', 'icon' => 'bi-search'],
        'laporan/ami/'             => ['label' => 'Laporan AMI Unit', 'icon' => 'bi-file-earmark-text'],
        'laporan/ami_institusi/'   => ['label' => 'Laporan AMI Institusi', 'icon' => 'bi-file-earmark-bar-graph'],
    ];

} else {

    $amiTabs = [
        'master/auditor/'        => ['label' => 'Data Auditor', 'icon' => 'bi-person-badge'],
        'master/periods/'        => ['label' => 'Periode Audit', 'icon' => 'bi-calendar3'],
        'audit/assignments/'     => ['label' => 'Penugasan Audit', 'icon' => 'bi-clipboard-check'],
        'audit/workspace/'       => ['label' => 'Workspace Audit (LKA)', 'icon' => 'bi-journal-text'],
        'audit/findings/'        => ['label' => 'Temuan Audit', 'icon' => 'bi-search'],
        'laporan/ami/'           => ['label' => 'Laporan AMI Unit', 'icon' => 'bi-file-earmark-text'],
        'laporan/ami_institusi/' => ['label' => 'Laporan AMI Institusi', 'icon' => 'bi-file-earmark-bar-graph'],
    ];

}

?>

<div class="ami-tab-nav mb-3">
    <?php foreach ($amiTabs as $path => $tab): ?>
        <a href="<?= BASE_URL . $path ?>" class="ami-tab <?= str_contains($currentUri, '/' . rtrim($path, '/')) ? 'active' : '' ?>">
            <i class="bi <?= $tab['icon'] ?>"></i>
            <span><?= $tab['label'] ?></span>
        </a>
    <?php endforeach; ?>
</div>