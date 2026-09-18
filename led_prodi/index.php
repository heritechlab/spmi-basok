<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../led_shared/repository.php';
require_once __DIR__ . '/../led_shared/service.php';

$repository = new LedRepository($conn);
$service    = new LedService($repository);

$assignmentId = (int) ($_GET['assignment_id'] ?? 0);
$myUnitId = $service->getMyUnitId($_SESSION['user_id'] ?? 0);

$assignment = null;
$myAssignments = [];

if ($assignmentId > 0) {

    $assignment = $service->getAssignment($assignmentId);

    if (!$assignment || (int) $assignment['auditee_id'] !== $myUnitId) {
        die('Akses ditolak. Penugasan ini bukan milik unit Anda.');
    }

} else {

    $myAssignments = $service->getMyAssignments($myUnitId)['data'];
}

require_once __DIR__ . '/../layouts/app.php';

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="container-fluid py-4">

    <?php require_once __DIR__ . '/../audit/tabs.php'; ?>

    <div class="greeting-card mb-3">

        <?php
            $hour = (int) date('H');
            if ($hour < 11) { $greeting = 'Selamat Pagi'; }
            elseif ($hour < 15) { $greeting = 'Selamat Siang'; }
            elseif ($hour < 18) { $greeting = 'Selamat Sore'; }
            else { $greeting = 'Selamat Malam'; }

            $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        ?>

        <div class="greeting-date">
            <i class="bi bi-calendar3"></i>
            <?= date('d') . ' ' . $bulan[(int) date('n')] . ' ' . date('Y') ?>
        </div>

        <div class="indicator-summary-title">
            <?= $greeting ?>, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Auditee') ?>!
        </div>

        <div class="indicator-summary-greeting">
            Lengkapi LED (Laporan Evaluasi Diri) setiap Standar sebelum proses Audit Dokumen dan Visitasi dimulai.
        </div>

        <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Auditee" class="standard-summary-hero">

    </div>

    <h4 class="mb-3">LED (Laporan Evaluasi Diri) — Program Studi</h4>

    <?php if (!$assignment): ?>

        <div class="card shadow-sm">
            <div class="card-header"><strong>Pilih Penugasan Audit</strong></div>
            <div class="card-body">

                <?php if (empty($myAssignments)): ?>

                    <p class="text-muted mb-0">Belum ada penugasan audit untuk unit Anda.</p>

                <?php else: ?>

                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>No. Penugasan</th>
                                <th>Periode</th>
                                <th width="110">Tanggal</th>
                                <th width="120">Status</th>
                                <th width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myAssignments as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['assignment_number']) ?></td>
                                    <td><?= htmlspecialchars($a['period_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($a['audit_date'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($a['status']) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>led_prodi/?assignment_id=<?= $a['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-file-earmark-text"></i> Buka LED
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php endif; ?>

            </div>
        </div>

    <?php else: ?>

        <div class="card shadow-sm mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted d-block">No. Penugasan</small>
                    <strong><?= htmlspecialchars($assignment['assignment_number']) ?></strong>
                    <div class="text-muted mt-1">Unit: <?= htmlspecialchars($assignment['auditee_name']) ?></div>
                </div>
                <a href="<?= BASE_URL ?>led_prodi/" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Ganti Penugasan</a>
            </div>
        </div>

        <div id="ledContainer"></div>

    <?php endif; ?>

</div>

<!-- Modal Isian -->
<div class="modal fade" id="ledEntryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="ledEntryForm" enctype="multipart/form-data">
                <input type="hidden" id="entry_assignment_id" name="assignment_id" value="<?= $assignmentId ?>">
                <input type="hidden" id="entry_standard_id" name="standard_id">
                <input type="hidden" id="entry_indicator_id" name="indicator_id">
                <div class="modal-header">
                    <h5 class="modal-title">Isian Evaluasi Diri</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <p class="text-muted small" id="entryIndicatorLabel"></p>

                    <div class="mb-2">
                        <label class="form-label small">Capaian Target</label>
                        <input type="text" class="form-control form-control-sm" name="capaian_realisasi" id="entry_capaian" placeholder="Isi capaian/realisasi sesuai target">
                    </div>

                    <div class="mb-2">
                        <label class="form-label small">Pernyataan Evaluasi</label>
                        <textarea class="form-control form-control-sm" name="pernyataan_evaluasi" id="entry_evaluasi" rows="4" placeholder="Jelaskan secara singkat dan ringkas bagaimana PS mencapai sub-elemen ini..."></textarea>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small">Unggah Dokumen</label>
                            <input type="file" class="form-control form-control-sm" name="dokumen_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Link Dokumen (Opsional)</label>
                            <input type="text" class="form-control form-control-sm" name="dokumen_link" id="entry_link" placeholder="https://...">
                        </div>
                    </div>

                    <div id="entryExistingDocs"></div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script>
    LED_LEVEL = 'prodi';
    LED_ASSIGNMENT_ID = <?= $assignmentId ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/led.js"></script>