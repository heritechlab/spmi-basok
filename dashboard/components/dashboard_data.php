<?php

/* ===========================
   DASHBOARD DATA
=========================== */

/* Standar */

$totalStandar = 0;

$q = mysqli_query($conn,"SELECT COUNT(*) total FROM standards WHERE is_active = 1");

if($q){

    $totalStandar = mysqli_fetch_assoc($q)['total'];

}

/* Audit */

$totalAudit = 0;

$q = mysqli_query($conn,"SELECT COUNT(*) total FROM audit_periods");

if($q){

    $totalAudit = mysqli_fetch_assoc($q)['total'];

}

/* Temuan */

$totalTemuan = 0;

$q = mysqli_query($conn,"SELECT COUNT(*) total FROM audit_checklist_results WHERE audit_status IS NOT NULL AND audit_status <> ''");

if($q){

    $totalTemuan = mysqli_fetch_assoc($q)['total'];

}

/* RTL */

$totalRTL = 0;

$q = mysqli_query($conn,"SELECT COUNT(*) total FROM rtm_action_plans");

if($q){

    $totalRTL = mysqli_fetch_assoc($q)['total'];

}

/* Indikator */

$totalIndikator = 0;

$q = mysqli_query($conn,"SELECT COUNT(*) total FROM audit_indicators WHERE status = 1");

if($q){

    $totalIndikator = mysqli_fetch_assoc($q)['total'];

}

/* Penugasan Audit */

$totalPenugasan = 0;

$q = mysqli_query($conn,"SELECT COUNT(*) total FROM audit_assignments");

if($q){

    $totalPenugasan = mysqli_fetch_assoc($q)['total'];

}

$roleLabels = [
    1 => 'Administrator',
    2 => 'Ketua LPM',
    3 => 'Auditor',
    4 => 'Auditee',
    5 => 'Pimpinan',
];

$nama = $_SESSION['full_name'] ?? "Pengguna";

$role = $roleLabels[(int)($_SESSION['role_id'] ?? 0)] ?? "Pengguna";

$jam = date("H");

if($jam < 11){

$salam="Selamat Pagi";

}elseif($jam <15){

$salam="Selamat Siang";

}elseif($jam<18){

$salam="Selamat Sore";

}else{

$salam="Selamat Malam";

}

/* ==========================================
   STATUS AUDIT
========================================== */

$auditDraft = 0;
$auditBerjalan = 0;
$auditSelesai = 0;

/* Draft */
$q = mysqli_query(
    $conn,
    "SELECT COUNT(*) total
     FROM audit_periods
     WHERE status='Draft'"
);

if($q){
    $auditDraft = mysqli_fetch_assoc($q)['total'];
}

/* Aktif (Berjalan) */
$q = mysqli_query(
    $conn,
    "SELECT COUNT(*) total
     FROM audit_periods
     WHERE status='Aktif'"
);

if($q){
    $auditBerjalan = mysqli_fetch_assoc($q)['total'];
}

/* Ditutup (Selesai) */
$q = mysqli_query(
    $conn,
    "SELECT COUNT(*) total
     FROM audit_periods
     WHERE status='Ditutup'"
);

if($q){
    $auditSelesai = mysqli_fetch_assoc($q)['total'];
}

/* Daftar lengkap Periode Audit (untuk rincian Widget Status Audit) */
$auditPeriodList = [];

$qPeriodList = mysqli_query($conn, "SELECT id, period_name, status FROM audit_periods ORDER BY id DESC");

if ($qPeriodList) {
    while ($row = mysqli_fetch_assoc($qPeriodList)) {
        $auditPeriodList[] = $row;
    }
}

$auditCompletionPercent = $totalAudit > 0 ? round(($auditSelesai / $totalAudit) * 100) : 0;

/* ==========================================
   SKOR CAPAIAN PER STANDAR (untuk Spiderweb)
========================================== */

/* Deteksi Periode Aktif (dipakai bersama beberapa Widget) */

$mutuPeriodId = null;
$mutuPeriodName = null;

$qPeriodAktif = mysqli_query($conn, "SELECT id, period_name FROM audit_periods WHERE status = 'Aktif' ORDER BY id DESC LIMIT 1");

if ($qPeriodAktif && mysqli_num_rows($qPeriodAktif) > 0) {
    $rowPeriod = mysqli_fetch_assoc($qPeriodAktif);
    $mutuPeriodId = (int) $rowPeriod['id'];
    $mutuPeriodName = $rowPeriod['period_name'];
} else {
    $qPeriodFallback = mysqli_query($conn, "SELECT id, period_name FROM audit_periods ORDER BY id DESC LIMIT 1");
    if ($qPeriodFallback && mysqli_num_rows($qPeriodFallback) > 0) {
        $rowPeriod = mysqli_fetch_assoc($qPeriodFallback);
        $mutuPeriodId = (int) $rowPeriod['id'];
        $mutuPeriodName = $rowPeriod['period_name'];
    }
}

