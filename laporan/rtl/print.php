<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../master/institution/repository.php';

$unitId = (int)($_GET['unit_id'] ?? 0);
$periodId = (int)($_GET['period_id'] ?? 0);

$repository = new RtlReportRepository($conn);
$service    = new RtlReportService($repository);

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

$result = $service->getReportData($unitId, $periodId);

if (!$result['success']) {
    die('<p style="font-family: sans-serif; padding: 40px;">' . htmlspecialchars($result['message']) . '</p>');
}

$data = $result['data'];

$unit = $data['unit'];
$period = $data['period'];
$evidences = $data['evidences'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan RTL - <?= htmlspecialchars($unit['name']) ?></title>

    <style>
        @page { size: A4; margin: 30mm 30mm 30mm 30mm; }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0 20px !important; }
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 13px;
            color: #000;
            padding: 30px 50px;
            line-height: 1.6;
        }

        .bab-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            text-decoration: underline;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th, table td { border: 1px solid #000; padding: 3px 6px; font-size: 12px; vertical-align: top; line-height: 1.1; }
        table th { background: #eee; text-align: center; }

        table a { color: #2575fc; text-decoration: underline; }

        .btn-print {
            position: fixed; top: 20px; right: 40px;
            padding: 10px 18px; background: #2575fc; color: #fff;
            border: none; border-radius: 6px; cursor: pointer; font-size: 14px; z-index: 999;
        }
    </style>
</head>
<body>

    <button class="btn-print no-print" onclick="window.print()">Print / Simpan sebagai PDF</button>

    <div class="bab-title">
        Laporan Bukti Pelaksanaan RTL<br>
        <?= htmlspecialchars($unit['name']) ?> - <?= htmlspecialchars($period['period_name']) ?>
    </div>

    <table>
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Indikator</th>
                <th>Kegiatan RTL</th>
                <th width="90">Waktu</th>
                <th width="90">PIC</th>
                <th width="80">Status</th>
                <th width="120">Diupload Oleh</th>
                <th>Dokumen Bukti</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($evidences)): ?>
                <tr><td colspan="8" style="text-align:center;">Belum ada bukti pelaksanaan RTL yang diunggah.</td></tr>
            <?php else: ?>
                <?php foreach ($evidences as $i => $e): ?>
                    <tr>
                        <td style="text-align:center;"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($e['item_code'] . ' - ' . $e['indicator']) ?></td>
                        <td><?= htmlspecialchars($e['activity']) ?></td>
                        <td style="text-align:center;"><?= htmlspecialchars($e['implementation_time'] ?? '-') ?></td>
                        <td style="text-align:center;"><?= htmlspecialchars($e['pic'] ?? '-') ?></td>
                        <td style="text-align:center;"><?= htmlspecialchars($e['status']) ?></td>
                        <td style="text-align:center;"><?= htmlspecialchars($e['uploaded_by_name'] ?? '-') ?></td>
                        <td>
                            <a href="<?= BASE_URL . htmlspecialchars($e['document_file']) ?>" target="_blank">
                                <?= htmlspecialchars($e['document_original_name']) ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>