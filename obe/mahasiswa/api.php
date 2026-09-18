<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$repository = new MahasiswaRepository($conn);
$service    = new MahasiswaService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'list':

            $kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);
            echo json_encode($service->getAllByKurikulum($kurikulumId));

            break;

        case 'get':

            $id = (int) ($_GET['id'] ?? 0);
            echo json_encode($service->getById($id));

            break;

        case 'kurikulum_list':

            $unitId = (int) ($_GET['unit_id'] ?? 0);

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            require_once __DIR__ . '/../kurikulum/repository.php';
            require_once __DIR__ . '/../kurikulum/service.php';

            $kurikulumRepo = new KurikulumRepository($conn);
            $kurikulumService = new KurikulumService($kurikulumRepo);

            echo json_encode($kurikulumService->getAll($unitId));

            break;

        case 'create':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            if (Auth::isAuditee()) {
                $_POST['unit_id'] = $_SESSION['unit_id'] ?? 0;
            }

            echo json_encode($service->create($_POST));

            break;

        case 'update':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $id = (int) ($_POST['id'] ?? 0);

            if (Auth::isAuditee()) {
                $_POST['unit_id'] = $_SESSION['unit_id'] ?? 0;
            }

            echo json_encode($service->update($id, $_POST));

            break;

        case 'delete':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $id = (int) ($_POST['id'] ?? 0);
            echo json_encode($service->delete($id));

            break;

        case 'import':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $unitId = (int) ($_POST['unit_id'] ?? 0);
            $kurikulumId = (int) ($_POST['kurikulum_id'] ?? 0);

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            $rowsJson = $_POST['rows'] ?? '[]';
            $rows = json_decode($rowsJson, true);

            if (!is_array($rows)) {
                echo json_encode(['success' => false, 'message' => 'Data impor tidak valid.']);
                break;
            }

            echo json_encode($service->importBatch($rows, $unitId, $kurikulumId));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}