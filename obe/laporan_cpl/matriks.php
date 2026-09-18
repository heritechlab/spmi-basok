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

$unitId = (int) ($_GET['unit_id'] ?? 0);
$periodeId = Periode::getActiveId($conn);

$repository = new LaporanCplRepository($conn);
$service    = new LaporanCplService($repository);

$result = $service->getLaporanProdi($unitId, $periodeId);

if (!$result['success']) {
    die('<div style="font-family:sans-serif; padding:40px;">' . htmlspecialchars($result['message']) . '</div>');
}

$d = $result['data'];
$mkListAll = $d['mk_list'];
$cplList = $d['cpl_list'];
$mkCplMatrix = $d['mk_cpl_matrix'];
$bobotMap = $d['bobot_map'];
$cplHasil = $d['cpl_hasil'];
$periodeLabel = $d['periode_label'] ?? '-';

if (empty($cplList)) {
    die('<div style="font-family:sans-serif; padding:40px;">Belum ada CPL untuk Program Studi ini.</div>');
}

$cplFinal = [];
foreach ($cplHasil as $c) {
    $cplFinal[$c['cpl_id']] = $c['ketercapaian'];
}

$mkGanjil = array_values(array_filter($mkListAll, fn($mk) => ((int) $mk['semester']) % 2 === 1));
$mkGenap  = array_values(array_filter($mkListAll, fn($mk) => ((int) $mk['semester']) % 2 === 0));

$institusi = $conn->query("SELECT * FROM institution_profile ORDER BY id ASC LIMIT 1")->fetch_assoc() ?: [];
$logoPath = !empty($institusi['logo']) ? BASE_URL . htmlspecialchars($institusi['logo']) : '';

$stmtUnit = $conn->prepare("SELECT name FROM units WHERE id = ?");
$stmtUnit->bind_param("i", $unitId);
$stmtUnit->execute();
$unitInfo = $stmtUnit->get_result()->fetch_assoc();

function getKategoriKetercapaian(?float $nilai): array
{
    if ($nilai === null) return ['label' => 'Belum Ada Data', 'color' => '#6b7280'];
    if ($nilai > 80) return ['label' => 'A - Sangat Baik / Istimewa', 'color' => '#059669'];
    if ($nilai >= 75) return ['label' => 'AB - Baik Sekali', 'color' => '#0d9488'];
    if ($nilai >= 70) return ['label' => 'B - Baik', 'color' => '#2563eb'];
    if ($nilai >= 65) return ['label' => 'BC - Cukup Baik', 'color' => '#d97706'];
    if ($nilai >= 56) return ['label' => 'C - Cukup', 'color' => '#ea580c'];
    if ($nilai >= 40) return ['label' => 'D - Kurang', 'color' => '#dc2626'];
    return ['label' => 'E - Gagal / Tidak Lulus', 'color' => '#991b1b'];
}

