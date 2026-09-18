<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../master/institution/repository.php';
require_once __DIR__ . '/repository.php';

$id = (int)($_GET['id'] ?? 0);

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

$sopRepo = new SopRepository($conn);
$sop = $sopRepo->findById($id);

if (!$sop) {
    die('<p style="font-family: sans-serif; padding: 40px;">Dokumen SOP tidak ditemukan.</p>');
}

$bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

$berlakuSejak = '-';
if (!empty($sop['effective_date'])) {
    $ts = strtotime($sop['effective_date']);
    $berlakuSejak = $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

$institusiName = $profile['institution_name'] ?? '';
$foundationName = $profile['foundation_name'] ?? '';

$jabatanMap = [
    'perumusan'    => $sop['perumusan_jabatan'] ?: '-',
    'pemeriksaan'  => $sop['pemeriksaan_jabatan'] ?: '-',
    'persetujuan'  => $sop['persetujuan_jabatan'] ?: '-',
    'penetapan'    => $sop['penetapan_jabatan'] ?: '-',
    'pengendalian' => $sop['pengendalian_jabatan'] ?: '-',
];

$prosedurLines = !empty($sop['prosedur']) ? array_filter(array_map('trim', explode("\n", $sop['prosedur']))) : [];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SOP - <?= htmlspecialchars($sop['title']) ?></title>

    <style>
@page { size: A4; margin: 15mm; }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
            .page-break { page-break-after: always; }
        }

        body {
            font-family: "Arial", sans-serif;
            font-size: 12px;
            color: #000;
            padding: 20px 30px;
            line-height: 1.5;
        }

        table { width: 100%; border-collapse: collapse; }
        table thead { display: table-header-group; }
        table tbody tr { page-break-inside: avoid; }

        .kop-table {
            margin-bottom: 4px;
        }

        .kop-table td {
            border: 1px solid #000;
            padding: 4px 8px;
            vertical-align: middle;
        }

        .kop-logo-cell {
            width: 90px;
            text-align: center;
        }

        .kop-logo-cell img {
            max-width: 80px;
            max-height: 80px;
        }

        .kop-title-cell {
            font-weight: bold;
            font-size: 13px;
            text-align: center;
        }

        .kop-label-cell {
            width: 110px;
            font-weight: bold;
        }

        .kop-value-cell {
            width: 120px;
        }

        .doc-title {
            text-align: center;
            margin: 16px 0 4px;
        }

        .doc-title h1 {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }

        .doc-title h2 {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 2px 0 0;
        }

        .approval-table {
            margin-top: 16px;
            margin-bottom: 16px;
        }

        .approval-table td, .approval-table th {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 11px;
            vertical-align: middle;
            text-align: center;
        }

        .approval-table th {
            background: #f0f0f0;
            font-weight: bold;
        }

        .approval-table .text-left {
            text-align: left;
        }

        .content-table td {
            border: 1px solid #000;
            padding: 8px 10px;
            vertical-align: top;
            font-size: 12px;
        }

        .content-table .no-cell {
            width: 30px;
            text-align: center;
            font-weight: bold;
        }

        .content-table .label-cell {
            width: 190px;
            font-weight: bold;
        }

        .bagan-alir-img {
            max-width: 100%;
            max-height: 400px;
        }

        .prosedur-list {
            margin: 0;
            padding-left: 18px;
        }

        .prosedur-list li {
            margin-bottom: 4px;
        }

        .footer-note {
            margin-top: 16px;
            font-size: 11px;
            display: flex;
            justify-content: space-between;
        }

        .btn-print {
            position: fixed;
            top: 20px;
            right: 40px;
            padding: 10px 18px;
            background: #5b21b6;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            z-index: 999;
        }
    </style>
</head>
<body>

    <button class="btn-print no-print" onclick="window.print()">Print / Simpan sebagai PDF</button>

