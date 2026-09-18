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
$kurikulumId  = (int) ($_GET['kurikulum_id'] ?? 0);
$periodeId    = Periode::getActiveId($conn);

$repository = new PenilaianRepository($conn);
$service    = new PenilaianService($repository);

$gridResult = $service->getGridData($mataKuliahId, $kurikulumId, $periodeId);

if (!$gridResult['success']) {
    die('<div style="font-family:sans-serif; padding:40px;">Data tidak ditemukan.</div>');
}

$komponenList  = $gridResult['data']['komponen_list'];
$mahasiswaList = $gridResult['data']['mahasiswa_list'];
$nilaiMap      = $gridResult['data']['nilai_map'];

$stmtMk = $conn->prepare("SELECT name, code FROM obe_mata_kuliah WHERE id = ?");
$stmtMk->bind_param("i", $mataKuliahId);
$stmtMk->execute();
$mk = $stmtMk->get_result()->fetch_assoc() ?: ['name' => '-', 'code' => '-'];

$institusi = $conn->query("SELECT * FROM institution_profile ORDER BY id ASC LIMIT 1")->fetch_assoc() ?: [];
$logoPath = !empty($institusi['logo']) ? BASE_URL . htmlspecialchars($institusi['logo']) : '';

$stmtPeriode = $conn->prepare("SELECT tahun_ajaran, jenis_semester FROM obe_periode_akademik WHERE id = ? LIMIT 1");
$stmtPeriode->bind_param("i", $periodeId);
$stmtPeriode->execute();
$periodeInfo = $stmtPeriode->get_result()->fetch_assoc();

$stmtJadwal = $conn->prepare("SELECT pertemuan, dosen_pengampu FROM obe_jadwal_dosen WHERE mata_kuliah_id = ? AND periode_id = ?");

$stmtJadwal = $conn->prepare("SELECT pertemuan, dosen_pengampu FROM obe_jadwal_dosen WHERE mata_kuliah_id = ? AND periode_id = ?");
$stmtJadwal->bind_param("ii", $mataKuliahId, $periodeId);
$stmtJadwal->execute();
$dosenPerTm = [];
foreach ($stmtJadwal->get_result()->fetch_all(MYSQLI_ASSOC) as $j) {
    $dosenPerTm[(int) $j['pertemuan']] = $j['dosen_pengampu'];
}

function komponenNilai(array $k, array $nilaiMap, int $mahasiswaId): ?float
{
    $key = $k['rps_id'] . '_' . $k['rencana_evaluasi_id'] . '_' . $mahasiswaId;
    return isset($nilaiMap[$key]) ? (float) $nilaiMap[$key] : null;
}

function hitungNilaiAkhir(array $komponenList, array $nilaiMap, int $mahasiswaId): ?float
{
    $sumNilaiBobot = 0;
    $sumBobot = 0;

    foreach ($komponenList as $k) {
        $nilai = komponenNilai($k, $nilaiMap, $mahasiswaId);
        if ($nilai === null) continue;
        $sumNilaiBobot += $nilai * $k['bobot'];
        $sumBobot += $k['bobot'];
    }

    return $sumBobot > 0 ? $sumNilaiBobot / $sumBobot : null;
}

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

$komponenByCpl = [];
foreach ($komponenList as $k) {
    $cplKey = $k['cpl_code'] ?: 'Tanpa CPL';
    if (!isset($komponenByCpl[$cplKey])) {
        $komponenByCpl[$cplKey] = [];
    }
    $komponenByCpl[$cplKey][] = $k;
}

uasort($komponenByCpl, function ($a, $b) {
    $sumA = array_sum(array_column($a, 'bobot'));
    $sumB = array_sum(array_column($b, 'bobot'));
    return $sumB <=> $sumA;
});

$komponenPivotFlat = [];
foreach ($komponenByCpl as $group) {
    foreach ($group as $k) {
        $komponenPivotFlat[] = $k;
    }
}

