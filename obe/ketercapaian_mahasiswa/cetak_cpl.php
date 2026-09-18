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

$repository = new KetercapaianMahasiswaRepository($conn);
$service = new KetercapaianMahasiswaService($repository);
$result = $service->getLaporanMahasiswa($unitId, $kurikulumId, $mahasiswaId, $periodeId);

$cplHasil = $result['success'] ? $result['data']['cpl_hasil'] : [];

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

$sumKetercapaian = 0;
$countKetercapaian = 0;
foreach ($cplHasil as $c) {
    if ($c['ketercapaian'] !== null) {
        $sumKetercapaian += $c['ketercapaian'];
        $countKetercapaian++;
    }
}
$totalRata = $countKetercapaian > 0 ? $sumKetercapaian / $countKetercapaian : null;

?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Ketercapaian CPL - <?= htmlspecialchars($mhs['nama']) ?></title>
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

    <div class="lap-title">REKAP KETERCAPAIAN CAPAIAN PEMBELAJARAN LULUSAN (CPL)</div>

    <table class="khs-info">
        <tr><td style="width:130px;">Nama Mahasiswa</td><td>: <strong><?= htmlspecialchars($mhs['nama']) ?></strong></td></tr>
        <tr><td>NIM</td><td>: <?= htmlspecialchars($mhs['nim']) ?></td></tr>
        <tr><td>Program Studi</td><td>: <?= htmlspecialchars($mhs['unit_name']) ?></td></tr>
    </table>

    <table class="lap-table">
        <thead>
            <tr>
                <th>No.</th>
                <th>Kode CPL</th>
                <th>Nilai Ketercapaian</th>
                <th>Lambang</th>
                <th>Angka Mutu</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cplHasil as $idx => $c): ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= htmlspecialchars($c['cpl_code']) ?></td>
                <td><?= $c['ketercapaian'] !== null ? number_format($c['ketercapaian'], 2) : '-' ?></td>
                <td><?= $c['ketercapaian'] !== null ? huruftMutu($c['ketercapaian']) : '-' ?></td>
                <td><?= $c['ketercapaian'] !== null ? number_format(angkaMutu($c['ketercapaian']), 2) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($cplHasil)): ?>
            <tr><td colspan="5">Belum ada data CPL untuk Program Studi ini.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">TOTAL / RATA-RATA</td>
                <td><?= $totalRata !== null ? number_format($totalRata, 2) : '-' ?></td>
                <td><?= $totalRata !== null ? huruftMutu($totalRata) : '-' ?></td>
                <td><?= $totalRata !== null ? number_format(angkaMutu($totalRata), 2) : '-' ?></td>
            </tr>
        </tfoot>
    </table>
</div>

</body>
</html>