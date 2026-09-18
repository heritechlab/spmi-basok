<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../master/periods/repository.php';
require_once __DIR__ . '/../../master/unit/repository.php';
require_once __DIR__ . '/../../core/Auth.php';

$repository = new FindingRepository($conn);
$service    = new FindingService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'statistics':

            $unitId = Auth::isAuditee() ? (int)($_SESSION['unit_id'] ?? 0) : 0;

            echo json_encode($service->getStatistics($unitId));

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
            
        case 'detail':

            $id = (int)($_GET['id'] ?? 0);

            echo json_encode($service->getDetail($id));

            break;

        case 'list':

            $draw   = (int)($_GET['draw'] ?? 1);
            $start  = (int)($_GET['start'] ?? 0);
            $length = (int)($_GET['length'] ?? 10);
            $search = trim($_GET['search']['value'] ?? '');
            $periodId = (int)($_GET['period_id'] ?? 0);
            $unitId = Auth::isAuditee() ? (int)($_SESSION['unit_id'] ?? 0) : (int)($_GET['unit_id'] ?? 0);
            $status = trim($_GET['status'] ?? '');

            $data  = $service->getAll($search, $periodId, $unitId, $status, $length, $start);
            $total = $service->count($search, $periodId, $unitId, $status);

            $badgeMap = [
                'Menyimpang'   => 'danger',
                'Belum Mencapai' => 'warning',
                'Mencapai'          => 'success',
                'Melampaui'         => 'primary',
            ];

            $rows = [];

            foreach ($data['data'] as $row) {

                $badge = $badgeMap[$row['audit_status']] ?? 'secondary';

                $rtlBadge = (int) $row['total_rtl'] > 0
                    ? '<span class="badge bg-success">Ada RTL</span>'
                    : '<span class="badge bg-secondary">Belum Ada RTL</span>';

                $truncate = function (?string $text, int $length = 60): string {
                    $text = trim($text ?? '');
                    if ($text === '') {
                        return '-';
                    }
                    $short = mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '...' : $text;
                    return '<span title="' . htmlspecialchars($text) . '">' . htmlspecialchars($short) . '</span>';
                };

                $rows[] = [
                    htmlspecialchars($row['standard_name'] ?? $row['standard_code']),
                    htmlspecialchars($row['item_code']) . ' - ' . htmlspecialchars($row['indicator']),
                    htmlspecialchars($row['auditee_name']),
                    htmlspecialchars($row['period_name'] ?? '-'),
                    htmlspecialchars($row['achievement'] ?? '-') . ' / ' . htmlspecialchars($row['target'] ?? '-'),
                    '<span class="badge bg-' . $badge . '">' . $row['audit_status'] . '</span>',
                    $truncate($row['field_audit_result'] ?? null, 120),
                    $truncate($row['recommendation'] ?? null),
                    $rtlBadge,
                    '<button class="btn btn-info btn-sm btn-detail" data-id="' . $row['id'] . '"><i class="bi bi-eye"></i></button>',
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