?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Detail Penilaian - <?= htmlspecialchars($mk['name']) ?></title>
<style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #14112b; background: #e5e5e5; margin: 0; }
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

    .cover-title { text-align: center; font-size: 20pt; font-weight: bold; margin: 40px 0 4px; }
    .cover-mk { text-align: center; font-size: 15pt; font-weight: bold; color: #5b21b6; margin-bottom: 40px; }
    .cover-info { font-size: 10pt; margin: 0; width: 100%; }
    .cover-info td { padding: 4px 10px 4px 0; border: none; vertical-align: top; }
    .cover-info .lbl { width: 170px; font-weight: bold; }
    .cover-desc-title { font-size: 11pt; font-weight: bold; color: #5b21b6; margin-bottom: 10px; border-bottom: 2px solid #ddd0f7; padding-bottom: 6px; }
    .cover-desc-item { display: flex; gap: 12px; margin-bottom: 16px; }
    .cover-desc-num {
        width: 28px; height: 28px; border-radius: 50%; background: #7c3aed; color: #fff;
        display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12pt; flex-shrink: 0;
    }
    .cover-desc-text { font-size: 9.5pt; }
    .cover-desc-text strong { display: block; font-size: 10pt; color: #14112b; margin-bottom: 2px; }

    .lap-title { font-weight: bold; font-size: 12pt; margin-bottom: 4px; }
    .lap-sub { font-size: 9pt; color: #6b6785; margin-bottom: 14px; }
    table.lap-table { width: 100%; border-collapse: collapse; font-size: 7.5pt; }
    table.lap-table th, table.lap-table td { border: 1px solid #999; padding: 4px 5px; text-align: center; }
    table.lap-table thead th { background: #f1edfc; color: #5b21b6; font-weight: bold; }
    table.lap-table tbody td.col-name { text-align: left; font-weight: 600; }
    table.lap-table tfoot td { background: #faf9fd; font-weight: bold; }
    .col-final { background: #ecfdf9 !important; font-weight: bold; color: #0d9488; }
    .rumus-box {
        background: #f6f3fc; border-left: 3px solid #7c3aed; border-radius: 8px;
        padding: 8px 12px; margin-top: 10px; font-size: 8.5pt; color: #4c1d95;
    }
    .rumus-box strong { display: block; margin-bottom: 3px; font-size: 8pt; text-transform: uppercase; letter-spacing: .3px; color: #5b21b6; }
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

    <div class="cover-title">LAPORAN CAPAIAN PEMBELAJARAN LULUSAN (CPL)</div>
    <div class="cover-mk">MK <?= htmlspecialchars(strtoupper($mk['name'])) ?></div>

    <div style="text-align:left;">
        <div class="cover-desc-title">Detail Mata Kuliah</div>
        <table class="cover-info" style="width:100%; margin:0 0 30px;">
            <tr><td class="lbl">Kode Mata Kuliah</td><td>: <?= htmlspecialchars($mk['code'] ?: '-') ?></td></tr>
            <tr><td class="lbl">Nama Mata Kuliah</td><td>: <?= htmlspecialchars($mk['name']) ?></td></tr>
            <tr><td class="lbl">Semester / Tahun Ajaran</td><td>: <?= $periodeInfo ? htmlspecialchars($periodeInfo['jenis_semester'] . ' ' . $periodeInfo['tahun_ajaran']) : '-' ?></td></tr>
            <tr><td class="lbl">Jumlah Mahasiswa</td><td>: <?= count($mahasiswaList) ?> orang</td></tr>
            <tr><td class="lbl">Tanggal Cetak</td><td>: <?= date('d F Y') ?></td></tr>
        </table>

        <div class="cover-desc-title">Daftar Isi &amp; Penjelasan Tabel</div>

        <div class="cover-desc-item">
            <div class="cover-desc-num">1</div>
            <div class="cover-desc-text">
                <strong>Tabel 1 — Hasil Penilaian per Mahasiswa</strong>
                Rincian nilai tiap Mahasiswa per Tatap Muka, dikelompokkan per CPL, lengkap dengan Nilai Ketercapaian CPL dan Nilai Akhir MK (Huruf/Angka Mutu). Disajikan 1 halaman per Mahasiswa.
            </div>
        </div>

        <div class="cover-desc-item">
            <div class="cover-desc-num">2</div>
            <div class="cover-desc-text">
                <strong>Tabel 2 — Rekap Nilai Tiap Tatap Muka, UTS, dan UAS</strong>
                Rekap nilai mentah seluruh Mahasiswa untuk setiap komponen penilaian (TM/UTS/UAS), disusun kronologis sesuai urutan Minggu perkuliahan, beserta Nilai Akhir MK.
            </div>
        </div>

        <div class="cover-desc-item">
            <div class="cover-desc-num">3</div>
            <div class="cover-desc-text">
                <strong>Tabel 3 — Ketercapaian OBE menurut CPL (Pivot)</strong>
                Kolom penilaian dikelompokkan ulang berdasar CPL (bukan urutan Minggu), dilengkapi baris "% Ketercapaian OBE per CPL" — angka inilah yang menjadi dasar Laporan Ketercapaian CPL Program Studi.
            </div>
        </div>

        <div class="cover-desc-item">
            <div class="cover-desc-num">4</div>
            <div class="cover-desc-text">
                <strong>Tabel 4 — Rekap Ketercapaian Mata Kuliah</strong>
                Ringkasan akhir seluruh Mahasiswa dalam 1 tabel: Ketercapaian tiap CPL, Nilai Rata-Rata, Nilai Akhir MK, Lambang, dan Angka Mutu.
            </div>
        </div>
    </div>
</div>

<?php foreach ($mahasiswaList as $m): ?>
<div class="lap-page">
    <div class="lap-title">Tabel 1 — Hasil Penilaian Mahasiswa: <?= htmlspecialchars($m['nama']) ?> (NIM <?= htmlspecialchars($m['nim']) ?>)</div>
    <div class="lap-sub"><?= htmlspecialchars($mk['code']) ?> - <?= htmlspecialchars($mk['name']) ?></div>

    <table class="lap-table">
        <thead>
            <tr>
                <th>TM</th>
                <th>Sub-CPMK</th>
                <th>Bentuk</th>
                <th>Kontribusi (%)</th>
                <?php foreach ($komponenByCpl as $cplLabel => $group): ?>
                    <th><?= htmlspecialchars($cplLabel) ?></th>
                <?php endforeach; ?>
                <th>Nilai</th>
                <th>Nilai Sesuai Proporsi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($komponenList as $k): ?>
            <?php $nilai = komponenNilai($k, $nilaiMap, (int) $m['id']); ?>
            <tr>
                <td>TM-<?= $k['pertemuan'] ?><?= in_array($k['label'], ['UTS', 'UAS'], true) ? ' (' . $k['label'] . ')' : '' ?></td>
                <td><?= htmlspecialchars($k['sub_cpmk_code'] ?: '-') ?></td>
                <td><?= htmlspecialchars($k['label']) ?></td>
                <td><?= number_format($k['bobot'], 1) ?>%</td>
                <?php foreach ($komponenByCpl as $cplLabel => $group): ?>
                    <?php $milikCpl = ($k['cpl_code'] ?: 'Tanpa CPL') === $cplLabel; ?>
                    <td><?= $milikCpl && $nilai !== null ? number_format($nilai, 0) : '-' ?></td>
                <?php endforeach; ?>
                <td><?= $nilai !== null ? number_format($nilai, 0) : '-' ?></td>
                <td><?= $nilai !== null ? number_format($nilai * $k['bobot'] / 100, 2) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Nilai Ketercapaian CPL:</td>
                <?php
                foreach ($komponenByCpl as $cplLabel => $group):
                    $sumNilaiBobot = 0; $sumBobot = 0;
                    foreach ($group as $k) {
                        $nilai = komponenNilai($k, $nilaiMap, (int) $m['id']);
                        if ($nilai === null) continue;
                        $sumNilaiBobot += $nilai * $k['bobot'];
                        $sumBobot += $k['bobot'];
                    }
                    $ketercapaianCpl = $sumBobot > 0 ? $sumNilaiBobot / $sumBobot : null;
                ?>
                    <td class="col-final"><?= $ketercapaianCpl !== null ? number_format($ketercapaianCpl, 2) : '-' ?></td>
                <?php endforeach; ?>
                <td colspan="2">
                    <?php
                    $nilaiAkhirMk = hitungNilaiAkhir($komponenList, $nilaiMap, (int) $m['id']);
                    echo $nilaiAkhirMk !== null
                        ? number_format($nilaiAkhirMk, 2) . ' (' . huruftMutu($nilaiAkhirMk) . ' / ' . number_format(angkaMutu($nilaiAkhirMk), 2) . ')'
                        : '-';
                    ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="rumus-box">
        <strong>Rumus Perhitungan</strong>
        Nilai Sesuai Proporsi = Nilai &times; Bobot Komponen &divide; 100<br>
        Nilai Ketercapaian CPL (per CPL) = &Sigma;(Nilai Komponen milik CPL tsb &times; Bobotnya) &divide; &Sigma;(Bobot Komponen milik CPL tsb)<br>
        Nilai Akhir MK = &Sigma;(Nilai Komponen &times; Bobot Komponen) &divide; &Sigma;(Bobot Komponen) &mdash; Huruf/Angka Mutu mengikuti skala institusi (A=4,00 &hellip; E=0,00)
    </div>
</div>
<?php endforeach; ?>

<div class="lap-page">
    <div class="lap-title">Tabel 2 — Rekap Nilai Tiap Tatap Muka, UTS, dan UAS</div>
    <div class="lap-sub"><?= htmlspecialchars($mk['code']) ?> - <?= htmlspecialchars($mk['name']) ?></div>

    <table class="lap-table">
        <thead>
            <tr>
                <th rowspan="4">No</th>
                <th rowspan="4">NIM</th>
                <th rowspan="4">Nama</th>
                <?php foreach ($komponenList as $k): ?>
                    <th><?= isset($dosenPerTm[$k['pertemuan']]) ? htmlspecialchars($dosenPerTm[$k['pertemuan']]) : '-' ?></th>
                <?php endforeach; ?>
                <th rowspan="4" class="col-final">Nilai<br>Akhir MK</th>
            </tr>
            <tr>
                <?php foreach ($komponenList as $k): ?>
                    <th><?= $k['cpl_code'] ? htmlspecialchars($k['cpl_code']) : '-' ?></th>
                <?php endforeach; ?>
            </tr>
            <tr>
                <?php foreach ($komponenList as $k): ?>
                    <th>TM-<?= $k['pertemuan'] ?><?= in_array($k['label'], ['UTS', 'UAS'], true) ? ' (' . $k['label'] . ')' : '' ?></th>
                <?php endforeach; ?>
            </tr>
            <tr>
                <?php foreach ($komponenList as $k): ?>
                    <th><?= number_format($k['bobot'], 1) ?>%</th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mahasiswaList as $idx => $m): ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= htmlspecialchars($m['nim']) ?></td>
                <td class="col-name"><?= htmlspecialchars($m['nama']) ?></td>
                <?php foreach ($komponenList as $k): ?>
                    <?php $nilai = komponenNilai($k, $nilaiMap, (int) $m['id']); ?>
                    <td><?= $nilai !== null ? number_format($nilai, 0) : '-' ?></td>
                <?php endforeach; ?>
                <?php $akhir = hitungNilaiAkhir($komponenList, $nilaiMap, (int) $m['id']); ?>
                <td class="col-final"><?= $akhir !== null ? number_format($akhir, 1) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($mahasiswaList)): ?>
            <tr><td colspan="<?= 4 + count($komponenList) ?>">Belum ada Mahasiswa.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Rata-Rata</td>
                <?php foreach ($komponenList as $k): ?>
                    <?php
                    $sum = 0; $count = 0;
                    foreach ($mahasiswaList as $m) {
                        $nilai = komponenNilai($k, $nilaiMap, (int) $m['id']);
                        if ($nilai !== null) { $sum += $nilai; $count++; }
                    }
                    $rata = $count > 0 ? $sum / $count : null;
                    ?>
                    <td><?= $rata !== null ? number_format($rata, 1) : '-' ?></td>
                <?php endforeach; ?>
                <td class="col-final">
                    <?php
                    $sumAkhir = 0; $countAkhir = 0;
                    foreach ($mahasiswaList as $m) {
                        $akhir = hitungNilaiAkhir($komponenList, $nilaiMap, (int) $m['id']);
                        if ($akhir !== null) { $sumAkhir += $akhir; $countAkhir++; }
                    }
                    echo $countAkhir > 0 ? number_format($sumAkhir / $countAkhir, 1) : '-';
                    ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="rumus-box">
        <strong>Rumus Perhitungan</strong>
        Kolom tiap TM = Nilai mentah yang diisi dosen di Grid Penilaian (0&ndash;100)<br>
        Nilai Akhir MK = &Sigma;(Nilai tiap TM &times; Bobot TM tsb) &divide; &Sigma;(Bobot seluruh TM) &mdash; identik dengan rumus di Tabel 1<br>
        Baris "Rata-Rata" = rata-rata sederhana (aritmetik) dari seluruh Mahasiswa untuk tiap kolom
    </div>
</div>

<div class="lap-page">
    <div class="lap-title">Tabel 3 — Ketercapaian OBE menurut CPL (Pivot)</div>
    <div class="lap-sub"><?= htmlspecialchars($mk['code']) ?> - <?= htmlspecialchars($mk['name']) ?> &mdash; Kolom dikelompokkan ulang berdasar CPL, bukan urutan Minggu</div>

    <table class="lap-table">
        <thead>
            <tr>
                <th rowspan="3">No</th>
                <th rowspan="3">NIM</th>
                <th rowspan="3">Nama</th>
                <?php foreach ($komponenByCpl as $cplLabel => $group): ?>
                    <th colspan="<?= count($group) ?>"><?= htmlspecialchars($cplLabel) ?></th>
                <?php endforeach; ?>
            </tr>
            <tr>
                <?php foreach ($komponenPivotFlat as $k): ?>
                    <th>TM-<?= $k['pertemuan'] ?><?= in_array($k['label'], ['UTS', 'UAS'], true) ? ' (' . $k['label'] . ')' : '' ?></th>
                <?php endforeach; ?>
            </tr>
            <tr>
                <?php foreach ($komponenPivotFlat as $k): ?>
                    <th><?= number_format($k['bobot'], 1) ?>%</th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mahasiswaList as $idx => $m): ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= htmlspecialchars($m['nim']) ?></td>
                <td class="col-name"><?= htmlspecialchars($m['nama']) ?></td>
                <?php foreach ($komponenPivotFlat as $k): ?>
                    <?php $nilai = komponenNilai($k, $nilaiMap, (int) $m['id']); ?>
                    <td><?= $nilai !== null ? number_format($nilai, 0) : '-' ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($mahasiswaList)): ?>
            <tr><td colspan="<?= 3 + count($komponenPivotFlat) ?>">Belum ada Mahasiswa.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Rata-Rata Nilai</td>
                <?php foreach ($komponenPivotFlat as $k): ?>
                    <?php
                    $sum = 0; $count = 0;
                    foreach ($mahasiswaList as $m) {
                        $nilai = komponenNilai($k, $nilaiMap, (int) $m['id']);
                        if ($nilai !== null) { $sum += $nilai; $count++; }
                    }
                    $rata = $count > 0 ? $sum / $count : null;
                    ?>
                    <td><?= $rata !== null ? number_format($rata, 1) : '-' ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td colspan="3">% Ketercapaian OBE per CPL</td>
                <?php foreach ($komponenByCpl as $cplLabel => $group): ?>
                    <?php
                    $sumKetercapaian = 0; $countMhs = 0;
                    foreach ($mahasiswaList as $m) {
                        $sumNilaiBobot = 0; $sumBobot = 0;
                        foreach ($group as $k) {
                            $nilai = komponenNilai($k, $nilaiMap, (int) $m['id']);
                            if ($nilai === null) continue;
                            $sumNilaiBobot += $nilai * $k['bobot'];
                            $sumBobot += $k['bobot'];
                        }
                        if ($sumBobot > 0) {
                            $sumKetercapaian += $sumNilaiBobot / $sumBobot;
                            $countMhs++;
                        }
                    }
                    $persenCpl = $countMhs > 0 ? $sumKetercapaian / $countMhs : null;
                    ?>
                    <td colspan="<?= count($group) ?>" class="col-final"><?= $persenCpl !== null ? number_format($persenCpl, 1) : '-' ?></td>
                <?php endforeach; ?>
            </tr>
        </tfoot>
    </table>

    <div class="rumus-box">
        <strong>Rumus Perhitungan</strong>
        Ketercapaian CPL (per Mahasiswa) = &Sigma;(Nilai Komponen milik CPL tsb &times; Bobotnya) &divide; &Sigma;(Bobot Komponen milik CPL tsb)<br>
        % Ketercapaian OBE per CPL (baris bawah) = &Sigma;(Ketercapaian CPL tiap Mahasiswa) &divide; Jumlah Mahasiswa &mdash; inilah angka yang dipakai di Tabel 4 dan Laporan CPL Prodi
    </div>
</div>

<div class="lap-page">
    <div class="lap-title">Tabel 4 — Rekap Ketercapaian Mata Kuliah</div>
    <div class="lap-sub"><?= htmlspecialchars($mk['code']) ?> - <?= htmlspecialchars($mk['name']) ?> &mdash; Ringkasan Ketercapaian CPL seluruh Mahasiswa</div>

    <table class="lap-table">
        <thead>
            <tr>
                <th>No</th>
                <th>NIM</th>
                <th>Nama Mahasiswa</th>
                <?php foreach ($komponenByCpl as $cplLabel => $group): ?>
                    <th><?= htmlspecialchars($cplLabel) ?></th>
                <?php endforeach; ?>
                <th class="col-final">Nilai Rata-Rata</th>
                <th class="col-final">Nilai Akhir MK</th>
                <th class="col-final">Lambang</th>
                <th class="col-final">Angka Mutu</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $cplTotals = array_fill_keys(array_keys($komponenByCpl), ['sum' => 0, 'count' => 0]);
            $overallSum = 0; $overallCount = 0;
            ?>
            <?php foreach ($mahasiswaList as $idx => $m): ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= htmlspecialchars($m['nim']) ?></td>
                <td class="col-name"><?= htmlspecialchars($m['nama']) ?></td>
                <?php
                $ketercapaianPerCpl = [];
                foreach ($komponenByCpl as $cplLabel => $group):
                    $sumNilaiBobot = 0; $sumBobot = 0;
                    foreach ($group as $k) {
                        $nilai = komponenNilai($k, $nilaiMap, (int) $m['id']);
                        if ($nilai === null) continue;
                        $sumNilaiBobot += $nilai * $k['bobot'];
                        $sumBobot += $k['bobot'];
                    }
                    $ketercapaian = $sumBobot > 0 ? $sumNilaiBobot / $sumBobot : null;
                    $ketercapaianPerCpl[$cplLabel] = $ketercapaian;
                    if ($ketercapaian !== null) {
                        $cplTotals[$cplLabel]['sum'] += $ketercapaian;
                        $cplTotals[$cplLabel]['count']++;
                    }
                ?>
                    <td><?= $ketercapaian !== null ? number_format($ketercapaian, 2) : '-' ?></td>
                <?php endforeach; ?>
                <?php
                $validValues = array_filter($ketercapaianPerCpl, fn($v) => $v !== null);
                $rataMhs = count($validValues) > 0 ? array_sum($validValues) / count($validValues) : null;
                if ($rataMhs !== null) { $overallSum += $rataMhs; $overallCount++; }
                ?>
                <td class="col-final"><?= $rataMhs !== null ? number_format($rataMhs, 2) : '-' ?></td>
                <?php $nilaiAkhirMk4 = hitungNilaiAkhir($komponenList, $nilaiMap, (int) $m['id']); ?>
                <td class="col-final"><?= $nilaiAkhirMk4 !== null ? number_format($nilaiAkhirMk4, 2) : '-' ?></td>
                <td class="col-final"><?= $nilaiAkhirMk4 !== null ? huruftMutu($nilaiAkhirMk4) : '-' ?></td>
                <td class="col-final"><?= $nilaiAkhirMk4 !== null ? number_format(angkaMutu($nilaiAkhirMk4), 2) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($mahasiswaList)): ?>
            <tr><td colspan="<?= 7 + count($komponenByCpl) ?>">Belum ada Mahasiswa.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Rata-Rata OBE</td>
                <?php foreach ($komponenByCpl as $cplLabel => $group): ?>
                    <?php
                    $t = $cplTotals[$cplLabel];
                    $rataCpl = $t['count'] > 0 ? $t['sum'] / $t['count'] : null;
                    ?>
                    <td class="col-final"><?= $rataCpl !== null ? number_format($rataCpl, 2) : '-' ?></td>
                <?php endforeach; ?>
                <td class="col-final"><?= $overallCount > 0 ? number_format($overallSum / $overallCount, 2) : '-' ?></td>
                <td class="col-final">-</td>
                <td class="col-final">-</td>
                <td class="col-final">-</td>
            </tr>
        </tfoot>
    </table>

    <div class="rumus-box">
        <strong>Rumus Perhitungan</strong>
        Nilai Rata-Rata (per Mahasiswa) = rata-rata sederhana dari seluruh kolom Ketercapaian CPL mahasiswa tsb (bukan tertimbang bobot antar-CPL)<br>
        Rata-Rata OBE (baris bawah, per kolom CPL) = &Sigma;(Ketercapaian CPL seluruh Mahasiswa) &divide; Jumlah Mahasiswa &mdash; identik dengan baris "% Ketercapaian OBE per CPL" di Tabel 3
    </div>
</div>

</body>
</html>