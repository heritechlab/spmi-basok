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

$repository = new IkuRepository($conn);
$service    = new IkuService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'criteria':

            echo json_encode($service->getCriteria());

            break;

        case 'units':

            echo json_encode($service->getProdiUnits());

            break;

        case 'get_indicators':

            $kategori = trim($_GET['kategori'] ?? 'wajib');
            $tahun = (int) ($_GET['tahun'] ?? date('Y'));
            $triwulan = trim($_GET['triwulan'] ?? 'TW1');
            $unitId = !empty($_GET['unit_id']) ? (int) $_GET['unit_id'] : null;

            if (Auth::isAuditee()) {
                $unitId = (int) ($_SESSION['unit_id'] ?? 0);
            }

            echo json_encode($service->getIndicatorsWithData($kategori, $tahun, $triwulan, $unitId));

            break;
        case 'list_all':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            echo json_encode($service->getAllIndicatorsFlat());

            break;

        case 'update_indicator':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $id = (int) ($_POST['id'] ?? 0);

            echo json_encode($service->updateIndicator($id, $_POST));

            break;

        case 'save_target':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $indicatorId = (int) ($_POST['indicator_id'] ?? 0);
            $tahun = (int) ($_POST['tahun'] ?? 0);
            $baseline = trim($_POST['baseline'] ?? '');
            $target = trim($_POST['target'] ?? '');

            echo json_encode($service->saveTarget($indicatorId, $tahun, $baseline, $target));

            break;

            case 'get_indicator_units':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $indicatorId = (int) ($_GET['indicator_id'] ?? 0);

            echo json_encode($service->getIndicatorUnitIds($indicatorId));

            break;

        case 'save_indicator_units':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $indicatorId = (int) ($_POST['indicator_id'] ?? 0);
            $unitIds = $_POST['unit_ids'] ?? [];

            if (!is_array($unitIds)) {
                $unitIds = [];
            }

            echo json_encode($service->saveIndicatorUnits($indicatorId, $unitIds));

            break;

        case 'pending_count':

            $unitId = Auth::isAuditee() ? (int) ($_SESSION['unit_id'] ?? 0) : (int) ($_GET['unit_id'] ?? 0);

            if ($unitId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Unit tidak ditemukan.']);
                break;
            }

            $tahun = (int) date('Y');
            $bulan = (int) date('n');
            $triwulan = $bulan <= 3 ? 'TW1' : ($bulan <= 6 ? 'TW2' : ($bulan <= 9 ? 'TW3' : 'TW4'));

            echo json_encode($service->getAssignedIndicatorCount($unitId, $tahun, $triwulan));

            break;

        case 'save_realization':

            $updatedBy = $_SESSION['user_id'] ?? 0;

            if (Auth::isAuditee()) {
                $_POST['unit_id'] = $_SESSION['unit_id'] ?? 0;
            }

            echo json_encode($service->saveRealization($_POST, $_FILES['bukti_file'] ?? [], $updatedBy));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}