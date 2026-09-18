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

$repository = new BobotKontribusiRepository($conn);
$service    = new BobotKontribusiService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'matriks':

            $unitId = (int) ($_GET['unit_id'] ?? 0);
            $kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);

            echo json_encode($service->getMatriksData($unitId, $kurikulumId));

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

        case 'tingkat1':

            $kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);
            $periodeId = Periode::getActiveId($conn);
            echo json_encode($service->getTingkat1($kurikulumId, $periodeId));

            break;
        
            case 'tingkat1_detail':

            $kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);
            $periodeId = Periode::getActiveId($conn);
            echo json_encode($service->getTingkat1Detail($kurikulumId, $periodeId));

            break;

        case 'hitung_otomatis':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $unitId = (int) ($_POST['unit_id'] ?? 0);
            $kurikulumId = (int) ($_POST['kurikulum_id'] ?? 0);
            $periodeId = Periode::getActiveId($conn);

            echo json_encode($service->hitungOtomatis($unitId, $kurikulumId, $periodeId));

            break;

        case 'save':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $itemsJson = $_POST['items'] ?? '[]';
            $items = json_decode($itemsJson, true);

            if (!is_array($items)) {
                echo json_encode(['success' => false, 'message' => 'Data tidak valid.']);
                break;
            }

            echo json_encode($service->saveBobotBatch($items));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}