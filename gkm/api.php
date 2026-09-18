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

$repository = new GkmRepository($conn);
$service    = new GkmService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'units':

            require_once __DIR__ . '/../master/unit/repository.php';
            $unitRepo = new UnitRepository($conn);
            $rows = $unitRepo->getAll();

            echo json_encode(['success' => true, 'message' => '', 'data' => $rows]);

            break;

        case 'list':

            $draw         = (int)($_GET['draw'] ?? 1);
            $start        = (int)($_GET['start'] ?? 0);
            $length       = (int)($_GET['length'] ?? 10);
            $unitId       = (int)($_GET['unit_id'] ?? 0);
            $semester     = trim($_GET['semester'] ?? '');
            $academicYear = trim($_GET['academic_year'] ?? '');

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            $data  = $service->getAll($unitId, $semester, $academicYear, $length, $start);
            $total = $service->count($unitId, $semester, $academicYear);

            $rows = [];

            foreach ($data['data'] as $row) {

                $aksi = '
                    <a href="' . BASE_URL . 'gkm/detail.php?id=' . $row['id'] . '" class="btn btn-info btn-sm" title="Kelola Checklist">
                        <i class="bi bi-list-check"></i>
                    </a>
                    <a href="' . BASE_URL . 'gkm/print_unit.php?id=' . $row['id'] . '" target="_blank" class="btn btn-danger btn-sm" title="Cetak Laporan">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </a>
                ';

                if (Auth::canManage()) {
                    $aksi .= '
                        <button class="btn btn-danger btn-sm btn-delete" data-id="' . $row['id'] . '" title="Hapus">
                            <i class="bi bi-trash"></i>
                        </button>
                    ';
                }

                $rows[] = [
                    htmlspecialchars($row['unit_code'] . ' - ' . $row['unit_name']),
                    htmlspecialchars($row['semester']),
                    htmlspecialchars($row['academic_year']),
                    date('d/m/Y', strtotime($row['created_at'])),
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

        case 'create':

            if (!Auth::canManageRtm()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk membuat data.']);
                break;
            }

$unitId = Auth::isAuditee() ? (int) ($_SESSION['unit_id'] ?? 0) : (int) ($_POST['unit_id'] ?? 0);
            $semester = trim($_POST['semester'] ?? '');
            $academicYear = trim($_POST['academic_year'] ?? '');
            $gkmNama = trim($_POST['gkm_nama'] ?? '');

            echo json_encode($service->create($unitId, $semester, $academicYear, $gkmNama, $_SESSION['user_id'] ?? 0));

            break;

        case 'delete':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menghapus data.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            echo json_encode($service->delete($id));

            break;

        case 'get_detail':

            $id = (int)($_GET['id'] ?? 0);

            echo json_encode($service->getDetail($id));

            break;
        case 'save_signatures':

            if (!Auth::canManageRtm()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            echo json_encode($service->saveSignatures($id, $_POST, $_FILES));

            break;

        case 'save_responses':

            if (!Auth::canManageRtm()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk mengubah data.']);
                break;
            }

            $monitoringId = (int)($_POST['monitoring_id'] ?? 0);
            $responses = $_POST['responses'] ?? [];

            echo json_encode($service->saveResponses($monitoringId, $responses));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}