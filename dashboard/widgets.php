<?php

// ======================================
// TOTAL STANDAR
// ======================================

$totalStandar = 0;

$q = $conn->query("SELECT COUNT(*) total FROM standards WHERE is_active = 1");

if ($q) {
    $totalStandar = $q->fetch_assoc()['total'];
}

// ======================================
// STANDAR SUDAH DIAUDIT
// ======================================

$standarDiaudit = 0;

$q = $conn->query("
    SELECT COUNT(DISTINCT c.standard_id) total
    FROM audit_checklist_results r
    JOIN audit_checklists c ON c.id = r.checklist_id
    WHERE r.audit_status IS NOT NULL AND r.audit_status <> ''
");

if ($q) {
    $standarDiaudit = $q->fetch_assoc()['total'];
}

$persenStandar = 0;

if ($totalStandar > 0) {
    $persenStandar = round(($standarDiaudit / $totalStandar) * 100);
}

// ======================================
// TEMUAN AKTIF
// ======================================

$temuanAktif = 0;

$q = $conn->query("
    SELECT COUNT(*) total
    FROM audit_checklist_results
    WHERE audit_status IN ('Menyimpang', 'Belum Mencapai')
");

if ($q) {
    $temuanAktif = $q->fetch_assoc()['total'];
}

// ======================================
// RTL
// ======================================

$totalRTL = 0;

$q = $conn->query("SELECT COUNT(*) total FROM rtm_action_plans");

if ($q) {
    $totalRTL = $q->fetch_assoc()['total'];
}

$rtlSelesai = 0;

$q = $conn->query("SELECT COUNT(*) total FROM rtm_action_plans WHERE status = 'Selesai'");

if ($q) {
    $rtlSelesai = $q->fetch_assoc()['total'];
}

$persenRTL = 0;

if ($totalRTL > 0) {
    $persenRTL = round(($rtlSelesai / $totalRTL) * 100);
}

// ======================================
// UNIT
// ======================================

$totalUnit = 0;

$q = $conn->query("SELECT COUNT(*) total FROM units WHERE status = 1 AND is_auditable = 1");

if ($q) {
    $totalUnit = $q->fetch_assoc()['total'];
}

$unitSelesai = 0;

$q = $conn->query("
    SELECT COUNT(DISTINCT auditee_id) total
    FROM audit_assignments
    WHERE status = 'Selesai'
");

if ($q) {
    $unitSelesai = $q->fetch_assoc()['total'];
}

$persenUnit = 0;

if ($totalUnit > 0) {
    $persenUnit = round(($unitSelesai / $totalUnit) * 100);
}

?>