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

$repository = new RubrikPenilaianRepository($conn);
$service    = new RubrikPenilaianService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'master':

            echo json_encode($service->getMaster());

            break;

        case 'list_by_mk':

            $mkId = (int) ($_GET['mata_kuliah_id'] ?? 0);
            $periodeId = Periode::getActiveId($conn);
            echo json_encode($service->getByMataKuliah($mkId, $periodeId));

            break;

        case 'save':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $mkId = (int) ($_POST['mata_kuliah_id'] ?? 0);
            $rows = json_decode($_POST['rows'] ?? '[]', true) ?: [];
            $periodeId = Periode::getActiveId($conn);

            echo json_encode($service->save($mkId, $periodeId, $rows));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}