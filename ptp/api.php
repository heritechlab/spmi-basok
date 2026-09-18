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
require_once __DIR__ . '/../master/periods/repository.php';

$repository = new PtpRepository($conn);
$service    = new PtpService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'periods':

            $periodRepo = new PeriodRepository($conn);
            $rows = $periodRepo->getAll('', '', 100, 0);

            echo json_encode(['success' => true, 'message' => '', 'data' => $rows]);

            break;

        case 'statistics':

            $unitId = Auth::isAuditee() ? (int)($_SESSION['unit_id'] ?? 0) : (int)($_GET['unit_id'] ?? 0);

            echo json_encode($service->getStatistics($unitId));

            break;

        case 'list':

            $draw   = (int)($_GET['draw'] ?? 1);
            $start  = (int)($_GET['start'] ?? 0);
            $length = (int)($_GET['length'] ?? 10);
            $periodId = (int)($_GET['period_id'] ?? 0);
            $status = trim($_GET['status'] ?? '');
            $unitId = Auth::isAuditee() ? (int)($_SESSION['unit_id'] ?? 0) : (int)($_GET['unit_id'] ?? 0);

            $data  = $service->getAll($unitId, $periodId, $status, $length, $start);
            $total = $service->count($unitId, $periodId, $status);

            $badgeMap = ['Draft' => 'secondary', 'Selesai' => 'success'];

            $rows = [];

            foreach ($data['data'] as $row) {

                $badge = $badgeMap[$row['status']] ?? 'secondary';

                $aksi = '
                    <a href="' . BASE_URL . 'ptp/detail.php?id=' . $row['id'] . '" class="btn btn-info btn-sm" title="Kelola">
                        <i class="bi bi-folder2-open"></i>
                    </a>
                    <a href="' . BASE_URL . 'laporan/ptp/print.php?unit_id=' . $row['unit_id'] . '&period_id=' . $row['period_id'] . '" target="_blank" class="btn btn-danger btn-sm" title="Cetak PDF">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </a>
                ';

                $rows[] = [
                    $row['meeting_number'],
                    htmlspecialchars($row['period_name'] ?? '-'),
                    htmlspecialchars($row['meeting_date'] ?? '-'),
                    (int) $row['total_items'],
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

            if (!Auth::canManagePtp()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menambah data.']);
                break;
            }

            $input = $_POST;

            if (Auth::isAuditee()) {
                $input['unit_id'] = $_SESSION['unit_id'] ?? 0;
            }

            echo json_encode($service->create($input));

            break;

        case 'update':

            if (!Auth::canManagePtp()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk mengubah data.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            $input = $_POST;

            if (Auth::isAuditee()) {
                $input['unit_id'] = $_SESSION['unit_id'] ?? 0;
            }

            echo json_encode($service->update($id, $input));

            break;

        case 'eligible_items':

            $unitId = (int)($_GET['unit_id'] ?? 0);
            $periodId = (int)($_GET['period_id'] ?? 0);

            echo json_encode($service->getEligibleItems($unitId, $periodId));

            break;

        case 'save_item':

            if (!Auth::canManagePtp()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menambah usulan.']);
                break;
            }

            echo json_encode($service->saveItem($_POST));

            break;

        case 'delete_item':

            if (!Auth::canManagePtp()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menghapus usulan.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            echo json_encode($service->deleteItem($id));

            break;

        case 'apply_item':

            if (!Auth::canVerifyRtl()) {
                echo json_encode(['success' => false, 'message' => 'Hanya Admin/Ka. LPM yang dapat menerapkan perubahan ke Master Indikator.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            echo json_encode($service->applyItem($id));

            break;
        
        case 'units':

            require_once __DIR__ . '/../master/unit/repository.php';
            $unitRepo = new UnitRepository($conn);
            $rows = $unitRepo->getAll();

            echo json_encode(['success' => true, 'message' => '', 'data' => $rows]);

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}