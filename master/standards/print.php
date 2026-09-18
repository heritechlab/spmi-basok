<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../institution/repository.php';
require_once __DIR__ . '/repository.php';

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

$standardRepo = new StandardRepository($conn);

$standards = $standardRepo->getStandards();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Standar - <?= htmlspecialchars($profile['institution_name'] ?? 'SIQUA') ?></title>

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
        <h2>Daftar Master Standar</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Kode</th>
                <th>Nama Standar</th>
                <th>Tanggal Terbit</th>
                <th width="60">Revisi</th>
                <th width="80">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($standards)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;">Tidak ada data</td>
                </tr>
            <?php else: ?>
                <?php foreach ($standards as $i => $row): ?>
                    <tr>
                        <td style="text-align:center;"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($row['code']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td style="text-align:center;"><?= htmlspecialchars($row['publish_date'] ?? '-') ?></td>
                        <td style="text-align:center;"><?= (int)$row['revision'] ?></td>
                        <td style="text-align:center;"><?= htmlspecialchars($row['status_name'] ?? '-') ?></td>
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