function renderBobotTable(array $mkList, array $cplList, array $bobotMap, string $judulSemester): string
{
    if (empty($mkList)) {
        return '<div class="lap-page"><div class="lap-title">Persentase Kontribusi OBE MK - ' . htmlspecialchars($judulSemester) . '</div><p class="text-muted">Tidak ada Mata Kuliah pada kelompok Semester ini.</p></div>';
    }

    $colTotal = array_fill_keys(array_column($cplList, 'id'), 0);
    $html = '<div class="lap-page">';
    $html .= '<div class="lap-title">Tabel A — Persentase Kontribusi OBE Mata Kuliah terhadap Pencapaian OBE Prodi &mdash; ' . htmlspecialchars($judulSemester) . '</div>';
    $html .= '<div class="lap-sub">Ditentukan dari Bobot Kontribusi MK-CPL (sudah dinormalisasi, total per kolom CPL = 100% dihitung dari SELURUH MK, bukan hanya kelompok semester ini)</div>';
    $html .= '<table class="lap-table"><thead><tr><th>No</th><th>Mata Kuliah</th>';
    foreach ($cplList as $cpl) { $html .= '<th>' . htmlspecialchars($cpl['code']) . '</th>'; }
    $html .= '<th class="col-final">Rata-rata</th></tr></thead><tbody>';

    foreach ($mkList as $idx => $mk) {
        $html .= '<tr><td>' . ($idx + 1) . '</td><td class="col-name">' . htmlspecialchars($mk['name']) . '</td>';
        $rowSum = 0;
        foreach ($cplList as $cpl) {
            $key = $mk['id'] . '_' . $cpl['id'];
            $val = $bobotMap[$key] ?? null;
            if ($val !== null) { $rowSum += $val; $colTotal[$cpl['id']] += $val; }
            $html .= '<td>' . ($val !== null ? number_format($val, 1) : '-') . '</td>';
        }
        $html .= '<td class="col-final">' . (count($cplList) > 0 ? number_format($rowSum / count($cplList), 1) : '-') . '</td></tr>';
    }

    $html .= '<tr class="tf"><td colspan="2">Subtotal Kelompok Ini</td>';
    $grand = 0;
    foreach ($cplList as $cpl) {
        $html .= '<td>' . number_format($colTotal[$cpl['id']], 1) . '</td>';
        $grand += $colTotal[$cpl['id']];
    }
    $html .= '<td class="col-final">' . (count($cplList) > 0 ? number_format($grand / count($cplList), 1) : '-') . '</td></tr>';
    $html .= '</tbody></table>';

    $html .= '<div class="rumus-box"><strong>Rumus Perhitungan</strong>Bobot Kontribusi MK terhadap CPL &mdash; ditentukan Ka. Prodi/GKM (Hitung Otomatis dari total Bobot RPS per CPL, dinormalisasi supaya tiap kolom CPL = 100%), boleh disesuaikan manual.</div>';

    $html .= '</div>';

    return $html;
}

function renderKetercapaianTable(array $mkList, array $cplList, array $mkCplMatrix, array $cplFinal, string $judulSemester, bool $tampilkanRataProdi): string
{
    if (empty($mkList)) {
        return '<div class="lap-page"><div class="lap-title">Ketercapaian OBE per MK - ' . htmlspecialchars($judulSemester) . '</div><p class="text-muted">Tidak ada Mata Kuliah pada kelompok Semester ini.</p></div>';
    }

    $html = '<div class="lap-page">';
    $html .= '<div class="lap-title">Tabel B — Rekapitulasi Persentase Ketercapaian OBE pada Prodi &mdash; ' . htmlspecialchars($judulSemester) . '</div>';
    $html .= '<div class="lap-sub">Nilai Ketercapaian CPL rata-rata kelas per Mata Kuliah (Tingkat 3)</div>';
    $html .= '<table class="lap-table"><thead><tr><th>No</th><th>Mata Kuliah</th>';
    foreach ($cplList as $cpl) { $html .= '<th>' . htmlspecialchars($cpl['code']) . '</th>'; }
    $html .= '<th class="col-final">Rata-rata</th></tr></thead><tbody>';

    foreach ($mkList as $idx => $mk) {
        $html .= '<tr><td>' . ($idx + 1) . '</td><td class="col-name">' . htmlspecialchars($mk['name']) . '</td>';
        $rowSum = 0; $rowCount = 0;
        foreach ($cplList as $cpl) {
            $key = $mk['id'] . '_' . $cpl['id'];
            $val = $mkCplMatrix[$key] ?? null;
            if ($val !== null) { $rowSum += $val; $rowCount++; }
            $html .= '<td>' . ($val !== null ? number_format($val, 2) : '-') . '</td>';
        }
        $html .= '<td class="col-final">' . ($rowCount > 0 ? number_format($rowSum / $rowCount, 2) : '-') . '</td></tr>';
    }

    if ($tampilkanRataProdi) {
        $html .= '<tr><td colspan="2">Rata-rata (Ketercapaian OBE Prodi &mdash; SELURUH Semester)</td>';
        $grandSum = 0; $grandCount = 0;
        foreach ($cplList as $cpl) {
            $val = $cplFinal[$cpl['id']] ?? null;
            if ($val !== null) { $grandSum += $val; $grandCount++; }
            $html .= '<td class="col-final">' . ($val !== null ? number_format($val, 2) : '-') . '</td>';
        }
        $html .= '<td class="col-final">' . ($grandCount > 0 ? number_format($grandSum / $grandCount, 2) : '-') . '</td></tr>';
    }

    $html .= '</tbody></table>';

    $html .= '<div class="rumus-box"><strong>Rumus Perhitungan</strong>Ketercapaian CPL per MK = &Sigma;(Nilai Ketercapaian CPL tiap Mahasiswa) &divide; Jumlah Mahasiswa (Tingkat 3).';
    if ($tampilkanRataProdi) {
        $html .= '<br>Ketercapaian OBE Prodi (baris bawah) = &Sigma;(Ketercapaian CPL tiap MK &times; Bobot Kontribusi MK &divide; 100), dijumlah dari seluruh MK yang punya Bobot Kontribusi terisi untuk CPL tersebut (Tingkat 4).';
    }
    $html .= '</div>';

    $html .= '</div>';

    return $html;
}

