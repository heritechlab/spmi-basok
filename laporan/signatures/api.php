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

$repository = new LaporanSignatureRepository($conn);
$service    = new LaporanSignatureService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'get':

            $reportType = trim($_GET['report_type'] ?? '');
            $periodId = (int) ($_GET['period_id'] ?? 0);
            $unitId = !empty($_GET['unit_id']) ? (int) $_GET['unit_id'] : null;

            echo json_encode($service->find($reportType, $periodId, $unitId));

            break;

        case 'save':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menyimpan data ini.']);
                break;
            }

            echo json_encode($service->save($_POST, $_FILES));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}