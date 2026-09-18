<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/guard.php';

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'approve':

            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
                break;
            }

            $stmt = $conn->prepare("
                UPDATE audit_assignments
                SET approved = 1, approved_by = ?, approved_at = NOW()
                WHERE id = ?
            ");

            $stmt->bind_param("ii", $_SESSION['user_id'], $id);
            $stmt->execute();

            $stmtInfo = $conn->prepare("
                SELECT a.assignment_number, us.full_name AS lead_name, us.email AS lead_email, u.name AS unit_name
                FROM audit_assignments a
                LEFT JOIN users us ON us.id = a.lead_auditor
                LEFT JOIN units u ON u.id = a.auditee_id
                WHERE a.id = ?
                LIMIT 1
            ");
            $stmtInfo->bind_param("i", $id);
            $stmtInfo->execute();
            $info = $stmtInfo->get_result()->fetch_assoc();

            if ($info && !empty($info['lead_email'])) {

                require_once __DIR__ . '/../core/Mailer.php';

                $mailer = new Mailer();

                $body = "
                    <p>Yth. " . htmlspecialchars($info['lead_name'] ?? 'Bapak/Ibu') . ",</p>
                    <p>Penugasan audit dengan nomor <strong>" . htmlspecialchars($info['assignment_number']) . "</strong> untuk unit <strong>" . htmlspecialchars($info['unit_name'] ?? '-') . "</strong> telah disetujui oleh Auditee.</p>
                    <p>Silakan login ke SIQUA untuk melanjutkan proses audit (Workspace Audit).</p>
                    <p>Terima kasih.</p>
                ";

            $mailer->send($info['lead_email'], $info['lead_name'] ?? '', 'Penugasan Audit Disetujui - ' . $info['assignment_number'], $body);

                $stmtLeadId = $conn->prepare("SELECT lead_auditor FROM audit_assignments WHERE id = ? LIMIT 1");
                $stmtLeadId->bind_param("i", $id);
                $stmtLeadId->execute();
                $leadIdRow = $stmtLeadId->get_result()->fetch_assoc();

                require_once __DIR__ . '/../core/Notifier.php';

                Notifier::send(
                    $conn,
                    (int) ($leadIdRow['lead_auditor'] ?? 0),
                    'Penugasan Disetujui',
                    'Penugasan ' . $info['assignment_number'] . ' telah disetujui Auditee.',
                    BASE_URL . 'audit/workspace/'
                );
            }

            echo json_encode(['success' => true, 'message' => 'Penugasan berhasil disetujui.']);

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}