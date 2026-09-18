<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';

if (!Auth::canManage()) {
    die('Anda tidak memiliki akses ke halaman ini.');
}

$repository = new CapaianKriteriaRepository($conn);
$service    = new CapaianKriteriaService($repository);

$prodiCriteria = $service->getProdiCriteria()['data'];
$institutionCriteria = $service->getInstitutionCriteria()['data'];

require_once __DIR__ . '/../../layouts/app.php';

?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Kelola Pemetaan Standar ke Kriteria</h4>
        <a href="<?= BASE_URL ?>capaian_kriteria/" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" style="font-size:13px;">
                    <thead class="table-light">
                        <tr>
                            <th width="90">Kode</th>
                            <th>Nama Standar</th>
                            <th width="260">Kriteria Prodi (8)</th>
                            <th width="280">Kriteria Institusi (4)</th>
                        </tr>
                    </thead>
                    <tbody id="ckMappingTbody">
                        <tr><td colspan="4" class="text-center text-muted">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script>
    CK_PRODI_CRITERIA = <?= json_encode($prodiCriteria) ?>;
    CK_INST_CRITERIA = <?= json_encode($institutionCriteria) ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/capaian_kriteria_manage.js"></script>