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
require_once __DIR__ . '/../../master/auditor/repository.php';
require_once __DIR__ . '/../../core/Auth.php';

$repository = new AssignmentRepository($conn);
$service    = new AssignmentService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'statistics':

            echo json_encode($service->getStatistics());

            break;

        case 'periods':

            $periodRepo = new PeriodRepository($conn);
            $rows = $periodRepo->getAll('', '', 100, 0);

            echo json_encode(['success' => true, 'message' => '', 'data' => $rows]);

            break;

        case 'units':

            $unitRepo = new UnitRepository($conn);
            $rows = $unitRepo->getAll('', 500, 0);

            echo json_encode(['success' => true, 'message' => '', 'data' => $rows]);

            break;

        case 'auditors':

            $auditorRepo = new AuditorRepository($conn);
            $rows = $auditorRepo->getAll('', 1, 100, 0);

            echo json_encode(['success' => true, 'message' => '', 'data' => $rows]);

            break;
        
        case 'team_candidates':

            $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE role_id = 3 AND status = 1 ORDER BY full_name ASC");
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            echo json_encode(['success' => true, 'message' => '', 'data' => $rows]);

            break;

        case 'standards':

            require_once __DIR__ . '/../../master/standards/repository.php';

            $standardRepo = new StandardRepository($conn);
            $rows = $standardRepo->getStandards();

            echo json_encode(['success' => true, 'message' => '', 'data' => $rows]);

            break;

        case 'list':

            $draw   = (int)($_GET['draw'] ?? 1);
            $start  = (int)($_GET['start'] ?? 0);
            $length = (int)($_GET['length'] ?? 10);
            $search = trim($_GET['search']['value'] ?? '');
            $periodId = (int)($_GET['period_id'] ?? 0);
            $status = trim($_GET['status'] ?? '');

            $data  = $service->getAll($search, $periodId, $status, $length, $start);
            $total = $service->count($search, $periodId, $status);

            $rows = [];

            $badgeMap = [
                'Draft'       => 'secondary',
                'Dijadwalkan' => 'info',
                'Berlangsung' => 'warning',
                'Selesai'     => 'success',
            ];

            foreach ($data['data'] as $row) {

                $badge = $badgeMap[$row['status']] ?? 'secondary';

                if (Auth::canManage()) {
                $aksi = '
                    <a href="' . BASE_URL . 'audit/workspace/?assignment_id=' . $row['id'] . '" class="btn btn-info btn-sm" title="Workspace Audit">
                        <i class="bi bi-clipboard-data"></i>
                    </a>
                    <button class="btn btn-warning btn-sm btn-edit" data-id="' . $row['id'] . '">
                        <i class="bi bi-pencil"></i>
                    </button>
                ';
                if ((int)$row['approved'] === 0) {
                    $aksi .= '
                        <button class="btn btn-success btn-sm btn-approve" data-id="' . $row['id'] . '" title="Setujui Penugasan">
                            <i class="bi bi-check-circle"></i>
                        </button>
                    ';
                }

                if ($row['status'] === 'Draft') {
                    $aksi .= '
                        <button class="btn btn-danger btn-sm btn-delete" data-id="' . $row['id'] . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    ';
                }
                } else {

                    $aksi = '
                        <button class="btn btn-warning btn-sm btn-locked" title="Anda tidak memiliki akses untuk mengubah data">
                            <i class="bi bi-pencil"></i>
                        </button>
                    ';

                }

                $suratTugas = !empty($row['document_file'])
                    ? '<a href="' . BASE_URL . $row['document_file'] . '" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-filetype-pdf"></i></a>'
                    : '<span class="text-muted">-</span>';

                $auditeeDisplay = htmlspecialchars($row['auditee_name'] ?? '-');
                if (!empty($row['auditee_head'])) {
                    $auditeeDisplay .= '<br><small class="text-muted">PIC: ' . htmlspecialchars($row['auditee_head']) . '</small>';
                }

                $approvalBadge = (int)$row['approved'] === 1
                    ? '<span class="badge bg-success">Disetujui</span>'
                    : '<span class="badge bg-warning text-dark">Menunggu Persetujuan</span>';

                $rows[] = [
                    $row['assignment_number'],
                    $auditeeDisplay,
                    htmlspecialchars($row['lead_auditor_name'] ?? '-'),
                    htmlspecialchars($row['period_name'] ?? '-'),
                    $row['audit_type'],
                    htmlspecialchars($row['audit_date'] ?? '-'),
                    $suratTugas,
                    $approvalBadge,
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

            $result = $service->getById($id);

            if ($result['success']) {
                $result['data']['standard_ids'] = $repository->getStandardIds($id);
                $team = $repository->getTeamMembers($id);
                $result['data']['team_member_ids'] = array_map(fn($m) => (int) $m['user_id'], $team);
            }

            echo json_encode($result);

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

        case 'approve':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menyetujui penugasan.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            echo json_encode($service->approve($id));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}