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

$repository = new BuktiRepository($conn);
$service    = new BuktiService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

case 'list':

            $draw         = (int)($_GET['draw'] ?? 1);
            $start        = (int)($_GET['start'] ?? 0);
            $length       = (int)($_GET['length'] ?? 10);
            $category     = trim($_GET['category'] ?? '');
            $unitId       = (int)($_GET['unit_id'] ?? 0);
            $semester     = trim($_GET['semester'] ?? '');
            $academicYear = trim($_GET['academic_year'] ?? '');

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            $data  = $service->getAll($category, $unitId, $semester, $academicYear, '', $length, $start);
            $total = $service->count($category, $unitId, $semester, $academicYear, '');

            $rows = [];

            foreach ($data['data'] as $row) {

                $aksi = '
                    <a href="' . BASE_URL . htmlspecialchars($row['file_path']) . '" target="_blank" class="btn btn-outline-secondary btn-sm" title="Unduh">
                        <i class="bi bi-download"></i>
                    </a>
                ';

                if (Auth::canManage() || (int)$row['uploaded_by'] === (int)($_SESSION['user_id'] ?? 0)) {
                    $aksi .= '
                        <button class="btn btn-danger btn-sm btn-delete" data-id="' . $row['id'] . '" title="Hapus">
                            <i class="bi bi-trash"></i>
                        </button>
                    ';
                }

                $rows[] = [
                    '<span class="badge bg-secondary">' . htmlspecialchars($row['category']) . '</span>',
                    htmlspecialchars($row['document_name']),
                    htmlspecialchars(($row['unit_code'] ?? '-') . ' - ' . ($row['unit_name'] ?? '-')),
                    htmlspecialchars($row['semester'] ?: '-') . ' ' . htmlspecialchars($row['academic_year'] ?: ''),
                    htmlspecialchars($row['uploaded_by_name'] ?? '-'),
                    date('d/m/Y H:i', strtotime($row['created_at'])),
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

case 'upload':

            if (empty($_SESSION['login'])) {
                echo json_encode(['success' => false, 'message' => 'Sesi tidak valid, silakan login ulang.']);
                break;
            }

            if (empty($_FILES['bukti_file'])) {
                echo json_encode(['success' => false, 'message' => 'File belum dipilih.']);
                break;
            }

            $input = $_POST;

            if (Auth::isAuditee()) {
                $input['unit_id'] = $_SESSION['unit_id'] ?? 0;
            }

            echo json_encode($service->upload($input, $_FILES['bukti_file']));

            break;
        case 'units':

            require_once __DIR__ . '/../master/unit/repository.php';
            $unitRepo = new UnitRepository($conn);
            $rows = $unitRepo->getAll();

            echo json_encode(['success' => true, 'message' => '', 'data' => $rows]);

            break;

        case 'delete':

            if (empty($_SESSION['login'])) {
                echo json_encode(['success' => false, 'message' => 'Sesi tidak valid.']);
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