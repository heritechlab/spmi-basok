<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';

if (!Auth::canManage()) {
    die('<div style="padding:40px; font-family:sans-serif;">Anda tidak memiliki akses ke halaman ini.</div>');
}

$assignmentId = (int) ($_GET['assignment_id'] ?? 0);

$stmtA = $conn->prepare("
    SELECT a.*, u.name AS unit_name, u.code AS unit_code,
           lu.full_name AS lead_auditor_name,
           co.full_name AS coordinator_name
    FROM audit_assignments a
    JOIN units u ON u.id = a.auditee_id
    LEFT JOIN users lu ON lu.id = a.lead_auditor
    LEFT JOIN users co ON co.id = a.coordinator
    WHERE a.id = ?
    LIMIT 1
");
$stmtA->bind_param("i", $assignmentId);
$stmtA->execute();
$assignment = $stmtA->get_result()->fetch_assoc();

if (!$assignment) {
    die('<div style="padding:40px; font-family:sans-serif;">Penugasan Audit tidak ditemukan.</div>');
}

$stmtF = $conn->prepare("
    SELECT r.*, ai.item_code, ai.indicator, ai.statement, ai.target,
           s.name AS standard_name, s.code AS standard_code,
           c.id AS checklist_id
    FROM audit_checklists c
    JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
    JOIN standards s ON s.id = c.standard_id
    LEFT JOIN audit_checklist_results r ON r.checklist_id = c.id
    WHERE c.assignment_id = ?
    ORDER BY s.name ASC, ai.item_code ASC
");
$stmtF->bind_param("i", $assignmentId);
$stmtF->execute();
$allChecklist = $stmtF->get_result()->fetch_all(MYSQLI_ASSOC);

function klasifikasiTemuan(?array $row): ?string
{
    if (!$row || empty($row['audit_status'])) return null;

    if ((int) ($row['is_temuan_risiko_tinggi'] ?? 0) === 1) {
        return 'Temuan Risiko Tinggi';
    }

    return match ($row['audit_status']) {
        'Menyimpang' => 'Ketidaksesuaian Mayor',
        'Belum Mencapai' => 'Ketidaksesuaian Minor',
        'Melampaui' => 'Observasi / OFI',
        default => null,
    };
}

$temuanTerklasifikasi = ['Ketidaksesuaian Mayor' => [], 'Ketidaksesuaian Minor' => [], 'Observasi / OFI' => [], 'Temuan Risiko Tinggi' => []];
$checklistIds = [];

foreach ($allChecklist as $row) {
    $kategori = klasifikasiTemuan($row);
    if ($kategori === null) continue;

    $temuanTerklasifikasi[$kategori][] = $row;
    $checklistIds[] = (int) $row['checklist_id'];
}

$riskMap = [];
if (!empty($checklistIds)) {
    $placeholders = implode(',', array_fill(0, count($checklistIds), '?'));
    $stmtR = $conn->prepare("
        SELECT sumber_id, likelihood, impact, level_risiko, deskripsi_dampak
        FROM risk_register
        WHERE sumber_jenis = 'temuan_audit' AND sumber_id IN ($placeholders)
    ");
    $types = str_repeat('i', count($checklistIds));
    $stmtR->bind_param($types, ...$checklistIds);
    $stmtR->execute();
    foreach ($stmtR->get_result()->fetch_all(MYSQLI_ASSOC) as $rr) {
        $riskMap[(int) $rr['sumber_id']] = $rr;
    }
}

$stmtRingkasan = $conn->prepare("
    SELECT
        SUM(CASE WHEN level_risiko = 'Ekstrem' THEN 1 ELSE 0 END) AS ekstrem,
        SUM(CASE WHEN level_risiko = 'Tinggi' THEN 1 ELSE 0 END) AS tinggi,
        SUM(CASE WHEN level_risiko = 'Sedang' THEN 1 ELSE 0 END) AS sedang,
        SUM(CASE WHEN level_risiko = 'Rendah' THEN 1 ELSE 0 END) AS rendah
    FROM risk_register
    WHERE unit_id = ? AND status IN ('Teranalisis','Termitigasi')
");
$stmtRingkasan->bind_param("i", $assignment['auditee_id']);
$stmtRingkasan->execute();
$ringkasanRisiko = $stmtRingkasan->get_result()->fetch_assoc();

$institusi = $conn->query("SELECT * FROM institution_profile ORDER BY id ASC LIMIT 1")->fetch_assoc() ?: [];
$logoPath = !empty($institusi['logo']) ? BASE_URL . htmlspecialchars($institusi['logo']) : '';

$kepalaLpm = $conn->query("SELECT full_name FROM users WHERE role_id = 2 LIMIT 1")->fetch_assoc();

?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Audit Mutu Internal Berbasis Risiko</title>
<style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #14112b; background: #e5e5e5; margin: 0; }
    .lap-page { background: #fff; width: 210mm; min-height: 297mm; margin: 16px auto; padding: 18mm; box-shadow: 0 0 8px rgba(0,0,0,0.15); box-sizing: border-box; }

    .kop-table { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
    .kop-table td { border: none; vertical-align: middle; padding: 0; }
    .kop-logo { width: 100px; text-align: center; }
    .kop-logo img { width: 78px; }
    .kop-text { text-align: center; font-family: "Times New Roman", Times, serif; line-height: 1.15; }
    .kop-text .inst-name { font-size: 14pt; font-weight: bold; }
    .kop-divider { border-bottom: 3px solid #000; margin-top: 6px; margin-bottom: 2px; }
    .kop-divider-thin { border-bottom: 1px solid #000; margin-bottom: 18px; }

    .lap-title { text-align: center; font-size: 15pt; font-weight: bold; margin: 6px 0 2px; }
    .lap-sub { text-align: center; font-size: 10pt; color: #6b6785; margin-bottom: 18px; }

    table.info-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 9.5pt; }
    table.info-table td { padding: 3px 6px; vertical-align: top; }
    table.info-table td.lbl { width: 160px; font-weight: bold; }

    h3.sect { font-size: 11pt; font-weight: bold; color: #5b21b6; border-bottom: 2px solid #e5deF5; padding-bottom: 4px; margin: 20px 0 10px; }

    .risk-summary { display: flex; gap: 10px; margin-bottom: 16px; }
    .risk-box { flex: 1; text-align: center; border-radius: 8px; padding: 10px; color: #fff; font-weight: bold; }
    .risk-box .num { font-size: 20pt; }
    .risk-box .lbl { font-size: 8.5pt; }

    table.temuan-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 14px; }
    table.temuan-table th, table.temuan-table td { border: 1px solid #999; padding: 5px 7px; text-align: left; vertical-align: top; }
    table.temuan-table thead th { background: #f1edfc; color: #5b21b6; text-align: center; }
    .kategori-header { background: #14112b; color: #fff; font-weight: bold; padding: 6px 10px; font-size: 9.5pt; margin-top: 14px; border-radius: 4px 4px 0 0; }

    .ttd-wrap { display: flex; justify-content: space-between; margin-top: 40px; }
    .ttd-box { text-align: center; width: 45%; font-size: 9.5pt; }
    .ttd-space { height: 70px; }

    .no-print { position: fixed; top: 16px; right: 16px; }
    .no-print button { background: #7c3aed; color: #fff; border: none; border-radius: 8px; padding: 10px 18px; font-size: 12px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(124,58,237,0.3); }
    @media print { body { background: #fff; } .lap-page { box-shadow: none; margin: 0; } .no-print { display: none; } }
</style>
</head>
<body>

<div class="no-print"><button onclick="window.print()">Cetak / Simpan PDF</button></div>

<div class="lap-page">

    <table class="kop-table">
        <tr>
            <td class="kop-logo"><?php if ($logoPath): ?><img src="<?= $logoPath ?>"><?php endif; ?></td>
            <td class="kop-text">
                <div class="inst-name"><?= htmlspecialchars(strtoupper($institusi['institution_name'] ?? '-')) ?></div>
                <div style="font-size:8pt;"><?= htmlspecialchars($institusi['address'] ?? '') ?></div>
            </td>
        </tr>
    </table>
    <div class="kop-divider"></div>
    <div class="kop-divider-thin"></div>

    <div class="lap-title">LAPORAN AUDIT MUTU INTERNAL BERBASIS RISIKO</div>
    <div class="lap-sub">Nomor: <?= htmlspecialchars($assignment['assignment_number']) ?></div>

    <table class="info-table">
        <tr><td class="lbl">Unit Auditee</td><td>: <?= htmlspecialchars($assignment['unit_code'] . ' - ' . $assignment['unit_name']) ?></td></tr>
        <tr><td class="lbl">Ruang Lingkup Audit</td><td>: <?= nl2br(htmlspecialchars($assignment['scope'] ?: '-')) ?></td></tr>
        <tr><td class="lbl">Daftar Auditor</td><td>: Ketua Tim: <?= htmlspecialchars($assignment['lead_auditor_name'] ?: '-') ?><?= $assignment['coordinator_name'] ? ' &middot; Koordinator: ' . htmlspecialchars($assignment['coordinator_name']) : '' ?></td></tr>
        <tr><td class="lbl">Metodologi Audit</td><td>: <?= htmlspecialchars($assignment['audit_type'] ?: '-') ?> &mdash; <?= htmlspecialchars($assignment['audit_method'] ?: '-') ?></td></tr>
        <tr><td class="lbl">Tanggal Pelaksanaan</td><td>: <?= $assignment['audit_date'] ? date('d F Y', strtotime($assignment['audit_date'])) : '-' ?></td></tr>
        <tr><td class="lbl">Tanggal Laporan</td><td>: <?= date('d F Y') ?></td></tr>
    </table>

    <h3 class="sect">Ringkasan Tingkat Risiko Unit</h3>
    <div class="risk-summary">
        <div class="risk-box" style="background:#dc2626;"><div class="num"><?= (int) ($ringkasanRisiko['ekstrem'] ?? 0) ?></div><div class="lbl">EKSTREM</div></div>
        <div class="risk-box" style="background:#f97316;"><div class="num"><?= (int) ($ringkasanRisiko['tinggi'] ?? 0) ?></div><div class="lbl">TINGGI</div></div>
        <div class="risk-box" style="background:#eab308;"><div class="num"><?= (int) ($ringkasanRisiko['sedang'] ?? 0) ?></div><div class="lbl">SEDANG</div></div>
        <div class="risk-box" style="background:#22c55e;"><div class="num"><?= (int) ($ringkasanRisiko['rendah'] ?? 0) ?></div><div class="lbl">RENDAH</div></div>
    </div>

    <h3 class="sect">Daftar Temuan Audit</h3>

    <?php foreach ($temuanTerklasifikasi as $kategori => $items): ?>
        <?php if (empty($items)) continue; ?>
        <div class="kategori-header"><?= htmlspecialchars($kategori) ?> (<?= count($items) ?>)</div>
        <table class="temuan-table">
            <thead>
                <tr>
                    <th width="80">Kode</th>
                    <th>Standar / Indikator</th>
                    <th>Akar Masalah (Root Cause)</th>
                    <th width="90">Tingkat Risiko</th>
                    <th>Rekomendasi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                <?php $risk = $riskMap[(int) $it['checklist_id']] ?? null; ?>
                <tr>
                    <td><?= htmlspecialchars($it['item_code']) ?></td>
                    <td><?= htmlspecialchars($it['standard_name']) ?><br><span style="color:#6b6785;"><?= htmlspecialchars($it['indicator'] ?: $it['statement']) ?></span></td>
                    <td><?= nl2br(htmlspecialchars($it['root_cause'] ?: '-')) ?></td>
                    <td>
                        <?php if ($risk): ?>
                            <strong><?= htmlspecialchars($risk['level_risiko'] ?? '-') ?></strong><br>
                            <span style="color:#6b6785;">L<?= (int) $risk['likelihood'] ?> &times; I<?= (int) $risk['impact'] ?></span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= nl2br(htmlspecialchars($it['recommendation'] ?: ($it['rekomendasi_mitigasi_segera'] ?: '-'))) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>

    <?php if (array_sum(array_map('count', $temuanTerklasifikasi)) === 0): ?>
        <p style="color:#6b6785;">Tidak ada temuan yang dikategorikan pada penugasan audit ini.</p>
    <?php endif; ?>

    <h3 class="sect">Kesimpulan Audit</h3>
    <p><?= nl2br(htmlspecialchars($assignment['closing_summary'] ?: 'Belum diisi.')) ?></p>

    <h3 class="sect">Kewajiban Tindak Lanjut Auditee</h3>
    <p>Auditee wajib menyusun Rencana Perbaikan (Corrective Action Plan) untuk seluruh temuan Ketidaksesuaian Mayor, Minor, dan Temuan Risiko Tinggi paling lambat 14 (empat belas) hari kerja sejak laporan ini diterbitkan, sesuai Pasal 34 Peraturan Rektor tentang Audit Mutu Internal Berbasis Risiko.</p>

    <div class="ttd-wrap">
        <div class="ttd-box">
            <div>Ketua Tim Auditor,</div>
            <div class="ttd-space"></div>
            <div><strong><?= htmlspecialchars($assignment['lead_auditor_name'] ?: '(...........................)') ?></strong></div>
        </div>
        <div class="ttd-box">
            <div>Mengesahkan,<br>Kepala Lembaga Penjaminan Mutu</div>
            <div class="ttd-space"></div>
            <div><strong><?= htmlspecialchars($kepalaLpm['full_name'] ?? '(...........................)') ?></strong></div>
        </div>
    </div>

</div>

</body>
</html>