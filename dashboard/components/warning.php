<?php

// ===============================
// EXECUTIVE ALERT
// ===============================

// Audit Draft
$auditDraft = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) total
        FROM audit_periods
        WHERE status='Draft'"
    )
)['total'];

// Audit Aktif
$auditAktif = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) total
        FROM audit_periods
        WHERE status='Aktif'"
    )
)['total'];

// Standar
$totalStandar = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) total
        FROM standards WHERE is_active = 1"
    )
)['total'];

// Temuan
$totalTemuan = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) total
        FROM audit_checklist_results WHERE audit_status IS NOT NULL AND audit_status <> ''"
    )
)['total'];

// RTL
$totalRTL = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) total
        FROM rtm_action_plans"
    )
)['total'];

?>

<div class="col-lg-5">

<div class="widget h-100">

<div class="widget-header mb-4">

<h4>

<i class="bi bi-exclamation-triangle-fill text-warning"></i>

Executive Alert

</h4>

<small>

Monitoring Kondisi Audit Internal

</small>

</div>

<div class="alert-list">

<!-- Audit -->

<div class="alert-item <?= ($auditAktif>0)?'success':'danger'; ?>">

<div class="alert-icon">

<i class="bi <?= ($auditAktif>0)?
'bi-check-circle-fill':
'bi-exclamation-circle-fill'; ?>"></i>

</div>

<div>

<strong>

<?= ($auditAktif>0)?
'Audit Aktif':
'Audit Belum Dimulai'; ?>

</strong>

<?= ($auditAktif>0)?
'Periode Audit sedang berjalan':
'Belum ada Periode Audit yang aktif'; ?>

</div>

</div>

<!-- Standar -->

<div class="alert-item <?= ($totalStandar>0)?'success':'danger'; ?>">

<div class="alert-icon">

<i class="bi <?= ($totalStandar>0)?
'bi-bookmark-check-fill':
'bi-bookmark-x-fill'; ?>"></i>

</div>

<div>

<strong>

<?= $totalStandar ?>

Standar Mutu

</strong>

Standar yang tersedia di SIQUA

</div>

</div>

<!-- Temuan -->

<div class="alert-item <?= ($totalTemuan>0)?'warning':'primary'; ?>">

<div class="alert-icon">

<i class="bi bi-search"></i>

</div>

<div>

<strong>

<?= $totalTemuan ?>

Temuan Audit

</strong>

<?= ($totalTemuan==0)?
'Belum terdapat temuan audit':
'Perlu monitoring tindak lanjut'; ?>

</div>

</div>

<!-- RTL -->

<div class="alert-item <?= ($totalRTL>0)?'success':'danger'; ?>">

<div class="alert-icon">

<i class="bi bi-list-check"></i>

</div>

<div>

<strong>

<?= $totalRTL ?>

RTL

</strong>

<?= ($totalRTL==0)?
'Belum ada Rencana Tindak Lanjut':
'RTL telah tersedia'; ?>

</div>

</div>

</div>

</div>

</div>