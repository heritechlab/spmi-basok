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

$repository = new FormulirRepository($conn);
$service    = new FormulirService($repository);

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

            $badgeMap = ['Menunggu Persetujuan' => 'warning', 'Disahkan' => 'success', 'Ditolak' => 'danger'];

            $rows = [];

            foreach ($data['data'] as $row) {

                $badge = $badgeMap[$row['status']] ?? 'secondary';

                $aksi = '
                    <a href="' . BASE_URL . 'formulir/generate_pdf.php?id=' . $row['id'] . '" target="_blank" class="btn btn-danger btn-sm" title="Unduh PDF (Pengesahan + Isi Tergabung)">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </a>
                ';

                if ($row['status'] !== 'Disahkan') {
                    $aksi .= '
                        <button class="btn btn-primary btn-sm btn-edit" data-id="' . $row['id'] . '" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                    ';
                }

                if (Auth::canVerifyRtl() && $row['status'] === 'Menunggu Persetujuan') {
                    $aksi .= '
                        <button class="btn btn-success btn-sm btn-approve" data-id="' . $row['id'] . '" title="Sahkan">
                            <i class="bi bi-check-circle"></i>
                        </button>
                        <button class="btn btn-warning btn-sm btn-reject" data-id="' . $row['id'] . '" title="Tolak">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    ';
                }

                if (Auth::canManage()) {
                    $aksi .= '
                        <button class="btn btn-danger btn-sm btn-delete" data-id="' . $row['id'] . '" title="Hapus">
                            <i class="bi bi-trash"></i>
                        </button>
                    ';
                }

                $bulanSingkat = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];

                $berlakuSejak = '-';
                if (!empty($row['effective_date'])) {
                    $ts = strtotime($row['effective_date']);
                    $berlakuSejak = $bulanSingkat[(int) date('n', $ts)] . ' ' . date('Y', $ts);
                }

                $rows[] = [
                    htmlspecialchars($row['document_number']),
                    htmlspecialchars($row['title']),
                    htmlspecialchars($berlakuSejak),
                    (int) $row['revision'],
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

            if (empty($_SESSION['login'])) {
                echo json_encode(['success' => false, 'message' => 'Sesi tidak valid, silakan login ulang.']);
                break;
            }

            echo json_encode($service->create($_POST));

            break;

        case 'update':

            if (empty($_SESSION['login'])) {
                echo json_encode(['success' => false, 'message' => 'Sesi tidak valid, silakan login ulang.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            echo json_encode($service->update($id, $_POST));

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

            if (!Auth::canVerifyRtl()) {
                echo json_encode(['success' => false, 'message' => 'Hanya Admin/Ka. LPM yang dapat mengesahkan Formulir.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            echo json_encode($service->approve($id));

            break;

        case 'reject':

            if (!Auth::canVerifyRtl()) {
                echo json_encode(['success' => false, 'message' => 'Hanya Admin/Ka. LPM yang dapat menolak Formulir.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);
            $note = trim($_POST['rejection_note'] ?? '');

            if ($note === '') {
                echo json_encode(['success' => false, 'message' => 'Catatan penolakan wajib diisi.']);
                break;
            }

            echo json_encode($service->reject($id, $note));

            break;

        case 'upload_file':

            if (empty($_SESSION['login'])) {
                echo json_encode(['success' => false, 'message' => 'Sesi tidak valid.']);
                break;
            }

            $id = (int)($_POST['id'] ?? 0);

            if (empty($_FILES['formulir_file'])) {
                echo json_encode(['success' => false, 'message' => 'File belum dipilih.']);
                break;
            }

            echo json_encode($service->uploadFile($id, $_FILES['formulir_file']));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}