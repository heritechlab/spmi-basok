<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';
require_once __DIR__ . '/../../master/periods/repository.php';

$repository = new RtmRepository($conn);
$service    = new RtmService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'periods':

            $periodRepo = new PeriodRepository($conn);
            $rows = $periodRepo->getAll('', '', 100, 0);

            echo json_encode(['success' => true, 'message' => '', 'data' => $rows]);

            break;

        case 'overall':

            $periodId = (int)($_GET['period_id'] ?? 0);

            echo json_encode($service->getMonitoringOverall($periodId));

            break;

        case 'by_unit':

            $periodId = (int)($_GET['period_id'] ?? 0);

            echo json_encode($service->getMonitoringByUnit($periodId));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}