<!-- ===================================================== -->
    <!-- HALAMAN 1: KOP DOKUMEN + PENGESAHAN -->
    <!-- ===================================================== -->

    <div class="page-break">

    <table class="kop-table">
        <tr>
            <td class="kop-logo-cell" rowspan="4">
                <?php if (!empty($profile['logo'])): ?>
                    <img src="<?= BASE_URL . htmlspecialchars($profile['logo']) ?>" alt="Logo">
                <?php endif; ?>
            </td>
            <td class="kop-title-cell" rowspan="2">
                Lembaga Penjaminan Mutu<br><?= htmlspecialchars($institusiName) ?>
            </td>
            <td class="kop-label-cell">No. Dokumen</td>
            <td class="kop-value-cell"><?= htmlspecialchars($sop['document_number']) ?></td>
        </tr>
        <tr>
            <td class="kop-label-cell">Berlaku sejak</td>
            <td class="kop-value-cell"><?= htmlspecialchars($berlakuSejak) ?></td>
        </tr>
        <tr>
            <td class="kop-title-cell" rowspan="2">
                <?= htmlspecialchars($sop['title']) ?>
            </td>
            <td class="kop-label-cell">Revisi</td>
            <td class="kop-value-cell"><?= (int) $sop['revision'] ?></td>
        </tr>
        <tr>
            <td class="kop-label-cell">Halaman</td>
            <td class="kop-value-cell">1 dari <?= (int) $sop['total_pages'] ?></td>
        </tr>
    </table>

    <!-- ===================================================== -->
    <!-- JUDUL DOKUMEN -->
    <!-- ===================================================== -->

    <div class="doc-title">
        <h1><?= htmlspecialchars($sop['title']) ?></h1>
        <h2><?= htmlspecialchars($institusiName) ?></h2>
    </div>

    <!-- ===================================================== -->
    <!-- TABEL PROSES PERSETUJUAN -->
    <!-- ===================================================== -->

