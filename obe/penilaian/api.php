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

$repository = new PenilaianRepository($conn);
$service    = new PenilaianService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'grid':

            $mataKuliahId = (int) ($_GET['mata_kuliah_id'] ?? 0);
            $kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);
            $periodeId = Periode::getActiveId($conn);

            echo json_encode($service->getGridData($mataKuliahId, $kurikulumId, $periodeId));

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

        case 'mata_kuliah_list':

            $kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);

            require_once __DIR__ . '/../mata_kuliah/repository.php';
            require_once __DIR__ . '/../mata_kuliah/service.php';

            $mkRepo = new MataKuliahRepository($conn);
            $mkService = new MataKuliahService($mkRepo);
            $periodeId = Periode::getActiveId($conn);

            echo json_encode($mkService->getAll($kurikulumId, $periodeId));

            break;

        case 'laporan':

            $mataKuliahId = (int) ($_GET['mata_kuliah_id'] ?? 0);
            $kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);
            $periodeId = Periode::getActiveId($conn);

            echo json_encode($service->getLaporanCapaian($mataKuliahId, $kurikulumId, $periodeId));

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

            $periodeId = Periode::getActiveId($conn);
            echo json_encode($service->saveNilaiBatch($periodeId, $items));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}