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

$mahasiswaId = (int) ($_GET['mahasiswa_id'] ?? 0);
$unitId = (int) ($_GET['unit_id'] ?? 0);
$kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);
$periodeId = Periode::getActiveId($conn);

$stmtMhs = $conn->prepare("
    SELECT m.id, m.nim, m.nama, u.name AS unit_name
    FROM obe_mahasiswa m
    JOIN obe_kurikulum k ON k.id = m.kurikulum_id
    JOIN units u ON u.id = k.unit_id
    WHERE m.id = ? LIMIT 1
");
$stmtMhs->bind_param("i", $mahasiswaId);
$stmtMhs->execute();
$mhs = $stmtMhs->get_result()->fetch_assoc();

if (!$mhs) {
    die('<div style="font-family:sans-serif; padding:40px;">Mahasiswa tidak ditemukan.</div>');
}

$institusi = $conn->query("SELECT * FROM institution_profile ORDER BY id ASC LIMIT 1")->fetch_assoc() ?: [];
$logoPath = !empty($institusi['logo']) ? BASE_URL . htmlspecialchars($institusi['logo']) : '';

$stmtP = $conn->prepare("SELECT tahun_ajaran, jenis_semester FROM obe_periode_akademik WHERE id = ? LIMIT 1");
$stmtP->bind_param("i", $periodeId);
$stmtP->execute();
$periode = $stmtP->get_result()->fetch_assoc();

$repository = new KetercapaianMahasiswaRepository($conn);
$service = new KetercapaianMahasiswaService($repository);
$result = $service->getLaporanMahasiswa($unitId, $kurikulumId, $mahasiswaId, $periodeId);

$mkDiambil = $result['success'] ? $result['data']['mk_diambil'] : [];
$cplList = $result['success'] ? $result['data']['cpl_list'] : [];
$mkCplScore = $result['success'] ? $result['data']['mk_cpl_score'] : [];

function huruftMutu(float $nilai): string
{
    if ($nilai >= 80) return 'A';
    if ($nilai >= 75) return 'AB';
    if ($nilai >= 70) return 'B';
    if ($nilai >= 65) return 'BC';
    if ($nilai >= 56) return 'C';
    if ($nilai >= 40) return 'D';
    return 'E';
}

function angkaMutu(float $nilai): float
{
    if ($nilai >= 80) return 4.00;
    if ($nilai >= 75) return 3.50;
    if ($nilai >= 70) return 3.00;
    if ($nilai >= 65) return 2.50;
    if ($nilai >= 56) return 2.00;
    if ($nilai >= 40) return 1.00;
    return 0.00;
}

$mkDetail = [];
foreach ($mkDiambil as $mk) {
    $stmtMk = $conn->prepare("SELECT code, sks_tatap_muka, sks_praktikum, sks_praktek_lapangan, sks_simulasi FROM obe_mata_kuliah WHERE id = ? LIMIT 1");
    $stmtMk->bind_param("i", $mk['id']);
    $stmtMk->execute();
    $row = $stmtMk->get_result()->fetch_assoc() ?: [];

    $stmtDosen = $conn->prepare("
        SELECT d.name, d.gelar_depan, d.gelar_belakang
        FROM obe_mata_kuliah_dosen md
        JOIN obe_dosen d ON d.id = md.dosen_id
        WHERE md.mata_kuliah_id = ? AND md.periode_id = ? AND md.peran = 'Koordinator'
        LIMIT 1
    ");
    $stmtDosen->bind_param("ii", $mk['id'], $periodeId);
    $stmtDosen->execute();
    $dosen = $stmtDosen->get_result()->fetch_assoc();
    $dosenName = $dosen ? trim(($dosen['gelar_depan'] ?? '') . ' ' . $dosen['name'] . ($dosen['gelar_belakang'] ? ', ' . $dosen['gelar_belakang'] : '')) : '-';

    $mkDetail[] = [
        'code'  => $row['code'] ?? '-',
        'name'  => $mk['nama'],
        'sks'   => (float) ($row['sks_tatap_muka'] ?? 0) + (float) ($row['sks_praktikum'] ?? 0) + (float) ($row['sks_praktek_lapangan'] ?? 0) + (float) ($row['sks_simulasi'] ?? 0),
        'dosen' => $dosenName,
        'mk_id' => $mk['id'],
    ];
}

?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Ketercapaian CPL per MK - <?= htmlspecialchars($mhs['nama']) ?></title>
<style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #000; background: #e5e5e5; margin: 0; }
    .lap-page {
        background: #fff; width: 297mm; min-height: 210mm; margin: 16px auto; padding: 14mm;
        box-shadow: 0 0 8px rgba(0,0,0,0.15); box-sizing: border-box;
    }

    .kop-table { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
    .kop-table td { border: none; vertical-align: middle; padding: 0; }
    .kop-logo { width: 100px; text-align: center; }
    .kop-logo img { width: 88px; }
    .kop-text { text-align: center; font-family: "Times New Roman", Times, serif; line-height: 1.15; }
    .kop-text .inst-yayasan { font-size: 12pt; font-weight: bold; letter-spacing: .2px; margin-bottom: 2px; }
    .kop-text .inst-yayasan span { display: inline-block; white-space: nowrap; transform: scaleY(1.3); }
    .kop-text .inst-name { font-size: 15pt; font-weight: bold; letter-spacing: 0; margin: 2px 0; }
    .kop-text .inst-name span { display: inline-block; white-space: nowrap; transform: scaleY(1.4); }
    .kop-text .inst-addr { font-size: 8pt; font-family: Arial, Helvetica, sans-serif; margin-top: 4px; line-height: 1.4; }
    .kop-text .inst-addr .addr-line { white-space: nowrap; }
    .kop-divider { border-bottom: 3px solid #000; margin-top: 6px; margin-bottom: 2px; }
    .kop-divider-thin { border-bottom: 1px solid #000; margin-bottom: 16px; }

    .lap-title { font-weight: bold; font-size: 12pt; text-align: center; margin-bottom: 4px; }
    .khs-info { font-size: 9.5pt; margin-bottom: 16px; }
    .khs-info td { padding: 2px 6px 2px 0; border: none; }
    table.lap-table { width: 100%; border-collapse: collapse; font-size: 8pt; }
    table.lap-table th, table.lap-table td { border: 1px solid #999; padding: 4px 6px; text-align: center; }
    table.lap-table thead th { background: #f1edfc; color: #5b21b6; font-weight: bold; }
    table.lap-table tbody td.col-name { text-align: left; }
    table.lap-table tfoot td { background: #faf9fd; font-weight: bold; }
    .col-final { background: #ecfdf9 !important; font-weight: bold; color: #0d9488; }
    .no-print { position: fixed; top: 16px; right: 16px; }
    .no-print button {
        background: #7c3aed; color: #fff; border: none; border-radius: 8px; padding: 10px 18px;
        font-size: 12px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(124,58,237,0.3);
    }
    @media print { body { background: #fff; } .lap-page { box-shadow: none; margin: 0; } .no-print { display: none; } }
</style>
</head>
<body>

<div class="no-print"><button onclick="window.print()">Cetak / Simpan PDF</button></div>

<div class="lap-page">

    <table class="kop-table">
        <tr>
            <td class="kop-logo" valign="middle"><?php if ($logoPath): ?><img src="<?= $logoPath ?>" style="vertical-align:middle; display:block; margin:0 auto;"><?php endif; ?></td>
            <td class="kop-text">
                <?php if (!empty($institusi['foundation_name'])): ?>
                    <div class="inst-yayasan"><span><?= htmlspecialchars(strtoupper($institusi['foundation_name'])) ?></span></div>
                <?php endif; ?>
                <div class="inst-name"><span><?= htmlspecialchars(strtoupper($institusi['institution_name'] ?? '-')) ?></span></div>
                <div class="inst-addr">
                    <div class="addr-line">
                        <?= htmlspecialchars($institusi['address'] ?? '') ?>
                        <?= !empty($institusi['phone']) ? ' Telp. ' . htmlspecialchars($institusi['phone']) : '' ?>
                    </div>
                    <div class="addr-line">
                        <?= !empty($institusi['website']) ? 'website : ' . htmlspecialchars($institusi['website']) . '  ' : '' ?>
                        <?= !empty($institusi['email']) ? 'e-mail : ' . htmlspecialchars($institusi['email']) : '' ?>
                    </div>
                </div>
            </td>
        </tr>
    </table>
    <div class="kop-divider"></div>
    <div class="kop-divider-thin"></div>

    <div class="lap-title">KETERCAPAIAN CPL PER MATA KULIAH</div>

    <table class="khs-info">
        <tr><td style="width:130px;">Nama Mahasiswa</td><td>: <strong><?= htmlspecialchars($mhs['nama']) ?></strong></td></tr>
        <tr><td>NIM</td><td>: <?= htmlspecialchars($mhs['nim']) ?></td></tr>
        <tr><td>Program Studi</td><td>: <?= htmlspecialchars($mhs['unit_name']) ?></td></tr>
    </table>

    <table class="lap-table">
        <thead>
            <tr>
                <th>No.</th>
                <th>Tahun MK</th>
                <th>Kode MK</th>
                <th>Nama Mata Kuliah</th>
                <th>SKS</th>
                <?php foreach ($cplList as $cpl): ?>
                    <th><?= htmlspecialchars($cpl['code']) ?></th>
                <?php endforeach; ?>
                <th class="col-final">Rata-Rata CPL</th>
                <th>Angka</th>
                <th>Dosen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mkDetail as $idx => $mk): ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= $periode ? htmlspecialchars($periode['tahun_ajaran']) : '-' ?></td>
                <td><?= htmlspecialchars($mk['code']) ?></td>
                <td class="col-name"><?= htmlspecialchars($mk['name']) ?></td>
                <td><?= number_format($mk['sks'], 0) ?></td>
                <?php
                $rowSum = 0;
                $rowCount = 0;
                foreach ($cplList as $cpl):
                    $key = $mk['mk_id'] . '_' . $cpl['id'];
                    $val = $mkCplScore[$key] ?? null;
                    if ($val !== null) { $rowSum += $val; $rowCount++; }
                ?>
                    <td><?= $val !== null ? number_format($val, 1) : '-' ?></td>
                <?php endforeach; ?>
                <?php $rowRata = $rowCount > 0 ? $rowSum / $rowCount : null; ?>
                <td class="col-final"><?= $rowRata !== null ? number_format($rowRata, 2) : '-' ?></td>
                <td><?= $rowRata !== null ? number_format(angkaMutu($rowRata), 2) . ' (' . huruftMutu($rowRata) . ')' : '-' ?></td>
                <td class="col-name"><?= htmlspecialchars($mk['dosen']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($mkDetail)): ?>
            <tr><td colspan="<?= 8 + count($cplList) ?>">Belum ada Mata Kuliah dengan nilai.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>