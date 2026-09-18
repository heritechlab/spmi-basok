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

        case 'periods':

            $periodRepo = new PeriodRepository($conn);
            $rows = $periodRepo->getAll('', '', 100, 0);

            echo json_encode(['success' => true, 'message' => '', 'data' => $rows]);

            break;
            
        case 'statistics':

            $periodId = (int)($_GET['period_id'] ?? 0);
            $unitId = Auth::isAuditee() ? (int)($_SESSION['unit_id'] ?? 0) : (int)($_GET['unit_id'] ?? 0);

            echo json_encode($service->getActionPlanStatistics($unitId));

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
            $verifBadge = ['Belum Diverifikasi' => 'secondary', 'Sesuai' => 'success', 'Perlu Revisi' => 'danger'];

            $rows = [];

            foreach ($data['data'] as $row) {

                $badge = $statusBadge[$row['status']] ?? 'secondary';
                $vBadge = $verifBadge[$row['verification_status'] ?? 'Belum Diverifikasi'] ?? 'secondary';
                $verifStatus = $row['verification_status'] ?? 'Belum Diverifikasi';

                $aksi = '
                    <button class="btn btn-info btn-sm btn-detail-implementation" data-id="' . $row['id'] . '">
                        <i class="bi bi-eye"></i>
                    </button>
                ';

                $rows[] = [
                    htmlspecialchars($row['item_code']) . ' - ' . htmlspecialchars($row['indicator']) . '<br><small class="text-muted">' . htmlspecialchars($row['standard_code']) . ' | ' . htmlspecialchars($row['auditee_name'] ?? '-') . '</small>',
                    htmlspecialchars($row['activity']),
                    '<span class="badge bg-' . $badge . '">' . $row['status'] . '</span>',
                    '<span class="badge bg-' . $vBadge . '">' . $verifStatus . '</span>',
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

            echo json_encode($service->getActionPlanDetail($id));

            break;

        case 'save_implementation':

            if (!Auth::canManageRtm()) {
                echo json_encode(['success' => false, 'message' => 'Hanya Auditee yang dapat mengisi pelaksanaan.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            echo json_encode($service->updateImplementation($id, $_POST));

            break;

case 'upload_evidence':

            if (!Auth::canManageRtm()) {
                echo json_encode(['success' => false, 'message' => 'Hanya Auditee yang dapat mengunggah bukti.']);
                break;
            }

            $actionPlanId = (int)($_POST['action_plan_id'] ?? 0);
            $linkUrl = trim($_POST['link_url'] ?? '');

            echo json_encode($service->uploadEvidence($actionPlanId, $_FILES['evidence'] ?? [], $linkUrl));

            break;

        case 'delete_evidence':

            if (!Auth::canManageRtm()) {
                echo json_encode(['success' => false, 'message' => 'Hanya Auditee yang dapat menghapus bukti.']);
                break;
            }

            $evidenceId = (int)($_POST['evidence_id'] ?? 0);

            echo json_encode($service->deleteEvidence($evidenceId));

            break;

        case 'verify':

            if (!Auth::canVerifyRtl()) {
                echo json_encode(['success' => false, 'message' => 'Hanya Admin/Ka. LPM yang dapat memverifikasi.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);
            $verificationStatus = trim($_POST['verification_status'] ?? '');
            $verificationNote = trim($_POST['verification_note'] ?? '');

            echo json_encode($service->verifyActionPlan($id, $verificationStatus, $verificationNote));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}