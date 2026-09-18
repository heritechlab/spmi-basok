<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Periode.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$repository = new MataKuliahRepository($conn);
$service    = new MataKuliahService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'list':

            $kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);
            $periodeId = Periode::getActiveId($conn);

            echo json_encode($service->getAll($kurikulumId, $periodeId));

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

        case 'get':

            $id = (int) ($_GET['id'] ?? 0);
            $periodeId = Periode::getActiveId($conn);
            echo json_encode($service->getById($id, $periodeId));

            break;

        case 'dosen_list':

            $unitId = (int) ($_GET['unit_id'] ?? 0);

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            require_once __DIR__ . '/../dosen/repository.php';
            require_once __DIR__ . '/../dosen/service.php';

            $dosenRepo = new DosenRepository($conn);
            $dosenService = new DosenService($dosenRepo);

            echo json_encode($dosenService->getAll($unitId));

            break;

        case 'matriks':

            $unitId = (int) ($_GET['unit_id'] ?? 0);

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            echo json_encode($service->getMatriksCplMk($unitId));

            break;

        case 'cpl_list':

            $unitId = (int) ($_GET['unit_id'] ?? 0);

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            echo json_encode($service->getCplList($unitId));

            break;

        case 'create':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }
            if (Auth::isAuditee()) {
                $_POST['unit_id'] = $_SESSION['unit_id'] ?? 0;
            }

            $_POST['periode_id'] = Periode::getActiveId($conn);
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

            $_POST['periode_id'] = Periode::getActiveId($conn);
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

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}