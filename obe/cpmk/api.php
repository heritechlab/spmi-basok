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

$repository = new CpmkRepository($conn);
$service    = new CpmkService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'list':

            $mataKuliahId = (int) ($_GET['mata_kuliah_id'] ?? 0);
            echo json_encode($service->getAllByMataKuliah($mataKuliahId));

            break;

        case 'list_all':

            $kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);

            echo json_encode($service->getAllByKurikulum($kurikulumId));

            break;

        case 'matriks':

            $unitId = (int) ($_GET['unit_id'] ?? 0);
            $kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            echo json_encode($service->getMatriksCpmkCpl($unitId, $kurikulumId));

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
            echo json_encode($service->getById($id));

            break;

        case 'mata_kuliah_list':

            $kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);

            require_once __DIR__ . '/../mata_kuliah/repository.php';
            require_once __DIR__ . '/../mata_kuliah/service.php';

            $mkRepo = new MataKuliahRepository($conn);
            $mkService = new MataKuliahService($mkRepo);
            $periodeId = Periode::getActiveId($conn);

            echo json_encode($mkService->getAll($kurikulumId, $periodeId));

            break;

        case 'cpl_list':

            $unitId = (int) ($_GET['unit_id'] ?? 0);

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            require_once __DIR__ . '/../cpl/repository.php';
            require_once __DIR__ . '/../cpl/service.php';

            $cplRepo = new CplRepository($conn);
            $cplService = new CplService($cplRepo);

            echo json_encode($cplService->getAll($unitId));

            break;

        case 'create':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            echo json_encode($service->create($_POST));

            break;

        case 'update':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $id = (int) ($_POST['id'] ?? 0);
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