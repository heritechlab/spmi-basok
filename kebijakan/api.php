<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$repository = new KebijakanRepository($conn);
$service    = new KebijakanService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'list':

            $category = trim($_GET['category'] ?? '');

            echo json_encode($service->getByCategory($category));

            break;

        case 'upload':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $uploadedBy = $_SESSION['user_id'] ?? 0;

            echo json_encode($service->upload($_POST, $_FILES['file'] ?? [], $uploadedBy));

            break;

        case 'delete':

            if (!Auth::canManage()) {
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