<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$repository = new DiskusiCplRepository($conn);
$service    = new DiskusiCplService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'list':

            $mahasiswaId = (int) ($_GET['mahasiswa_id'] ?? 0);
            echo json_encode($service->getByMahasiswa($mahasiswaId));

            break;

        case 'create':

            $mahasiswaId = (int) ($_POST['mahasiswa_id'] ?? 0);
            $pesan = $_POST['pesan'] ?? '';
            $penulisNama = $_SESSION['full_name'] ?? ($_SESSION['name'] ?? 'Pengguna');

            echo json_encode($service->create($mahasiswaId, $penulisNama, $pesan));

            break;
        case 'update_status_nilai':

            $mahasiswaId = (int) ($_POST['mahasiswa_id'] ?? 0);
            $rpsId = (int) ($_POST['rps_id'] ?? 0);
            $reId = (int) ($_POST['rencana_evaluasi_id'] ?? 0);
            $status = trim($_POST['status'] ?? '');

            if (!in_array($status, ['setuju', 'diajukan'], true)) {
                echo json_encode(['success' => false, 'message' => 'Status tidak valid.']);
                break;
            }

            $stmtUpd = $conn->prepare("
                UPDATE obe_penilaian
                SET status_review = ?
                WHERE mahasiswa_id = ? AND rps_id = ? AND rencana_evaluasi_id = ?
            ");
            $stmtUpd->bind_param("siii", $status, $mahasiswaId, $rpsId, $reId);
            $stmtUpd->execute();

            echo json_encode(['success' => true, 'message' => 'Status berhasil diperbarui.']);

            break;

        case 'setuju_semua_mk':

            $mahasiswaId = (int) ($_POST['mahasiswa_id'] ?? 0);
            $mataKuliahId = (int) ($_POST['mata_kuliah_id'] ?? 0);
            $periodeId = (int) ($_POST['periode_id'] ?? 0);

            $stmtBulk = $conn->prepare("
                UPDATE obe_penilaian p
                JOIN obe_rps r ON r.id = p.rps_id
                SET p.status_review = 'setuju'
                WHERE p.mahasiswa_id = ? AND r.mata_kuliah_id = ? AND p.periode_id = ? AND p.status_review = 'belum'
            ");
            $stmtBulk->bind_param("iii", $mahasiswaId, $mataKuliahId, $periodeId);
            $stmtBulk->execute();

            echo json_encode(['success' => true, 'message' => $stmtBulk->affected_rows . ' nilai berhasil disetujui sekaligus.']);

            break;

        case 'delete':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $id = (int) ($_POST['id'] ?? 0);
            echo json_encode($service->delete($id));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}