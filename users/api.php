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
require_once __DIR__ . '/../master/unit/repository.php';

if (!Auth::isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Halaman ini hanya untuk Administrator.']);
    exit;
}

$repository = new UserRepository($conn);
$service    = new UserService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'statistics':

            echo json_encode($service->getStatistics());

            break;

        case 'units':

            $unitRepo = new UnitRepository($conn);
            $rows = $unitRepo->getAll();

            echo json_encode(['success' => true, 'message' => '', 'data' => $rows]);

            break;

        case 'list':

            $draw   = (int)($_GET['draw'] ?? 1);
            $start  = (int)($_GET['start'] ?? 0);
            $length = (int)($_GET['length'] ?? 10);
            $search = trim($_GET['search']['value'] ?? '');
            $roleId = (int)($_GET['role_id'] ?? 0);
            $statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? (int) $_GET['status'] : -1;

            $data  = $service->getAll($search, $roleId, $statusFilter, $length, $start);
            $total = $service->count($search, $roleId, $statusFilter);

            $rows = [];

            foreach ($data['data'] as $row) {

                $statusBadge = (int)$row['status'] === 1
                    ? '<span class="badge bg-success">Aktif</span>'
                    : '<span class="badge bg-secondary">Nonaktif</span>';

                $toggleBtn = (int)$row['status'] === 1
                    ? '<button class="btn btn-warning btn-sm btn-toggle-status" data-id="' . $row['id'] . '" data-status="0" title="Nonaktifkan"><i class="bi bi-slash-circle"></i></button>'
                    : '<button class="btn btn-success btn-sm btn-toggle-status" data-id="' . $row['id'] . '" data-status="1" title="Aktifkan"><i class="bi bi-check-circle"></i></button>';

                $aksi = '
                    <button class="btn btn-primary btn-sm btn-edit" data-id="' . $row['id'] . '">
                        <i class="bi bi-pencil"></i>
                    </button>
                    ' . $toggleBtn . '
                ';

                $rows[] = [
                    htmlspecialchars($row['full_name']),
                    htmlspecialchars($row['username']),
                    '<span class="badge bg-info text-dark">' . htmlspecialchars($row['role_name'] ?? '-') . '</span>',
                    htmlspecialchars($row['unit_name'] ?? '-'),
                    htmlspecialchars($row['email'] ?? '-'),
                    $statusBadge,
                    $aksi,
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => (int)$total['data'],
                'recordsFiltered' => (int)$total['data'],
                'data' => $rows,
            ]);

            break;

        case 'get':

            $id = (int)($_GET['id'] ?? 0);

            echo json_encode($service->getById($id));

            break;

        case 'create':

            echo json_encode($service->create($_POST));

            break;

        case 'update':

            $id = (int)($_POST['id'] ?? 0);

            echo json_encode($service->update($id, $_POST));

            break;

        case 'toggle_status':

            $id = (int)($_POST['id'] ?? 0);
            $status = (int)($_POST['status'] ?? 1);

            echo json_encode($service->toggleStatus($id, $status));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}