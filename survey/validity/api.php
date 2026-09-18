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
    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
    exit;
}

$surveyRepo = new SurveyRepository($conn);
$repository = new SurveyValidityRepository($conn);
$service    = new SurveyValidityService($repository, $surveyRepo);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'get':

            $typeId = (int) ($_GET['type_id'] ?? 0);

            echo json_encode($service->getTestWithItems($typeId));

            break;

        case 'save':

            $typeId = (int) ($_POST['type_id'] ?? 0);
            $updatedBy = $_SESSION['user_id'] ?? 0;

            echo json_encode($service->save($typeId, $_POST, $updatedBy));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}