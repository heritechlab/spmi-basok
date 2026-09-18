<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';

if (empty($_SESSION['login']) || !Auth::isMahasiswa()) {
    header("Location: login.php");
    exit;
}

$mahasiswaId = Auth::getMahasiswaId();

$stmtNim = $conn->prepare("SELECT nim, nama FROM obe_mahasiswa WHERE id = ? LIMIT 1");
$stmtNim->bind_param("i", $mahasiswaId);
$stmtNim->execute();
$mhs = $stmtNim->get_result()->fetch_assoc();
$nim = $mhs['nim'] ?? null;

$activeSurveys = $conn->query("SELECT id, label, survey_slug, survey_url, sort_order FROM survey_gate_settings WHERE enabled = 1 ORDER BY sort_order ASC")->fetch_all(MYSQLI_ASSOC);

$daftarStatus = [];
$semuaSelesai = true;

foreach ($activeSurveys as $sv) {
    $stmtType = $conn->prepare("SELECT id FROM survey_types WHERE slug = ? LIMIT 1");
    $stmtType->bind_param("s", $sv['survey_slug']);
    $stmtType->execute();
    $typeRow = $stmtType->get_result()->fetch_assoc();

    $selesai = false;

    if ($typeRow && $nim) {
        $stmtResp = $conn->prepare("SELECT id FROM survey_responses WHERE type_id = ? AND nim_pengisi = ? AND survey_year = YEAR(NOW()) LIMIT 1");
        $stmtResp->bind_param("is", $typeRow['id'], $nim);
        $stmtResp->execute();
        $selesai = (bool) $stmtResp->get_result()->fetch_assoc();
    }

    if (!$selesai) $semuaSelesai = false;

    $daftarStatus[] = [
        'label'   => $sv['label'],
        'url'     => $sv['survey_url'],
        'selesai' => $selesai,
    ];
}

if ($semuaSelesai || empty($activeSurveys)) {
    header("Location: ../dashboard/");
    exit;
}

?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Survey Wajib</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
    body { background: linear-gradient(135deg, #5b21b6 0%, #6a11cb 50%, #581c87 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: Arial, sans-serif; padding: 20px; }
    .card-wrap { background: #fff; border-radius: 18px; padding: 36px 32px; width: 100%; max-width: 480px; box-shadow: 0 20px 50px rgba(0,0,0,0.25); }
    .icon-badge { width: 56px; height: 56px; border-radius: 50%; background: #f1edfc; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 16px; }
    .survey-item { border: 1px solid #eceaf5; border-radius: 12px; padding: 14px 16px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
    .survey-item.done { background: #f0fdf9; border-color: #99f6e4; }
</style>
</head>
<body>

<div class="card-wrap">
    <div class="icon-badge"><i class="bi bi-clipboard2-check-fill"></i></div>
    <h5 class="text-center fw-bold mb-1">Wajib Isi Survey</h5>
    <p class="text-center text-muted small mb-4">Selesaikan seluruh Survey berikut sebelum meninjau nilai Anda pada Periode ini.</p>

    <?php foreach ($daftarStatus as $s): ?>
    <div class="survey-item <?= $s['selesai'] ? 'done' : '' ?>">
        <div>
            <div class="fw-semibold small"><?= htmlspecialchars($s['label']) ?></div>
            <?php if ($s['selesai']): ?>
                <span class="text-success small"><i class="bi bi-check-circle-fill"></i> Sudah diisi</span>
            <?php else: ?>
                <span class="text-muted small">Belum diisi</span>
            <?php endif; ?>
        </div>
        <?php if (!$s['selesai']): ?>
        <a href="<?= htmlspecialchars($s['url']) ?>" target="_blank" class="btn btn-primary btn-sm">
            <i class="bi bi-box-arrow-up-right"></i> Isi
        </a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <button type="button" class="btn btn-success w-100 mt-3" onclick="window.location.reload()">
        <i class="bi bi-arrow-clockwise"></i> Saya Sudah Selesai, Cek Ulang
    </button>
</div>

</body>
</html>