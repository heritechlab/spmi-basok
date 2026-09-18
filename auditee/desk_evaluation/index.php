<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

$redirectAssignmentId = (int) ($_GET['assignment_id'] ?? 0);
$redirectUrl = BASE_URL . 'led_prodi/' . ($redirectAssignmentId > 0 ? '?assignment_id=' . $redirectAssignmentId : '');

header('Location: ' . $redirectUrl);
exit;

require_once __DIR__ . '/../guard.php';
require_once __DIR__ . '/../../master/standards/repository.php';

$assignmentId = (int)($_GET['assignment_id'] ?? 0);

/*
|--------------------------------------------------------------------------
| Ambil unit milik Auditee yang login
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("SELECT unit_id FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$myUnitId = (int)($me['unit_id'] ?? 0);

/*
|--------------------------------------------------------------------------
| Ambil penugasan & pastikan memang milik unit ini
|--------------------------------------------------------------------------
*/

$assignment = null;

if ($assignmentId > 0) {

    $stmt = $conn->prepare("
        SELECT a.id, a.assignment_number, a.auditee_id, u.name AS auditee_name
        FROM audit_assignments a
        LEFT JOIN units u ON u.id = a.auditee_id
        WHERE a.id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $assignmentId);
    $stmt->execute();
    $assignment = $stmt->get_result()->fetch_assoc();

    if (!$assignment || (int)$assignment['auditee_id'] !== $myUnitId) {
        die('Akses ditolak. Penugasan ini bukan milik unit Anda.');
    }
}

/*
|--------------------------------------------------------------------------
| Kalau belum pilih penugasan, ambil daftar penugasan milik unit ini
|--------------------------------------------------------------------------
*/

$myAssignments = [];

