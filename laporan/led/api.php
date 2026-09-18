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

$repository = new LedReportRepository($conn);
$service    = new LedReportService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'data':

            $unitId = (int) ($_GET['unit_id'] ?? 0);
            $periodId = (int) ($_GET['period_id'] ?? 0);

            if ($unitId <= 0 || $periodId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Unit dan Periode wajib dipilih.']);
                break;
            }

            echo json_encode(['success' => true, 'message' => '', 'data' => $service->getReportData($unitId, $periodId)]);

            break;

        case 'save_narrative':

            $unitId = (int) ($_POST['unit_id'] ?? 0);

            if (Auth::isAuditee()) {
                $stmtMe = $conn->prepare("SELECT unit_id FROM users WHERE id = ? LIMIT 1");
                $stmtMe->bind_param("i", $_SESSION['user_id']);
                $stmtMe->execute();
                $myUnitId = (int) ($stmtMe->get_result()->fetch_assoc()['unit_id'] ?? 0);

                if ($unitId !== $myUnitId) {
                    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
                    break;
                }
            } elseif (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }
            $periodId = (int) ($_POST['period_id'] ?? 0);
            $section = trim($_POST['section'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $updatedBy = $_SESSION['user_id'] ?? 0;

            echo json_encode($service->saveNarrative($unitId, $periodId, $section, $content, $updatedBy));

            break;

        case 'save_kriteria_analysis':

            $unitId = (int) ($_POST['unit_id'] ?? 0);

            if (Auth::isAuditee()) {
                $stmtMe = $conn->prepare("SELECT unit_id FROM users WHERE id = ? LIMIT 1");
                $stmtMe->bind_param("i", $_SESSION['user_id']);
                $stmtMe->execute();
                $myUnitId = (int) ($stmtMe->get_result()->fetch_assoc()['unit_id'] ?? 0);

                if ($unitId !== $myUnitId) {
                    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
                    break;
                }
            } elseif (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }
            $periodId = (int) ($_POST['period_id'] ?? 0);
            $criteriaId = (int) ($_POST['criteria_id'] ?? 0);
            $kekuatan = trim($_POST['kekuatan'] ?? '');
            $kelemahan = trim($_POST['kelemahan'] ?? '');
            $updatedBy = $_SESSION['user_id'] ?? 0;

            echo json_encode($service->saveKriteriaAnalysis($unitId, $periodId, $criteriaId, $kekuatan, $kelemahan, $updatedBy));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}