?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Ketercapaian CPL Program Studi</title>
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
    .cover-info { font-size: 10pt; margin: 0 0 30px; width: 100%; }
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
    table.lap-table { width: 100%; border-collapse: collapse; font-size: 8pt; }
    table.lap-table th, table.lap-table td { border: 1px solid #999; padding: 4px 6px; text-align: center; }
    table.lap-table thead th { background: #f1edfc; color: #5b21b6; font-weight: bold; }
    table.lap-table tbody td.col-name { text-align: left; font-weight: 600; }
    table.lap-table tr.tf td { background: #faf9fd; font-weight: bold; }
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
    <div class="cover-mk">PROGRAM STUDI <?= htmlspecialchars(strtoupper($unitInfo['name'] ?? '-')) ?></div>

    <div style="text-align:left;">
        <div class="cover-desc-title">Detail Program Studi</div>
        <table class="cover-info">
            <tr><td class="lbl">Program Studi</td><td>: <?= htmlspecialchars($unitInfo['name'] ?? '-') ?></td></tr>
            <tr><td class="lbl">Semester / Tahun Ajaran</td><td>: <?= htmlspecialchars($periodeLabel) ?></td></tr>
            <tr><td class="lbl">Jumlah Mata Kuliah</td><td>: <?= count($mkListAll) ?> MK (lintas seluruh Kurikulum yang berlaku pada Periode ini)</td></tr>
            <tr><td class="lbl">Jumlah CPL</td><td>: <?= count($cplList) ?> CPL (<?= htmlspecialchars(implode(', ', array_column($cplList, 'code'))) ?>)</td></tr>
            <tr><td class="lbl">Tanggal Cetak</td><td>: <?= date('d F Y') ?></td></tr>
        </table>

        <div class="cover-desc-title">Daftar Isi &amp; Penjelasan Tabel</div>

        <div class="cover-desc-item">
            <div class="cover-desc-num">1</div>
            <div class="cover-desc-text">
                <strong>Tabel Ringkasan — Ketercapaian CPL Program Studi (Hasil Akhir)</strong>
                Angka final Ketercapaian tiap CPL di tingkat Program Studi, hasil dari Ketercapaian CPL tiap Mata Kuliah dikalikan Bobot Kontribusinya. Inilah angka yang menjadi acuan evaluasi capaian pembelajaran lulusan.
            </div>
        </div>

        <div class="cover-desc-item">
            <div class="cover-desc-num">2</div>
            <div class="cover-desc-text">
                <strong>Tabel A — Persentase Kontribusi OBE Mata Kuliah</strong>
                Bobot Kontribusi tiap Mata Kuliah terhadap tiap CPL Prodi (total per kolom CPL = 100%). Disajikan untuk Seluruh Semester, lalu dipecah per Semester Ganjil dan Genap.
            </div>
        </div>

        <div class="cover-desc-item">
            <div class="cover-desc-num">3</div>
            <div class="cover-desc-text">
                <strong>Tabel B — Rekapitulasi Ketercapaian OBE per Mata Kuliah</strong>
                Nilai Ketercapaian CPL rata-rata kelas untuk tiap Mata Kuliah. Disajikan untuk Seluruh Semester (dengan baris Ketercapaian OBE Prodi final), lalu dipecah per Semester Ganjil dan Genap.
            </div>
        </div>
    </div>
</div>

<div class="lap-page">
    <div class="lap-title">Tabel Ringkasan — Ketercapaian CPL Program Studi (Hasil Akhir)</div>
    <div class="lap-sub"><?= htmlspecialchars($unitInfo['name'] ?? '-') ?> &mdash; <?= htmlspecialchars($periodeLabel) ?></div>

    <table class="lap-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode CPL</th>
                <th>Ketercapaian (%)</th>
                <th>Kategori</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cplHasil as $idx => $c): ?>
            <?php $kategori = getKategoriKetercapaian($c['ketercapaian']); ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= htmlspecialchars($c['cpl_code']) ?></td>
                <td class="col-final"><?= $c['ketercapaian'] !== null ? number_format($c['ketercapaian'], 2) : '-' ?></td>
                <td style="color:<?= $kategori['color'] ?>; font-weight:bold;"><?= htmlspecialchars($kategori['label']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <?php
                $validVals = array_filter(array_column($cplHasil, 'ketercapaian'), fn($v) => $v !== null);
                $rataProdi = count($validVals) > 0 ? array_sum($validVals) / count($validVals) : null;
                ?>
                <td colspan="2">Rata-Rata Ketercapaian OBE Prodi</td>
                <td class="col-final"><?= $rataProdi !== null ? number_format($rataProdi, 2) : '-' ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="rumus-box">
        <strong>Rumus Perhitungan</strong>
        Ketercapaian CPL Prodi = &Sigma;(Ketercapaian CPL tiap MK &times; Bobot Kontribusi MK &divide; 100), dijumlah dari seluruh MK yang memiliki Bobot Kontribusi terisi untuk CPL tersebut.<br>
        Klasifikasi: <strong>&gt;80 A</strong> &middot; <strong>75&ndash;79,99 AB</strong> &middot; <strong>70&ndash;74,99 B</strong> &middot; <strong>65&ndash;69,99 BC</strong> &middot; <strong>56&ndash;64,99 C</strong> &middot; <strong>40&ndash;55,99 D</strong> &middot; <strong>&lt;40 E</strong><br>
        <em>Catatan: CPL dengan hasil BC ke bawah dianggap belum memenuhi batas kelulusan minimal.</em>
    </div>
</div>

<?= renderBobotTable($mkListAll, $cplList, $bobotMap, 'Seluruh Semester') ?>
<?= renderKetercapaianTable($mkListAll, $cplList, $mkCplMatrix, $cplFinal, 'Seluruh Semester', true) ?>

<?= renderBobotTable($mkGanjil, $cplList, $bobotMap, 'Semester Ganjil') ?>
<?= renderBobotTable($mkGenap, $cplList, $bobotMap, 'Semester Genap') ?>
<?= renderKetercapaianTable($mkGanjil, $cplList, $mkCplMatrix, $cplFinal, 'Semester Ganjil', false) ?>
<?= renderKetercapaianTable($mkGenap, $cplList, $mkCplMatrix, $cplFinal, 'Semester Genap', false) ?>

</body>
</html>