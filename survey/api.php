<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$repository = new SurveyRepository($conn);
$service    = new SurveyService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'units':

            require_once __DIR__ . '/../master/unit/repository.php';
            $unitRepo = new UnitRepository($conn);
            $rows = $unitRepo->getAll();

            echo json_encode(['success' => true, 'message' => '', 'data' => $rows]);

            break;

        case 'submit':

            $typeId = (int) ($_POST['type_id'] ?? 0);

            if ($typeId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Jenis survey tidak valid.']);
                break;
            }

            echo json_encode($service->submit($typeId, $_POST));

            break;

        case 'generate_follow_up':

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            require_once __DIR__ . '/../core/Auth.php';

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk membuat Tindak Lanjut.']);
                break;
            }

            $genTypeId = (int) ($_POST['type_id'] ?? 0);
            $genYear = (int) ($_POST['year'] ?? 0);
            $genUnitId = (int) ($_POST['unit_id'] ?? 0);

            echo json_encode($service->generateFollowUp($genTypeId, $genYear, $genUnitId));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}