$standardScores = [];
$standardScoresLemah = [];

if ($mutuPeriodId) {

    $qStd = mysqli_query($conn, "
        SELECT
            s.name AS standard_name,
            COUNT(*) AS total,
            SUM(CASE WHEN r.audit_status = 'Menyimpang' THEN 1 ELSE 0 END) AS tidak_terpenuhi,
            SUM(CASE WHEN r.audit_status = 'Belum Mencapai' THEN 1 ELSE 0 END) AS sebagian,
            SUM(CASE WHEN r.audit_status = 'Mencapai' THEN 1 ELSE 0 END) AS memenuhi,
            SUM(CASE WHEN r.audit_status = 'Melampaui' THEN 1 ELSE 0 END) AS melampaui
        FROM audit_checklist_results r
        JOIN audit_checklists c ON c.id = r.checklist_id
        JOIN audit_assignments a ON a.id = c.assignment_id
        JOIN standards s ON s.id = c.standard_id
        WHERE a.period_id = $mutuPeriodId
          AND r.audit_status IS NOT NULL AND r.audit_status <> ''
        GROUP BY s.id, s.code
        ORDER BY s.code ASC
    ");

    if ($qStd) {
        while ($row = mysqli_fetch_assoc($qStd)) {

            $total = (int) $row['total'];

            $skor = $total > 0
                ? round((
                    (int) $row['tidak_terpenuhi'] * 1
                    + (int) $row['sebagian'] * 2
                    + (int) $row['memenuhi'] * 3
                    + (int) $row['melampaui'] * 4
                ) / $total, 2)
                : null;

            $standardScores[] = [
                'label' => $row['standard_name'],
                'score' => $skor,
            ];

            if ($skor !== null && $skor < 2) {
                $standardScoresLemah[] = ['label' => $row['standard_name'], 'skor' => $skor];
            }
        }
    }

    usort($standardScoresLemah, fn($a, $b) => $a['skor'] <=> $b['skor']);
}

/* ==========================================
   TREN SKOR CAPAIAN & TEMUAN PER PERIODE
========================================== */

$trendLabels = [];
$trendScores = [];
$trendFindings = [];

$qTrend = mysqli_query($conn, "
    SELECT
        p.id, p.period_name,
        COUNT(r.id) AS total_hasil,
        SUM(CASE WHEN r.audit_status = 'Menyimpang' THEN 1 ELSE 0 END) AS tidak_terpenuhi,
            SUM(CASE WHEN r.audit_status = 'Belum Mencapai' THEN 1 ELSE 0 END) AS sebagian,
            SUM(CASE WHEN r.audit_status = 'Mencapai' THEN 1 ELSE 0 END) AS memenuhi,
        SUM(CASE WHEN r.audit_status = 'Melampaui' THEN 1 ELSE 0 END) AS melampaui
    FROM audit_periods p
    LEFT JOIN audit_assignments a ON a.period_id = p.id
    LEFT JOIN audit_checklists c ON c.assignment_id = a.id
    LEFT JOIN audit_checklist_results r ON r.checklist_id = c.id AND r.audit_status IS NOT NULL AND r.audit_status <> ''
    GROUP BY p.id, p.period_name
    ORDER BY p.id DESC
    LIMIT 3
");

$trendRows = [];

if ($qTrend) {
    while ($row = mysqli_fetch_assoc($qTrend)) {
        $trendRows[] = $row;
    }
}

$trendRows = array_reverse($trendRows);

foreach ($trendRows as $row) {

    $total = (int) $row['total_hasil'];

    $skor = $total > 0
        ? round((
            (int) $row['tidak_terpenuhi'] * 1
            + (int) $row['sebagian'] * 2
            + (int) $row['memenuhi'] * 3
            + (int) $row['melampaui'] * 4
        ) / $total, 2)
        : null;

    $jumlahTemuan = (int) $row['tidak_terpenuhi'] + (int) $row['sebagian'];

    $trendLabels[] = $row['period_name'];
    $trendScores[] = $skor;
    $trendFindings[] = $jumlahTemuan;
}
/* ==========================================
   STATUS CAPAIAN PER JENIS IKU (untuk Grafik Batang)
========================================== */

$ikuStatusLabels = ['IKU Wajib', 'IKU Pilihan', 'IKU PT', 'IKT'];
$ikuStatusData = [
    'Menyimpang'     => [0, 0, 0, 0],
    'Belum Mencapai' => [0, 0, 0, 0],
    'Mencapai'       => [0, 0, 0, 0],
    'Melampaui'      => [0, 0, 0, 0],
];

$qIkuStatus = mysqli_query($conn, "
    SELECT
        ai.indicator_type,
        SUM(CASE WHEN r.audit_status = 'Menyimpang' THEN 1 ELSE 0 END) AS menyimpang,
        SUM(CASE WHEN r.audit_status = 'Belum Mencapai' THEN 1 ELSE 0 END) AS belum_mencapai,
        SUM(CASE WHEN r.audit_status = 'Mencapai' THEN 1 ELSE 0 END) AS mencapai,
        SUM(CASE WHEN r.audit_status = 'Melampaui' THEN 1 ELSE 0 END) AS melampaui
    FROM audit_checklist_results r
    JOIN audit_checklists c ON c.id = r.checklist_id
    JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
    WHERE r.audit_status IS NOT NULL AND r.audit_status <> ''
    GROUP BY ai.indicator_type
");

if ($qIkuStatus) {
    while ($row = mysqli_fetch_assoc($qIkuStatus)) {

        $index = array_search($row['indicator_type'], $ikuStatusLabels, true);

        if ($index === false) {
            continue;
        }

        $ikuStatusData['Menyimpang'][$index]     = (int) $row['menyimpang'];
        $ikuStatusData['Belum Mencapai'][$index] = (int) $row['belum_mencapai'];
        $ikuStatusData['Mencapai'][$index]       = (int) $row['mencapai'];
        $ikuStatusData['Melampaui'][$index]      = (int) $row['melampaui'];
    }
}

require_once __DIR__ . '/mutu_data.php';

/* ==========================================
   WIDGET: CAPAIAN IKU DIKTI 4 TRIWULAN
========================================== */

$mutuTahunAktif = null;

if ($mutuPeriodName && preg_match('/(\d{4})/', $mutuPeriodName, $mTahun)) {
    $mutuTahunAktif = (int) $mTahun[1];
}

$ikuComparisonList = [];

if ($mutuTahunAktif) {

    $qIkuInd = mysqli_query($conn, "
        SELECT id, code, name, satuan, direction
        FROM iku_indicators
        WHERE kategori='wajib' AND is_active=1 AND is_selected=1
        ORDER BY sort_order ASC
    ");

    $ikuIndList = [];
    if ($qIkuInd) {
        while ($row = mysqli_fetch_assoc($qIkuInd)) {
            $ikuIndList[] = $row;
        }
    }

    foreach ($ikuIndList as $ind) {

        $indId = (int) $ind['id'];

        $qTarget = mysqli_query($conn, "SELECT target FROM iku_targets WHERE indicator_id = $indId AND tahun = $mutuTahunAktif LIMIT 1");
        $targetVal = ($qTarget && mysqli_num_rows($qTarget) > 0) ? mysqli_fetch_assoc($qTarget)['target'] : null;

        $realisasiVal = null;
        $realisasiTw = null;

        foreach (['TW4', 'TW3', 'TW2', 'TW1'] as $tw) {

            $qReal = mysqli_query($conn, "
                SELECT realisasi FROM iku_realizations
                WHERE indicator_id = $indId AND tahun = $mutuTahunAktif AND triwulan = '$tw'
                  AND realisasi IS NOT NULL AND realisasi != ''
                LIMIT 1
            ");

            if ($qReal && mysqli_num_rows($qReal) > 0) {
                $realisasiVal = mysqli_fetch_assoc($qReal)['realisasi'];
                $realisasiTw = $tw;
                break;
            }
        }

        $tercapai = null;

        if ($targetVal !== null && $targetVal !== '' && $realisasiVal !== null && $realisasiVal !== '') {

            $targetNormalized = str_replace(',', '.', $targetVal);
            $realisasiNormalized = str_replace(',', '.', $realisasiVal);

            if (is_numeric($targetNormalized) && is_numeric($realisasiNormalized)) {

                $targetNum = (float) $targetNormalized;
                $realisasiNum = (float) $realisasiNormalized;

                $tercapai = ($ind['direction'] === 'rendah')
                    ? ($realisasiNum <= $targetNum)
                    : ($realisasiNum >= $targetNum);

            } else {
                $tercapai = (trim($targetVal) === trim($realisasiVal));
            }
        }

        $ikuComparisonList[] = [
            'code' => $ind['code'],
            'name' => $ind['name'],
            'satuan' => $ind['satuan'],
            'target' => $targetVal,
            'realisasi' => $realisasiVal,
            'realisasi_tw' => $realisasiTw,
            'tercapai' => $tercapai,
        ];
    }
}