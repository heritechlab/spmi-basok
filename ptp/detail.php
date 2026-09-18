<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$id = (int)($_GET['id'] ?? 0);

$repository = new PtpRepository($conn);
$service    = new PtpService($repository);

$result = $service->getById($id);

if (!$result['success']) {
    die('Data rapat PTP tidak ditemukan.');
}

$meeting = $result['data'];

require_once __DIR__ . '/../layouts/app.php';

?>

<style>
    #tableEligible, #tableItems { font-size: 12.5px; border-collapse: separate; border-spacing: 0; }
    #tableEligible thead th, #tableItems thead th {
        background: #f6f3fc; color: #5b21b6; font-weight: 700; font-size: 10.5px;
        text-transform: uppercase; letter-spacing: .5px; padding: 11px 14px;
        border-bottom: 2px solid #ddd0f7; border-top: none; vertical-align: middle;
    }
    #tableEligible tbody td, #tableItems tbody td {
        padding: 10px 14px; vertical-align: middle; color: #2d2a45; font-weight: 400;
        border-color: #f0eef7;
    }
    #tableEligible tbody tr:nth-child(even), #tableItems tbody tr:nth-child(even) { background: #faf9fd; }
    #tableEligible tbody tr:hover, #tableItems tbody tr:hover { background: #f1edfc; }
    #tableEligible tbody td:first-child, #tableItems tbody td:first-child {
        border-left: 3px solid #ddd0f7; font-weight: 600; color: #14112b;
    }
    #tableEligible tbody tr:hover td:first-child, #tableItems tbody tr:hover td:first-child {
        border-left-color: #7c3aed;
    }
    #tableEligible .badge, #tableItems .badge { font-weight: 600; font-size: 10.5px; padding: 4px 10px; border-radius: 20px; }
    #tableEligible .btn-sm, #tableItems .btn-sm { border-radius: 8px; padding: 5px 10px; font-size: 11px; }

    .card:has(#tableEligible), .card:has(#tableItems) {
        border: 1px solid #eceaf5; border-radius: 14px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(20,17,43,0.04), 0 8px 20px rgba(20,17,43,0.03);
    }
    .card:has(#tableEligible) .card-header, .card:has(#tableItems) .card-header {
        background: #faf9fd; border-bottom: 1px solid #eceaf5; padding: 14px 20px;
    }
    .card:has(#tableEligible) .card-header strong, .card:has(#tableItems) .card-header strong {
        font-size: 13.5px; font-weight: 700; color: #14112b; letter-spacing: -.1px;
    }
    .card:has(#tableEligible) .card-body, .card:has(#tableItems) .card-body { padding: 20px; }
        #ptpInfoCard {
        background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 100%);
        border: none; border-radius: 16px;
        box-shadow: 0 8px 24px rgba(76,29,149,0.22);
    }
    #ptpInfoCard .card-body { padding: 22px 24px; }
    #ptpInfoCard small.text-muted {
        font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
        color: rgba(255,255,255,0.65) !important; margin-bottom: 4px;
    }
    #ptpInfoCard strong {
        font-size: 13.5px; font-weight: 700; color: #fff; display: block; line-height: 1.35;
    }
    #ptpInfoCard hr { border-color: rgba(255,255,255,0.18); margin: 16px 0 12px; }
    #ptpInfoCard .badge, #ptpInfoCard .d-flex.flex-wrap span {
        background: rgba(255,255,255,0.14) !important; color: #fff !important;
        border: 1px solid rgba(255,255,255,0.2);
    }
</style>

<div class="container-fluid py-4">

    <h4 class="mb-3">Kelola Rapat PTP</h4>

    <div class="card shadow-sm mb-3" id="ptpInfoCard">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <small class="text-muted d-block">No. Rapat</small>
                    <strong><?= htmlspecialchars($meeting['meeting_number']) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Unit Kerja</small>
                    <strong><?= htmlspecialchars($meeting['unit_name'] ?? '-') ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Periode</small>
                    <strong><?= htmlspecialchars($meeting['period_name'] ?? '-') ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Tanggal & Waktu</small>
                    <strong>
                        <?= htmlspecialchars($meeting['meeting_date'] ?? '-') ?>
                        <?php if (!empty($meeting['start_time'])): ?>
                            (<?= htmlspecialchars(substr($meeting['start_time'], 0, 5)) ?> - <?= htmlspecialchars(substr($meeting['end_time'] ?? '', 0, 5)) ?>)
                        <?php endif; ?>
                    </strong>
                </div>
            </div>

            <?php if (!empty($meeting['participants'])): ?>
                <hr>
                <small class="text-muted d-block mb-1">Peserta Rapat</small>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($meeting['participants'] as $p): ?>
                        <span class="badge bg-light text-dark border">
                            <?= htmlspecialchars($p['full_name']) ?>
                            <?php if (!empty($p['position'])): ?> - <?= htmlspecialchars($p['position']) ?><?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- INDIKATOR MENCAPAI/MELAMPAUI (BAHAN USULAN) -->
    <!-- ===================================================== -->

    <?php if (Auth::canManagePtp()): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-header">
            <strong>Indikator Mencapai/Melampaui (Bahan Usulan Peningkatan)</strong>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="tableEligible">
                    <thead>
                        <tr>
                            <th width="90">Kode</th>
                            <th>Standar</th>
                            <th>Indikator</th>
                            <th width="90">Target</th>
                            <th width="100">Status</th>
                            <th width="100">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="eligibleTableBody">
                        <tr><td colspan="6" class="text-muted">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===================================================== -->
    <!-- DAFTAR USULAN PENINGKATAN -->
    <!-- ===================================================== -->

    <div class="card shadow-sm">
        <div class="card-header">
            <strong>Daftar Usulan Peningkatan</strong>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="tableItems">
                    <thead>
                        <tr>
                            <th>Indikator</th>
                            <th>Sebelum</th>
                            <th>Sesudah (Usulan)</th>
                            <th width="100">Status</th>
                            <th width="140">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="itemsTableBody">
                        <tr><td colspan="5" class="text-muted">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/views/item_modal.php'; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
    const PTP_MEETING_ID = <?= (int) $id ?>;
    const PTP_UNIT_ID = <?= (int) $meeting['unit_id'] ?>;
    const PTP_PERIOD_ID = <?= (int) $meeting['period_id'] ?>;
    const CAN_MANAGE_PTP = <?= Auth::canManagePtp() ? 'true' : 'false' ?>;
    const CAN_APPLY_PTP = <?= Auth::canVerifyRtl() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/ptp_detail.js"></script>