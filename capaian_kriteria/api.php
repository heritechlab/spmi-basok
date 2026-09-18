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

$repository = new CapaianKriteriaRepository($conn);
$service    = new CapaianKriteriaService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'periods':

            echo json_encode($service->getPeriods());

            break;

        case 'units':

            echo json_encode($service->getProdiUnits());

            break;

        case 'prodi_scores':

            $unitId = Auth::isAuditee() ? (int) ($_SESSION['unit_id'] ?? 0) : (int) ($_GET['unit_id'] ?? 0);
            $periodId = (int) ($_GET['period_id'] ?? 0);

            if ($unitId <= 0 || $periodId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Program Studi dan Periode wajib dipilih.']);
                break;
            }

            echo json_encode($service->getProdiCriteriaScores($unitId, $periodId));

            break;

        case 'institution_scores':

            $periodId = (int) ($_GET['period_id'] ?? 0);

            if ($periodId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Periode wajib dipilih.']);
                break;
            }

            echo json_encode($service->getInstitutionCriteriaScores($periodId));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}