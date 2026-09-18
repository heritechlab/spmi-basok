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

$repository = new BahanKajianRepository($conn);
$service    = new BahanKajianService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'list_all':

            $unitId = (int) ($_GET['unit_id'] ?? 0);

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            echo json_encode($service->getAllByUnit($unitId));

            break;

        case 'sub_cpmk_list':

            $unitId = (int) ($_GET['unit_id'] ?? 0);

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            echo json_encode($service->getSubCpmkList($unitId));

            break;

        case 'get':

            $id = (int) ($_GET['id'] ?? 0);
            echo json_encode($service->getById($id));

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