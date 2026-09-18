<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Periode.php';
require_once __DIR__ . '/../penilaian/repository.php';
require_once __DIR__ . '/../penilaian/service.php';

$mahasiswaId = (int) ($_GET['mahasiswa_id'] ?? 0);
$periodeId = Periode::getActiveId($conn);

$stmtMhs = $conn->prepare("
    SELECT m.id, m.nim, m.nama, m.kurikulum_id, u.id AS unit_id, u.name AS unit_name
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

if (!$periode) {
    die('<div style="font-family:sans-serif; padding:40px;">Belum ada Periode Akademik aktif.</div>');
}

$semesterMod = $periode['jenis_semester'] === 'Ganjil' ? 1 : 0;

$stmtMk = $conn->prepare("
    SELECT id, code, name, semester, sks_tatap_muka, sks_praktikum, sks_praktek_lapangan, sks_simulasi
    FROM obe_mata_kuliah
    WHERE kurikulum_id = ? AND is_active = 1 AND (semester % 2) = ?
    ORDER BY semester ASC, name ASC
");
$stmtMk->bind_param("ii", $mhs['kurikulum_id'], $semesterMod);
$stmtMk->execute();
$mkList = $stmtMk->get_result()->fetch_all(MYSQLI_ASSOC);

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

$penilaianRepo = new PenilaianRepository($conn);

$rows = [];
$totalSks = 0;
$totalMutu = 0;

foreach ($mkList as $mk) {
    $komponenRaw = $penilaianRepo->getKomponenListByMataKuliah((int) $mk['id'], $periodeId);

    if (empty($komponenRaw)) {
        continue;
    }

    $nilaiRows = $penilaianRepo->getNilaiGrid((int) $mk['id'], $periodeId);
    $nilaiMap = [];
    foreach ($nilaiRows as $nr) {
        $key = $nr['rps_id'] . '_' . $nr['rencana_evaluasi_id'] . '_' . $nr['mahasiswa_id'];
        $nilaiMap[$key] = (float) $nr['nilai'];
    }

    $sumNilaiBobot = 0;
    $sumBobot = 0;
    foreach ($komponenRaw as $k) {
        $reId = $k['rencana_evaluasi_id'] !== null ? (int) $k['rencana_evaluasi_id'] : 0;
        $key = $k['rps_id'] . '_' . $reId . '_' . $mahasiswaId;
        if (!isset($nilaiMap[$key])) continue;
        $sumNilaiBobot += $nilaiMap[$key] * (float) $k['komponen_bobot'];
        $sumBobot += (float) $k['komponen_bobot'];
    }

    $nilaiAkhir = $sumBobot > 0 ? $sumNilaiBobot / $sumBobot : null;

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

    $sksMk = (float) $mk['sks_tatap_muka'] + (float) $mk['sks_praktikum'] + (float) $mk['sks_praktek_lapangan'] + (float) $mk['sks_simulasi'];

    $rows[] = [
        'code'  => $mk['code'],
        'name'  => $mk['name'],
        'sks'   => $sksMk,
        'nilai' => $nilaiAkhir,
        'dosen' => $dosenName,
    ];

    if ($nilaiAkhir !== null) {
        $totalSks += $sksMk;
        $totalMutu += $sksMk * angkaMutu($nilaiAkhir);
    }
}

$ipSemester = $totalSks > 0 ? $totalMutu / $totalSks : null;

?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>KHS - <?= htmlspecialchars($mhs['nama']) ?></title>
<style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #000; background: #e5e5e5; margin: 0; }
    .lap-page {
        background: #fff; width: 210mm; min-height: 297mm; margin: 16px auto; padding: 14mm;
        box-shadow: 0 0 8px rgba(0,0,0,0.15); box-sizing: border-box;
    }

    .kop-table { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
    .kop-table td { border: none; vertical-align: middle; padding: 0; }
    .kop-logo { width: 100px; text-align: center; }
    .kop-logo img { width: 88px; }
    .kop-text { text-align: center; font-family: "Times New Roman", Times, serif; line-height: 1.15; }
    .kop-text .inst-yayasan { font-size: 12pt; font-weight: bold; letter-spacing: .2px; margin-bottom: 2px; white-space: nowrap; display: inline-block; transform: scaleY(1.3); }
    .kop-text .inst-name { font-size: 15pt; font-weight: bold; letter-spacing: 0; margin: 2px 0; white-space: nowrap; display: inline-block; transform: scaleY(1.4); }
    .kop-text .inst-addr { font-size: 8pt; font-family: Arial, Helvetica, sans-serif; margin-top: 4px; line-height: 1.4; }
    .kop-text .inst-addr .addr-line { white-space: nowrap; }
    .kop-divider { border-bottom: 3px solid #000; margin-top: 6px; margin-bottom: 2px; }
    .kop-divider-thin { border-bottom: 1px solid #000; margin-bottom: 16px; }

    .lap-title { font-weight: bold; font-size: 12pt; text-align: center; margin-bottom: 4px; }
    .khs-info { font-size: 9.5pt; margin-bottom: 16px; }
    .khs-info td { padding: 2px 6px 2px 0; border: none; }
    table.lap-table { width: 100%; border-collapse: collapse; font-size: 9pt; }
    table.lap-table th, table.lap-table td { border: 1px solid #999; padding: 5px 7px; text-align: center; }
    table.lap-table thead th { background: #f1edfc; color: #5b21b6; font-weight: bold; }
    table.lap-table tbody td.col-name { text-align: left; }
    table.lap-table tfoot td { background: #faf9fd; font-weight: bold; }
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

    <div style="display:grid; grid-template-columns:100px 1fr; align-items:center; gap:14px; margin-bottom:2px;">
        <div style="text-align:center;"><?php if ($logoPath): ?><img src="<?= $logoPath ?>" style="width:88px;"><?php endif; ?></div>
        <div class="kop-text">
            <?php if (!empty($institusi['foundation_name'])): ?>
                <div class="inst-yayasan"><?= htmlspecialchars(strtoupper($institusi['foundation_name'])) ?></div>
            <?php endif; ?>
            <div class="inst-name"><?= htmlspecialchars(strtoupper($institusi['institution_name'] ?? '-')) ?></div>
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
        </div>
    </div>
    <div class="kop-divider"></div>
    <div class="kop-divider-thin"></div>

    <div class="lap-title">KARTU HASIL STUDI (KHS)</div>
    <div class="lap-sub" style="text-align:center; color:#6b6785; margin-bottom:16px;"><?= htmlspecialchars($periode['jenis_semester']) ?> <?= htmlspecialchars($periode['tahun_ajaran']) ?></div>

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
                <th>Nilai</th>
                <th>Lambang</th>
                <th>Angka</th>
                <th>Dosen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $idx => $r): ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= htmlspecialchars($periode['tahun_ajaran']) ?></td>
                <td><?= htmlspecialchars($r['code'] ?: '-') ?></td>
                <td class="col-name"><?= htmlspecialchars($r['name']) ?></td>
                <td><?= number_format($r['sks'], 0) ?></td>
                <td><?= $r['nilai'] !== null ? number_format($r['nilai'], 2) : '-' ?></td>
                <td><?= $r['nilai'] !== null ? huruftMutu($r['nilai']) : '-' ?></td>
                <td><?= $r['nilai'] !== null ? number_format(angkaMutu($r['nilai']), 2) : '-' ?></td>
                <td class="col-name"><?= htmlspecialchars($r['dosen']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
            <tr><td colspan="9">Belum ada Mata Kuliah dengan nilai pada Periode ini.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total / Indeks Prestasi Semester (IPS)</td>
                <td><?= number_format($totalSks, 0) ?></td>
                <td colspan="3"></td>
                <td><?= $ipSemester !== null ? number_format($ipSemester, 2) : '-' ?></td>
            </tr>
        </tfoot>
    </table>
</div>

</body>
</html>