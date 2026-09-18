<?php

declare(strict_types=1);

if ((int)($_SESSION['role_id'] ?? 0) !== 3) {
    return;
}

$myUserId = (int) ($_SESSION['user_id'] ?? 0);

$stmt = $conn->prepare("
    SELECT DISTINCT
        a.id, a.assignment_number, a.audit_date, a.status,
        p.period_name,
        u.name AS auditee_name
    FROM audit_assignments a
    LEFT JOIN audit_periods p ON p.id = a.period_id
    LEFT JOIN units u ON u.id = a.auditee_id
    LEFT JOIN audit_team_members tm ON tm.assignment_id = a.id AND tm.status = 'Aktif'
    WHERE a.lead_auditor = ? OR tm.user_id = ?
    ORDER BY a.audit_date DESC
    LIMIT 10
");

$stmt->bind_param("ii", $myUserId, $myUserId);
$stmt->execute();
$myAssignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($myAssignments as &$a) {

    $stmtProgress = $conn->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN r.audit_status IS NOT NULL AND r.audit_status <> '' THEN 1 ELSE 0 END) AS filled
        FROM audit_checklists c
        LEFT JOIN audit_checklist_results r ON r.checklist_id = c.id
        WHERE c.assignment_id = ?
    ");
    $stmtProgress->bind_param("i", $a['id']);
    $stmtProgress->execute();
    $prog = $stmtProgress->get_result()->fetch_assoc();

    $total = (int) ($prog['total'] ?? 0);
    $filled = (int) ($prog['filled'] ?? 0);

    $a['progress_percent'] = $total > 0 ? round(($filled / $total) * 100) : 0;
}
unset($a);

$statusBadge = ['Draft' => 'secondary', 'Dijadwalkan' => 'info', 'Berlangsung' => 'warning', 'Selesai' => 'success'];

?>

<div class="card shadow-sm mb-4">

    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-clipboard-check"></i> Penugasan Audit Saya</strong>
        <a href="<?= BASE_URL ?>audit/workspace/" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
    </div>

    <div class="card-body">

        <?php if (empty($myAssignments)): ?>

            <p class="text-muted mb-0">Belum ada penugasan audit untuk Anda saat ini.</p>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>No. Penugasan</th>
                            <th>Unit Kerja</th>
                            <th width="110">Periode</th>
                            <th width="100">Tanggal</th>
                            <th width="180">Progres LKA</th>
                            <th width="100">Status</th>
                            <th width="80">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myAssignments as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['assignment_number']) ?></td>
                                <td><?= htmlspecialchars($a['auditee_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($a['period_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($a['audit_date'] ?? '-') ?></td>
                                <td>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: <?= $a['progress_percent'] ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?= $a['progress_percent'] ?>% selesai</small>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $statusBadge[$a['status']] ?? 'secondary' ?>"><?= htmlspecialchars($a['status']) ?></span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>audit/workspace/?assignment_id=<?= $a['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-journal-text"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </div>

</div>