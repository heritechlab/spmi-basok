<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Periode.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$mataKuliahId = (int) ($_GET['mata_kuliah_id'] ?? 0);
$periodeId = Periode::getActiveId($conn);

$repository = new CetakRpsRepository($conn);
$service    = new CetakRpsService($repository);
$result     = $service->getData($mataKuliahId, $periodeId);

if (!$result['success']) {
    die('<div style="font-family:sans-serif; padding:40px;">' . htmlspecialchars($result['message']) . '</div>');
}

$d = $result['data'];
$mk = $d['mk'];
$institusi = $d['institusi'] ?? [];
$profilProdi = $d['profil_prodi'] ?? [];

function dosenLabel(?array $d): string
{
    if (!$d) return '-';
    return trim(($d['gelar_depan'] ?? '') . ' ' . ($d['name'] ?? '') . ($d['gelar_belakang'] ? ', ' . $d['gelar_belakang'] : ''));
}

function numberedLines(?string $text): array
{
    if (!$text) return [];
    $lines = preg_split('/\r\n|\r|\n/', trim($text));
    return array_values(array_filter($lines, fn($l) => trim($l) !== ''));
}

$totalSks = (float) $mk['sks_tatap_muka'] + (float) $mk['sks_praktikum'] + (float) $mk['sks_praktek_lapangan'] + (float) $mk['sks_simulasi'];

$logoPath = !empty($institusi['logo']) ? BASE_URL . htmlspecialchars($institusi['logo']) : '';