if (!$assignment) {

    $stmt = $conn->prepare("
        SELECT a.id, a.assignment_number, a.audit_date, a.status, p.period_name
        FROM audit_assignments a
        LEFT JOIN audit_periods p ON p.id = a.period_id
        WHERE a.auditee_id = ?
        ORDER BY a.audit_date DESC
    ");
    $stmt->bind_param("i", $myUnitId);
    $stmt->execute();
    $myAssignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/*
|--------------------------------------------------------------------------
| Ambil daftar standar aktif + status evaluasi masing-masing
|--------------------------------------------------------------------------
*/

$stmtStdIds = $conn->prepare("SELECT standard_id FROM assignment_standards WHERE assignment_id = ?");
$stmtStdIds->bind_param("i", $assignmentId);
$stmtStdIds->execute();
$selectedStandardIds = array_column($stmtStdIds->get_result()->fetch_all(MYSQLI_ASSOC), 'standard_id');

$standardRepo = new StandardRepository($conn);
$allStandards = $standardRepo->getStandards();

if (!empty($selectedStandardIds)) {
    $standards = array_values(array_filter($allStandards, function ($s) use ($selectedStandardIds) {
        return in_array((int) $s['id'], array_map('intval', $selectedStandardIds), true);
    }));
} else {
    $standards = $allStandards;
}

$stmt = $conn->prepare("
    SELECT standard_id, status,
        (SELECT COUNT(*) FROM desk_evaluation_documents WHERE desk_evaluation_id = de.id) AS doc_count
    FROM desk_evaluations de
    WHERE assignment_id = ?
");
$stmt->bind_param("i", $assignmentId);
$stmt->execute();
$evalRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$evalMap = [];
foreach ($evalRows as $row) {
    $evalMap[(int)$row['standard_id']] = $row;
}

require_once __DIR__ . '/../../layouts/app.php';

?>

<div class="container-fluid py-4">

    <h4 class="mb-3">Desk Evaluation</h4>

    <?php
        $hour = (int) date('H');
        if ($hour < 11) { $greeting = 'Selamat Pagi'; }
        elseif ($hour < 15) { $greeting = 'Selamat Siang'; }
        elseif ($hour < 18) { $greeting = 'Selamat Sore'; }
        else { $greeting = 'Selamat Malam'; }

        $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

        $totalStandar = isset($standards) ? count($standards) : 0;
        $totalSelesai = 0;

        if (isset($evalMap)) {
            foreach ($evalMap as $ev) {
                if (($ev['status'] ?? '') === 'Selesai') {
                    $totalSelesai++;
                }
            }
        }
    ?>

    <div class="dual-card-row mb-3">

        <div class="greeting-card">

            <div class="greeting-date">
                <i class="bi bi-calendar3"></i>
                <?= date('d') . ' ' . $bulan[(int) date('n')] . ' ' . date('Y') ?>
            </div>

            <div class="indicator-summary-title">
                <?= $greeting ?>, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Auditee') ?>!
            </div>

            <div class="indicator-summary-greeting">
                Lengkapi Desk Evaluation setiap Standar sebelum proses Audit Dokumen dan Visitasi dimulai.
            </div>

            <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Auditee" class="standard-summary-hero">

        </div>

        <div class="info-card info-card-compact">

            <div class="info-card-header">
                <i class="bi bi-bar-chart-fill"></i>
                Ringkasan Desk Evaluation
            </div>

            <div class="info-card-body">

                <div class="info-card-total">
                    <div class="info-card-total-label">Total Standar</div>
                    <div class="info-card-total-value"><?= $totalStandar ?></div>
                </div>

                <?php $pctSelesai = $totalStandar > 0 ? round(($totalSelesai / $totalStandar) * 100) : 0; ?>

                <div class="info-card-items-grid info-card-items-grid-2">

                    <div class="info-card-item accent-green">
                        <div class="info-card-icon"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="info-card-text">
                            <div class="info-card-label">Selesai</div>
                            <div class="info-card-value"><?= $totalSelesai ?></div>
                            <div class="info-card-progress-track">
                                <div class="info-card-progress-fill" style="width: <?= $pctSelesai ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card-item accent-orange">
                        <div class="info-card-icon"><i class="bi bi-hourglass"></i></div>
                        <div class="info-card-text">
                            <div class="info-card-label">Belum</div>
                            <div class="info-card-value"><?= $totalStandar - $totalSelesai ?></div>
                            <div class="info-card-progress-track">
                                <div class="info-card-progress-fill" style="width: <?= 100 - $pctSelesai ?>%"></div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <?php require_once __DIR__ . '/../../audit/tabs.php'; ?>

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
                                            <a href="<?= BASE_URL ?>auditee/desk_evaluation/?assignment_id=<?= $a['id'] ?>" class="btn btn-primary btn-sm">
                                                <i class="bi bi-file-earmark-text"></i> Buka
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
                <div class="card-body">
                    <small class="text-muted d-block">No. Penugasan</small>
                    <strong><?= htmlspecialchars($assignment['assignment_number']) ?></strong>
                    <div class="text-muted mt-1">Unit: <?= htmlspecialchars($assignment['auditee_name']) ?></div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header"><strong>Desk Evaluation per Standar</strong></div>
            <div class="card-body">

                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th width="100">Kode</th>
                            <th>Nama Standar</th>
                            <th width="100">Dokumen</th>
                            <th width="120">Status</th>
                            <th width="100">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($standards as $s): ?>
                            <?php
                                $sid = (int)$s['id'];
                                $eval = $evalMap[$sid] ?? null;
                                $status = $eval['status'] ?? 'Belum';
                                $docCount = $eval['doc_count'] ?? 0;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($s['code']) ?></td>
                                <td><?= htmlspecialchars($s['name']) ?></td>
                                <td><?= $docCount ?> file</td>
                                <td>
                                    <?php if ($status === 'Selesai'): ?>
                                        <span class="badge bg-success">Selesai</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Belum</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm btn-fill-de"
                                        data-assignment="<?= $assignmentId ?>"
                                        data-standard="<?= $sid ?>"
                                        data-standard-name="<?= htmlspecialchars($s['code'] . ' - ' . $s['name']) ?>">
                                        <i class="bi bi-pencil-square"></i> Isi
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            </div>

        </div>

        <?php endif; ?>

</div>

    <?php if ($assignment): ?>
    <?php require_once __DIR__ . '/modal.php'; ?>
    <?php endif; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<script>
    const BASE_URL_DE = "<?= BASE_URL ?>";
</script>
<script src="<?= BASE_URL ?>assets/js/desk_evaluation.js"></script>