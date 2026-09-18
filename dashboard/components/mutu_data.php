<?php

/* ==========================================
   WIDGET 1 & 2: KONDISI MUTU INSTITUSI
========================================== */

function siquaGetKategoriMutu(?float $skor): string
{
    if ($skor === null) return '-';
    if ($skor < 2) return 'Sangat Kurang Baik';
    if ($skor < 3) return 'Kurang Baik';
    if ($skor < 4) return 'Baik';
    return 'Sangat Baik';
}

function siquaGetWarnaMutu(?float $skor): string
{
    if ($skor === null) return '#9ca3af';
    if ($skor < 2) return '#ef4444';
    if ($skor < 3) return '#f59e0b';
    if ($skor < 4) return '#84cc16';
    return '#22c55e';
}

$mutuSkorInstitusi = null;
$mutuDistribusiProdi = ['Sangat Baik' => 0, 'Baik' => 0, 'Kurang Baik' => 0, 'Sangat Kurang Baik' => 0];
$mutuProdiTerendah = [];

if ($mutuPeriodId) {

    /* Skor Capaian Mutu Institusi (rata-rata seluruh Standar, gabungan semua Prodi) */

    $qSkorInstitusi = mysqli_query($conn, "
        SELECT
            SUM(CASE WHEN r.audit_status = 'Menyimpang' THEN 1 ELSE 0 END) AS tidak_terpenuhi,
            SUM(CASE WHEN r.audit_status = 'Belum Mencapai' THEN 1 ELSE 0 END) AS sebagian,
            SUM(CASE WHEN r.audit_status = 'Mencapai' THEN 1 ELSE 0 END) AS memenuhi,
            SUM(CASE WHEN r.audit_status = 'Melampaui' THEN 1 ELSE 0 END) AS melampaui,
            COUNT(*) AS total
        FROM audit_checklist_results r
        JOIN audit_checklists c ON c.id = r.checklist_id
        JOIN audit_assignments a ON a.id = c.assignment_id
        WHERE a.period_id = $mutuPeriodId
          AND r.audit_status IS NOT NULL AND r.audit_status <> ''
    ");

    if ($qSkorInstitusi) {
        $row = mysqli_fetch_assoc($qSkorInstitusi);
        $total = (int) $row['total'];

        if ($total > 0) {
            $mutuSkorInstitusi = round((
                (int) $row['tidak_terpenuhi'] * 1
                + (int) $row['sebagian'] * 2
                + (int) $row['memenuhi'] * 3
                + (int) $row['melampaui'] * 4
            ) / $total, 2);
        }
    }

    /* Distribusi Status per Program Studi */

    $qProdiScores = mysqli_query($conn, "
        SELECT
            u.id AS unit_id, u.name AS unit_name,
            SUM(CASE WHEN r.audit_status = 'Menyimpang' THEN 1 ELSE 0 END) AS tidak_terpenuhi,
            SUM(CASE WHEN r.audit_status = 'Belum Mencapai' THEN 1 ELSE 0 END) AS sebagian,
            SUM(CASE WHEN r.audit_status = 'Mencapai' THEN 1 ELSE 0 END) AS memenuhi,
            SUM(CASE WHEN r.audit_status = 'Melampaui' THEN 1 ELSE 0 END) AS melampaui,
            COUNT(*) AS total
        FROM audit_checklist_results r
        JOIN audit_checklists c ON c.id = r.checklist_id
        JOIN audit_assignments a ON a.id = c.assignment_id
        JOIN units u ON u.id = a.auditee_id
        WHERE a.period_id = $mutuPeriodId
          AND u.type = 'Program Studi'
          AND r.audit_status IS NOT NULL AND r.audit_status <> ''
        GROUP BY u.id, u.name
    ");

    $prodiScoreList = [];

    if ($qProdiScores) {
        while ($row = mysqli_fetch_assoc($qProdiScores)) {

            $total = (int) $row['total'];

            if ($total === 0) continue;

            $skor = round((
                (int) $row['tidak_terpenuhi'] * 1
                + (int) $row['sebagian'] * 2
                + (int) $row['memenuhi'] * 3
                + (int) $row['melampaui'] * 4
            ) / $total, 2);

            $kategori = siquaGetKategoriMutu($skor);
            $mutuDistribusiProdi[$kategori]++;

            $prodiScoreList[] = ['name' => $row['unit_name'], 'skor' => $skor];
        }
    }

    usort($prodiScoreList, fn($a, $b) => $a['skor'] <=> $b['skor']);
    $mutuProdiTerendah = array_slice($prodiScoreList, 0, 3);
}
    /* 6 Kategori Kriteria Institusi (untuk Grafik Garis Widget 1) */

    $mutuKriteriaLabels = [];
    $mutuKriteriaScores = [];
    $mutuKriteriaLemah = [];

    $qInstCriteria = mysqli_query($conn, "
        SELECT id, name FROM institution_criteria
        WHERE is_active = 1
          AND id NOT IN (SELECT DISTINCT parent_id FROM institution_criteria WHERE parent_id IS NOT NULL)
        ORDER BY sort_order ASC
    ");

    $instCriteriaList = [];
    if ($qInstCriteria) {
        while ($row = mysqli_fetch_assoc($qInstCriteria)) {
            $instCriteriaList[] = $row;
        }
    }

    foreach ($instCriteriaList as $crit) {

        $critId = (int) $crit['id'];

        $qCritScore = mysqli_query($conn, "
            SELECT
                SUM(CASE WHEN r.audit_status = 'Menyimpang' THEN 1 ELSE 0 END) AS tidak_terpenuhi,
                SUM(CASE WHEN r.audit_status = 'Belum Mencapai' THEN 1 ELSE 0 END) AS sebagian,
                SUM(CASE WHEN r.audit_status = 'Mencapai' THEN 1 ELSE 0 END) AS memenuhi,
                SUM(CASE WHEN r.audit_status = 'Melampaui' THEN 1 ELSE 0 END) AS melampaui,
                COUNT(*) AS total
            FROM audit_checklist_results r
            JOIN audit_checklists c ON c.id = r.checklist_id
            JOIN audit_assignments a ON a.id = c.assignment_id
            JOIN standards s ON s.id = c.standard_id
            WHERE a.period_id = $mutuPeriodId
              AND s.institution_criteria_id = $critId
              AND r.audit_status IS NOT NULL AND r.audit_status <> ''
        ");

        $skorCrit = null;

        if ($qCritScore) {
            $row = mysqli_fetch_assoc($qCritScore);
            $total = (int) $row['total'];

            if ($total > 0) {
                $skorCrit = round((
                    (int) $row['tidak_terpenuhi'] * 1
                    + (int) $row['sebagian'] * 2
                    + (int) $row['memenuhi'] * 3
                    + (int) $row['melampaui'] * 4
                ) / $total, 2);
            }
        }

        $labelPendek = preg_replace('/^Kriteria [\d.]+\.?\s*/', '', $crit['name']);

        $mutuKriteriaLabels[] = $labelPendek;
        $mutuKriteriaScores[] = $skorCrit;

        if ($skorCrit !== null && $skorCrit < 2) {
            $mutuKriteriaLemah[] = ['label' => $labelPendek, 'skor' => $skorCrit];
        }
    }

        /* Skor tiap Prodi per Kriteria Prodi (8 Kriteria) - untuk Spiderweb multi-Prodi */

    $mutuProdiSpiderLabels = [];
    $mutuProdiSpiderDatasets = [];
    $mutuProdiTerendahStandar = null;

    $qProdiCriteria = mysqli_query($conn, "SELECT id, name FROM acc_criteria WHERE is_active = 1 ORDER BY sort_order ASC");

    $prodiCriteriaList = [];
    if ($qProdiCriteria) {
        while ($row = mysqli_fetch_assoc($qProdiCriteria)) {
            $prodiCriteriaList[] = $row;
            $mutuProdiSpiderLabels[] = preg_replace('/^Kriteria \d+\.\s*/', '', $row['name']);
        }
    }

     $mutuColorPalette = ['#7c3aed', '#2563eb', '#0891b2', '#059669', '#d97706', '#dc2626', '#db2777', '#4f46e5', '#65a30d', '#0284c7'];

    if ($mutuPeriodId && !empty($prodiCriteriaList)) {

        $qAllUnits = mysqli_query($conn, "
            SELECT DISTINCT u.id, u.name
            FROM audit_assignments a
            JOIN units u ON u.id = a.auditee_id
            WHERE a.period_id = $mutuPeriodId
              AND u.type = 'Program Studi'
            ORDER BY u.name ASC
        ");

        $allUnitsForSpider = [];
        if ($qAllUnits) {
            while ($row = mysqli_fetch_assoc($qAllUnits)) {
                $allUnitsForSpider[] = $row;
            }
        }

        require_once __DIR__ . '/../../capaian_kriteria/repository.php';
        require_once __DIR__ . '/../../capaian_kriteria/service.php';

        $ckRepository = new CapaianKriteriaRepository($conn);
        $ckService = new CapaianKriteriaService($ckRepository);

        foreach ($allUnitsForSpider as $idx => $unit) {

            $unitId = (int) $unit['id'];

            $ckScores = $ckService->getProdiCriteriaScores($unitId, $mutuPeriodId)['data'];

            $scoresPerCriteria = array_map(fn($c) => $c['skor'], $ckScores);

            $mutuProdiSpiderDatasets[] = [
                'label' => $unit['name'],
                'data' => $scoresPerCriteria,
                'color' => $mutuColorPalette[$idx % count($mutuColorPalette)],
            ];
        }

        /* Cari Standar dengan skor terendah pada Prodi termutu terendah */

        if (!empty($mutuProdiTerendah)) {

            $prodiTerendahNama = $mutuProdiTerendah[0]['name'];

            $qProdiTerendahId = mysqli_query($conn, "SELECT id FROM units WHERE name = '" . mysqli_real_escape_string($conn, $prodiTerendahNama) . "' LIMIT 1");

            if ($qProdiTerendahId && mysqli_num_rows($qProdiTerendahId) > 0) {

                $prodiTerendahId = (int) mysqli_fetch_assoc($qProdiTerendahId)['id'];

                $qStandarTerendah = mysqli_query($conn, "
                    SELECT
                        s.name AS standard_name,
                        SUM(CASE WHEN r.audit_status = 'Menyimpang' THEN 1 ELSE 0 END) AS tidak_terpenuhi,
                        SUM(CASE WHEN r.audit_status = 'Belum Mencapai' THEN 1 ELSE 0 END) AS sebagian,
                        SUM(CASE WHEN r.audit_status = 'Mencapai' THEN 1 ELSE 0 END) AS memenuhi,
                        SUM(CASE WHEN r.audit_status = 'Melampaui' THEN 1 ELSE 0 END) AS melampaui,
                        COUNT(*) AS total
                    FROM audit_checklist_results r
                    JOIN audit_checklists c ON c.id = r.checklist_id
                    JOIN audit_assignments a ON a.id = c.assignment_id
                    JOIN standards s ON s.id = c.standard_id
                    WHERE a.period_id = $mutuPeriodId AND a.auditee_id = $prodiTerendahId
                      AND r.audit_status IS NOT NULL AND r.audit_status <> ''
                    GROUP BY s.id, s.name
                ");

                $standarScoresList = [];

                if ($qStandarTerendah) {
                    while ($row = mysqli_fetch_assoc($qStandarTerendah)) {

                        $total = (int) $row['total'];
                        if ($total === 0) continue;

                        $skorStd = round((
                            (int) $row['tidak_terpenuhi'] * 1
                            + (int) $row['sebagian'] * 2
                            + (int) $row['memenuhi'] * 3
                            + (int) $row['melampaui'] * 4
                        ) / $total, 2);

                        $standarScoresList[] = ['name' => $row['standard_name'], 'skor' => $skorStd];
                    }
                }

                if (!empty($standarScoresList)) {
                    usort($standarScoresList, fn($a, $b) => $a['skor'] <=> $b['skor']);
                    $mutuProdiTerendahStandar = $standarScoresList[0];
                }
            }
        }
    }