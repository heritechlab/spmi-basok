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

$repository = new IndikatorPenilaianRepository($conn);
$service    = new IndikatorPenilaianService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'list':

            echo json_encode($service->getAll());
            break;

        case 'bentuk_penilaian_list':

            require_once __DIR__ . '/../bentuk_penilaian/repository.php';
            require_once __DIR__ . '/../bentuk_penilaian/service.php';

            $bpRepo = new BentukPenilaianRepository($conn);
            $bpService = new BentukPenilaianService($bpRepo);

            echo json_encode($bpService->getAll());
            break;

        case 'get':

            $id = (int) ($_GET['id'] ?? 0);
            echo json_encode($service->getById($id));
            break;

        case 'create':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }
            echo json_encode($service->create($_POST));
            break;

        case 'update':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }
            $id = (int) ($_POST['id'] ?? 0);
            echo json_encode($service->update($id, $_POST));
            break;

        case 'delete':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }
            $id = (int) ($_POST['id'] ?? 0);
            echo json_encode($service->delete($id));
            break;

        case 'rubrik_get':

            $id = (int) ($_GET['id'] ?? 0);
            echo json_encode($service->getRubrikById($id));
            break;

        case 'rubrik_create':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }
            echo json_encode($service->createRubrik($_POST));
            break;

        case 'rubrik_update':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }
            $id = (int) ($_POST['id'] ?? 0);
            echo json_encode($service->updateRubrik($id, $_POST));
            break;

        case 'rubrik_delete':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }
            $id = (int) ($_POST['id'] ?? 0);
            echo json_encode($service->deleteRubrik($id));
            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}