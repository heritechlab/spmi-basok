<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

if (!Auth::canManage()) {
    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses ke halaman ini.']);
    exit;
}

$accRepository = new AccRepository($conn);
$manageRepository = new AccManageRepository($conn);
$service = new AccManageService($manageRepository, $accRepository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'tables':

            echo json_encode($service->getAllTables());

            break;

        case 'detail':

            $code = trim($_GET['code'] ?? '');

            echo json_encode($service->getTableDetail($code));

            break;

        case 'save_table':

            $id = (int) ($_POST['id'] ?? 0);
            $code = trim($_POST['code'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');

            echo json_encode($service->saveTable($id, $code, $title, $description));

            break;

        case 'delete_table':

            $id = (int) ($_POST['id'] ?? 0);

            echo json_encode($service->deleteTable($id));

            break;

        case 'save_column':

            $id = (int) ($_POST['id'] ?? 0);
            $tableId = (int) ($_POST['table_id'] ?? 0);
            $groupLabel = trim($_POST['group_label'] ?? '');
            $label = trim($_POST['label'] ?? '');
            $dataType = trim($_POST['data_type'] ?? 'integer');
            $totalMode = trim($_POST['total_mode'] ?? 'sum');

            echo json_encode($service->saveColumn($id, $tableId, $groupLabel, $label, $dataType, $totalMode));

            break;

        case 'delete_column':

            $id = (int) ($_POST['id'] ?? 0);

            echo json_encode($service->deleteColumn($id));

            break;

        case 'update_labels':

            $tableId = (int) ($_POST['table_id'] ?? 0);

            echo json_encode($service->updateTableLabels($tableId, $_POST));

            break;

        case 'criteria':

            echo json_encode($service->getCriteria());

            break;
        
        case 'units':

            echo json_encode(['success' => true, 'message' => '', 'data' => $accRepository->getAllUnits()]);

            break;

        case 'update_table_criteria':

            $tableId = (int) ($_POST['table_id'] ?? 0);
            $criteriaId = (int) ($_POST['criteria_id'] ?? 0);

            echo json_encode($service->updateTableCriteria($tableId, $criteriaId));

            break;

        case 'academic_years':

            echo json_encode($service->getAcademicYears());

            break;

        case 'save_academic_year':

            $label = trim($_POST['label'] ?? '');

            echo json_encode($service->saveAcademicYear($label));

            break;

        case 'delete_academic_year':

            $id = (int) ($_POST['id'] ?? 0);

            echo json_encode($service->deleteAcademicYear($id));

            break;

        case 'documents_by_criteria':

            $criteriaId = (int) ($_GET['criteria_id'] ?? 0);

            echo json_encode($service->getDocumentsByCriteria($criteriaId));

            break;

case 'save_document':

            $id = (int) ($_POST['id'] ?? 0);
            $criteriaId = (int) ($_POST['criteria_id'] ?? 0);
            $documentName = trim($_POST['document_name'] ?? '');
            $unitIds = $_POST['unit_id'] ?? [];

            if (!is_array($unitIds)) {
                $unitIds = [];
            }

            echo json_encode($service->saveDocument($id, $criteriaId, $documentName, $unitIds));

            break;

        case 'get_document_units':

            $documentId = (int) ($_GET['document_id'] ?? 0);

            echo json_encode($service->getDocumentUnitIds($documentId));

            break;

        case 'get_table_units':

            $tableId = (int) ($_GET['table_id'] ?? 0);

            echo json_encode($service->getTableUnitIds($tableId));

            break;

        case 'save_table_units':

            $tableId = (int) ($_POST['table_id'] ?? 0);
            $unitIds = $_POST['unit_ids'] ?? [];

            if (!is_array($unitIds)) {
                $unitIds = [];
            }

            echo json_encode($service->saveTableUnits($tableId, $unitIds));

            break;

        case 'delete_document':

            $id = (int) ($_POST['id'] ?? 0);

            echo json_encode($service->deleteDocument($id));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}