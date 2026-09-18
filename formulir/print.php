<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../master/institution/repository.php';
require_once __DIR__ . '/repository.php';

$id = (int)($_GET['id'] ?? 0);

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

$formulirRepo = new FormulirRepository($conn);
$formulir = $formulirRepo->findById($id);

if (!$formulir) {
    die('<p style="font-family: sans-serif; padding: 40px;">Dokumen Formulir tidak ditemukan.</p>');
}

$bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

$berlakuSejak = '-';
if (!empty($formulir['effective_date'])) {
    $ts = strtotime($formulir['effective_date']);
    $berlakuSejak = $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

$institusiName = $profile['institution_name'] ?? '';

$jabatanMap = [
    'perumusan'    => $formulir['perumusan_jabatan'] ?: '-',
    'pemeriksaan'  => $formulir['pemeriksaan_jabatan'] ?: '-',
    'persetujuan'  => $formulir['persetujuan_jabatan'] ?: '-',
    'penetapan'    => $formulir['penetapan_jabatan'] ?: '-',
    'pengendalian' => $formulir['pengendalian_jabatan'] ?: '-',
];

$hasPdf = !empty($formulir['file_path']) && strtolower(pathinfo($formulir['file_path'], PATHINFO_EXTENSION)) === 'pdf';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Formulir - <?= htmlspecialchars($formulir['title']) ?></title>

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

        table {
            width: 100%;
            border-collapse: collapse;
        }

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

        .footer-note {
            margin-top: 16px;
            font-size: 11px;
            display: flex;
            justify-content: space-between;
        }

        .pdf-embed-wrap {
            width: 180mm;
            margin: 0 auto;
        }

        .pdf-embed-wrap iframe {
            width: 180mm;
            height: 267mm;
            border: 1px solid #000;
            background: #fff;
            display: block;
            overflow: hidden;
        }

        .no-file-notice {
            border: 1px dashed #999;
            padding: 20px;
            text-align: center;
            color: #666;
            margin-top: 20px;
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
    <!-- HALAMAN 1: KOP + PENGESAHAN -->
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
                <td class="kop-value-cell"><?= htmlspecialchars($formulir['document_number']) ?></td>
            </tr>
            <tr>
                <td class="kop-label-cell">Berlaku sejak</td>
                <td class="kop-value-cell"><?= htmlspecialchars($berlakuSejak) ?></td>
            </tr>
            <tr>
                <td class="kop-title-cell" rowspan="2">
                    <?= htmlspecialchars($formulir['title']) ?>
                </td>
                <td class="kop-label-cell">Revisi</td>
                <td class="kop-value-cell"><?= (int) $formulir['revision'] ?></td>
            </tr>
            <tr>
                <td class="kop-label-cell">Halaman</td>
                <td class="kop-value-cell">1 dari <?= (int) $formulir['total_pages'] ?></td>
            </tr>
        </table>

        <div class="doc-title">
            <h1><?= htmlspecialchars($formulir['title']) ?></h1>
            <h2><?= htmlspecialchars($institusiName) ?></h2>
        </div>

        <?php if (!empty($formulir['description'])): ?>
            <p><?= nl2br(htmlspecialchars($formulir['description'])) ?></p>
        <?php endif; ?>

        <table class="approval-table">
            <thead>
                <tr>
                    <th rowspan="2" width="70">Proses</th>
                    <th colspan="3">Penanggung Jawab</th>
                    <th rowspan="2" width="70">Tanggal</th>
                </tr>
                <tr>
                    <th width="190">Nama</th>
                    <th width="190">Jabatan</th>
                    <th width="70">Tanda Tangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-left">1. Perumusan</td>
                    <td class="text-left"><?= htmlspecialchars($formulir['perumusan_nama'] ?: '') ?></td>
                    <td class="text-left"><?= htmlspecialchars($jabatanMap['perumusan']) ?></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-left">2. Pemeriksaan</td>
                    <td class="text-left"><?= htmlspecialchars($formulir['pemeriksaan_nama'] ?: '') ?></td>
                    <td class="text-left"><?= htmlspecialchars($jabatanMap['pemeriksaan']) ?></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-left">3. Persetujuan</td>
                    <td class="text-left"><?= htmlspecialchars($formulir['persetujuan_nama'] ?: '') ?></td>
                    <td class="text-left"><?= htmlspecialchars($jabatanMap['persetujuan']) ?></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-left">4. Penetapan</td>
                    <td class="text-left"><?= htmlspecialchars($formulir['penetapan_nama'] ?: '') ?></td>
                    <td class="text-left"><?= htmlspecialchars($jabatanMap['penetapan']) ?></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="text-left">5. Pengendalian</td>
                    <td class="text-left"><?= htmlspecialchars($formulir['pengendalian_nama'] ?: '') ?></td>
                    <td class="text-left"><?= htmlspecialchars($jabatanMap['pengendalian']) ?></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <div class="footer-note">
            <span>1</span>
            <span><?= htmlspecialchars(strtoupper($institusiName)) ?></span>
        </div>

    </div>

    <!-- ===================================================== -->
    <!-- HALAMAN 2 DST: ISI FORMULIR (PDF) -->
    <!-- ===================================================== -->

    <div>

        <?php if ($hasPdf): ?>

            <div class="pdf-embed-wrap">
                <iframe src="<?= BASE_URL . htmlspecialchars($formulir['file_path']) ?>#toolbar=0&navpanes=0&scrollbar=0&view=FitH"></iframe>
            </div>

        <?php else: ?>

            <div class="no-file-notice">
                Belum ada file PDF isi Formulir yang diunggah untuk dokumen ini.
            </div>

        <?php endif; ?>

    </div>

</body>
</html>