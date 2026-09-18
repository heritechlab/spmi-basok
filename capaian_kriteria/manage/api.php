<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';

if (!Auth::canManage()) {
    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
    exit;
}

$repository = new CapaianKriteriaRepository($conn);
$service    = new CapaianKriteriaService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'standards':

            echo json_encode($service->getStandardsFlat());

            break;

        case 'save_mapping':

            $standardId = (int) ($_POST['standard_id'] ?? 0);
            $prodiCriteriaId = (int) ($_POST['prodi_criteria_id'] ?? 0);
            $institutionCriteriaId = (int) ($_POST['institution_criteria_id'] ?? 0);

            echo json_encode($service->saveStandardMapping($standardId, $prodiCriteriaId, $institutionCriteriaId));

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}