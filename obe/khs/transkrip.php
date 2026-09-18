<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../penilaian/repository.php';
require_once __DIR__ . '/../penilaian/service.php';

$mahasiswaId = (int) ($_GET['mahasiswa_id'] ?? 0);

$stmtMhs = $conn->prepare("
    SELECT m.id, m.nim, m.nama, m.angkatan, m.kurikulum_id, u.id AS unit_id, u.name AS unit_name
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

$periodeList = $conn->query("SELECT id FROM obe_periode_akademik ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$periodeIds = array_column($periodeList, 'id');

$stmtMk = $conn->prepare("
    SELECT id, code, name, semester, sks_tatap_muka, sks_praktikum, sks_praktek_lapangan, sks_simulasi
    FROM obe_mata_kuliah
    WHERE kurikulum_id = ? AND is_active = 1
    ORDER BY semester ASC, name ASC
");
$stmtMk->bind_param("i", $mhs['kurikulum_id']);
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

function romawi(int $angka): string
{
    $map = [1=>'I',2=>'II',3=>'III',4=>'IV',5=>'V',6=>'VI',7=>'VII',8=>'VIII',9=>'IX',10=>'X',11=>'XI',12=>'XII',13=>'XIII',14=>'XIV'];
    return $map[$angka] ?? (string) $angka;
}

$penilaianRepo = new PenilaianRepository($conn);

$bySemester = [];
$totalSks = 0;
$totalMutu = 0;

foreach ($mkList as $mk) {

    $nilaiAkhir = null;

    foreach ($periodeIds as $pid) {
        $komponenRaw = $penilaianRepo->getKomponenListByMataKuliah((int) $mk['id'], (int) $pid);
        if (empty($komponenRaw)) {
            continue;
        }

        $nilaiRows = $penilaianRepo->getNilaiGrid((int) $mk['id'], (int) $pid);
        $nilaiMap = [];
        foreach ($nilaiRows as $nr) {
            $key = $nr['rps_id'] . '_' . $nr['rencana_evaluasi_id'] . '_' . $nr['mahasiswa_id'];
            $nilaiMap[$key] = (float) $nr['nilai'];
        }

        $sumNilaiBobot = 0;
        $sumBobot = 0;
        foreach ($komponenRaw as $k) {
            $key = $k['rps_id'] . '_' . $k['rencana_evaluasi_id'] . '_' . $mahasiswaId;
            if (!isset($nilaiMap[$key])) continue;
            $sumNilaiBobot += $nilaiMap[$key] * (float) $k['komponen_bobot'];
            $sumBobot += (float) $k['komponen_bobot'];
        }

        if ($sumBobot > 0) {
            $nilaiAkhir = $sumNilaiBobot / $sumBobot;
            break;
        }
    }

    if ($nilaiAkhir === null) {
        continue;
    }

    $sksMk = (float) $mk['sks_tatap_muka'] + (float) $mk['sks_praktikum'] + (float) $mk['sks_praktek_lapangan'] + (float) $mk['sks_simulasi'];
    $semester = (int) $mk['semester'];

    if (!isset($bySemester[$semester])) {
        $bySemester[$semester] = [];
    }

    $bySemester[$semester][] = [
        'code'  => $mk['code'],
        'name'  => $mk['name'],
        'sks'   => $sksMk,
        'nilai' => $nilaiAkhir,
    ];

    $totalSks += $sksMk;
    $totalMutu += $sksMk * angkaMutu($nilaiAkhir);
}

ksort($bySemester);

$ipk = $totalSks > 0 ? $totalMutu / $totalSks : null;

?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Transkrip Nilai - <?= htmlspecialchars($mhs['nama']) ?></title>
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

    .lap-title { font-weight: bold; font-size: 13pt; text-align: center; margin-bottom: 2px; }
    .lap-sub { font-size: 13pt; font-weight: bold; text-align: center; margin-bottom: 4px; }

    .khs-info { font-size: 10pt; margin: 18px 0 16px; }
    .khs-info td { padding: 2px 10px 2px 0; border: none; vertical-align: top; }
    .khs-info .lbl { width: 190px; font-weight: bold; text-transform: uppercase; }
    .khs-info .sep { width: 15px; }

    table.lap-table { width: 100%; border-collapse: collapse; font-size: 9pt; }
    table.lap-table th, table.lap-table td { border: 1px solid #000; padding: 5px 7px; text-align: center; }
    table.lap-table thead th { font-weight: bold; }
    table.lap-table tbody td.col-name { text-align: left; }
    table.lap-table td.col-semester { font-weight: bold; }
    table.lap-table tr.sep-row td { border: none; padding: 4px 0; }
    table.lap-table tfoot td { font-weight: bold; }

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
            </td>
        </tr>
    </table>

    <div class="kop-divider"></div>
    <div class="kop-divider-thin"></div>

    <div class="lap-title">TRANSKRIP NILAI</div>
    <div class="lap-sub">PROGRAM STUDI <?= htmlspecialchars(strtoupper($mhs['unit_name'])) ?></div>
    <div class="lap-sub">PROGRAM PENDIDIKAN S1 (S1)</div>

    <table class="khs-info">
        <tr><td class="lbl">NAMA MAHASISWA</td><td class="sep">:</td><td><strong><?= htmlspecialchars(strtoupper($mhs['nama'])) ?></strong></td></tr>
        <tr><td class="lbl">NPM</td><td class="sep">:</td><td><?= htmlspecialchars($mhs['nim']) ?></td></tr>
        <tr><td class="lbl">TAHUN MASUK</td><td class="sep">:</td><td><?= htmlspecialchars((string) $mhs['angkatan']) ?></td></tr>
    </table>

    <table class="lap-table">
        <thead>
            <tr>
                <th style="width:70px;">SEMESTER</th>
                <th style="width:110px;">KODE MK</th>
                <th>MATA KULIAH</th>
                <th style="width:50px;">SKS<br>(K)</th>
                <th style="width:50px;">NILAI<br>(N)</th>
                <th style="width:55px;">K.N</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($bySemester)): ?>
            <tr><td colspan="6">Belum ada Mata Kuliah dengan nilai.</td></tr>
            <?php endif; ?>
            <?php $semesterIdx = 0; $totalSemesterGroup = count($bySemester); ?>
            <?php foreach ($bySemester as $semester => $rows): ?>
            <?php $semesterIdx++; ?>
            <?php foreach ($rows as $i => $r): ?>
            <tr>
                <?php if ($i === 0): ?>
                <td class="col-semester" rowspan="<?= count($rows) ?>"><?= romawi($semester) ?>.</td>
                <?php endif; ?>
                <td><?= htmlspecialchars($r['code'] ?: '-') ?></td>
                <td class="col-name"><?= htmlspecialchars(strtoupper($r['name'])) ?></td>
                <td><?= number_format($r['sks'], 0) ?></td>
                <td><?= huruftMutu($r['nilai']) ?></td>
                <td><?= number_format($r['sks'] * angkaMutu($r['nilai']), 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if ($semesterIdx < $totalSemesterGroup): ?>
            <tr class="sep-row"><td colspan="6">&nbsp;</td></tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">TOTAL / IPK</td>
                <td><?= number_format($totalSks, 0) ?></td>
                <td></td>
                <td><?= $ipk !== null ? number_format($ipk, 2) : '-' ?></td>
            </tr>
        </tfoot>
    </table>
</div>

</body>
</html>