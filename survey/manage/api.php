<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';

if (!Auth::canManage()) {
    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses ke halaman ini.']);
    exit;
}

$repository = new SurveyRepository($conn);
$service    = new SurveyService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'types':

            echo json_encode($service->getAllTypes());

            break;

        case 'tree':

            $typeId = (int) ($_GET['type_id'] ?? 0);

            echo json_encode($service->getManageTree($typeId));

            break;

        case 'is_layanan_based':

            $typeId = (int) ($_GET['type_id'] ?? 0);

            echo json_encode(['success' => true, 'message' => '', 'data' => $service->isLayananBased($typeId)]);

            break;

        case 'save_layanan':

            $typeId = (int) ($_POST['type_id'] ?? 0);
            $id = (int) ($_POST['id'] ?? 0);
            $name = $_POST['name'] ?? '';

            echo json_encode($service->saveLayanan($typeId, $id, $name));

            break;

        case 'delete_layanan':

            $id = (int) ($_POST['id'] ?? 0);

            echo json_encode($service->deleteLayanan($id));

            break;
        
        case 'get_units':

            echo json_encode($service->getAllUnits());

            break;

        case 'save_category_unit':

            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $unitId = !empty($_POST['unit_id']) ? (int) $_POST['unit_id'] : null;

            echo json_encode($service->updateCategoryResponsibleUnit($categoryId, $unitId));

            break;

        case 'save_category':

            $typeId = (int) ($_POST['type_id'] ?? 0);
            $layananId = !empty($_POST['layanan_id']) ? (int) $_POST['layanan_id'] : null;
            $id = (int) ($_POST['id'] ?? 0);
            $name = $_POST['name'] ?? '';

            echo json_encode($service->saveCategory($typeId, $layananId, $id, $name));

            break;

        case 'delete_category':

            $id = (int) ($_POST['id'] ?? 0);

            echo json_encode($service->deleteCategory($id));

            break;

        case 'save_question':

            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $id = (int) ($_POST['id'] ?? 0);
            $text = $_POST['question_text'] ?? '';

            echo json_encode($service->saveQuestion($categoryId, $id, $text));

            break;

        case 'delete_question':

            $id = (int) ($_POST['id'] ?? 0);

            echo json_encode($service->deleteQuestion($id));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}