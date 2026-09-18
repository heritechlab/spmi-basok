<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$repository = new WorkspaceRepository($conn);
$service    = new WorkspaceService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'checklist':

            $assignmentId = (int)($_GET['assignment_id'] ?? 0);

            echo json_encode($service->getChecklist($assignmentId));

            break;

        case 'progress':

            $assignmentId = (int)($_GET['assignment_id'] ?? 0);

            echo json_encode($service->getProgress($assignmentId));

            break;

        case 'item':

            $id = (int)($_GET['id'] ?? 0);

            echo json_encode($service->getChecklistItem($id));

            break;

        case 'desk_eval':

            $assignmentId = (int)($_GET['assignment_id'] ?? 0);
            $standardId = (int)($_GET['standard_id'] ?? 0);
            $indicatorId = (int)($_GET['indicator_id'] ?? 0);

            echo json_encode($service->getDeskEvaluation($assignmentId, $standardId, $indicatorId));

            break;

        case 'risk_register_ref':

            $standardId = (int) ($_GET['standard_id'] ?? 0);
            $indicatorId = (int) ($_GET['indicator_id'] ?? 0);

            require_once __DIR__ . '/../../risiko/repository.php';
            $risikoRepo = new RisikoRepository($conn);

            $risks = $risikoRepo->getRisksBySumberList([
                ['sumber_jenis' => 'standar', 'sumber_id' => $standardId],
                ['sumber_jenis' => 'indikator', 'sumber_id' => $indicatorId],
            ]);

            echo json_encode(['success' => true, 'data' => $risks]);

            break;

        case 'save':

            $checklistId = (int)($_POST['checklist_id'] ?? 0);
            $assignmentId = (int)($_POST['assignment_id'] ?? 0);

            $input = $_POST;
            $input['auditor_id'] = $_SESSION['user_id'] ?? 0;

            $result = $service->saveResult($checklistId, $assignmentId, $input);

            if ($result['success'] && in_array(trim($input['audit_status'] ?? ''), ['Menyimpang', 'Belum Mencapai'], true)) {
                try {
                    require_once __DIR__ . '/../../risiko/repository.php';
                    $ctx = $repository->getContextForRisk($checklistId);

                    if ($ctx) {
                        $risikoRepo = new RisikoRepository($conn);
                        $deskripsi = 'Temuan Audit: ' . $ctx['item_code'] . ' - ' . $ctx['indicator']
                            . ' (Standar: ' . $ctx['standard_name'] . ') berstatus "' . $input['audit_status'] . '"';

                        $risikoRepo->createRiskFromFinding((int) $ctx['unit_id'], $checklistId, $deskripsi);
                    }
                } catch (Throwable $e) {
                    // gagal auto-draft risiko tidak boleh membatalkan penyimpanan hasil audit
                }
            }

            echo json_encode($result);

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}