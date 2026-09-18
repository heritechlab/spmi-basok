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

$repository = new AccRepository($conn);
$service    = new AccService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'tables':

            echo json_encode($service->getAllTables());

            break;

        case 'units':

            echo json_encode($service->getAllUnits());

            break;
        
        case 'criteria_progress_by_year':

            $unitId = Auth::isAuditee() ? (int) ($_SESSION['unit_id'] ?? 0) : (int) ($_GET['unit_id'] ?? 0);

            if ($unitId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Unit Kerja belum dipilih.']);
                break;
            }

            echo json_encode($service->getCriteriaProgressByYear($unitId));

            break;
        
        case 'criteria_progress':

            $unitId = Auth::isAuditee() ? (int) ($_SESSION['unit_id'] ?? 0) : (int) ($_GET['unit_id'] ?? 0);

            if ($unitId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Unit Kerja belum dipilih.']);
                break;
            }

            echo json_encode($service->getCriteriaProgress($unitId));

            break;

        case 'pending_count':

            $unitId = Auth::isAuditee() ? (int) ($_SESSION['unit_id'] ?? 0) : (int) ($_GET['unit_id'] ?? 0);

            if ($unitId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Unit tidak ditemukan.']);
                break;
            }

            echo json_encode($service->getPendingCount($unitId));

            break;

        case 'get_table':

            $code = trim($_GET['code'] ?? '');
            $unitId = (int) ($_GET['unit_id'] ?? 0);

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            echo json_encode($service->getTableWithData($code, $unitId));

            break;

        case 'save':

            $code = trim($_POST['code'] ?? '');
            $unitId = (int) ($_POST['unit_id'] ?? 0);
            $cells = $_POST['cells'] ?? [];

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            } elseif (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk mengisi data ini.']);
                break;
            }

            if ($unitId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Program Studi wajib dipilih.']);
                break;
            }

            $updatedBy = $_SESSION['user_id'] ?? 0;

            echo json_encode($service->saveData($code, $unitId, $cells, $updatedBy));

            break;

        case 'add_grouped_row':

            $code = trim($_POST['code'] ?? '');
            $unitId = (int) ($_POST['unit_id'] ?? 0);
            $groupKey = trim($_POST['group_key'] ?? '');
            $label = trim($_POST['label'] ?? '');

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            } elseif (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            if ($unitId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Program Studi wajib dipilih.']);
                break;
            }

            echo json_encode($service->addGroupedRow($code, $unitId, $groupKey, $label));

            break;

        case 'add_row':

            $code = trim($_POST['code'] ?? '');
            $unitId = (int) ($_POST['unit_id'] ?? 0);
            $label = trim($_POST['label'] ?? '');

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            } elseif (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            if ($unitId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Program Studi wajib dipilih.']);
                break;
            }

            echo json_encode($service->addRow($code, $unitId, $label));

            break;

        case 'update_row_label':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $rowId = (int) ($_POST['row_id'] ?? 0);
            $label = trim($_POST['label'] ?? '');

            echo json_encode($service->updateRowLabel($rowId, $label));

            break;

        case 'delete_row':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $rowId = (int) ($_POST['row_id'] ?? 0);

            echo json_encode($service->deleteRow($rowId));

            break;

        case 'add_column':

            $code = trim($_POST['code'] ?? '');
            $unitId = (int) ($_POST['unit_id'] ?? 0);
            $label = trim($_POST['label'] ?? '');

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            } elseif (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            if ($unitId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Program Studi wajib dipilih.']);
                break;
            }

            echo json_encode($service->addDynamicColumn($code, $unitId, $label));

            break;

        case 'delete_column':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $columnId = (int) ($_POST['column_id'] ?? 0);
            $unitId = (int) ($_POST['unit_id'] ?? 0);

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            echo json_encode($service->deleteDynamicColumn($columnId, $unitId));

            break;

        case 'update_labels':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $tableId = (int) ($_POST['table_id'] ?? 0);

            echo json_encode($service->updateTableLabels($tableId, $_POST));

            break;

        case 'upload_document':

            $tableId = (int) ($_POST['table_id'] ?? 0);
            $unitId = (int) ($_POST['unit_id'] ?? 0);

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            } elseif (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $uploadedBy = $_SESSION['user_id'] ?? 0;

            echo json_encode($service->uploadDocument($tableId, $unitId, $_POST, $_FILES['document'] ?? [], $uploadedBy));

            break;

        case 'delete_document':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $docId = (int) ($_POST['id'] ?? 0);

            echo json_encode($service->deleteDocument($docId));

            break;

        case 'criteria':

            echo json_encode($service->getCriteria());

            break;

        case 'academic_years':

            echo json_encode($service->getAcademicYears());

            break;

case 'tables_by_criteria':

            $criteriaId = (int) ($_GET['criteria_id'] ?? 0);
            $unitId = (int) ($_GET['unit_id'] ?? 0);

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            echo json_encode($service->getTablesByCriteria($criteriaId, $unitId));

            break;

        case 'update_table_unit':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $tableId = (int) ($_POST['table_id'] ?? 0);
            $unitId = (int) ($_POST['unit_id'] ?? 0);

            echo json_encode($service->updateTableUnit($tableId, $unitId));

            break;

        case 'checklist':

            $criteriaId = (int) ($_GET['criteria_id'] ?? 0);
            $unitId = (int) ($_GET['unit_id'] ?? 0);
            $semester = trim($_GET['semester'] ?? '');
            $academicYear = trim($_GET['academic_year'] ?? '');

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            echo json_encode($service->getDocumentChecklist($criteriaId, $unitId, $semester, $academicYear));

            break;

        case 'upload_checklist_file':

            $documentId = (int) ($_POST['document_id'] ?? 0);
            $unitId = (int) ($_POST['unit_id'] ?? 0);
            $semester = trim($_POST['semester'] ?? '');
            $academicYear = trim($_POST['academic_year'] ?? '');

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            } elseif (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            if ($unitId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Program Studi wajib dipilih.']);
                break;
            }

            $uploadedBy = $_SESSION['user_id'] ?? 0;

            echo json_encode($service->uploadDocumentFile($documentId, $unitId, $semester, $academicYear, $_POST, $_FILES['document'] ?? [], $uploadedBy));

            break;

        case 'delete_checklist_upload':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $uploadId = (int) ($_POST['id'] ?? 0);

            echo json_encode($service->deleteDocumentUpload($uploadId));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}