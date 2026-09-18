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

$repository = new PeriodRepository($conn);
$service    = new PeriodService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'statistics':

            echo json_encode($service->getStatistics());

            break;

        case 'list':

            $draw   = (int)($_GET['draw'] ?? 1);
            $start  = (int)($_GET['start'] ?? 0);
            $length = (int)($_GET['length'] ?? 10);
            $search = trim($_GET['search']['value'] ?? '');
            $status = trim($_GET['status'] ?? '');

            $data  = $service->getAll($search, $status, $length, $start);
            $total = $service->count($search, $status);

            $rows = [];

            $badgeMap = [
                'Draft'   => 'secondary',
                'Aktif'   => 'success',
                'Ditutup' => 'dark',
            ];

            foreach ($data['data'] as $row) {

                $badge = $badgeMap[$row['status']] ?? 'secondary';

                $periode = htmlspecialchars($row['start_date'] ?? '-') . ' s/d ' . htmlspecialchars($row['end_date'] ?? '-');

                if (Auth::canManage()) {
                    $aksi = '
                        <button class="btn btn-warning btn-sm btn-edit" data-id="' . $row['id'] . '">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-danger btn-sm btn-delete" data-id="' . $row['id'] . '">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    ';
                } else {
                    $aksi = '
                        <button class="btn btn-warning btn-sm btn-locked" title="Anda tidak memiliki akses untuk mengubah data">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-danger btn-sm btn-locked" title="Anda tidak memiliki akses untuk menutup periode">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    ';
                }

                $rows[] = [
                    $row['period_name'],
                    '<span class="badge-year">' . htmlspecialchars($row['year']) . '</span>',
                    $row['academic_year'],
                    $periode,
                    '<span class="badge bg-' . $badge . '">' . $row['status'] . '</span>',
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

            echo json_encode($service->create($_POST));

            break;

        case 'update':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk mengubah data.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            echo json_encode($service->update($id, $_POST));

            break;

        case 'delete':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menutup periode.']);
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