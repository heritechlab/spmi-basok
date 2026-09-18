<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../institution/repository.php';
require_once __DIR__ . '/repository.php';

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

$indicatorRepo = new IndicatorRepository($conn);

$standardId = isset($_GET['standard_id']) && $_GET['standard_id'] !== '' ? (int) $_GET['standard_id'] : 0;
$indicatorType = $_GET['indicator_type'] ?? '';

$indicators = $indicatorRepo->getAll('', $standardId, $indicatorType, 1000, 0);

$indicatorIds = array_map(fn($r) => (int) $r['id'], $indicators);
$ptpStatusMap = $indicatorRepo->getPtpStatusMap($indicatorIds);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Indikator - <?= htmlspecialchars($profile['institution_name'] ?? 'SIQUA') ?></title>

    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 13px;
            color: #000;
            padding: 30px 40px;
        }

        .kop {
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .kop img {
            height: 80px;
            width: auto;
        }

        .kop-text {
            text-align: center;
            flex: 1;
        }

        .kop-text h1 {
            font-size: 24px;
            margin: 0;
            text-transform: uppercase;
            font-weight: bold;
        }

        .kop-text p {
            margin: 3px 0;
            font-size: 14px;
        }

        .title {
            text-align: center;
            margin-bottom: 20px;
        }

        .title h2 {
            font-size: 15px;
            text-decoration: underline;
            text-transform: uppercase;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        table th, table td {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 12px;
            vertical-align: top;
        }

        table th {
            background: #eee;
            text-align: center;
        }

        .signature {
            width: 260px;
            margin-left: auto;
            text-align: center;
        }

        .signature .space {
            height: 70px;
        }

        .btn-print {
            position: fixed;
            top: 20px;
            right: 40px;
            padding: 10px 18px;
            background: #2575fc;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <button class="btn-print no-print" onclick="window.print()">Print / Simpan sebagai PDF</button>

    <div class="kop">

        <?php if (!empty($profile['logo'])): ?>
            <img src="<?= BASE_URL . htmlspecialchars($profile['logo']) ?>" alt="Logo">
        <?php endif; ?>

        <div class="kop-text">

            <?php if (!empty($profile['foundation_name'])): ?>
                <p style="font-size: 20px; font-weight: bold; margin: 0;">
                    <?= htmlspecialchars($profile['foundation_name']) ?>
                </p>
            <?php endif; ?>

            <h1><?= htmlspecialchars($profile['institution_name'] ?? '') ?></h1>

            <p>
                <?= htmlspecialchars($profile['address'] ?? '') ?>
                <?= !empty($profile['city']) ? ', ' . htmlspecialchars($profile['city']) : '' ?>
                <?= !empty($profile['province']) ? ', ' . htmlspecialchars($profile['province']) : '' ?>
                <?= !empty($profile['postal_code']) ? ' ' . htmlspecialchars($profile['postal_code']) : '' ?>
            </p>
            <p>
                <?= !empty($profile['phone']) ? 'Telp: ' . htmlspecialchars($profile['phone']) : '' ?>
                <?= !empty($profile['email']) ? ' | Email: ' . htmlspecialchars($profile['email']) : '' ?>
                <?= !empty($profile['website']) ? ' | ' . htmlspecialchars($profile['website']) : '' ?>
            </p>
        </div>

    </div>

    <div class="title">
        <h2>Daftar Master Indikator</h2>
    </div>

<table>
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Kode</th>
                <th>Butir Standar</th>
                <th>Indikator</th>
                <th>Target</th>
                <th width="90">Jenis Indikator</th>
                <th width="90">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($indicators)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;">Tidak ada data</td>
                </tr>
            <?php else: ?>
                <?php foreach ($indicators as $i => $row): ?>
                    <?php
                        $lastApplied = $ptpStatusMap[(int) $row['id']] ?? null;
                        $statusText = $lastApplied ? 'Ditingkatkan (' . date('d/m/Y', strtotime($lastApplied)) . ')' : 'Tetap';
                    ?>
                    <tr>
                        <td style="text-align:center;"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($row['item_code']) ?></td>
                        <td><?= htmlspecialchars($row['statement']) ?></td>
                        <td><?= htmlspecialchars($row['indicator']) ?></td>
                        <td><?= htmlspecialchars($row['target']) ?></td>
                        <td style="text-align:center;"><?= htmlspecialchars($row['indicator_type']) ?></td>
                        <td style="text-align:center;"><?= htmlspecialchars($statusText) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="signature">
        <p><?= htmlspecialchars($profile['city'] ?? '') ?>, <?= date('d F Y') ?></p>
        <p><?= htmlspecialchars($profile['leader_title'] ?? 'Ketua LPM') ?></p>
        <div class="space"></div>
        <p style="font-weight:bold; text-decoration: underline;">
            <?= htmlspecialchars($profile['leader_name'] ?? '(...........................)') ?>
        </p>
    </div>

</body>
</html>