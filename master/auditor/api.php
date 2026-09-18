<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../core/Auth.php';

$repository = new AuditorRepository($conn);
$service    = new AuditorService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'list':

            $draw   = (int)($_GET['draw'] ?? 1);
            $start  = (int)($_GET['start'] ?? 0);
            $length = (int)($_GET['length'] ?? 10);
            $search = trim($_GET['search']['value'] ?? '');

            $statusFilter = isset($_GET['status']) && $_GET['status'] !== ''
                ? (int) $_GET['status']
                : -1;

            $data  = $service->getAll($search, $statusFilter, $length, $start);
            $total = $service->count($search, $statusFilter);

            $rows = [];

            foreach ($data['data'] as $row) {

                $statusBadge = (int)$row['status'] === 1
                    ? '<span class="badge bg-success">Aktif</span>'
                    : '<span class="badge bg-secondary">Nonaktif</span>';

                $sertifikat = !empty($row['certificate_file'])
                    ? '<a href="' . BASE_URL . $row['certificate_file'] . '" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-filetype-pdf"></i></a>'
                    : '<span class="text-muted">-</span>';

                $aksi = '
                    <button class="btn btn-info btn-sm btn-detail" data-id="' . $row['id'] . '">
                        <i class="bi bi-eye"></i>
                    </button>
                ';

                if (Auth::canManage()) {
                    $aksi .= '
                        <button class="btn btn-warning btn-sm btn-edit" data-id="' . $row['id'] . '">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-danger btn-sm btn-delete" data-id="' . $row['id'] . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    ';
                } else {
                    $aksi .= '
                        <button class="btn btn-warning btn-sm btn-locked" title="Anda tidak memiliki akses untuk mengubah data">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-danger btn-sm btn-locked" title="Anda tidak memiliki akses untuk menghapus data">
                            <i class="bi bi-trash"></i>
                        </button>
                    ';
                }

                $rows[] = [
                    $row['full_name'],
                    $row['nidn_nip'],
                    $row['username'],
                    $row['email'],
                    $row['phone'],
                    $statusBadge,
                    $sertifikat,
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

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menambah data.']);
                break;
            }

            echo json_encode($service->create($_POST, $_FILES));

            break;

        case 'update':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk mengubah data.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            echo json_encode($service->update($id, $_POST, $_FILES));

            break;

        case 'delete':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menghapus data.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            echo json_encode($service->delete($id));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}