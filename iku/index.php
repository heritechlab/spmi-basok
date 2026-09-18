<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$repository = new IkuRepository($conn);
$service    = new IkuService($repository);

$units = Auth::isAuditee() ? [] : $service->getProdiUnits()['data'];

require_once __DIR__ . '/../layouts/app.php';

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="container-fluid py-4">

    <h4 class="mb-3">Indikator Kinerja Utama (IKU) Diktisaintek Berdampak</h4>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2">

                <?php if (!Auth::isAuditee()): ?>
                <div class="col-md-3">
                    <label class="form-label small">Unit Kerja</label>
                    <select class="form-select" id="ikuUnitSelector">
                        <option value="">-- Institusi (Tanpa Unit) --</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-md-3">
                    <label class="form-label small">Tahun</label>
                    <select class="form-select" id="ikuTahunSelector">
                        <?php for ($y = (int) date('Y') + 1; $y >= (int) date('Y') - 1; $y--): ?>
                            <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small">Triwulan</label>
                    <select class="form-select" id="ikuTriwulanSelector">
                        <option value="TW1">Triwulan 1</option>
                        <option value="TW2">Triwulan 2</option>
                        <option value="TW3">Triwulan 3</option>
                        <option value="TW4">Triwulan 4</option>
                    </select>
                </div>

            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <div></div>
        <div class="d-flex gap-2">
            <?php if (Auth::canManage()): ?>
                <a href="<?= BASE_URL ?>iku/manage/" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-gear"></i> Kelola Indikator & Target
                </a>
            <?php endif; ?>
            <button type="button" id="btnExportIku" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </button>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3" id="ikuKategoriTabs">
        <li class="nav-item">
            <a class="nav-link active" href="#" data-kategori="wajib">IKU Wajib <span class="badge bg-danger ms-1">27</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-kategori="pilihan">IKU Pilihan <span class="badge bg-warning text-dark ms-1">12</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-kategori="partisipatif">IKU Partisipatif <span class="badge bg-secondary ms-1">1</span></a>
        </li>
    </ul>

    <div id="ikuContainer"></div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
    IKU_IS_AUDITEE = <?= Auth::isAuditee() ? 'true' : 'false' ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/iku.js"></script>