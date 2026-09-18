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

$repository = new PelaporanSiakadRepository($conn);
$service    = new PelaporanSiakadService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'mata_kuliah_list':

            $kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);
            echo json_encode($service->getMataKuliahList($kurikulumId));

            break;

        case 'laporan':

            $mataKuliahId = (int) ($_GET['mata_kuliah_id'] ?? 0);
            $kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);

            echo json_encode($service->getLaporan($mataKuliahId, $kurikulumId));

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

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}