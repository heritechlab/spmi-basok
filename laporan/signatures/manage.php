<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../master/periods/repository.php';

if (!Auth::canManage()) {
    die('Anda tidak memiliki akses ke halaman ini.');
}

$reportType = trim($_GET['type'] ?? '');
$periodId = (int) ($_GET['period_id'] ?? 0);
$unitId = !empty($_GET['unit_id']) ? (int) $_GET['unit_id'] : null;

if ($reportType === 'rtm' && !empty($_GET['meeting_id'])) {

    $meetingId = (int) $_GET['meeting_id'];

    $stmtMeeting = $conn->prepare("SELECT unit_id, period_id FROM rtm_meetings WHERE id = ? LIMIT 1");
    $stmtMeeting->bind_param("i", $meetingId);
    $stmtMeeting->execute();
    $meetingRow = $stmtMeeting->get_result()->fetch_assoc();

    if (!$meetingRow) {
        die('Data Rapat tidak ditemukan.');
    }

    $unitId = !empty($meetingRow['unit_id']) ? (int) $meetingRow['unit_id'] : null;
    $periodId = (int) $meetingRow['period_id'];

    if (!$unitId) {
        die('Rapat ini belum memiliki Unit Kerja. Silakan lengkapi Unit Kerja pada data Rapat terlebih dahulu (Edit Rapat), baru coba lagi.');
    }
}

if (!in_array($reportType, ['unit', 'institusi', 'rtm', 'ptp'], true) || $periodId <= 0) {
    die('Parameter tidak valid.');
}

if (in_array($reportType, ['unit', 'rtm', 'ptp'], true) && !$unitId) {
    die('Unit Kerja wajib dipilih.');
}

$repository = new LaporanSignatureRepository($conn);
$service    = new LaporanSignatureService($repository);

$result = $service->find($reportType, $periodId, $unitId);
$data = $result['data'] ?? [];

$periodRepo = new PeriodRepository($conn);
$period = $periodRepo->findById($periodId) ?? [];

$unitName = '';
if ($unitId) {
    $stmtUnit = $conn->prepare("SELECT name FROM units WHERE id = ? LIMIT 1");
    $stmtUnit->bind_param("i", $unitId);
    $stmtUnit->execute();
    $unitRow = $stmtUnit->get_result()->fetch_assoc();
    $unitName = $unitRow['name'] ?? '';
}

require_once __DIR__ . '/../../layouts/app.php';

?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <?php
            $reportLabels = ['unit' => 'AMI per Unit', 'institusi' => 'AMI Institusi', 'rtm' => 'RTM Pengendalian', 'ptp' => 'PTP (Permintaan Tindakan Peningkatan)'];
        ?>
        <h4 class="mb-0">Kelola TTD Laporan <?= $reportLabels[$reportType] ?? '' ?></h4>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="history.back()">
            <i class="bi bi-arrow-left"></i> Kembali
        </button>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row">
                <?php if (in_array($reportType, ['unit', 'rtm', 'ptp'], true)): ?>
                <div class="col-md-6">
                    <small class="text-muted d-block">Unit Kerja</small>
                    <strong><?= htmlspecialchars($unitName) ?></strong>
                </div>
                <?php endif; ?>
                <div class="col-md-6">
                    <small class="text-muted d-block">Periode Audit</small>
                    <strong><?= htmlspecialchars($period['period_name'] ?? '-') ?></strong>
                </div>
            </div>
        </div>
    </div>

    <form id="signatureForm" enctype="multipart/form-data">

        <input type="hidden" name="report_type" value="<?= $reportType ?>">
        <input type="hidden" name="period_id" value="<?= $periodId ?>">
        <?php if ($unitId): ?>
            <input type="hidden" name="unit_id" value="<?= $unitId ?>">
        <?php endif; ?>

        <?php if ($reportType === 'unit'): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header"><strong>Ketua Tim Audit</strong></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label small">Nama</label>
                        <input type="text" class="form-control form-control-sm" name="ketua_tim_nama" value="<?= htmlspecialchars($data['ketua_tim_nama'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label small">Tanggal</label>
                        <input type="date" class="form-control form-control-sm" name="ketua_tim_tanggal" value="<?= htmlspecialchars($data['ketua_tim_tanggal'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label small">Tanda Tangan (Gambar)</label>
                        <div class="mb-1">
                            <?php if (!empty($data['ketua_tim_ttd'])): ?>
                                <img src="<?= BASE_URL . htmlspecialchars($data['ketua_tim_ttd']) ?>" style="max-height:45px; border:1px solid #ddd; border-radius:4px; padding:2px;">
                            <?php else: ?>
                                <span class="text-muted small">Belum ada TTD.</span>
                            <?php endif; ?>
                        </div>
                        <input type="file" class="form-control form-control-sm" name="ketua_tim_ttd" accept="image/jpeg,image/png">
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-3">
            <div class="card-header"><strong>Ketua LPM</strong></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label small">Nama</label>
                        <input type="text" class="form-control form-control-sm" name="ketua_lpm_nama" value="<?= htmlspecialchars($data['ketua_lpm_nama'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label small">Tanggal</label>
                        <input type="date" class="form-control form-control-sm" name="ketua_lpm_tanggal" value="<?= htmlspecialchars($data['ketua_lpm_tanggal'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label small">Tanda Tangan (Gambar)</label>
                        <div class="mb-1">
                            <?php if (!empty($data['ketua_lpm_ttd'])): ?>
                                <img src="<?= BASE_URL . htmlspecialchars($data['ketua_lpm_ttd']) ?>" style="max-height:45px; border:1px solid #ddd; border-radius:4px; padding:2px;">
                            <?php else: ?>
                                <span class="text-muted small">Belum ada TTD.</span>
                            <?php endif; ?>
                        </div>
                        <input type="file" class="form-control form-control-sm" name="ketua_lpm_ttd" accept="image/jpeg,image/png">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header"><strong>Ketua Institusi</strong></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label small">Nama</label>
                        <input type="text" class="form-control form-control-sm" name="ketua_institusi_nama" value="<?= htmlspecialchars($data['ketua_institusi_nama'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label small">Tanggal</label>
                        <input type="date" class="form-control form-control-sm" name="ketua_institusi_tanggal" value="<?= htmlspecialchars($data['ketua_institusi_tanggal'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label small">Tanda Tangan (Gambar)</label>
                        <div class="mb-1">
                            <?php if (!empty($data['ketua_institusi_ttd'])): ?>
                                <img src="<?= BASE_URL . htmlspecialchars($data['ketua_institusi_ttd']) ?>" style="max-height:45px; border:1px solid #ddd; border-radius:4px; padding:2px;">
                            <?php else: ?>
                                <span class="text-muted small">Belum ada TTD.</span>
                            <?php endif; ?>
                        </div>
                        <input type="file" class="form-control form-control-sm" name="ketua_institusi_ttd" accept="image/jpeg,image/png">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Simpan Data TTD
        </button>

    </form>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<script>
$(document).ready(function () {
    $("#signatureForm").on("submit", function (e) {
        e.preventDefault();

        const formData = new FormData(this);

        Swal.fire({ title: "Menyimpan...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        $.ajax({
            url: "<?= BASE_URL ?>laporan/signatures/api.php?action=save",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function (response) {
                Swal.close();

                if (!response.success) {
                    Swal.fire("Gagal", response.message, "error");
                    return;
                }

                Swal.fire({ icon: "success", title: "Berhasil", text: response.message, timer: 1500, showConfirmButton: false }).then(function () {
                    location.reload();
                });
            },
            error: function () {
                Swal.close();
                Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
            },
        });
    });
});
</script>