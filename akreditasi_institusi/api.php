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

$repository = new AkreditasiInstitusiRepository($conn);
$service    = new AkreditasiInstitusiService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'statistics':

            echo json_encode(['success' => true, 'message' => '', 'data' => $service->getStatistics()]);

            break;

        case 'list':

            $draw         = (int)($_GET['draw'] ?? 1);
            $start        = (int)($_GET['start'] ?? 0);
            $length       = (int)($_GET['length'] ?? 10);
            $kriteria     = trim($_GET['kriteria'] ?? '');
            $academicYear = trim($_GET['academic_year'] ?? '');

            $data  = $service->getAll($kriteria, $academicYear, $length, $start);
            $total = $service->count($kriteria, $academicYear);

            $rows = [];

            foreach ($data['data'] as $row) {

            $aksi = '';

                if (!empty($row['file_path'])) {
                    $aksi .= '
                        <a href="' . BASE_URL . htmlspecialchars($row['file_path']) . '" target="_blank" class="btn btn-outline-secondary btn-sm" title="Unduh File">
                            <i class="bi bi-download"></i>
                        </a>
                    ';
                }

                if (!empty($row['link_url'])) {
                    $aksi .= '
                        <a href="' . htmlspecialchars($row['link_url']) . '" target="_blank" class="btn btn-outline-primary btn-sm" title="Buka Link">
                            <i class="bi bi-link-45deg"></i>
                        </a>
                    ';
                }

                if (Auth::canManage() || (int)$row['uploaded_by'] === (int)($_SESSION['user_id'] ?? 0)) {
                    $aksi .= '
                        <button class="btn btn-danger btn-sm btn-delete" data-id="' . $row['id'] . '" title="Hapus">
                            <i class="bi bi-trash"></i>
                        </button>
                    ';
                }

                $rows[] = [
                    '<span class="badge bg-secondary">' . htmlspecialchars($row['kriteria']) . '</span>',
                    htmlspecialchars($row['document_name']),
                    htmlspecialchars($row['academic_year']),
                    htmlspecialchars($row['uploaded_by_name'] ?? '-'),
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

        case 'upload':

            if (empty($_SESSION['login'])) {
                echo json_encode(['success' => false, 'message' => 'Sesi tidak valid, silakan login ulang.']);
                break;
            }

            echo json_encode($service->upload($_POST, $_FILES['bukti_file'] ?? []));

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