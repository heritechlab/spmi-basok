<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../guard.php';
require_once __DIR__ . '/../../core/UploadHelper.php';

function jsonResponse(bool $success, string $message, $data = null): void {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Ambil unit_id milik user login (buat validasi kepemilikan)
|--------------------------------------------------------------------------
*/

$stmtMe = $conn->prepare("SELECT unit_id FROM users WHERE id = ? LIMIT 1");
$stmtMe->bind_param("i", $_SESSION['user_id']);
$stmtMe->execute();
$myUnitId = (int)($stmtMe->get_result()->fetch_assoc()['unit_id'] ?? 0);

function verifyOwnership(mysqli $conn, int $assignmentId, int $myUnitId): bool {
    $stmt = $conn->prepare("SELECT auditee_id FROM audit_assignments WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $assignmentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row && (int)$row['auditee_id'] === $myUnitId;
}

function notifyIfDeskEvaluationComplete(mysqli $conn, int $assignmentId): void
{
    try {

        $stmtIds = $conn->prepare("SELECT standard_id FROM assignment_standards WHERE assignment_id = ?");
        $stmtIds->bind_param("i", $assignmentId);
        $stmtIds->execute();
        $selectedStandardIds = array_column($stmtIds->get_result()->fetch_all(MYSQLI_ASSOC), 'standard_id');

        if (!empty($selectedStandardIds)) {
            $totalStandards = count($selectedStandardIds);
        } else {
            $countAll = $conn->query("SELECT COUNT(*) AS total FROM standards WHERE status = 1")->fetch_assoc();
            $totalStandards = (int) ($countAll['total'] ?? 0);
        }

        if ($totalStandards <= 0) {
            return;
        }

        $stmtDone = $conn->prepare("SELECT COUNT(*) AS done FROM desk_evaluations WHERE assignment_id = ? AND status = 'Selesai'");
        $stmtDone->bind_param("i", $assignmentId);
        $stmtDone->execute();
        $totalDone = (int) ($stmtDone->get_result()->fetch_assoc()['done'] ?? 0);

        if ($totalDone < $totalStandards) {
            return;
        }

        $stmtAssignment = $conn->prepare("
            SELECT a.assignment_number, a.desk_eval_notified, us.full_name AS lead_name, us.email AS lead_email, u.name AS unit_name
            FROM audit_assignments a
            LEFT JOIN users us ON us.id = a.lead_auditor
            LEFT JOIN units u ON u.id = a.auditee_id
            WHERE a.id = ?
            LIMIT 1
        ");
        $stmtAssignment->bind_param("i", $assignmentId);
        $stmtAssignment->execute();
        $assignment = $stmtAssignment->get_result()->fetch_assoc();

        if (!$assignment || (int) $assignment['desk_eval_notified'] === 1 || empty($assignment['lead_email'])) {
            return;
        }

        require_once __DIR__ . '/../../core/Mailer.php';

        $mailer = new Mailer();

        $body = "
            <p>Yth. " . htmlspecialchars($assignment['lead_name'] ?? 'Bapak/Ibu') . ",</p>
            <p>Desk Evaluation untuk penugasan audit <strong>" . htmlspecialchars($assignment['assignment_number']) . "</strong> pada unit <strong>" . htmlspecialchars($assignment['unit_name'] ?? '-') . "</strong> telah selesai diisi oleh Auditee untuk seluruh Standar.</p>
            <p>Silakan login ke SIQUA untuk melanjutkan proses Audit Dokumen dan Audit Lapangan (Workspace Audit/LKA).</p>
            <p>Terima kasih.</p>
        ";

        $mailer->send($assignment['lead_email'], $assignment['lead_name'] ?? '', 'Desk Evaluation Selesai - ' . $assignment['assignment_number'], $body);

        $stmtLead = $conn->prepare("SELECT lead_auditor FROM audit_assignments WHERE id = ? LIMIT 1");
        $stmtLead->bind_param("i", $assignmentId);
        $stmtLead->execute();
        $leadRow = $stmtLead->get_result()->fetch_assoc();

        require_once __DIR__ . '/../../core/Notifier.php';

        Notifier::send(
            $conn,
            (int) ($leadRow['lead_auditor'] ?? 0),
            'Desk Evaluation Selesai',
            'Desk Evaluation ' . $assignment['assignment_number'] . ' telah selesai diisi Auditee.',
            BASE_URL . 'audit/workspace/'
        );

        $update = $conn->prepare("UPDATE audit_assignments SET desk_eval_notified = 1 WHERE id = ?");
        $update->bind_param("i", $assignmentId);
        $update->execute();

    } catch (Throwable $e) {
        error_log('Gagal kirim email notifikasi Desk Evaluation selesai: ' . $e->getMessage());
    }
}

/*
|--------------------------------------------------------------------------
| Cari atau buat baris desk_evaluations
|--------------------------------------------------------------------------
*/

function getOrCreateEvaluation(mysqli $conn, int $assignmentId, int $standardId): int {
    $stmt = $conn->prepare("SELECT id FROM desk_evaluations WHERE assignment_id = ? AND standard_id = ? LIMIT 1");
    $stmt->bind_param("ii", $assignmentId, $standardId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        return (int)$row['id'];
    }

    $insert = $conn->prepare("INSERT INTO desk_evaluations (assignment_id, standard_id, status, created_at) VALUES (?, ?, 'Belum', NOW())");
    $insert->bind_param("ii", $assignmentId, $standardId);
    $insert->execute();

    return (int)$conn->insert_id;
}

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

case 'get':

            $assignmentId = (int)($_GET['assignment_id'] ?? 0);
            $standardId = (int)($_GET['standard_id'] ?? 0);

            if (!verifyOwnership($conn, $assignmentId, $myUnitId)) {
                jsonResponse(false, 'Akses ditolak.');
            }

            $stmt = $conn->prepare("SELECT * FROM desk_evaluations WHERE assignment_id = ? AND standard_id = ? LIMIT 1");
            $stmt->bind_param("ii", $assignmentId, $standardId);
            $stmt->execute();
            $eval = $stmt->get_result()->fetch_assoc();

            $stmtItems = $conn->prepare("SELECT id, audit_indicator_id, notes FROM desk_evaluation_items WHERE assignment_id = ? AND standard_id = ?");
            $stmtItems->bind_param("ii", $assignmentId, $standardId);
            $stmtItems->execute();
            $itemRows = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

            $itemNotes = [];
            $itemDocuments = [];

            foreach ($itemRows as $row) {

                $indicatorId = (int) $row['audit_indicator_id'];
                $itemNotes[$indicatorId] = $row['notes'];

                $stmtDoc = $conn->prepare("SELECT id, document_file, document_original_name, link_url FROM desk_evaluation_documents WHERE desk_evaluation_item_id = ?");
                $stmtDoc->bind_param("i", $row['id']);
                $stmtDoc->execute();
                $itemDocuments[$indicatorId] = $stmtDoc->get_result()->fetch_all(MYSQLI_ASSOC);
            }

            jsonResponse(true, 'OK', [
                'status' => $eval['status'] ?? 'Belum',
                'item_notes' => $itemNotes,
                'item_documents' => $itemDocuments,
            ]);

            break;

        case 'indicators':

            $standardId = (int)($_GET['standard_id'] ?? 0);

            $stmt = $conn->prepare("
                SELECT id, item_code, statement, indicator, target
                FROM audit_indicators
                WHERE standard_id = ? AND status = 1
                ORDER BY item_code ASC
            ");
            $stmt->bind_param("i", $standardId);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            jsonResponse(true, 'OK', $rows);

            break;

    case 'save':

            $assignmentId = (int)($_POST['assignment_id'] ?? 0);
            $standardId = (int)($_POST['standard_id'] ?? 0);
            $notesMap = $_POST['notes'] ?? [];

            if (!verifyOwnership($conn, $assignmentId, $myUnitId)) {
                jsonResponse(false, 'Akses ditolak.');
            }

            $evalId = getOrCreateEvaluation($conn, $assignmentId, $standardId);

            $allFilled = !empty($notesMap);
            $itemIdMap = [];

            foreach ($notesMap as $indicatorId => $noteText) {

                $indicatorId = (int) $indicatorId;
                $noteText = trim($noteText);

                if ($noteText === '') {
                    $allFilled = false;
                }

                $check = $conn->prepare("SELECT id FROM desk_evaluation_items WHERE assignment_id = ? AND audit_indicator_id = ? LIMIT 1");
                $check->bind_param("ii", $assignmentId, $indicatorId);
                $check->execute();
                $existingItem = $check->get_result()->fetch_assoc();

                if ($existingItem) {
                    $itemId = (int) $existingItem['id'];
                    $upd = $conn->prepare("UPDATE desk_evaluation_items SET notes = ?, updated_at = NOW() WHERE id = ?");
                    $upd->bind_param("si", $noteText, $itemId);
                    $upd->execute();
                } else {
                    $ins = $conn->prepare("INSERT INTO desk_evaluation_items (assignment_id, standard_id, audit_indicator_id, notes, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $ins->bind_param("iiis", $assignmentId, $standardId, $indicatorId, $noteText);
                    $ins->execute();
                    $itemId = (int) $conn->insert_id;
                }

                $itemIdMap[$indicatorId] = $itemId;
            }

            $status = $allFilled ? 'Selesai' : 'Belum';

            $updEval = $conn->prepare("UPDATE desk_evaluations SET status = ?, submitted_at = NOW(), updated_at = NOW() WHERE id = ?");
            $updEval->bind_param("si", $status, $evalId);
            $updEval->execute();

            $linksMap = $_POST['links'] ?? [];

            foreach ($linksMap as $indicatorId => $linkUrl) {

                $indicatorId = (int) $indicatorId;
                $linkUrl = trim($linkUrl);

                if ($linkUrl === '' || !isset($itemIdMap[$indicatorId])) {
                    continue;
                }

                if (!filter_var($linkUrl, FILTER_VALIDATE_URL)) {
                    continue;
                }

                $itemId = $itemIdMap[$indicatorId];

                $insertLink = $conn->prepare("
                    INSERT INTO desk_evaluation_documents (desk_evaluation_item_id, link_url, uploaded_at)
                    VALUES (?, ?, NOW())
                ");
                $insertLink->bind_param("is", $itemId, $linkUrl);
                $insertLink->execute();
            }

            if (!empty($_FILES['documents']['name']) && is_array($_FILES['documents']['name'])) {

                $uploader = new UploadHelper(
                    dirname(__DIR__, 2) . '/uploads/desk_evaluation',
                    ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'],
                    ['application/pdf', 'application/msword',
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                     'application/vnd.ms-excel',
                     'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                     'image/jpeg', 'image/png']
                );

                foreach ($_FILES['documents']['name'] as $indicatorId => $fileName) {

                    if (empty($fileName)) {
                        continue;
                    }

                    $indicatorId = (int) $indicatorId;

                    if (!isset($itemIdMap[$indicatorId])) {
                        continue;
                    }

                    $file = [
                        'name'     => $_FILES['documents']['name'][$indicatorId],
                        'type'     => $_FILES['documents']['type'][$indicatorId],
                        'tmp_name' => $_FILES['documents']['tmp_name'][$indicatorId],
                        'error'    => $_FILES['documents']['error'][$indicatorId],
                        'size'     => $_FILES['documents']['size'][$indicatorId],
                    ];

                    $uploaded = $uploader->upload($file);

                    $itemId = $itemIdMap[$indicatorId];

                    $insertDoc = $conn->prepare("
                        INSERT INTO desk_evaluation_documents (desk_evaluation_item_id, document_file, document_original_name, document_size, uploaded_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");

                    $insertDoc->bind_param(
                        "issi",
                        $itemId,
                        $uploaded['document_file'],
                        $uploaded['document_original_name'],
                        $uploaded['document_size']
                    );

                    $insertDoc->execute();
                }
            }

            notifyIfDeskEvaluationComplete($conn, $assignmentId);

            jsonResponse(true, 'Desk Evaluation berhasil disimpan.');

            break;

    case 'delete_document':

            $docId = (int)($_POST['doc_id'] ?? 0);

            $stmt = $conn->prepare("
                SELECT d.id FROM desk_evaluation_documents d
                JOIN desk_evaluation_items i ON i.id = d.desk_evaluation_item_id
                JOIN audit_assignments a ON a.id = i.assignment_id
                WHERE d.id = ? AND a.auditee_id = ?
                LIMIT 1
            ");
            $stmt->bind_param("ii", $docId, $myUnitId);
            $stmt->execute();

            if (!$stmt->get_result()->fetch_assoc()) {
                jsonResponse(false, 'Akses ditolak.');
            }

            $delete = $conn->prepare("DELETE FROM desk_evaluation_documents WHERE id = ?");
            $delete->bind_param("i", $docId);
            $delete->execute();

            jsonResponse(true, 'Dokumen berhasil dihapus.');

            break;

        default:

            jsonResponse(false, 'Aksi tidak dikenal.');
    }

} catch (Throwable $e) {

    jsonResponse(false, $e->getMessage());
}