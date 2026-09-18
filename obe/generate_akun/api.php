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

if (!Auth::canManage()) {
    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
    exit;
}

$repository = new GenerateAkunRepository($conn);
$service    = new GenerateAkunService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'preview_dosen':

            $unitId = (int) ($_GET['unit_id'] ?? 0);
            echo json_encode($service->getPreviewDosen($unitId));

            break;

        case 'preview_mahasiswa':

            $kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);
            echo json_encode($service->getPreviewMahasiswa($kurikulumId));

            break;

        case 'generate_dosen':

            $unitId = (int) ($_POST['unit_id'] ?? 0);
            $dosenIds = $_POST['dosen_ids'] ?? [];
            echo json_encode($service->generateDosen($unitId, $dosenIds));

            break;

        case 'generate_mahasiswa':

            $unitId = (int) ($_POST['unit_id'] ?? 0);
            $kurikulumId = (int) ($_POST['kurikulum_id'] ?? 0);
            $mahasiswaIds = $_POST['mahasiswa_ids'] ?? [];
            echo json_encode($service->generateMahasiswa($unitId, $kurikulumId, $mahasiswaIds));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}