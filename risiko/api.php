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

$repository = new RisikoRepository($conn);
$service    = new RisikoService($repository);

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'identifikasi_data':
            $unitId = (int) ($_GET['unit_id'] ?? 0);
            echo json_encode($service->getIdentifikasiData($unitId));
            break;

        case 'tambah_risiko':
            $unitId = (int) ($_POST['unit_id'] ?? 0);
            $sumberJenis = trim($_POST['sumber_jenis'] ?? '');
            $sumberId = (int) ($_POST['sumber_id'] ?? 0);
            $deskripsi = trim($_POST['deskripsi_risiko'] ?? '');
            $kategori = trim($_POST['kategori_risiko'] ?? '') ?: null;
            $createdBy = $_SESSION['user_id'] ?? null;
            echo json_encode($service->tambahRisiko($unitId, $sumberJenis, $sumberId, $deskripsi, $kategori, $createdBy));
            break;

        case 'analisis_data':
            echo json_encode($service->getAnalisisData());
            break;

        case 'simpan_analisis':
            $riskId = (int) ($_POST['risk_id'] ?? 0);
            $likelihood = (int) ($_POST['likelihood'] ?? 0);
            $impact = (int) ($_POST['impact'] ?? 0);
            $detectability = !empty($_POST['detectability']) ? (int) $_POST['detectability'] : null;
            $controlReadiness = !empty($_POST['control_readiness']) ? (int) $_POST['control_readiness'] : null;
            $deskripsiDampak = trim($_POST['deskripsi_dampak'] ?? '') ?: null;
            echo json_encode($service->analisisRisiko($riskId, $likelihood, $impact, $detectability, $controlReadiness, $deskripsiDampak));
            break;

        case 'finished_assignments':
            echo json_encode($service->getFinishedAssignments());
            break;

        case 'pascaaudit_risks':
            $assignmentId = (int) ($_GET['assignment_id'] ?? 0);
            echo json_encode($service->getPascaauditRisks($assignmentId));
            break;

        case 'simpan_review_pascaaudit':
            $riskId = (int) ($_POST['risk_id'] ?? 0);
            $likelihood = (int) ($_POST['likelihood'] ?? 0);
            $impact = (int) ($_POST['impact'] ?? 0);
            $detectability = !empty($_POST['detectability']) ? (int) $_POST['detectability'] : null;
            $controlReadiness = !empty($_POST['control_readiness']) ? (int) $_POST['control_readiness'] : null;
            $rekomendasi = $_POST['rekomendasi_lanjutan'] ?? '';
            $reviewedBy = (int) ($_SESSION['user_id'] ?? 0);
            echo json_encode($service->simpanReviewPascaaudit($riskId, $likelihood, $impact, $detectability, $controlReadiness, $rekomendasi, $reviewedBy));
            break;

        case 'sampaikan_pascaaudit':
            $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
            echo json_encode($service->sampaikanKeAuditee($assignmentId));
            break;

        case 'link_options':
            $riskId = (int) ($_GET['risk_id'] ?? 0);
            echo json_encode($service->getLinkOptions($riskId));
            break;

        case 'tautkan_rtl':
            $riskId = (int) ($_POST['risk_id'] ?? 0);
            $rtlId = (int) ($_POST['rtl_id'] ?? 0);
            echo json_encode($service->tautkanRtl($riskId, $rtlId));
            break;

        case 'tautkan_ptp':
            $riskId = (int) ($_POST['risk_id'] ?? 0);
            $ptpId = (int) ($_POST['ptp_id'] ?? 0);
            echo json_encode($service->tautkanPtp($riskId, $ptpId));
            break;

        case 'prioritas_audit':
            echo json_encode($service->getPrioritasAudit());
            break;

        case 'mitigasi_data':
            echo json_encode($service->getMitigasiData());
            break;

        case 'tambah_mitigasi':
            $riskId = (int) ($_POST['risk_id'] ?? 0);
            $tindakan = trim($_POST['tindakan_mitigasi'] ?? '');
            $akarMasalah = trim($_POST['akar_masalah'] ?? '') ?: null;
            $indikator = trim($_POST['indikator_keberhasilan'] ?? '') ?: null;
            $picUserId = !empty($_POST['pic_user_id']) ? (int) $_POST['pic_user_id'] : null;
            $targetTanggal = $_POST['target_tanggal'] ?? null;
            echo json_encode($service->tambahMitigasi($riskId, $tindakan, $akarMasalah, $indikator, $picUserId, $targetTanggal));
            break;

        case 'update_status_efektivitas':
            $planId = (int) ($_POST['plan_id'] ?? 0);
            $status = trim($_POST['status'] ?? '');
            echo json_encode($service->updateStatusEfektivitas($planId, $status));
            break;

        case 'update_status_mitigasi':
            $planId = (int) ($_POST['plan_id'] ?? 0);
            $status = trim($_POST['status'] ?? '');
            echo json_encode($service->updateMitigasiStatus($planId, $status));
            break;

        case 'tandai_termitigasi':
            $riskId = (int) ($_POST['risk_id'] ?? 0);
            echo json_encode($service->tandaiTermitigasi($riskId));
            break;

        case 'kriteria_list':
            echo json_encode(['success' => true, 'data' => $repository->getAccCriteria()]);
            break;

        case 'unit_list':
            echo json_encode(['success' => true, 'data' => $repository->getUnitList()]);
            break;

        case 'user_list':
            echo json_encode(['success' => true, 'data' => $repository->getUserList()]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}