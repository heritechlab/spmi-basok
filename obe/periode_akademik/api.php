<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/PeriodeCopyService.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$repository = new PeriodeAkademikRepository($conn);
$service    = new PeriodeAkademikService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'list':

            echo json_encode($service->getAll());

            break;
        
        case 'active':

            echo json_encode($service->getActive());

            break;

        case 'create':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            echo json_encode($service->create($_POST));

            break;

        case 'set_active':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $id = (int) ($_POST['id'] ?? 0);
            echo json_encode($service->setActive($id));

            break;

        case 'delete':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $id = (int) ($_POST['id'] ?? 0);
            echo json_encode($service->delete($id));

            break;        

        case 'copy':

            if (!Auth::canManage()) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
                break;
            }

            $sourceId = (int) ($_POST['source_periode_id'] ?? 0);
            $targetId = (int) ($_POST['target_periode_id'] ?? 0);

            try {
                $copyService = new PeriodeCopyService($conn);
                $result = $copyService->copy($sourceId, $targetId);

                $ringkasan = "RPS: {$result['rps']}, Rencana Evaluasi: {$result['rencana_evaluasi']}, Tim Teaching: {$result['tim_teaching']}, Rubrik Penilaian: {$result['rubrik_penilaian']}";

                echo json_encode(['success' => true, 'message' => "Berhasil disalin. ({$ringkasan})"]);
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}