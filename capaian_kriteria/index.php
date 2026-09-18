<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$repository = new CapaianKriteriaRepository($conn);
$service    = new CapaianKriteriaService($repository);

$periods = $service->getPeriods()['data'];
$units = Auth::isAuditee() ? [] : $service->getProdiUnits()['data'];

require_once __DIR__ . '/../layouts/app.php';

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Dashboard Capaian Kriteria</h4>
        <?php if (Auth::canManage()): ?>
        <a href="<?= BASE_URL ?>capaian_kriteria/manage/" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-gear"></i> Kelola Pemetaan Standar
        </a>
        <?php endif; ?>
    </div>

    <ul class="nav nav-tabs mb-3" id="ckLevelTabs">
        <li class="nav-item">
            <a class="nav-link active" href="#" data-level="prodi">Tingkat Program Studi</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-level="institusi">Tingkat Institusi</a>
        </li>
    </ul>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4" id="ckUnitWrapper">
                    <?php if (!Auth::isAuditee()): ?>
                    <label class="form-label small">Program Studi</label>
                    <select class="form-select" id="ckUnitSelector">
                        <option value="">-- Pilih Program Studi --</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Periode Audit</label>
                    <select class="form-select" id="ckPeriodSelector">
                        <option value="">-- Pilih Periode --</option>
                        <?php foreach ($periods as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['period_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div id="ckSpiderWrapper" class="card shadow-sm mb-3" style="display:none;">
        <div class="card-body">
            <div class="text-center mb-2" style="font-size:14px; font-weight:700; color:#3a3348;">
                <i class="bi bi-diagram-3"></i> Profil Capaian Seluruh Kriteria
            </div>
            <div style="max-width:420px; height:400px; margin:0 auto;">
                <canvas id="ckSpiderChart"></canvas>
            </div>
            <div id="ckWarningBox" class="mt-3"></div>
        </div>
    </div>

    <div id="ckContainer" class="row g-3"></div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script>
    CK_IS_AUDITEE = <?= Auth::isAuditee() ? 'true' : 'false' ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= BASE_URL ?>assets/js/capaian_kriteria.js"></script>