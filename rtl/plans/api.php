<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';
require_once __DIR__ . '/../../master/periods/repository.php';
require_once __DIR__ . '/../../master/unit/repository.php';

$repository = new RtmRepository($conn);
$service    = new RtmService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'statistics':

            $unitId = Auth::isAuditee() ? (int)($_SESSION['unit_id'] ?? 0) : 0;

            echo json_encode($service->getActionPlanStatistics($unitId));

            break;

        case 'periods':

            $periodRepo = new PeriodRepository($conn);
            $rows = $periodRepo->getAll('', '', 100, 0);

            echo json_encode(['success' => true, 'message' => '', 'data' => $rows]);

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
            $periodId = (int)($_GET['period_id'] ?? 0);
            $status = trim($_GET['status'] ?? '');
            $unitId = Auth::isAuditee() ? (int)($_SESSION['unit_id'] ?? 0) : (int)($_GET['unit_id'] ?? 0);

            $data  = $service->getAllActionPlans($periodId, $unitId, $status, $length, $start);
            $total = $service->countAllActionPlans($periodId, $unitId, $status);

            $statusBadge = ['Belum' => 'secondary', 'Proses' => 'warning', 'Selesai' => 'success'];

            $rows = [];

            foreach ($data['data'] as $row) {

                $badge = $statusBadge[$row['status']] ?? 'secondary';

                $kuadran = '<span class="badge bg-dark">' . $row['importance'] . '</span> <span class="badge bg-info text-dark">' . $row['urgency'] . '</span>';

                $rows[] = [
                    htmlspecialchars($row['item_code']) . ' - ' . htmlspecialchars($row['indicator']) . '<br><small class="text-muted">' . htmlspecialchars($row['standard_code']) . ' | ' . htmlspecialchars($row['auditee_name'] ?? '-') . '</small>',
                    htmlspecialchars($row['activity']),
                    $kuadran,
                    htmlspecialchars($row['implementation_time'] ?? '-'),
                    htmlspecialchars($row['pic'] ?? '-'),
                    '<span class="badge bg-' . $badge . '">' . $row['status'] . '</span>',
                    '<a href="' . BASE_URL . 'rtl/detail.php?id=' . $row['rtm_meeting_id'] . '" class="btn btn-info btn-sm" title="Lihat di Rapat ' . htmlspecialchars($row['meeting_number']) . '"><i class="bi bi-folder2-open"></i></a>',
                ];
            }

            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => (int)$total['data'],
                'recordsFiltered' => (int)$total['data'],
                'data' => $rows,
            ]);

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}