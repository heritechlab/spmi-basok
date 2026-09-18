<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/master/institution/repository.php';

$type = $_GET['type'] ?? '';
$id = (int) ($_GET['id'] ?? 0);
$role = $_GET['role'] ?? '';

$validRoles = ['perumusan', 'pemeriksaan', 'persetujuan', 'penetapan', 'pengendalian', 'gkm', 'lpm', 'ketua_tim', 'ketua_lpm', 'ketua_institusi', 'notulis', 'pimpinan'];
$roleLabels = [
    'perumusan'        => 'Perumusan',
    'pemeriksaan'      => 'Pemeriksaan',
    'persetujuan'      => 'Persetujuan',
    'penetapan'        => 'Penetapan',
    'pengendalian'     => 'Pengendalian',
    'gkm'              => 'Ketua Gugus Kendali Mutu',
    'lpm'              => 'Ketua Lembaga Penjaminan Mutu',
    'ketua_tim'        => 'Ketua Tim Audit',
    'ketua_lpm'        => 'Ketua Lembaga Penjaminan Mutu',
    'ketua_institusi'  => 'Ketua Institusi',
    'notulis'          => 'Notulis Rapat',
    'pimpinan'         => 'Pimpinan Rapat',
];

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

$document = null;
$documentTitle = '';
$documentNumber = '';
$errorMessage = '';

