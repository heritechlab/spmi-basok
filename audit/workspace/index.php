<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../assignments/repository.php';

$config = require __DIR__ . '/config.php';

$assignmentId = (int)($_GET['assignment_id'] ?? 0);

$assignmentRepo = new AssignmentRepository($conn);
$assignment = $assignmentId > 0 ? $assignmentRepo->findById($assignmentId) : null;

if ($assignment && (int)($_SESSION['role_id'] ?? 0) === 3) {

    $myUserId = (int) ($_SESSION['user_id'] ?? 0);

    $stmtCheck = $conn->prepare("
        SELECT a.id
        FROM audit_assignments a
        LEFT JOIN audit_team_members tm ON tm.assignment_id = a.id AND tm.status = 'Aktif'
        WHERE a.id = ? AND (a.lead_auditor = ? OR tm.user_id = ?)
        LIMIT 1
    ");
    $stmtCheck->bind_param("iii", $assignmentId, $myUserId, $myUserId);
    $stmtCheck->execute();

    if (!$stmtCheck->get_result()->fetch_assoc()) {
        die('Akses ditolak. Penugasan ini bukan tanggung jawab Anda.');
    }

}

if ((int)($_SESSION['role_id'] ?? 0) === 3) {

    $myUserId = (int) ($_SESSION['user_id'] ?? 0);

    $stmt = $conn->prepare("
        SELECT DISTINCT
            a.id, a.assignment_number, a.audit_date, a.status,
            u.name AS auditee_name
        FROM audit_assignments a
        LEFT JOIN units u ON u.id = a.auditee_id
        LEFT JOIN audit_team_members tm ON tm.assignment_id = a.id AND tm.status = 'Aktif'
        WHERE a.lead_auditor = ? OR tm.user_id = ?
        ORDER BY a.audit_date DESC
    ");
    $stmt->bind_param("ii", $myUserId, $myUserId);
    $stmt->execute();
    $allAssignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} else {

    $allAssignments = $assignmentRepo->getAll('', 0, '', 200, 0);

}

require_once __DIR__ . '/../../layouts/app.php';

?>

<style>
    #checklistContainer h6 {
        font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
        color: #5b21b6 !important; background: #f6f3fc; padding: 8px 12px; border-radius: 8px;
        border-left: 3px solid #7c3aed;
    }
    #checklistContainer table { font-size: 12.5px; border-collapse: separate; border-spacing: 0; }
    #checklistContainer table thead th {
        background: #faf9fd; color: #6b6785; font-weight: 600; font-size: 10.5px;
        text-transform: uppercase; letter-spacing: .5px; padding: 10px 12px;
        border-bottom: 1px solid #eceaf5; border-top: none; vertical-align: middle;
    }
    #checklistContainer table tbody td {
        padding: 9px 12px; vertical-align: middle; color: #2d2a45; font-weight: 400;
        border-color: #f0eef7;
    }
    #checklistContainer table tbody tr:nth-child(even) { background: #faf9fd; }
    #checklistContainer table tbody tr:hover { background: #f1edfc; }
    #checklistContainer table tbody td:first-child { border-left: 3px solid #ddd0f7; font-weight: 600; color: #14112b; }
    #checklistContainer table tbody tr:hover td:first-child { border-left-color: #7c3aed; }
    #checklistContainer .badge { font-weight: 600; font-size: 10.5px; padding: 4px 10px; border-radius: 20px; }
    #checklistContainer .btn-fill { border-radius: 8px; padding: 5px 10px; font-size: 11px; }
    #wsInfoCard, #wsProgressCard {
        border: 1px solid #eceaf5 !important; border-radius: 14px;
        box-shadow: 0 1px 3px rgba(20,17,43,0.04), 0 8px 20px rgba(20,17,43,0.03) !important;
    }
    #wsInfoCard .col-md-3 {
        padding: 14px 16px; border-right: 1px solid #f0eef7;
    }
    #wsInfoCard .col-md-3:last-child { border-right: none; }
    #wsInfoCard small.text-muted {
        font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
        color: #a39fb5 !important; margin-bottom: 4px;
    }
    #wsInfoCard strong {
        font-size: 13.5px; font-weight: 700; color: #14112b; display: block; line-height: 1.35;
    }
    #wsInfoCard .text-muted.small { font-size: 11px; color: #9490a8 !important; margin-top: 2px; }
    #wsInfoCard .ws-code-badge {
        display: inline-block; background: #f1edfc; color: #5b21b6 !important;
        padding: 4px 10px; border-radius: 7px; font-size: 12px; font-weight: 700;
        letter-spacing: .2px; font-family: 'Courier New', monospace;
    }
    #wsInfoCard .ws-unit-name {
        color: #6d28d9 !important; font-weight: 800;
    }

    #wsProgressCard #progressLabel { font-size: 12.5px; font-weight: 700; color: #14112b; }
    #wsProgressCard .progress { border-radius: 20px; background: #f3f1f9; overflow: hidden; }
    #wsProgressCard .progress-bar {
        background: linear-gradient(90deg, #7c3aed, #a78bfa) !important;
        border-radius: 20px;
    }
        #wsPickerCard {
        border: 1px solid #eceaf5 !important; border-radius: 16px;
        box-shadow: 0 1px 3px rgba(20,17,43,0.04), 0 8px 20px rgba(20,17,43,0.03) !important;
    }
    #wsPickerCard .card-body { padding: 28px; text-align: center; }
    #wsPickerCard .ws-picker-icon {
        width: 52px; height: 52px; border-radius: 14px; background: #f1edfc; color: #7c3aed;
        display: flex; align-items: center; justify-content: center; font-size: 24px;
        margin: 0 auto 14px;
    }
    #wsPickerCard .form-label {
        font-size: 15px; font-weight: 700; color: #14112b; text-transform: none;
        letter-spacing: 0; margin-bottom: 4px;
    }
    #wsPickerCard .d-flex.gap-2 { max-width: 520px; margin: 0 auto; }
    #wsPickerCard #pickerAssignment {
        font-size: 12.5px; border: 1px solid #e2dff2; border-radius: 10px; padding: 10px 14px;
        transition: border-color .15s, box-shadow .15s;
    }
    #wsPickerCard #pickerAssignment:focus {
        border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.10); outline: none;
    }
    #wsPickerCard #btnOpenWorkspace {
        background: #7c3aed; border-color: #7c3aed; border-radius: 10px; padding: 10px 20px;
        font-weight: 600; font-size: 12.5px; white-space: nowrap;
        transition: all .15s;
    }
    #wsPickerCard #btnOpenWorkspace:hover { background: #6d28d9; border-color: #6d28d9; transform: translateY(-1px); }