?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak RPS - <?= htmlspecialchars($mk['name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Times+New+Roman&display=swap" rel="stylesheet">
<style>
    body { font-family: "Times New Roman", Times, serif; font-size: 12pt; color: #000; background: #e5e5e5; margin: 0; }
    .rps-print-page {
        background: #fff;
        width: 210mm;
        min-height: 297mm;
        margin: 16px auto;
        padding: 15mm;
        box-shadow: 0 0 8px rgba(0,0,0,0.15);
        box-sizing: border-box;
        page-break-after: always;
    }
    .formulir-header { width: 100%; border-collapse: collapse; margin-bottom: 24px; font-size: 10pt; }
    .formulir-header td { border: 1px solid #000; padding: 4px 8px; vertical-align: middle; }
    .formulir-header .logo-cell { width: 70px; text-align: center; }
    .formulir-header .logo-cell img { width: 55px; }
    .formulir-header .label-cell { width: 110px; font-weight: normal; }
    .formulir-header .lembaga-cell { text-align: center; font-weight: bold; }
    .cover-title { text-align: center; font-weight: bold; font-size: 13pt; margin-top: 40px; }
    .cover-mk { text-align: center; font-weight: bold; font-size: 13pt; margin-top: 30px; text-transform: uppercase; }
    .cover-sks { text-align: center; font-weight: bold; font-size: 12pt; margin-top: 6px; }
    .cover-logo { text-align: center; margin-top: 40px; }
    .cover-logo img { width: 140px; }
    .cover-label { text-align: center; font-weight: bold; margin-top: 40px; }
    .cover-value { text-align: center; margin-top: 4px; }
    .cover-value.underline { text-decoration: underline; font-weight: bold; }
    .cover-footer { text-align: center; font-weight: bold; margin-top: 50px; line-height: 1.6; }
    .vm-section-title { text-align: center; font-weight: bold; font-size: 13pt; margin-bottom: 4px; }
    .vm-section-sub { text-align: center; font-weight: bold; font-size: 13pt; margin-bottom: 24px; }
    .vm-box { border: 1px solid #000; margin-bottom: 24px; }
    .vm-box-title { text-align: center; font-weight: bold; border-bottom: 1px solid #000; padding: 6px; }
    .vm-box-body { padding: 14px 18px; text-align: justify; }
    .vm-box-body ol { margin: 0; padding-left: 20px; }
    .nilai-dasar-list { padding-left: 22px; }
    .nilai-dasar-list li { font-weight: bold; margin-bottom: 4px; }
    table.rps-identity { width: 100%; border-collapse: collapse; font-size: 10.5pt; margin-top: 10px; }
    table.rps-identity th, table.rps-identity td { border: 1px solid #000; padding: 5px 8px; vertical-align: top; }
    table.rps-identity thead { display: table-header-group; }
    table.rps-identity .identity-logo { width: 70px; text-align: center; }
    table.rps-identity .identity-logo img { width: 55px; }
    table.rps-identity .section-title { text-align: center; font-weight: bold; background: #fff; }
    table.rps-identity .green-header { background: #f1edfc; font-weight: bold; text-align: center; }
    table.rps-identity .green-header.row-label { text-align: left; }
    table.rps-identity .signature-cell { text-align: center; height: 70px; vertical-align: bottom; }
    table.rps-identity .signature-name { font-weight: bold; }
    table.rps-identity .signature-label { font-size: 8.5pt; font-style: italic; color: #555; font-weight: normal; margin-bottom: 40px; }
    @page { size: A4 portrait; margin: 15mm; }
    @page landscape-page { size: A4 landscape; margin: 10mm; }
    .landscape-page { page: landscape-page; width: 277mm; }
    table.rps-aktivitas { width: 100%; border-collapse: collapse; font-size: 8pt; margin-top: 10px; }
    table.rps-aktivitas th, table.rps-aktivitas td { border: 1px solid #000; padding: 4px 5px; vertical-align: top; }
    table.rps-aktivitas thead { display: table-header-group; }
    table.rps-aktivitas thead th { background: #f1edfc; text-align: center; font-weight: bold; }
    table.rps-aktivitas .mg-cell { text-align: center; font-weight: bold; }
    .peta-cp-title { text-align: center; font-weight: bold; font-size: 12.5pt; margin-bottom: 14px; }
    .cp-box-cpl { border: 2px solid #7c3aed; background: #f1edfc; padding: 10px 14px; margin-bottom: 10px; border-radius: 4px; }
    .cp-box-cpl .cp-box-title { font-weight: bold; margin-bottom: 6px; font-size: 9.5pt; }
    .cp-box-cpl ol, .cp-box-cpmk ol { margin: 0; padding-left: 22px; font-size: 8.5pt; }
    .cp-box-cpl ol li, .cp-box-cpmk ol li { padding-left: 2px; }
    .cp-box-cpl ol li, .cp-box-cpmk ol li { margin-bottom: 3px; }
    .cp-box-cpmk { border: 2px solid #9575cd; background: #f5f2fb; padding: 10px 14px; margin-bottom: 10px; border-radius: 4px; }
    .cp-box-cpmk .cp-box-title { font-weight: bold; margin-bottom: 6px; font-size: 9.5pt; }
    .cp-evaluasi-bar { background: #45818e; color: #fff; text-align: center; font-weight: bold; padding: 5px; margin: 6px 0; border-radius: 4px; font-size: 9pt; }
    .cp-arrow-row { text-align: center; font-size: 13pt; color: #666; line-height: 1; margin: 0; }
    .cp-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 2px; }
    .cp-subcpmk-box { background: #d9d2e9; border: 1px solid #8e7cc3; border-radius: 4px; padding: 6px 8px; font-size: 7.8pt; line-height: 1.25; }
    .cp-subcpmk-box strong { display: block; margin-bottom: 2px; }
    .cp-row-partial { display: flex; justify-content: center; gap: 8px; margin-bottom: 2px; }
    .cp-row-partial .cp-subcpmk-box { flex: 0 1 23%; }
    @media print {
        body { background: #fff; }
        .rps-print-page { box-shadow: none; margin: 0; }
        .no-print { display: none !important; }
    }
    .no-print { position: fixed; top: 16px; right: 16px; z-index: 999; }
</style>
</head>
<body>

<div class="no-print">
    <button class="btn btn-primary shadow" onclick="window.print()"><i class="bi bi-printer-fill"></i> Simpan &amp; Cetak PDF</button>
</div>

<!-- ===================== HALAMAN 1: COVER ===================== -->
<div class="rps-print-page">


    <div class="cover-title">RENCANA PEMBELAJARAN SEMESTER (RPS)</div>
    <div class="cover-mk"><?= htmlspecialchars($mk['name']) ?></div>
    <div class="cover-sks">(<?= (int) $totalSks ?> SKS) Semester <?= htmlspecialchars((string) $mk['semester']) ?></div>

    <?php if ($logoPath): ?>
    <div class="cover-logo"><img src="<?= $logoPath ?>"></div>
    <?php endif; ?>

    <div class="cover-label">KODE MATA KULIAH</div>
    <div class="cover-value underline"><?= htmlspecialchars($mk['code'] ?? '-') ?></div>

    <div class="cover-label">KOORDINATOR</div>
    <div class="cover-value"><?= htmlspecialchars(dosenLabel($d['koordinator'])) ?></div>

    <?php if (!empty($d['tim_teaching'])): ?>
    <div class="cover-label">TIM</div>
    <?php foreach ($d['tim_teaching'] as $t): ?>
        <div class="cover-value"><?= htmlspecialchars(dosenLabel($t)) ?></div>
    <?php endforeach; ?>
    <?php endif; ?>

    <div class="cover-footer">
        PROGRAM STUDI <?= htmlspecialchars(strtoupper($mk['unit_name'] ?? '')) ?><br>
        <?= htmlspecialchars(strtoupper($institusi['institution_name'] ?? '')) ?><br>
        TAHUN <?= htmlspecialchars($mk['tahun_ajaran'] ?: date('Y') . '/' . (date('Y') + 1)) ?>
    </div>
</div>

<!-- ===================== HALAMAN 2: VISI MISI INSTITUSI ===================== -->
<div class="rps-print-page">


    <div class="vm-section-title">VISI MISI</div>
    <div class="vm-section-sub"><?= htmlspecialchars(strtoupper($institusi['institution_name'] ?? '')) ?></div>

    <div class="vm-box">
        <div class="vm-box-title">VISI</div>
        <div class="vm-box-body">&ldquo;<?= nl2br(htmlspecialchars($institusi['vision'] ?? '-')) ?>&rdquo;</div>
    </div>

    <div class="vm-box">
        <div class="vm-box-title">MISI</div>
        <div class="vm-box-body">
            <ol>
                <?php foreach (numberedLines($institusi['mission'] ?? '') as $line): ?>
                    <li><?= htmlspecialchars(preg_replace('/^\d+[\.\)]\s*/', '', $line)) ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
</div>

<!-- ===================== HALAMAN 3: VISI MISI PRODI ===================== -->
<div class="rps-print-page">


    <div class="vm-section-title">VISI MISI</div>
    <div class="vm-section-sub">PROGRAM STUDI <?= htmlspecialchars(strtoupper($mk['unit_name'] ?? '')) ?></div>

    <div class="vm-box">
        <div class="vm-box-title">VISI</div>
        <div class="vm-box-body"><?= nl2br(htmlspecialchars($profilProdi['visi'] ?? '-')) ?></div>
    </div>

    <div class="vm-box">
        <div class="vm-box-title">MISI</div>
        <div class="vm-box-body">
            <ol>
                <?php foreach (numberedLines($profilProdi['misi'] ?? '') as $line): ?>
                    <li><?= htmlspecialchars(preg_replace('/^\d+[\.\)]\s*/', '', $line)) ?></li>
                <?php endforeach; ?>
                <?php if (empty(numberedLines($profilProdi['misi'] ?? ''))): ?>
                    <li class="text-muted">-</li>
                <?php endif; ?>
            </ol>
        </div>
    </div>
</div>

<!-- ===================== HALAMAN 4: UNGGULAN / NILAI DASAR PRODI ===================== -->
<div class="rps-print-page">


    <div class="vm-section-title">UNGGULAN PROGRAM STUDI</div>
    <div class="vm-section-sub">PROGRAM STUDI <?= htmlspecialchars(strtoupper($mk['unit_name'] ?? '')) ?></div>

    <ol class="nilai-dasar-list">
        <?php $unggulanLines = numberedLines($profilProdi['unggulan'] ?? ''); ?>
        <?php if (empty($unggulanLines)): ?>
            <li class="text-muted" style="font-weight:normal;">Belum diisi.</li>
        <?php endif; ?>
        <?php foreach ($unggulanLines as $line): ?>
            <li><?= htmlspecialchars(preg_replace('/^\d+[\.\)]\s*/', '', $line)) ?></li>
        <?php endforeach; ?>
    </ol>
</div>

<!-- ===================== HALAMAN 5+: BAGIAN AWAL RPS ===================== -->
<div class="rps-print-page landscape-page">


    <table class="rps-identity" style="margin-top:0;">
        <tr>
            <td class="identity-logo"><?php if ($logoPath): ?><img src="<?= $logoPath ?>"><?php endif; ?></td>
            <td class="section-title">
                RENCANA PEMBELAJARAN SEMESTER TA <?= htmlspecialchars($mk['tahun_ajaran'] ?: date('Y') . '/' . (date('Y') + 1)) ?><br>
                PROGRAM STUDI <?= htmlspecialchars(strtoupper($mk['unit_name'] ?? '')) ?><br>
                <?= htmlspecialchars(strtoupper($institusi['institution_name'] ?? '')) ?>
            </td>
        </tr>
    </table>

    <table class="rps-identity">
        <colgroup>
            <col style="width:20%;"><col style="width:14%;"><col style="width:22%;"><col style="width:24%;"><col style="width:10%;"><col style="width:10%;">
        </colgroup>
        <tr>
            <td class="green-header">MATA KULIAH</td>
            <td class="green-header">KODE MK</td>
            <td class="green-header">KELOMPOK BIDANG ILMU</td>
            <td class="green-header">BOBOT SKS</td>
            <td class="green-header">SEMESTER</td>
            <td class="green-header">DIREVISI</td>
        </tr>
        <tr>
            <td><?= htmlspecialchars($mk['name']) ?></td>
            <td style="text-align:center;"><?= htmlspecialchars($mk['code'] ?? '-') ?></td>
            <td><?= htmlspecialchars($mk['rumpun_mk'] ?? '-') ?></td>
            <td>
                T=<?= (float) $mk['sks_tatap_muka'] ?>
                &nbsp;P=<?= (float) $mk['sks_praktikum'] ?>
                &nbsp;PL=<?= (float) $mk['sks_praktek_lapangan'] ?>
                <?php if ((float) $mk['sks_simulasi'] > 0): ?>&nbsp;S=<?= (float) $mk['sks_simulasi'] ?><?php endif; ?>
            </td>
            <td style="text-align:center;"><?= htmlspecialchars((string) $mk['semester']) ?></td>
            <td style="text-align:center;"><?= $mk['tanggal_revisi_rps'] ? htmlspecialchars(date('d/m/Y', strtotime($mk['tanggal_revisi_rps']))) : '-' ?></td>
        </tr>
    </table>

    <table class="rps-identity">
        <colgroup>
            <col style="width:8%;"><col style="width:23%;"><col style="width:23%;"><col style="width:23%;"><col style="width:23%;">
        </colgroup>
        <tr>
            <td class="green-header" rowspan="2">OTORISASI</td>
            <td class="green-header">Pengembang RPS</td>
            <td class="green-header">Koordinator MK</td>
            <td class="green-header">Gugus Kendali Mutu</td>
            <td class="green-header">Ka. Prodi</td>
        </tr>
        <tr>
            <td class="signature-cell">
                <div class="signature-label">Tanda Tangan</div>
                <div class="signature-name"><?= htmlspecialchars(dosenLabel($d['pengembang'])) ?></div>
            </td>
            <td class="signature-cell">
                <div class="signature-label">Tanda Tangan</div>
                <div class="signature-name"><?= htmlspecialchars(dosenLabel($d['koordinator'])) ?></div>
            </td>
            <td class="signature-cell">
                <div class="signature-label">Tanda Tangan</div>
                <div class="signature-name"><?= htmlspecialchars(dosenLabel($d['gkm'])) ?></div>
            </td>
            <td class="signature-cell">
                <div class="signature-label">Tanda Tangan</div>
                <div class="signature-name"><?= htmlspecialchars($mk['ka_prodi_name'] ?? '-') ?></div>
            </td>
        </tr>
    </table>

    <?php
    $cplRowCount = count($d['cpl_list']) ?: 1;
    $cpmkRowCount = count($d['cpmk_list']) ?: 1;
    $subCpmkRowCount = count($d['sub_cpmk_list']) ?: 1;
    $cpRowspan = 3 + $cplRowCount + $cpmkRowCount + $subCpmkRowCount;
    ?>
    <table class="rps-identity">
        <colgroup>
            <col style="width:14%;"><col style="width:86%;">
        </colgroup>
        <tr>
            <td class="green-header row-label" rowspan="<?= $cpRowspan ?>">Capaian Pembelajaran<br><i>(Learning Outcome)</i></td>
            <td class="green-header">Capaian Pembelajaran Lulusan (CPL) yang dibebankan pada MK <?= htmlspecialchars($mk['name']) ?></td>
        </tr>
        <?php foreach ($d['cpl_list'] as $cpl): ?>
        <tr><td><strong><?= htmlspecialchars($cpl['code']) ?></strong> &nbsp; <?= htmlspecialchars($cpl['description']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($d['cpl_list'])): ?>
        <tr><td class="text-muted">Belum ada CPL yang dipetakan.</td></tr>
        <?php endif; ?>

        <tr><td class="green-header row-label">Capaian Pembelajaran Mata Kuliah (CPMK)</td></tr>
        <?php foreach ($d['cpmk_list'] as $cpmk): ?>
        <tr><td><strong><?= htmlspecialchars($cpmk['code']) ?></strong> &nbsp; <?= htmlspecialchars($cpmk['description']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($d['cpmk_list'])): ?>
        <tr><td class="text-muted">Belum ada CPMK.</td></tr>
        <?php endif; ?>

        <tr><td class="green-header row-label">Kemampuan Akhir Tiap Tahapan Belajar (Sub-CPMK)</td></tr>
        <?php foreach ($d['sub_cpmk_list'] as $sc): ?>
        <tr><td><strong><?= htmlspecialchars($sc['code']) ?></strong> &nbsp; <?= htmlspecialchars($sc['description']) ?> &nbsp; <em>(<?= htmlspecialchars($sc['cpmk_code']) ?>)</em></td></tr>
        <?php endforeach; ?>
        <?php if (empty($d['sub_cpmk_list'])): ?>
        <tr><td class="text-muted">Belum ada Sub-CPMK.</td></tr>
        <?php endif; ?>
    </table>

        <table class="rps-identity">
        <colgroup>
            <col style="width:14%;"><col style="width:86%;">
        </colgroup>
        <tr><td class="green-header row-label" colspan="2">Matriks Kegayutan CPL dan CPMK</td></tr>
        <tr>
            <td></td>
            <td style="padding:0;">
                <?php if (!empty($d['cpl_list']) && !empty($d['cpmk_list'])): ?>
                <table style="width:100%; border-collapse:collapse; font-size:8.5pt;">
                    <thead>
                        <tr>
                            <th style="border:1px solid #000; padding:4px 6px; background:#f1edfc;">CPL &#92; CPMK</th>
                            <?php foreach ($d['cpmk_list'] as $cpmk): ?>
                                <th style="border:1px solid #000; padding:4px 4px; background:#f1edfc; text-align:center;"><?= htmlspecialchars($cpmk['code']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($d['cpl_list'] as $cpl): ?>
                        <tr>
                            <td style="border:1px solid #000; padding:4px 6px; font-weight:bold;"><?= htmlspecialchars($cpl['code']) ?></td>
                            <?php foreach ($d['cpmk_list'] as $cpmk): ?>
                                <td style="border:1px solid #000; padding:4px 4px; text-align:center;">
                                    <?= ((int) $cpmk['cpl_id'] === (int) $cpl['id']) ? '&#10003;' : '' ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <span class="text-muted">Belum ada CPL/CPMK untuk ditampilkan matriksnya.</span>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <table class="rps-identity">
        <colgroup>
            <col style="width:14%;"><col style="width:86%;">
        </colgroup>
        <tr><td class="green-header row-label" colspan="2">Matriks Kegayutan CPMK dan Sub-CPMK</td></tr>
        <tr>
            <td></td>
            <td style="padding:0;">
                <?php if (!empty($d['cpmk_list']) && !empty($d['sub_cpmk_list'])): ?>
                <table style="width:100%; border-collapse:collapse; font-size:8.5pt;">
                    <thead>
                        <tr>
                            <th style="border:1px solid #000; padding:4px 6px; background:#f1edfc;">CPMK &#92; Sub-CPMK</th>
                            <?php foreach ($d['sub_cpmk_list'] as $sc): ?>
                                <th style="border:1px solid #000; padding:4px 3px; background:#f1edfc; text-align:center;"><?= htmlspecialchars($sc['code']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($d['cpmk_list'] as $cpmk): ?>
                        <tr>
                            <td style="border:1px solid #000; padding:4px 6px; font-weight:bold;"><?= htmlspecialchars($cpmk['code']) ?></td>
                            <?php foreach ($d['sub_cpmk_list'] as $sc): ?>
                                <td style="border:1px solid #000; padding:4px 3px; text-align:center;">
                                    <?= ((int) $sc['cpmk_id'] === (int) $cpmk['id']) ? '&#10003;' : '' ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <span class="text-muted">Belum ada CPMK/Sub-CPMK untuk ditampilkan matriksnya.</span>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <table class="rps-identity">
        <colgroup>
            <col style="width:14%;"><col style="width:86%;">
        </colgroup>
        <tr><td class="green-header row-label">Deskripsi Mata Kuliah</td><td><?= $mk['deskripsi'] ? nl2br(htmlspecialchars($mk['deskripsi'])) : '-' ?></td></tr>
        <?php
        $bahanKajianUnique = [];
        foreach ($d['rps_list'] as $r) {
            foreach ($r['bahan_kajian'] as $bk) {
                $bahanKajianUnique[$bk['id']] = $bk['nama_bahan_kajian'];
            }
        }
        ?>
        <tr>
            <td class="green-header row-label">Bahan Kajian</td>
            <td>
                <?php if ($bahanKajianUnique): ?>
                    <ol style="margin:0; padding-left:18px;">
                        <?php foreach ($bahanKajianUnique as $nama): ?>
                            <li><?= htmlspecialchars($nama) ?></li>
                        <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    <span class="text-muted">Belum ada Bahan Kajian yang dipilih di RPS.</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr><td class="green-header row-label">Daftar Pustaka Wajib</td><td><?= $mk['pustaka_utama'] ? nl2br(htmlspecialchars($mk['pustaka_utama'])) : '-' ?></td></tr>
        <tr><td class="green-header row-label">Daftar Pustaka Pendukung</td><td><?= $mk['pustaka_pendukung'] ? nl2br(htmlspecialchars($mk['pustaka_pendukung'])) : '-' ?></td></tr>
        <tr><td class="green-header row-label">Media Pembelajaran</td><td><?= $mk['media_pembelajaran'] ? nl2br(htmlspecialchars($mk['media_pembelajaran'])) : '-' ?></td></tr>
        <tr>
            <td class="green-header row-label">Tim Teaching</td>
            <td>
                <?php if ($d['koordinator']): ?><?= htmlspecialchars(dosenLabel($d['koordinator'])) ?> (Koordinator)<br><?php endif; ?>
                <?php foreach ($d['tim_teaching'] as $t): ?><?= htmlspecialchars(dosenLabel($t)) ?><br><?php endforeach; ?>
                <?php if (!$d['koordinator'] && empty($d['tim_teaching'])): ?>-<?php endif; ?>
            </td>
        </tr>
        <tr><td class="green-header row-label">Prasyarat Mata Kuliah</td><td><?= htmlspecialchars($mk['prasyarat_mk'] ?: '-') ?></td></tr>
    </table>
</div>
<?php
function renderIndikatorHtml(array $row): string
{
    $parts = [];
    if (!empty($row['indikator_umum'])) {
        $parts[] = '<strong>Umum:</strong> ' . htmlspecialchars($row['indikator_umum']);
    }
    if (!empty($row['indikator_khusus'])) {
        $parts[] = '<strong>Khusus:</strong> ' . htmlspecialchars($row['indikator_khusus']);
    }
    return $parts ? implode('<br>', $parts) : '-';
}

function renderKriteriaBentukHtml(array $row): string
{
    if (empty($row['rencana_evaluasi_list'])) return '-';

    $parts = [];
    foreach ($row['rencana_evaluasi_list'] as $re) {
        $block = '<strong>' . htmlspecialchars($re['basis_evaluasi']) . '</strong>';
        foreach (($re['indikator'] ?? []) as $i) {
            $block .= '<br>- ' . htmlspecialchars($i['indikator']);
            if (!empty($i['nama_kriteria'])) {
                $block .= '<br>&nbsp;&nbsp;<em>' . htmlspecialchars($i['nama_kriteria']) . '</em>';
            }
        }
        $parts[] = $block;
    }
    return implode('<hr style="margin:3px 0;">', $parts);
}

function renderMetodeHtml(array $bentuk, array $metode): string
{
    $names = array_merge(
        array_column($bentuk, 'nama_bentuk'),
        array_column($metode, 'nama_metode')
    );
    $names = array_filter($names);
    return $names ? htmlspecialchars(implode(', ', $names)) : '-';
}

// Urutkan Sub-CPMK berdasarkan Pertemuan (dari data RPS)
$subCpmkPertemuanMap = [];
foreach ($d['rps_list'] as $r) {
    $scId = (int) $r['sub_cpmk_id'];
    $p = (int) $r['pertemuan'];
    if (!isset($subCpmkPertemuanMap[$scId]) || $p < $subCpmkPertemuanMap[$scId]) {
        $subCpmkPertemuanMap[$scId] = $p;
    }
}

// Urutkan MENURUN (pertemuan terakhir di atas, Mg 1 di paling bawah) - alur baca bawah ke atas
$subCpmkOrderedDesc = $d['sub_cpmk_list'];
usort($subCpmkOrderedDesc, function ($a, $b) use ($subCpmkPertemuanMap) {
    $pa = $subCpmkPertemuanMap[(int) $a['id']] ?? 0;
    $pb = $subCpmkPertemuanMap[(int) $b['id']] ?? 0;
    return $pb <=> $pa;
});

$semuaPertemuan = array_values($subCpmkPertemuanMap);
$maxPertemuan = $semuaPertemuan ? max($semuaPertemuan) : 0;
$utsBoundaryPertemuan = (int) floor($maxPertemuan / 2);

$groupSetelahCpmk = array_values(array_filter($subCpmkOrderedDesc, function ($sc) use ($subCpmkPertemuanMap, $utsBoundaryPertemuan) {
    return ($subCpmkPertemuanMap[(int) $sc['id']] ?? 0) > $utsBoundaryPertemuan;
}));
$groupSebelumUts = array_values(array_filter($subCpmkOrderedDesc, function ($sc) use ($subCpmkPertemuanMap, $utsBoundaryPertemuan) {
    return ($subCpmkPertemuanMap[(int) $sc['id']] ?? 0) <= $utsBoundaryPertemuan;
}));

function renderCpSubCpmkRows(array $items, array $pertemuanMap, bool $trailingArrow = true): void
{
    // Urutkan naik (Minggu terkecil dulu) supaya baris PALING BAWAH penuh (4 kartu) duluan,
    // sisa (kurang dari 4) otomatis "mengambang" ke baris PALING ATAS kelompok ini.
    usort($items, function ($a, $b) use ($pertemuanMap) {
        $pa = $pertemuanMap[(int) $a['id']] ?? 0;
        $pb = $pertemuanMap[(int) $b['id']] ?? 0;
        return $pa <=> $pb;
    });

    $chunks = array_reverse(array_chunk($items, 4));
    $total = count($chunks);

    foreach ($chunks as $idx => $rowItems) {
        $rowClass = count($rowItems) === 4 ? 'cp-row' : 'cp-row cp-row-partial';
        echo '<div class="' . $rowClass . '">';
        foreach ($rowItems as $sc) {
            $mg = isset($pertemuanMap[(int) $sc['id']]) ? ' (Mg ' . $pertemuanMap[(int) $sc['id']] . ')' : '';
            echo '<div class="cp-subcpmk-box"><strong>' . htmlspecialchars($sc['code']) . $mg . '</strong>'
                . htmlspecialchars($sc['description']) . ' <em>(' . htmlspecialchars($sc['cpmk_code']) . ')</em></div>';
        }
        echo '</div>';
        if ($idx < $total - 1 || $trailingArrow) {
            echo '<div class="cp-arrow-row">&uarr;</div>';
        }
    }
}
?>

<!-- ===================== HALAMAN: PETA CAPAIAN PEMBELAJARAN ===================== -->
<div class="rps-print-page">


    <div class="peta-cp-title">Peta Capaian Pembelajaran Mata Kuliah <?= htmlspecialchars($mk['name']) ?></div>

    <div class="cp-box-cpl">
        <div class="cp-box-title">Capaian Pembelajaran Lulusan (CPL) yang dibebankan pada MK <?= htmlspecialchars($mk['name']) ?></div>
        <ol>
            <?php foreach ($d['cpl_list'] as $cpl): ?>
                <li><?= htmlspecialchars($cpl['description']) ?></li>
            <?php endforeach; ?>
            <?php if (empty($d['cpl_list'])): ?><li class="text-muted">-</li><?php endif; ?>
        </ol>
    </div>

    <div class="cp-box-cpmk">
        <div class="cp-box-title">Capaian Pembelajaran Mata Kuliah (CPMK)</div>
        <ol>
            <?php foreach ($d['cpmk_list'] as $cpmk): ?>
                <li><?= htmlspecialchars($cpmk['description']) ?> <em>(<?= htmlspecialchars($cpmk['cpl_code'] ?? '-') ?>)</em></li>
            <?php endforeach; ?>
            <?php if (empty($d['cpmk_list'])): ?><li class="text-muted">-</li><?php endif; ?>
        </ol>
    </div>

    <div class="cp-evaluasi-bar">EVALUASI: UJIAN AKHIR SEMESTER</div>
    <div class="cp-arrow-row">&uarr;</div>

    <?php renderCpSubCpmkRows($groupSetelahCpmk, $subCpmkPertemuanMap, true); ?>

    <?php if (!empty($groupSebelumUts)): ?>
        <div class="cp-evaluasi-bar">EVALUASI: UJIAN TENGAH SEMESTER (setelah Minggu <?= $utsBoundaryPertemuan ?>)</div>
        <div class="cp-arrow-row">&uarr;</div>
        <?php renderCpSubCpmkRows($groupSebelumUts, $subCpmkPertemuanMap, false); ?>
    <?php endif; ?>

    <?php if (empty($subCpmkOrderedDesc)): ?>
        <div class="text-muted text-center">Belum ada Sub-CPMK.</div>
    <?php endif; ?>
</div>

<!-- ===================== HALAMAN: AKTIVITAS PEMBELAJARAN (LANDSCAPE) ===================== -->
<div class="rps-print-page landscape-page">


    <div class="vm-section-title" style="text-align:left; font-size:12pt;">AKTIVITAS PEMBELAJARAN</div>

    <table class="rps-aktivitas">
        <thead>
            <tr>
                <th rowspan="2" style="width:30px;">Mg</th>
                <th rowspan="2" style="width:130px;">Sub-CPMK</th>
                <th colspan="2">Penilaian</th>
                <th colspan="2">Bentuk Pembelajaran, Metode Pembelajaran</th>
                <th rowspan="2" style="width:55px;">Alokasi Waktu</th>
                <th rowspan="2" style="width:110px;">Materi Pembelajaran</th>
                <th rowspan="2" style="width:110px;">Pengalaman Belajar</th>
                <th rowspan="2" style="width:40px;">Bobot (%)</th>
            </tr>
            <tr>
                <th style="width:100px;">Indikator</th>
                <th style="width:120px;">Kriteria &amp; Bentuk</th>
                <th style="width:90px;">Luring (offline)</th>
                <th style="width:90px;">Daring (online)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($d['rps_list'] as $row): ?>
            <tr>
                <td class="mg-cell"><?= (int) $row['pertemuan'] ?></td>
                <td>
                    <strong><?= htmlspecialchars($row['cpmk_code'] . ' - ' . $row['sub_cpmk_code']) ?></strong>
                    <?php if ($row['sub_cpmk_description']): ?><br><?= htmlspecialchars($row['sub_cpmk_description']) ?><?php endif; ?>
                </td>
                <td><?= renderIndikatorHtml($row) ?></td>
                <td><?= renderKriteriaBentukHtml($row) ?></td>
                <td><?= renderMetodeHtml($row['bentuk_luring'], $row['metode_luring']) ?></td>
                <td><?= renderMetodeHtml([], $row['metode_daring']) ?></td>
                <td><?= $row['alokasi_waktu_rincian'] ? htmlspecialchars($row['alokasi_waktu_rincian']) : (int) $row['alokasi_waktu_menit'] . "'" ?></td>
                <td><?= $row['materi_pembelajaran'] ? nl2br(htmlspecialchars($row['materi_pembelajaran'])) : '-' ?></td>
                <td><?= $row['pengalaman_belajar'] ? nl2br(htmlspecialchars($row['pengalaman_belajar'])) : '-' ?></td>
                <td class="text-center"><?= number_format((float) $row['bobot_penilaian'], 1) ?>%</td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($d['rps_list'])): ?>
            <tr><td colspan="10" class="text-center text-muted">Belum ada data RPS per Pertemuan.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ===================== HALAMAN: JADWAL MENGAJAR DOSEN ===================== -->
<div class="rps-print-page">
    <div class="vm-section-title" style="text-align:left; font-size:12pt;">JADWAL MENGAJAR DOSEN</div>
    <table class="rps-aktivitas">
        <thead>
            <tr>
                <th style="width:50px;">Mg</th>
                <th style="width:90px;">Hari</th>
                <th style="width:90px;">Jam Mulai</th>
                <th style="width:90px;">Jam Selesai</th>
                <th style="width:120px;">Ruang</th>
                <th>Dosen Pengampu</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($d['jadwal_dosen'] as $j): ?>
            <tr>
                <td class="mg-cell"><?= (int) $j['pertemuan'] ?></td>
                <td><?= htmlspecialchars($j['hari']) ?></td>
                <td><?= htmlspecialchars(substr($j['jam_mulai'], 0, 5)) ?></td>
                <td><?= htmlspecialchars(substr($j['jam_selesai'], 0, 5)) ?></td>
                <td><?= htmlspecialchars($j['ruang'] ?: '-') ?></td>
                <td><?= htmlspecialchars($j['dosen_pengampu'] ?: '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($d['jadwal_dosen'])): ?>
            <tr><td colspan="6" class="text-center text-muted">Belum ada Jadwal Dosen.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$ranahLabels = ['Afektif' => 'SIKAP', 'Kognitif' => 'PENGETAHUAN', 'Psikomotorik' => 'KETERAMPILAN'];
$rubrikByRanah = [];
foreach ($d['rubrik_indikator'] as $ri) {
    $rubrikByRanah[$ri['taksonomi_ranah']][] = $ri;
}
?>
<?php foreach ($ranahLabels as $ranahKey => $ranahLabel): ?>
<?php if (!empty($rubrikByRanah[$ranahKey])): ?>
<!-- ===================== HALAMAN: RUBRIK PENILAIAN <?= $ranahLabel ?> ===================== -->
<div class="rps-print-page landscape-page">
    <div class="vm-section-title" style="text-align:left; font-size:12pt;">RUBRIK PENILAIAN <?= $ranahLabel ?></div>
    <table class="rps-aktivitas">
        <thead>
            <tr>
                <th style="width:30px;">No</th>
                <th style="width:160px;">Nama Mahasiswa</th>
                <?php foreach ($rubrikByRanah[$ranahKey] as $ri): ?>
                    <th><?= htmlspecialchars($ri['indikator']) ?></th>
                <?php endforeach; ?>
                <th style="width:50px;">SKOR</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($d['mahasiswa_list'])): ?>
                <?php foreach ($d['mahasiswa_list'] as $idx => $mhs): ?>
                <tr>
                    <td class="text-center"><?= $idx + 1 ?></td>
                    <td><?= htmlspecialchars($mhs['nama']) ?></td>
                    <?php foreach ($rubrikByRanah[$ranahKey] as $ri): ?>
                        <td>&nbsp;</td>
                    <?php endforeach; ?>
                    <td>&nbsp;</td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <?php for ($i = 1; $i <= 14; $i++): ?>
                <tr>
                    <td class="text-center"><?= $i ?></td>
                    <td>&nbsp;</td>
                    <?php foreach ($rubrikByRanah[$ranahKey] as $ri): ?>
                        <td>&nbsp;</td>
                    <?php endforeach; ?>
                    <td>&nbsp;</td>
                </tr>
                <?php endfor; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top:16px; font-weight:bold; font-size:9pt;">Kriteria penilaian :</div>
    <table style="border-collapse:collapse; margin-top:6px; font-size:9pt;">
        <tr><th style="border:1px solid #000; padding:4px 20px; background:#f1edfc;">INTERVAL NILAI</th><th style="border:1px solid #000; padding:4px 20px; background:#f1edfc;">Nilai</th></tr>
        <tr><td style="border:1px solid #000; padding:4px 20px;">80.0-100</td><td style="border:1px solid #000; padding:4px 20px; text-align:center;">A</td></tr>
        <tr><td style="border:1px solid #000; padding:4px 20px;">75.0-79.99</td><td style="border:1px solid #000; padding:4px 20px; text-align:center;">AB</td></tr>
        <tr><td style="border:1px solid #000; padding:4px 20px;">70.0-74.99</td><td style="border:1px solid #000; padding:4px 20px; text-align:center;">B</td></tr>
        <tr><td style="border:1px solid #000; padding:4px 20px;">65.0-69.99</td><td style="border:1px solid #000; padding:4px 20px; text-align:center;">BC</td></tr>
        <tr><td style="border:1px solid #000; padding:4px 20px;">56.0-64.99</td><td style="border:1px solid #000; padding:4px 20px; text-align:center;">C</td></tr>
        <tr><td style="border:1px solid #000; padding:4px 20px;">41.0-55.99</td><td style="border:1px solid #000; padding:4px 20px; text-align:center;">D</td></tr>
        <tr><td style="border:1px solid #000; padding:4px 20px;">&le; 40.0</td><td style="border:1px solid #000; padding:4px 20px; text-align:center;">E</td></tr>
    </table>

    <div style="text-align:right; margin-top:30px; font-size:10pt;">
        Jambi, ........................ 20....<br><br><br>
        <strong>Dosen Pengampu</strong>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<!-- ===================== HALAMAN: PANDUAN RUBRIK PENILAIAN ===================== -->
<div class="rps-print-page">
    <div class="vm-section-title" style="text-align:left; font-size:12pt;">PANDUAN RUBRIK PENILAIAN</div>

    <div style="font-size:9pt; margin-bottom:6px;"><strong>A. Kriteria Penilaian Aktivitas Partisipatif / Diskusi</strong></div>
    <table style="width:100%; border-collapse:collapse; font-size:7.5pt; margin-bottom:16px;">
        <tr>
            <th style="border:1px solid #000; padding:4px; background:#f1edfc;">Aspek</th>
            <th style="border:1px solid #000; padding:4px; background:#f1edfc;">4 (Sangat Baik)</th>
            <th style="border:1px solid #000; padding:4px; background:#f1edfc;">3 (Baik)</th>
            <th style="border:1px solid #000; padding:4px; background:#f1edfc;">2 (Cukup)</th>
            <th style="border:1px solid #000; padding:4px; background:#f1edfc;">1 (Kurang)</th>
        </tr>
        <tr><td style="border:1px solid #000; padding:4px;"><strong>Relevansi</strong> (30%)</td><td style="border:1px solid #000; padding:4px;">Sangat relevan, pemahaman mendalam</td><td style="border:1px solid #000; padding:4px;">Relevan dengan topik</td><td style="border:1px solid #000; padding:4px;">Cukup relevan</td><td style="border:1px solid #000; padding:4px;">Tidak relevan</td></tr>
        <tr><td style="border:1px solid #000; padding:4px;"><strong>Kedalaman Analisis</strong> (25%)</td><td style="border:1px solid #000; padding:4px;">Sangat mendalam &amp; kritis</td><td style="border:1px solid #000; padding:4px;">Mendalam dan kritis</td><td style="border:1px solid #000; padding:4px;">Cukup mendalam</td><td style="border:1px solid #000; padding:4px;">Kurang mendalam</td></tr>
        <tr><td style="border:1px solid #000; padding:4px;"><strong>Kejelasan</strong> (20%)</td><td style="border:1px solid #000; padding:4px;">Sangat jelas &amp; terstruktur</td><td style="border:1px solid #000; padding:4px;">Jelas dan mudah dipahami</td><td style="border:1px solid #000; padding:4px;">Cukup jelas</td><td style="border:1px solid #000; padding:4px;">Tidak jelas</td></tr>
        <tr><td style="border:1px solid #000; padding:4px;"><strong>Sikap &amp; Etika</strong> (15%)</td><td style="border:1px solid #000; padding:4px;">Sangat menghormati, sopan</td><td style="border:1px solid #000; padding:4px;">Menghormati dan sopan</td><td style="border:1px solid #000; padding:4px;">Cukup sopan</td><td style="border:1px solid #000; padding:4px;">Tidak sopan</td></tr>
        <tr><td style="border:1px solid #000; padding:4px;"><strong>Kreativitas</strong> (10%)</td><td style="border:1px solid #000; padding:4px;">Sangat kreatif &amp; orisinal</td><td style="border:1px solid #000; padding:4px;">Kreatif dan orisinal</td><td style="border:1px solid #000; padding:4px;">Cukup kreatif</td><td style="border:1px solid #000; padding:4px;">Tidak kreatif</td></tr>
    </table>

    <div style="font-size:9pt; margin-bottom:6px;"><strong>B. Kriteria Penilaian Tugas Proyek (Produk/Video)</strong></div>
    <table style="width:100%; border-collapse:collapse; font-size:7pt; margin-bottom:16px;">
        <tr>
            <th style="border:1px solid #000; padding:3px; background:#f1edfc;">Kelompok Aspek</th>
            <th style="border:1px solid #000; padding:3px; background:#f1edfc;">Sub-Aspek</th>
            <th style="border:1px solid #000; padding:3px; background:#f1edfc;">4 (Sangat Baik)</th>
            <th style="border:1px solid #000; padding:3px; background:#f1edfc;">3 (Baik)</th>
            <th style="border:1px solid #000; padding:3px; background:#f1edfc;">2 (Cukup)</th>
            <th style="border:1px solid #000; padding:3px; background:#f1edfc;">1 (Kurang)</th>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;" rowspan="3"><strong>Konten</strong> (30%)</td>
            <td style="border:1px solid #000; padding:3px;">Relevansi &amp; Keakuratan</td>
            <td style="border:1px solid #000; padding:3px;">Sangat relevan &amp; akurat</td>
            <td style="border:1px solid #000; padding:3px;">Relevan dan akurat</td>
            <td style="border:1px solid #000; padding:3px;">Cukup relevan</td>
            <td style="border:1px solid #000; padding:3px;">Tidak relevan/akurat</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;">Kedalaman Materi</td>
            <td style="border:1px solid #000; padding:3px;">Sangat mendalam</td>
            <td style="border:1px solid #000; padding:3px;">Mendalam</td>
            <td style="border:1px solid #000; padding:3px;">Cukup mendalam</td>
            <td style="border:1px solid #000; padding:3px;">Tidak mendalam</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;">Kreativitas</td>
            <td style="border:1px solid #000; padding:3px;">Sangat kreatif</td>
            <td style="border:1px solid #000; padding:3px;">Kreatif</td>
            <td style="border:1px solid #000; padding:3px;">Cukup kreatif</td>
            <td style="border:1px solid #000; padding:3px;">Tidak kreatif</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;" rowspan="2"><strong>Struktur &amp; Organisasi</strong> (20%)</td>
            <td style="border:1px solid #000; padding:3px;">Alur Cerita</td>
            <td style="border:1px solid #000; padding:3px;">Alur sangat jelas</td>
            <td style="border:1px solid #000; padding:3px;">Alur jelas</td>
            <td style="border:1px solid #000; padding:3px;">Alur cukup jelas</td>
            <td style="border:1px solid #000; padding:3px;">Alur tidak jelas</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;">Penggunaan Visual</td>
            <td style="border:1px solid #000; padding:3px;">Sangat mendukung</td>
            <td style="border:1px solid #000; padding:3px;">Mendukung narasi</td>
            <td style="border:1px solid #000; padding:3px;">Cukup mendukung</td>
            <td style="border:1px solid #000; padding:3px;">Tidak mendukung</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;" rowspan="3"><strong>Kualitas Teknis</strong> (20%)</td>
            <td style="border:1px solid #000; padding:3px;">Audio</td>
            <td style="border:1px solid #000; padding:3px;">Sangat jernih</td>
            <td style="border:1px solid #000; padding:3px;">Jernih</td>
            <td style="border:1px solid #000; padding:3px;">Cukup jernih</td>
            <td style="border:1px solid #000; padding:3px;">Tidak jernih</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;">Video</td>
            <td style="border:1px solid #000; padding:3px;">Sangat baik</td>
            <td style="border:1px solid #000; padding:3px;">Baik</td>
            <td style="border:1px solid #000; padding:3px;">Cukup baik</td>
            <td style="border:1px solid #000; padding:3px;">Tidak baik</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;">Pengeditan</td>
            <td style="border:1px solid #000; padding:3px;">Sangat rapi</td>
            <td style="border:1px solid #000; padding:3px;">Rapi</td>
            <td style="border:1px solid #000; padding:3px;">Cukup rapi</td>
            <td style="border:1px solid #000; padding:3px;">Tidak rapi</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;" rowspan="3"><strong>Penyampaian</strong> (15%)</td>
            <td style="border:1px solid #000; padding:3px;">Kejelasan Narasi</td>
            <td style="border:1px solid #000; padding:3px;">Sangat jelas</td>
            <td style="border:1px solid #000; padding:3px;">Jelas</td>
            <td style="border:1px solid #000; padding:3px;">Cukup jelas</td>
            <td style="border:1px solid #000; padding:3px;">Tidak jelas</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;">Penguasaan Materi</td>
            <td style="border:1px solid #000; padding:3px;">Sangat baik</td>
            <td style="border:1px solid #000; padding:3px;">Baik</td>
            <td style="border:1px solid #000; padding:3px;">Cukup</td>
            <td style="border:1px solid #000; padding:3px;">Kurang</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;">Penggunaan Bahasa</td>
            <td style="border:1px solid #000; padding:3px;">Sangat tepat</td>
            <td style="border:1px solid #000; padding:3px;">Tepat</td>
            <td style="border:1px solid #000; padding:3px;">Cukup tepat</td>
            <td style="border:1px solid #000; padding:3px;">Tidak tepat</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;" rowspan="2"><strong>Kreativitas &amp; Inovasi</strong> (15%)</td>
            <td style="border:1px solid #000; padding:3px;">Ide Kreatif</td>
            <td style="border:1px solid #000; padding:3px;">Sangat kreatif</td>
            <td style="border:1px solid #000; padding:3px;">Kreatif</td>
            <td style="border:1px solid #000; padding:3px;">Cukup kreatif</td>
            <td style="border:1px solid #000; padding:3px;">Tidak kreatif</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;">Inovasi Teknologi</td>
            <td style="border:1px solid #000; padding:3px;">Sangat mendukung</td>
            <td style="border:1px solid #000; padding:3px;">Mendukung</td>
            <td style="border:1px solid #000; padding:3px;">Cukup mendukung</td>
            <td style="border:1px solid #000; padding:3px;">Tidak mendukung</td>
        </tr>
    </table>

    <div style="font-size:9pt; margin-bottom:6px;"><strong>B. Komposisi Rencana Evaluasi (Acuan)</strong></div>
    <table style="width:100%; border-collapse:collapse; font-size:7.5pt;">
        <tr>
            <th style="border:1px solid #000; padding:4px; background:#f1edfc;">No</th>
            <th style="border:1px solid #000; padding:4px; background:#f1edfc;">Basis Evaluasi</th>
            <th style="border:1px solid #000; padding:4px; background:#f1edfc;">Bobot</th>
        </tr>
        <tr><td style="border:1px solid #000; padding:4px; text-align:center;">1</td><td style="border:1px solid #000; padding:4px;">Aktivitas Partisipatif</td><td style="border:1px solid #000; padding:4px; text-align:center;">50%</td></tr>
        <tr><td style="border:1px solid #000; padding:4px; text-align:center;">2</td><td style="border:1px solid #000; padding:4px;">Hasil Proyek</td><td style="border:1px solid #000; padding:4px; text-align:center;">30%</td></tr>
        <tr><td style="border:1px solid #000; padding:4px; text-align:center;">3</td><td style="border:1px solid #000; padding:4px;">Ujian Tengah Semester</td><td style="border:1px solid #000; padding:4px; text-align:center;">10%</td></tr>
        <tr><td style="border:1px solid #000; padding:4px; text-align:center;">4</td><td style="border:1px solid #000; padding:4px;">Ujian Akhir Semester</td><td style="border:1px solid #000; padding:4px; text-align:center;">10%</td></tr>
        <tr><td colspan="2" style="border:1px solid #000; padding:4px; text-align:right;"><strong>Jumlah</strong></td><td style="border:1px solid #000; padding:4px; text-align:center;"><strong>100%</strong></td></tr>
    </table>
    <div style="font-size:8pt; margin-top:10px; font-style:italic; color:#555;">Sumber: Pedoman Penilaian - LMS SPADA Kemdiktisaintek</div>
</div>

<?php
// Bangun daftar kolom TM x Komponen (sama pola dengan menu Penilaian)
$gridKomponen = [];
foreach ($d['rps_list'] as $r) {
    if (!empty($r['rencana_evaluasi_list'])) {
        foreach ($r['rencana_evaluasi_list'] as $re) {
            $gridKomponen[] = [
                'pertemuan' => (int) $r['pertemuan'],
                'label'     => $re['basis_evaluasi'],
            ];
        }
    } else {
        $gridKomponen[] = [
            'pertemuan' => (int) $r['pertemuan'],
            'label'     => 'Nilai TM',
        ];
    }
}

$gridGroups = [];
foreach ($gridKomponen as $k) {
    $gridGroups[$k['pertemuan']][] = $k['label'];
}
?>

<?php
// Bangun hirarki CPL -> CPMK -> Sub-CPMK -> TM/Komponen dari $d['rps_list']
$hierarchy = [];
foreach ($d['rps_list'] as $r) {
    $cplKey = $r['cpl_id'] ? 'cpl_' . $r['cpl_id'] : 'cpl_none';
    $cplLabel = $r['cpl_code'] ?: 'Tanpa CPL';
    $cpmkKey = $r['cpmk_id'] ? 'cpmk_' . $r['cpmk_id'] : 'cpmk_none';
    $cpmkLabel = $r['cpmk_code'] ?: '-';
    $subKey = $r['sub_cpmk_id'] ? 'sub_' . $r['sub_cpmk_id'] : 'sub_none';
    $subLabel = $r['sub_cpmk_code'] ?: '-';

    if (!isset($hierarchy[$cplKey])) {
        $hierarchy[$cplKey] = ['label' => $cplLabel, 'cpmk' => []];
    }
    if (!isset($hierarchy[$cplKey]['cpmk'][$cpmkKey])) {
        $hierarchy[$cplKey]['cpmk'][$cpmkKey] = ['label' => $cpmkLabel, 'sub' => []];
    }
    if (!isset($hierarchy[$cplKey]['cpmk'][$cpmkKey]['sub'][$subKey])) {
        $hierarchy[$cplKey]['cpmk'][$cpmkKey]['sub'][$subKey] = ['label' => $subLabel, 'items' => []];
    }

    if (!empty($r['rencana_evaluasi_list'])) {
        foreach ($r['rencana_evaluasi_list'] as $re) {
            $hierarchy[$cplKey]['cpmk'][$cpmkKey]['sub'][$subKey]['items'][] = [
                'pertemuan' => (int) $r['pertemuan'],
                'label'     => $re['basis_evaluasi'],
            ];
        }
    } else {
        $hierarchy[$cplKey]['cpmk'][$cpmkKey]['sub'][$subKey]['items'][] = [
            'pertemuan' => (int) $r['pertemuan'],
            'label'     => 'Nilai TM',
        ];
    }
}

$totalLeafColumns = 0;
foreach ($hierarchy as $cpl) {
    foreach ($cpl['cpmk'] as $cpmk) {
        foreach ($cpmk['sub'] as $sub) {
            $totalLeafColumns += count($sub['items']);
        }
    }
}
?>

<!-- ===================== HALAMAN: GRID PENILAIAN (KOSONG) ===================== -->
<?php
$estimatedWidthPx = 220 + ($totalLeafColumns * 55);
$availableWidthPx = 1000;
$gridScale = $totalLeafColumns > 0 ? min(1, $availableWidthPx / $estimatedWidthPx) : 1;
?>
<div class="rps-print-page landscape-page">
    <div class="vm-section-title" style="text-align:left; font-size:12pt;">GRID PENILAIAN</div>
    <table class="rps-aktivitas" style="zoom: <?= round($gridScale * 100) ?>%;">
        <thead>
            <tr>
                <th rowspan="4" style="width:30px;">No</th>
                <th rowspan="4" style="width:160px;">Nama Mahasiswa</th>
                <?php foreach ($hierarchy as $cpl): ?>
                    <?php
                    $cplSpan = 0;
                    foreach ($cpl['cpmk'] as $cpmk) {
                        foreach ($cpmk['sub'] as $sub) {
                            $cplSpan += count($sub['items']);
                        }
                    }
                    ?>
                    <th colspan="<?= $cplSpan ?>" style="background:#e5dcfb;"><?= htmlspecialchars($cpl['label']) ?></th>
                <?php endforeach; ?>
            </tr>
            <tr>
                <?php foreach ($hierarchy as $cpl): ?>
                    <?php foreach ($cpl['cpmk'] as $cpmk): ?>
                        <?php
                        $cpmkSpan = 0;
                        foreach ($cpmk['sub'] as $sub) {
                            $cpmkSpan += count($sub['items']);
                        }
                        ?>
                        <th colspan="<?= $cpmkSpan ?>" style="background:#f1edfc;"><?= htmlspecialchars($cpmk['label']) ?></th>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tr>
            <tr>
                <?php foreach ($hierarchy as $cpl): ?>
                    <?php foreach ($cpl['cpmk'] as $cpmk): ?>
                        <?php foreach ($cpmk['sub'] as $sub): ?>
                            <th colspan="<?= count($sub['items']) ?>" style="background:#eef4fe;"><?= htmlspecialchars($sub['label']) ?></th>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tr>
            <tr>
                <?php foreach ($hierarchy as $cpl): ?>
                    <?php foreach ($cpl['cpmk'] as $cpmk): ?>
                        <?php foreach ($cpmk['sub'] as $sub): ?>
                            <?php foreach ($sub['items'] as $item): ?>
                                <th style="width:55px;"><?= htmlspecialchars($item['label']) ?><br><span style="font-weight:400;">TM <?= $item['pertemuan'] ?></span></th>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($d['mahasiswa_list'])): ?>
                <?php foreach ($d['mahasiswa_list'] as $idx => $mhs): ?>
                <tr>
                    <td class="text-center"><?= $idx + 1 ?></td>
                    <td><?= htmlspecialchars($mhs['nama']) ?></td>
                    <?php for ($i = 0; $i < $totalLeafColumns; $i++): ?>
                        <td>&nbsp;</td>
                    <?php endfor; ?>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <?php for ($i = 1; $i <= 14; $i++): ?>
                <tr>
                    <td class="text-center"><?= $i ?></td>
                    <td>&nbsp;</td>
                    <?php for ($j = 0; $j < $totalLeafColumns; $j++): ?>
                        <td>&nbsp;</td>
                    <?php endfor; ?>
                </tr>
                <?php endfor; ?>
            <?php endif; ?>
            <?php if ($totalLeafColumns === 0): ?>
                <tr><td colspan="2" class="text-center text-muted">Belum ada data RPS untuk membentuk kolom penilaian.</td></tr>
            <?php endif; ?>
        </tbody>
</table>
</div>

<?php if (!empty($d['rencana_tugas_list'])): ?>
<?php foreach ($d['rencana_tugas_list'] as $rt): ?>
<!-- ===================== HALAMAN: LAMPIRAN RENCANA TUGAS ke-<?= $rt['tugas_ke'] ?> ===================== -->
<div class="rps-print-page">
    <div class="vm-section-title" style="text-align:left; font-size:12pt;">LAMPIRAN RENCANA TUGAS</div>
    <div class="vm-section-sub" style="text-align:left; font-size:11pt; margin-bottom:16px;">Tugas ke-<?= $rt['tugas_ke'] ?> &mdash; <?= htmlspecialchars($rt['basis_evaluasi']) ?></div>

    <table class="rps-identity">
        <colgroup>
            <col style="width:28%;"><col style="width:72%;">
        </colgroup>
        <tr><td class="green-header row-label">Mata Kuliah</td><td><?= htmlspecialchars($mk['name']) ?></td></tr>
        <tr><td class="green-header row-label">Kode Mata Kuliah</td><td><?= htmlspecialchars($mk['code'] ?? '-') ?></td></tr>
        <tr><td class="green-header row-label">Minggu Ke</td><td><?= $rt['minggu_mulai'] ?: '-' ?><?php if ($rt['minggu_selesai'] && $rt['minggu_selesai'] != $rt['minggu_mulai']): ?> s.d. Minggu <?= $rt['minggu_selesai'] ?><?php endif; ?></td></tr>
        <tr><td class="green-header row-label">Tugas Ke</td><td><?= $rt['tugas_ke'] ?></td></tr>
        <tr><td class="green-header row-label">Bentuk Tugas</td><td><?= $rt['bentuk_tugas'] ? htmlspecialchars($rt['bentuk_tugas']) : '-' ?></td></tr>
        <tr><td class="green-header row-label">Judul Tugas</td><td><strong><?= $rt['judul'] ? htmlspecialchars($rt['judul']) : '-' ?></strong></td></tr>
        <tr>
            <td class="green-header row-label">Sub-CPMK yang Dituju</td>
            <td>
                <?php if (!empty($rt['sub_cpmk_list'])): ?>
                    <?= htmlspecialchars(implode(', ', array_map(fn($s) => $s['code'], $rt['sub_cpmk_list']))) ?>
                <?php else: ?>-<?php endif; ?>
            </td>
        </tr>
        <tr><td class="green-header row-label">Deskripsi Tugas</td><td><?= $rt['deskripsi_tugas'] ? nl2br(htmlspecialchars($rt['deskripsi_tugas'])) : '-' ?></td></tr>
        <tr><td class="green-header row-label">Metode Pengerjaan</td><td><?= $rt['metode_pengerjaan'] ? nl2br(htmlspecialchars($rt['metode_pengerjaan'])) : '-' ?></td></tr>
        <tr><td class="green-header row-label">Bentuk &amp; Format Luaran</td><td><?= $rt['bentuk_luaran'] ? nl2br(htmlspecialchars($rt['bentuk_luaran'])) : '-' ?></td></tr>
        <tr>
            <td class="green-header row-label">Kriteria Penilaian / Indikator</td>
            <td>
                <?php if ($rt['kriteria_penilaian']): ?>
                    <?= nl2br(htmlspecialchars($rt['kriteria_penilaian'])) ?>
                <?php elseif (!empty($rt['indikator_list'])): ?>
                    <ul style="margin:0; padding-left:18px;">
                        <?php foreach ($rt['indikator_list'] as $ind): ?>
                            <li><?= htmlspecialchars($ind['indikator']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>-<?php endif; ?>
            </td>
        </tr>
        <tr><td class="green-header row-label">Bobot Nilai</td><td><strong><?= number_format($rt['bobot_persen'], 1) ?>%</strong></td></tr>
        <tr>
            <td class="green-header row-label">Jadwal</td>
            <td>
                Diberikan Minggu ke-<?= $rt['minggu_mulai'] ?: '-' ?>,
                dikumpulkan Minggu ke-<?= $rt['minggu_selesai'] ?: '-' ?>
            </td>
        </tr>
    </table>
</div>
<?php endforeach; ?>
<?php endif; ?>

</body>
</html>