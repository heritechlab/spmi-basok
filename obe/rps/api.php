<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Periode.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$repository = new RpsRepository($conn);
$service    = new RpsService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'list':

            $mataKuliahId = (int) ($_GET['mata_kuliah_id'] ?? 0);
            $periodeId = Periode::getActiveId($conn);
            echo json_encode($service->getAllByMataKuliah($mataKuliahId, $periodeId));

            break;

        case 'get':

            $id = (int) ($_GET['id'] ?? 0);
            echo json_encode($service->getById($id));

            break;

        case 'kurikulum_list':

            $unitId = (int) ($_GET['unit_id'] ?? 0);

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            require_once __DIR__ . '/../kurikulum/repository.php';
            require_once __DIR__ . '/../kurikulum/service.php';

            $kurikulumRepo = new KurikulumRepository($conn);
            $kurikulumService = new KurikulumService($kurikulumRepo);

            echo json_encode($kurikulumService->getAll($unitId));

            break;

        case 'mata_kuliah_list':

            $kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);

            require_once __DIR__ . '/../mata_kuliah/repository.php';
            require_once __DIR__ . '/../mata_kuliah/service.php';

            $mkRepo = new MataKuliahRepository($conn);
            $mkService = new MataKuliahService($mkRepo);
            $periodeId = Periode::getActiveId($conn);

            echo json_encode($mkService->getAll($kurikulumId, $periodeId));

            break;

       case 'rencana_evaluasi_by_pertemuan':

            $mataKuliahId = (int) ($_GET['mata_kuliah_id'] ?? 0);
            $pertemuan = (int) ($_GET['pertemuan'] ?? 0);
            $periodeId = Periode::getActiveId($conn);

            echo json_encode($service->getRencanaEvaluasiByPertemuan($mataKuliahId, $periodeId, $pertemuan));

            break;

       case 'sub_cpmk_list':

            $mataKuliahId = (int) ($_GET['mata_kuliah_id'] ?? 0);

            require_once __DIR__ . '/../cpmk/repository.php';
            require_once __DIR__ . '/../cpmk/service.php';

            $cpmkRepo = new CpmkRepository($conn);
            $cpmkList = $cpmkRepo->getAllByMataKuliah($mataKuliahId);

            $result = [];

            $subStmt = $conn->prepare("
                SELECT sc.id, sc.code, sc.description, c.code AS cpmk_code
                FROM obe_sub_cpmk sc
                JOIN obe_cpmk c ON c.id = sc.cpmk_id
                WHERE c.mata_kuliah_id = ?
                ORDER BY c.sort_order ASC, sc.sort_order ASC
            ");
            $subStmt->bind_param("i", $mataKuliahId);
            $subStmt->execute();
            $result = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);

            echo json_encode(['success' => true, 'data' => $result]);

            break;

        case 'bentuk_pembelajaran_list':

            require_once __DIR__ . '/../bentuk_pembelajaran/repository.php';
            require_once __DIR__ . '/../bentuk_pembelajaran/service.php';

            $bpRepo = new BentukPembelajaranRepository($conn);
            $bpService = new BentukPembelajaranService($bpRepo);

            echo json_encode($bpService->getAll());

            break;

        case 'metode_pembelajaran_list':

            $kategori = trim($_GET['kategori'] ?? '');

            require_once __DIR__ . '/../metode_pembelajaran/repository.php';
            require_once __DIR__ . '/../metode_pembelajaran/service.php';

            $mpRepo = new MetodePembelajaranRepository($conn);
            $mpService = new MetodePembelajaranService($mpRepo);

            $allMetode = $mpService->getAll();

            if ($kategori !== '' && $allMetode['success']) {
                $allMetode['data'] = array_values(array_filter($allMetode['data'], function ($m) use ($kategori) {
                    return $m['kategori'] === $kategori;
                }));
            }

            echo json_encode($allMetode);

            break;

        case 'bahan_kajian_list':

            $subCpmkId = (int) ($_GET['sub_cpmk_id'] ?? 0);

            $stmt = $conn->prepare("SELECT id, nama_bahan_kajian FROM obe_bahan_kajian WHERE sub_cpmk_id = ? ORDER BY sort_order ASC");
            $stmt->bind_param("i", $subCpmkId);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            echo json_encode(['success' => true, 'data' => $data]);

            break;

        case 'rencana_evaluasi_list':

            $mataKuliahId = (int) ($_GET['mata_kuliah_id'] ?? 0);
            $periodeId = Periode::getActiveId($conn);
            echo json_encode($service->getRencanaEvaluasiList($mataKuliahId, $periodeId));

            break;

        case 'indikator_list':

            require_once __DIR__ . '/../indikator_penilaian/repository.php';
            require_once __DIR__ . '/../indikator_penilaian/service.php';

            $ipRepo = new IndikatorPenilaianRepository($conn);
            $ipService = new IndikatorPenilaianService($ipRepo);

            echo json_encode($ipService->getAll());

            break;

        case 'waktu_komponen':

            $kategori = trim($_GET['kategori'] ?? '');

            $stmt = $conn->prepare("SELECT nama_komponen, menit FROM obe_kategori_waktu_komponen WHERE kategori = ? ORDER BY sort_order ASC");
            $stmt->bind_param("s", $kategori);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $total = array_sum(array_column($rows, 'menit'));

            echo json_encode(['success' => true, 'data' => ['total_menit' => $total, 'komponen' => $rows]]);

            break;

        case 'create':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $_POST['periode_id'] = Periode::getActiveId($conn);
            echo json_encode($service->create($_POST));

            break;

        case 'update':

            if (!Auth::canManage() && !Auth::isAuditee()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $id = (int) ($_POST['id'] ?? 0);
            $_POST['periode_id'] = Periode::getActiveId($conn);
            echo json_encode($service->update($id, $_POST));

            break;

        case 'delete':

            if (!Auth::canManage() && !Auth::isAuditee()) {
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