<table class="approval-table">
        <thead>
            <tr>
                <th rowspan="2" width="90">Proses</th>
                <th colspan="3">Penanggung Jawab</th>
                <th rowspan="2" width="70">Tanggal</th>
            </tr>
            <tr>
                <th width="190">Nama</th>
                <th width="170">Jabatan</th>
                <th width="70">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-left">1. Perumusan</td>
                <td class="text-left nama-cell"><?= htmlspecialchars($sop['perumusan_nama'] ?: '') ?></td>
                <td class="text-left"><?= htmlspecialchars($jabatanMap['perumusan']) ?></td>
                <td style="text-align:center;">
                    <?php if (!empty($sop['perumusan_ttd'])): ?>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=<?= urlencode(BASE_URL . 'verify.php?type=sop&id=' . $sop['id'] . '&role=perumusan') ?>" style="width:40px; height:40px;">
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?= !empty($sop['perumusan_tanggal']) ? date('d/m/Y', strtotime($sop['perumusan_tanggal'])) : '' ?></td>
            </tr>
            <tr>
                <td class="text-left">2. Pemeriksaan</td>
                <td class="text-left nama-cell"><?= htmlspecialchars($sop['pemeriksaan_nama'] ?: '') ?></td>
                <td class="text-left"><?= htmlspecialchars($jabatanMap['pemeriksaan']) ?></td>
                <td style="text-align:center;">
                    <?php if (!empty($sop['pemeriksaan_ttd'])): ?>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=<?= urlencode(BASE_URL . 'verify.php?type=sop&id=' . $sop['id'] . '&role=pemeriksaan') ?>" style="width:40px; height:40px;">
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?= !empty($sop['pemeriksaan_tanggal']) ? date('d/m/Y', strtotime($sop['pemeriksaan_tanggal'])) : '' ?></td>
            </tr>
            <tr>
                <td class="text-left">3. Persetujuan</td>
                <td class="text-left nama-cell"><?= htmlspecialchars($sop['persetujuan_nama'] ?: '') ?></td>
                <td class="text-left"><?= htmlspecialchars($jabatanMap['persetujuan']) ?></td>
                <td style="text-align:center;">
                    <?php if (!empty($sop['persetujuan_ttd'])): ?>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=<?= urlencode(BASE_URL . 'verify.php?type=sop&id=' . $sop['id'] . '&role=persetujuan') ?>" style="width:40px; height:40px;">
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?= !empty($sop['persetujuan_tanggal']) ? date('d/m/Y', strtotime($sop['persetujuan_tanggal'])) : '' ?></td>
            </tr>
            <tr>
                <td class="text-left">4. Penetapan</td>
                <td class="text-left nama-cell"><?= htmlspecialchars($sop['penetapan_nama'] ?: '') ?></td>
                <td class="text-left"><?= htmlspecialchars($jabatanMap['penetapan']) ?></td>
                <td style="text-align:center;">
                    <?php if (!empty($sop['penetapan_ttd'])): ?>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=<?= urlencode(BASE_URL . 'verify.php?type=sop&id=' . $sop['id'] . '&role=penetapan') ?>" style="width:40px; height:40px;">
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?= !empty($sop['penetapan_tanggal']) ? date('d/m/Y', strtotime($sop['penetapan_tanggal'])) : '' ?></td>
            </tr>
            <tr>
                <td class="text-left">5. Pengendalian</td>
                <td class="text-left nama-cell"><?= htmlspecialchars($sop['pengendalian_nama'] ?: '') ?></td>
                <td class="text-left"><?= htmlspecialchars($jabatanMap['pengendalian']) ?></td>
                <td style="text-align:center;">
                    <?php if (!empty($sop['pengendalian_ttd'])): ?>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=<?= urlencode(BASE_URL . 'verify.php?type=sop&id=' . $sop['id'] . '&role=pengendalian') ?>" style="width:40px; height:40px;">
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?= !empty($sop['pengendalian_tanggal']) ? date('d/m/Y', strtotime($sop['pengendalian_tanggal'])) : '' ?></td>
            </tr>
        </tbody>
    </table>

    </div>

    <!-- ===================================================== -->
    <!-- TABEL ISI PROSEDUR -->
    <!-- ===================================================== -->

    <table class="content-table">

        <tr>
            <td class="no-cell">1.</td>
            <td class="label-cell">Tujuan Prosedur</td>
            <td><?= nl2br(htmlspecialchars($sop['tujuan_prosedur'] ?: '-')) ?></td>
        </tr>

        <tr>
            <td class="no-cell">2.</td>
            <td class="label-cell">Ruang Lingkup Prosedur</td>
            <td><?= nl2br(htmlspecialchars($sop['ruang_lingkup'] ?: '-')) ?></td>
        </tr>

        <tr>
            <td class="no-cell">3.</td>
            <td class="label-cell">Standar Terkait</td>
            <td>
                <?php if (!empty($sop['standard_code'])): ?>
                    <?= htmlspecialchars($sop['standard_code'] . ' - ' . $sop['standard_name']) ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <td class="no-cell">4.</td>
            <td class="label-cell">Definisi</td>
            <td><?= nl2br(htmlspecialchars($sop['definisi'] ?: '-')) ?></td>
        </tr>

        <tr>
            <td class="no-cell">5.</td>
            <td class="label-cell">Prosedur</td>
            <td>
                <?php if (!empty($prosedurLines)): ?>
                    <ol class="prosedur-list">
                        <?php foreach ($prosedurLines as $line): ?>
                            <li><?= htmlspecialchars($line) ?></li>
                        <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <td class="no-cell">6.</td>
            <td class="label-cell">Pihak yang Menjalankan Prosedur</td>
            <td><?= htmlspecialchars($sop['pihak_pelaksana'] ?: '-') ?></td>
        </tr>

        <tr>
            <td class="no-cell">7.</td>
            <td class="label-cell">Bagan Alir Prosedur</td>
            <td>
                <?php if (!empty($sop['bagan_alir_file'])): ?>
                    <img src="<?= BASE_URL . htmlspecialchars($sop['bagan_alir_file']) ?>" class="bagan-alir-img" alt="Bagan Alir">
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <td class="no-cell">8.</td>
            <td class="label-cell">Catatan</td>
            <td><?= nl2br(htmlspecialchars($sop['catatan'] ?: '-')) ?></td>
        </tr>

    </table>

    <!-- ===================================================== -->
    <!-- FOOTER -->
    <!-- ===================================================== -->

    <div class="footer-note">
        <span>1</span>
        <span><?= htmlspecialchars(strtoupper($institusiName)) ?></span>
    </div>

</body>
</html>