if (!in_array($role, $validRoles, true) || $id <= 0) {

    $errorMessage = 'Kode verifikasi tidak valid.';

} else {

    if ($type === 'standard') {

        $stmt = $conn->prepare("SELECT * FROM standards WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $document = $stmt->get_result()->fetch_assoc();
        $documentTitle = $document['name'] ?? '';
        $documentNumber = $document['document_number'] ?? '';

    } elseif ($type === 'sop') {

        $stmt = $conn->prepare("SELECT * FROM sop_documents WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $document = $stmt->get_result()->fetch_assoc();
        $documentTitle = $document['title'] ?? '';
        $documentNumber = $document['document_number'] ?? '';

} elseif ($type === 'formulir') {

        $stmt = $conn->prepare("SELECT * FROM form_documents WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $document = $stmt->get_result()->fetch_assoc();
        $documentTitle = $document['title'] ?? '';
        $documentNumber = $document['document_number'] ?? '';

    } elseif ($type === 'ami') {

        $stmt = $conn->prepare("
            SELECT las.*, p.period_name, u.name AS unit_name
            FROM laporan_ami_signatures las
            LEFT JOIN audit_periods p ON p.id = las.period_id
            LEFT JOIN units u ON u.id = las.unit_id
            WHERE las.id = ? LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $document = $stmt->get_result()->fetch_assoc();

        $documentTitle = 'Laporan AMI ' . (($document['report_type'] ?? '') === 'unit' ? 'per Unit Kerja - ' . ($document['unit_name'] ?? '') : 'Institusi');
        $documentNumber = 'Periode ' . ($document['period_name'] ?? '-');

    } elseif ($type === 'rtm_pengesahan') {

        $stmt = $conn->prepare("
            SELECT las.*, p.period_name, u.name AS unit_name
            FROM laporan_ami_signatures las
            LEFT JOIN audit_periods p ON p.id = las.period_id
            LEFT JOIN units u ON u.id = las.unit_id
            WHERE las.id = ? LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $document = $stmt->get_result()->fetch_assoc();

        $documentTitle = 'Laporan RTM Pengendalian - ' . ($document['unit_name'] ?? '');
        $documentNumber = 'Periode ' . ($document['period_name'] ?? '-');

    } elseif ($type === 'rtm_meeting') {

        $stmt = $conn->prepare("
            SELECT rm.*, u.name AS unit_name
            FROM rtm_meetings rm
            LEFT JOIN units u ON u.id = rm.unit_id
            WHERE rm.id = ? LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $document = $stmt->get_result()->fetch_assoc();

        $documentTitle = 'Berita Acara Rapat RTM - ' . ($document['meeting_number'] ?? '');
        $documentNumber = $document['unit_name'] ?? '-';

    } elseif ($type === 'gkm') {

        $stmt = $conn->prepare("
            SELECT gm.*, u.name AS unit_name
            FROM gkm_monitoring gm
            LEFT JOIN units u ON u.id = gm.unit_id
            WHERE gm.id = ? LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $document = $stmt->get_result()->fetch_assoc();
        $documentTitle = 'Laporan Monitoring GKM - ' . ($document['unit_name'] ?? '');
        $documentNumber = ($document['semester'] ?? '') . ' ' . ($document['academic_year'] ?? '');

    } else {

        $errorMessage = 'Jenis dokumen tidak dikenali.';

    }

    if ($document && empty($document[$role . '_ttd'])) {
        $errorMessage = 'Dokumen ini belum ditandatangani pada proses ' . ($roleLabels[$role] ?? $role) . '.';
        $document = null;
    }

    if (!$document && $errorMessage === '') {
        $errorMessage = 'Dokumen tidak ditemukan.';
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dokumen - <?= htmlspecialchars($profile['institution_name'] ?? 'SIQUA') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f2fb; font-family: Arial, sans-serif; }
        .verify-wrap { max-width: 480px; margin: 60px auto; padding: 0 16px; }
        .verify-card { background: #fff; border-radius: 20px; padding: 32px; box-shadow: 0 15px 40px rgba(30,20,60,0.1); text-align: center; }
        .verify-icon { width: 72px; height: 72px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 34px; }
        .verify-icon.success { background: rgba(34,197,94,0.12); color: #22c55e; }
        .verify-icon.error { background: rgba(220,38,38,0.12); color: #dc2626; }
        .verify-detail { text-align: left; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        .verify-detail .row-item { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; border-bottom: 1px dashed #eee; }
        .verify-detail .row-item span:first-child { color: #6b7280; }
        .verify-detail .row-item span:last-child { font-weight: 600; color: #1f2937; text-align: right; }
    </style>
</head>
<body>

    <div class="verify-wrap">

        <div class="verify-card">

            <?php if (!empty($profile['logo'])): ?>
                <img src="<?= BASE_URL . htmlspecialchars($profile['logo']) ?>" style="height:50px; margin-bottom:16px;">
            <?php endif; ?>

            <?php if ($document): ?>

                <div class="verify-icon success"><i class="bi bi-patch-check-fill"></i></div>
                <h5 class="fw-bold mb-1">Dokumen Terverifikasi</h5>
                <p class="text-muted small mb-0">Tanda tangan elektronik ini sah dan tercatat dalam sistem SIQUA.</p>

                <div class="verify-detail">
                    <div class="row-item"><span>No. Dokumen</span><span><?= htmlspecialchars($documentNumber) ?></span></div>
                    <div class="row-item"><span>Judul Dokumen</span><span><?= htmlspecialchars($documentTitle) ?></span></div>
                    <div class="row-item"><span>Proses</span><span><?= htmlspecialchars($roleLabels[$role]) ?></span></div>
<div class="row-item">
                        <span>Ditandatangani Oleh</span>
                        <span><?= htmlspecialchars($document[$role . '_nama'] ?: '-') ?></span>
                    </div>
                    <div class="row-item">
                        <span>Jabatan</span>
                        <span><?= htmlspecialchars($document[$role . '_jabatan'] ?? $roleLabels[$role] ?? '-') ?></span>
                    </div>
                    <div class="row-item">
                        <span>Tanggal</span>
                        <span><?= !empty($document[$role . '_tanggal']) ? date('d F Y', strtotime($document[$role . '_tanggal'])) : '-' ?></span>
                    </div>
                </div>

            <?php else: ?>

                <div class="verify-icon error"><i class="bi bi-x-circle-fill"></i></div>
                <h5 class="fw-bold mb-1">Verifikasi Gagal</h5>
                <p class="text-muted small mb-0"><?= htmlspecialchars($errorMessage) ?></p>

            <?php endif; ?>

        </div>

        <p class="text-center text-muted small mt-3">
            <?= htmlspecialchars($profile['institution_name'] ?? '') ?> &mdash; Sistem Informasi Audit Mutu Internal
        </p>

    </div>

</body>
</html>