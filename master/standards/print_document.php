<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../institution/repository.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/../indicators/repository.php';

$id = (int)($_GET['id'] ?? 0);

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

$standardRepo = new StandardRepository($conn);
$standard = $standardRepo->findById($id);

if (!$standard) {
    die('<p style="font-family: sans-serif; padding: 40px;">Standar tidak ditemukan.</p>');
}

$indicatorRepo = new IndicatorRepository($conn);
$indicators = $indicatorRepo->getAll('', $id, '', 1000, 0);

$bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

$berlakuSejak = '-';
if (!empty($standard['publish_date'])) {
    $ts = strtotime($standard['publish_date']);
    $berlakuSejak = $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

$institusiName = $profile['institution_name'] ?? '';

$processes = [
    '1. Perumusan'    => 'perumusan',
    '2. Pemeriksaan'  => 'pemeriksaan',
    '3. Persetujuan'  => 'persetujuan',
    '4. Penetapan'    => 'penetapan',
    '5. Pengendalian' => 'pengendalian',
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Standar - <?= htmlspecialchars($standard['name']) ?></title>

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

        p { text-align: justify; }

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

        .approval-table {
            margin: 16px 0;
        }

        .approval-table td, .approval-table th {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 11px;
            vertical-align: middle;
            text-align: left;
        }

        .approval-table th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        h3.section-title {
            font-size: 13px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 6px;
        }

        .content-table {
            margin-top: 8px;
            margin-bottom: 16px;
            border-collapse: collapse;
        }

        .content-table td, .content-table th {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 11px;
            vertical-align: top;
        }

        .content-table th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .content-table thead {
            display: table-header-group;
        }

        .content-table tr {
            page-break-inside: avoid;
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
            <td class="kop-logo-cell" rowspan="3">
                <?php if (!empty($profile['logo'])): ?>
                    <img src="<?= BASE_URL . htmlspecialchars($profile['logo']) ?>" alt="Logo">
                <?php endif; ?>
            </td>
            <td class="kop-title-cell" rowspan="2">
                Lembaga Penjaminan Mutu<br><?= htmlspecialchars($institusiName) ?>
            </td>
            <td class="kop-label-cell">No. Dokumen</td>
            <td class="kop-value-cell"><?= htmlspecialchars($standard['document_number'] ?? '-') ?></td>
        </tr>
        <tr>
            <td class="kop-label-cell">Berlaku sejak</td>
            <td class="kop-value-cell"><?= htmlspecialchars($berlakuSejak) ?></td>
        </tr>
        <tr>
            <td class="kop-title-cell">
                <?= htmlspecialchars(strtoupper($standard['name'])) ?>
            </td>
            <td class="kop-label-cell">Revisi</td>
            <td class="kop-value-cell"><?= (int) $standard['revision'] ?></td>
        </tr>
    </table>

    <div class="doc-title">
        <h1><?= htmlspecialchars($standard['name']) ?></h1>
    </div>

    <!-- ===================================================== -->
    <!-- TABEL PROSES PERSETUJUAN -->
    <!-- ===================================================== -->

<table class="approval-table">
        <thead>
            <tr>
                <th rowspan="2" width="18%">Proses</th>
                <th colspan="3">Penanggung Jawab</th>
                <th rowspan="2" width="10%">Tanggal</th>
            </tr>
            <tr>
                <th width="30%">Nama</th>
                <th width="30%">Jabatan</th>
                <th width="12%">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($processes as $label => $key): ?>
                <?php
                    $ttdPath = $standard[$key . '_ttd'] ?? null;
                    $tanggal = $standard[$key . '_tanggal'] ?? null;
                    $tanggalText = $tanggal ? date('d/m/Y', strtotime($tanggal)) : '';
                ?>
                <tr>
                    <td><?= $label ?></td>
                    <td><?= htmlspecialchars($standard[$key . '_nama'] ?: '') ?></td>
                    <td><?= htmlspecialchars($standard[$key . '_jabatan'] ?: '-') ?></td>
                    <td style="text-align:center;">
                        <?php if ($ttdPath): ?>
                            <?php
                                $verifyUrl = BASE_URL . 'verify.php?type=standard&id=' . $standard['id'] . '&role=' . $key;
                                $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode($verifyUrl);
                            ?>
                            <img src="<?= $qrUrl ?>" style="width:40px; height:40px;">
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;"><?= htmlspecialchars($tanggalText) ?></td>
                </tr>
<?php endforeach; ?>
        </tbody>
    </table>

    </div>

    <!-- ===================================================== -->
    <!-- HALAMAN 2 DST: ISI STANDAR -->
    <!-- ===================================================== -->

    <h3 class="section-title">1. Rasional</h3>
    <p><?= nl2br(htmlspecialchars($standard['rasional'] ?: '-')) ?></p>

    <h3 class="section-title">2. Pihak yang Bertanggung Jawab</h3>
    <p><?= nl2br(htmlspecialchars($standard['pihak_bertanggung_jawab'] ?: '-')) ?></p>

    <h3 class="section-title">3. Definisi Istilah</h3>
    <p><?= nl2br(htmlspecialchars($standard['definisi_istilah'] ?: '-')) ?></p>

<h3 class="section-title">4. Pernyataan Isi, Strategi Pelaksanaan, dan Indikator Ketercapaian Standar</h3>

    <?php
        /*
        |--------------------------------------------------------------------------
        | Kelompokkan indikator berdasarkan Pernyataan + Strategi yang sama
        |--------------------------------------------------------------------------
        */

$groups = [];

        foreach ($indicators as $ind) {

            $key = trim((string) ($ind['statement'] ?? ''));

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'statement' => $ind['statement'] ?? '',
                    'strategi'  => $ind['strategi'] ?? '',
                    'items'     => [],
                ];
            }

            $groups[$key]['items'][] = $ind;
        }

        $groups = array_values($groups);
    ?>

    <table class="content-table">
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Pernyataan Isi Standar</th>
                <th>Strategi Pencapaian Standar</th>
                <th>Indikator Ketercapaian Standar</th>
                <th width="70">Target</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($groups)): ?>
                <tr><td colspan="5" style="text-align:center;">Belum ada indikator untuk Standar ini.</td></tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($groups as $group): ?>
                    <?php $rowspan = count($group['items']); ?>
                    <?php foreach ($group['items'] as $j => $ind): ?>
                        <tr>
                            <?php if ($j === 0): ?>
                                <td style="text-align:center;" rowspan="<?= $rowspan ?>"><?= $no++ ?></td>
                                <td rowspan="<?= $rowspan ?>"><?= nl2br(htmlspecialchars($group['statement'] ?: '-')) ?></td>
                                <td rowspan="<?= $rowspan ?>"><?= nl2br(htmlspecialchars($group['strategi'] ?: '-')) ?></td>
                            <?php endif; ?>
                            <td><?= nl2br(htmlspecialchars($ind['indicator'] ?: '-')) ?></td>
                            <td style="text-align:center;"><?= htmlspecialchars($ind['target'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <h3 class="section-title">5. Dokumen Terkait Pelaksanaan Standar</h3>
    <p><?= nl2br(htmlspecialchars($standard['dokumen_terkait'] ?: '-')) ?></p>

    <h3 class="section-title">6. Referensi</h3>
    <p><?= nl2br(htmlspecialchars($standard['referensi'] ?: '-')) ?></p>

    <div class="footer-note">
        <span>1</span>
        <span><?= htmlspecialchars(strtoupper($institusiName)) ?></span>
    </div>

</body>
</html>