</style>

<div class="container-fluid py-4">

    <h4 class="mb-3">Lembar Kerja Audit (Workspace Auditor)</h4>

    <div class="dual-card-row mb-3">

        <div class="greeting-card">

            <?php
                $hour = (int) date('H');
                if ($hour < 11) { $greeting = 'Selamat Pagi'; }
                elseif ($hour < 15) { $greeting = 'Selamat Siang'; }
                elseif ($hour < 18) { $greeting = 'Selamat Sore'; }
                else { $greeting = 'Selamat Malam'; }
            ?>

            <div class="greeting-date">
                <i class="bi bi-calendar3"></i>
                <?php
                    $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                    echo date('d') . ' ' . $bulan[(int) date('n')] . ' ' . date('Y');
                ?>
            </div>

            <div class="indicator-summary-title">
                <?= $greeting ?>, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Auditor') ?>!
            </div>

            <div class="indicator-summary-greeting">
                Hai, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Auditor') ?>! Selamat melaksanakan Audit Mutu Internal.
            </div>

            <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Admin" class="standard-summary-hero">

        </div>

        <div class="info-card info-card-compact">

            <div class="info-card-header">
                <i class="bi bi-bar-chart-fill"></i>
                Ringkasan Data
            </div>

            <div class="info-card-body">

                <div class="info-card-total">
                    <div class="info-card-total-label">Total Penugasan</div>
                    <div class="info-card-total-value count-up" data-target="<?= count($allAssignments) ?>">0</div>
                </div>

            </div>

        </div>

    </div>

    <?php require_once __DIR__ . '/../tabs.php'; ?>

<?php if (!$assignment): ?>

        <div class="card shadow-sm mb-3" id="wsPickerCard">
            <div class="card-body">
                <div class="ws-picker-icon"><i class="bi bi-clipboard2-check"></i></div>
                <label class="form-label">Pilih Penugasan Audit</label>
                <div class="text-muted small mb-3">Pilih salah satu penugasan di bawah untuk mulai mengisi Checklist Indikator Mutu.</div>
                <div class="d-flex gap-2">
                    <select class="form-select" id="pickerAssignment">
                        <option value="">-- Pilih Penugasan --</option>
                        <?php foreach ($allAssignments as $a): ?>
                            <option value="<?= $a['id'] ?>">
                                <?= htmlspecialchars($a['assignment_number']) ?> - <?= htmlspecialchars($a['auditee_name'] ?? '-') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary" id="btnOpenWorkspace">
                        <i class="bi bi-arrow-right-circle"></i>
                        Buka
                    </button>
                </div>
            </div>
        </div>

        <script>
            document.getElementById('btnOpenWorkspace').addEventListener('click', function () {
                const id = document.getElementById('pickerAssignment').value;
                if (!id) {
                    alert('Silakan pilih penugasan terlebih dahulu.');
                    return;
                }
                window.location.href = "<?= BASE_URL ?>audit/workspace/?assignment_id=" + id;
            });
        </script>

    <?php else: ?>

        <div class="card shadow-sm mb-3" id="wsInfoCard">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <small class="text-muted d-block">No. Penugasan</small>
                        <strong class="ws-code-badge"><?= htmlspecialchars($assignment['assignment_number']) ?></strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Unit Kerja (Auditee)</small>
                        <strong class="ws-unit-name"><?= htmlspecialchars($assignment['auditee_name'] ?? '-') ?></strong>
                        <?php if (!empty($assignment['auditee_head'])): ?>
                            <div class="text-muted small">PIC: <?= htmlspecialchars($assignment['auditee_head']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Ketua Tim Audit</small>
                        <strong><?= htmlspecialchars($assignment['lead_auditor_name'] ?? '-') ?></strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Periode</small>
                        <strong><?= htmlspecialchars($assignment['period_name'] ?? '-') ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3" id="wsProgressCard">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong id="progressLabel">Progres Pengisian: 0 / 0 (0%)</strong>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-success" id="progressBar" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <strong>Checklist Indikator Mutu</strong>
            </div>
            <div class="card-body">
                <div id="checklistContainer">
                    <p class="text-muted">Memuat data checklist...</p>
                </div>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php if ($assignment): ?>
<?php require_once __DIR__ . '/views/modal.php'; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.count-up').forEach(function (el) {
        const target = parseInt(el.getAttribute('data-target'), 10) || 0;
        let current = 0;
        const step = Math.max(1, Math.ceil(target / 30));
        const timer = setInterval(function () {
            current += step;
            if (current >= target) { current = target; clearInterval(timer); }
            el.textContent = current;
        }, 30);
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<?php if ($assignment): ?>
<script>
    const ASSIGNMENT_ID = <?= (int) $assignmentId ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/workspace.js"></script>
<?php endif; ?>