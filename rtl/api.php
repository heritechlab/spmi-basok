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

$repository = new RtmRepository($conn);
$service    = new RtmService($repository);

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

        case 'list':

            $draw   = (int)($_GET['draw'] ?? 1);
            $start  = (int)($_GET['start'] ?? 0);
            $length = (int)($_GET['length'] ?? 10);
            $search = trim($_GET['search']['value'] ?? '');
            $periodId = (int)($_GET['period_id'] ?? 0);
            $status = trim($_GET['status'] ?? '');
            $unitId = Auth::isAuditee() ? (int)($_SESSION['unit_id'] ?? 0) : 0;

            $data  = $service->getAll($search, $periodId, $status, $unitId, $length, $start);
            $total = $service->count($search, $periodId, $status, $unitId);

            $badgeMap = ['Draft' => 'secondary', 'Selesai' => 'success'];

            $rows = [];

            foreach ($data['data'] as $row) {

                $badge = $badgeMap[$row['status']] ?? 'secondary';

                $aksi = '
                    <a href="' . BASE_URL . 'rtl/detail.php?id=' . $row['id'] . '" class="btn btn-info btn-sm" title="Kelola">
                        <i class="bi bi-folder2-open"></i>
                    </a>
                    <a href="' . BASE_URL . 'laporan/rtm/print.php?unit_id=' . $row['unit_id'] . '&period_id=' . $row['period_id'] . '" target="_blank" class="btn btn-danger btn-sm" title="Cetak PDF">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </a>
                ';

                if (Auth::canManageRtm()) {
                    $aksi .= '
                        <button class="btn btn-warning btn-sm btn-edit" data-id="' . $row['id'] . '">
                            <i class="bi bi-pencil"></i>
                        </button>
                    ';
                } else {
                    $aksi .= '
                        <button class="btn btn-warning btn-sm btn-locked" title="Hanya Auditee yang dapat mengubah data">
                            <i class="bi bi-pencil"></i>
                        </button>
                    ';
                }

                $rows[] = [
                    $row['meeting_number'],
                    htmlspecialchars($row['period_name'] ?? '-'),
                    htmlspecialchars($row['meeting_date'] ?? '-'),
                    (int) $row['total_rtl'],
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

            if (!Auth::canManageRtm()) {
                echo json_encode(['success' => false, 'message' => 'Hanya Auditee yang dapat mengisi RTM.']);
                break;
            }

            echo json_encode($service->create($_POST));

            break;

        case 'update':

            if (!Auth::canManageRtm()) {
                echo json_encode(['success' => false, 'message' => 'Hanya Auditee yang dapat mengubah data.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            echo json_encode($service->update($id, $_POST));

            break;

        case 'findings':

            $periodId = (int)($_GET['period_id'] ?? 0);
            $unitId = Auth::isAuditee() ? (int)($_SESSION['unit_id'] ?? 0) : 0;

            echo json_encode($service->getAvailableFindings($periodId, $unitId));

            break;

        case 'upload_document':

            if (!Auth::canManageRtm()) {
                echo json_encode(['success' => false, 'message' => 'Hanya Auditee yang dapat mengunggah dokumen.']);
                break;
            }

            $meetingId = (int)($_POST['rtm_meeting_id'] ?? 0);
            $type = $_POST['document_type'] ?? '';

            echo json_encode($service->uploadDocument($meetingId, $type, $_FILES['document'] ?? []));

            break;
        case 'save_berita_acara_signature':

            if (!Auth::canManageRtm()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $meetingId = (int)($_POST['meeting_id'] ?? 0);

            echo json_encode($service->saveBeritaAcaraSignature($meetingId, $_POST, $_FILES));

            break;

        case 'delete_document':

            if (!Auth::canManageRtm()) {
                echo json_encode(['success' => false, 'message' => 'Hanya Auditee yang dapat menghapus dokumen.']);
                break;
            }

            $docId = (int)($_POST['doc_id'] ?? 0);

            echo json_encode($service->deleteDocument($docId));

            break;

        case 'save_action_plan':

            if (!Auth::canManageRtm()) {
                echo json_encode(['success' => false, 'message' => 'Hanya Auditee yang dapat menyimpan RTL.']);
                break;
            }

            echo json_encode($service->saveActionPlan($_POST));

            break;

        case 'delete_action_plan':

            if (!Auth::canManageRtm()) {
                echo json_encode(['success' => false, 'message' => 'Hanya Auditee yang dapat menghapus RTL.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            echo json_encode($service->deleteActionPlan($id));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}