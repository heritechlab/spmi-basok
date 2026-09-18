<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$repository = new LedRepository($conn);
$service    = new LedService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'my_assignments':

            $myUnitId = $service->getMyUnitId($_SESSION['user_id'] ?? 0);

            echo json_encode($service->getMyAssignments($myUnitId));

            break;

        case 'structure':

            $level = trim($_GET['level'] ?? 'prodi');
            $assignmentId = (int) ($_GET['assignment_id'] ?? 0);

            $myUnitId = $service->getMyUnitId($_SESSION['user_id'] ?? 0);
            $assignment = $service->getAssignment($assignmentId);

            if (!$assignment || (int) $assignment['auditee_id'] !== $myUnitId) {
                echo json_encode(['success' => false, 'message' => 'Akses ditolak. Penugasan ini bukan milik unit Anda.']);
                break;
            }

            echo json_encode($service->getLedStructure($level, $assignmentId));

            break;

        case 'save_entry':

            $updatedBy = $_SESSION['user_id'] ?? 0;
            $myUnitId = $service->getMyUnitId($updatedBy);

            echo json_encode($service->saveEntry($_POST, $_FILES['dokumen_file'] ?? [], $updatedBy, $myUnitId));

            break;

        case 'delete_document':

            $docId = (int) ($_POST['doc_id'] ?? 0);
            $myUnitId = $service->getMyUnitId($_SESSION['user_id'] ?? 0);

            echo json_encode($service->deleteDocument($docId, $myUnitId));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}