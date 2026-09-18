<?php

// ================================
// PROGRESS AUDIT
// ================================

// Total Standar
$totalStandar = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT COUNT(*) total FROM standards WHERE is_active = 1")
)['total'];

// Total Audit
$totalAudit = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT COUNT(*) total FROM audit_periods")
)['total'];

// Total Temuan
$totalTemuan = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT COUNT(*) total FROM audit_checklist_results WHERE audit_status IS NOT NULL AND audit_status <> ''")
)['total'];

// Total RTL
$totalRTL = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT COUNT(*) total FROM rtm_action_plans")
)['total'];

/*
======================================
PROGRESS
======================================
*/

$progressAudit = ($totalStandar>0)
    ? round(($totalAudit/$totalStandar)*100)
    : 0;

$progressRTL = ($totalTemuan>0)
    ? round(($totalRTL/$totalTemuan)*100)
    : 0;

$progressStandar = ($totalStandar>0)?100:0;

$progressTemuan = ($totalTemuan>0)?100:0;

?>

<div class="row mt-4">

<div class="col-12">

<div class="widget progress-widget">

<div class="widget-header mb-4">

<h4>

<i class="bi bi-graph-up-arrow text-success"></i>

Progress Audit

</h4>

<small>

Monitoring Penyelesaian Audit Mutu Internal

</small>

</div>

<!-- Progress Besar -->

<div class="overall-progress">

<div class="overall-number">

<?= $progressAudit ?>%

</div>

<div class="overall-label">

Progress Audit Keseluruhan

</div>

<div class="progress-track">

<div
class="progress-fill"

data-width="<?= $progressAudit ?>">
</div>

</div>

</div>

<hr>

<!-- Detail -->

<div class="row">

<div class="col-md-3">

<div class="progress-item">

<div class="d-flex justify-content-between">

<span>Standar</span>

<strong><?= $progressStandar ?>%</strong>

</div>

<div class="mini-progress">

<div
class="mini-fill bg-primary"
data-width="<?= $progressStandar ?>">
</div>

</div>

<small><?= $totalStandar ?> Standar</small>

</div>

</div>

<div class="col-md-3">

<div class="progress-item">

<div class="d-flex justify-content-between">

<span>Audit</span>

<strong><?= $progressAudit ?>%</strong>

</div>

<div class="mini-progress">

<div
class="mini-fill bg-success"
data-width="<?= $progressAudit ?>">
</div>

</div>

<small><?= $totalAudit ?> Audit</small>

</div>

</div>

<div class="col-md-3">

<div class="progress-item">

<div class="d-flex justify-content-between">

<span>Temuan</span>

<strong><?= $progressTemuan ?>%</strong>

</div>

<div class="mini-progress">

<div
class="mini-fill bg-warning"
data-width="<?= $progressTemuan ?>">
</div>

</div>

<small><?= $totalTemuan ?> Temuan</small>

</div>

</div>

<div class="col-md-3">

<div class="progress-item">

<div class="d-flex justify-content-between">

<span>RTL</span>

<strong><?= $progressRTL ?>%</strong>

</div>

<div class="mini-progress">

<div
class="mini-fill bg-danger"
data-width="<?= $progressRTL ?>">
</div>

</div>

<small><?= $totalRTL ?> RTL</small>

</div>

</div>

</div>

</div>

</div>

</div>