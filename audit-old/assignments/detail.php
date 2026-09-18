<?php
/*
|--------------------------------------------------------------------------
| SIQUA
|--------------------------------------------------------------------------
| Detail Assignment
|--------------------------------------------------------------------------
*/

require_once '../../config/config.php';
require_once 'functions.php';

if (!isLogin()) {
    redirect(BASE_URL.'auth/login.php');
}

$pageTitle = "Detail Penugasan Audit";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id<=0){

    setAlert(
        'danger',
        'Data tidak ditemukan.'
    );

    redirect('index.php');

}
$assignment = getAssignmentDetail($id);
$teamMembers = getAssignmentTeam($id);
$activity = [

    'instrument' => 14,

    'checklist' => 9,

    'finding' => 3,

    'evidence' => 12,

    'rtl' => 2,

    'report' => 0

];

if(!$assignment){

    setAlert(
        'danger',
        'Penugasan Audit tidak ditemukan.'
    );

    redirect('index.php');

}
include '../../dashboard/layout.php';
?>

<div class="container-fluid">

<div class="card shadow-sm border-0 mb-4">

    <div class="card-body">

        <div class="row align-items-center">

            <div class="col-lg-8">

                <h3 class="fw-bold mb-2">

                    <?= htmlspecialchars($assignment['assignment_number']) ?>

                </h3>

                <p class="text-muted mb-3">

                    <?= htmlspecialchars($assignment['audit_type']) ?>

                </p>

                <?= badgeStatus($assignment['status']) ?>

            </div>

            <div class="col-lg-4 text-end">

                <h2 class="fw-bold text-primary">

                    <?= (int)$assignment['progress_percent'] ?>%

                </h2>

                <small class="text-muted">

                    <?= htmlspecialchars($assignment['current_step']) ?>

                </small>

            </div>

        </div>
        <hr>

<div class="progress mb-4" style="height:10px;">

    <div
        class="progress-bar bg-success"
        style="width:<?= (int)$assignment['progress_percent'] ?>%">
    </div>

</div>
<div class="row">

    <div class="col-md-3">

        <small class="text-muted">Periode Audit</small>

        <h6><?= htmlspecialchars($assignment['period_name']) ?></h6>

    </div>

    <div class="col-md-3">

        <small class="text-muted">Auditee</small>

        <h6><?= htmlspecialchars($assignment['auditee_name']) ?></h6>

    </div>

    <div class="col-md-3">

        <small class="text-muted">Lead Auditor</small>

        <h6><?= htmlspecialchars($assignment['lead_auditor_name']) ?></h6>

    </div>

    <div class="col-md-3">

        <small class="text-muted">Koordinator</small>

        <h6><?= htmlspecialchars($assignment['coordinator_name']) ?></h6>

    </div>

</div>
<hr>

<div class="d-flex gap-2">

    <a href="edit.php?id=<?= $assignment['id'] ?>"
       class="btn btn-warning">

        <i class="bi bi-pencil-square"></i>

        Edit

    </a>

    <a href="start.php?id=<?= $assignment['id'] ?>"
       class="btn btn-success">

        <i class="bi bi-play-fill"></i>

        Mulai Audit

    </a>

    <a href="#"
       class="btn btn-outline-primary">

        <i class="bi bi-printer"></i>

        Cetak

    </a>

</div>

    </div>

</div>


<div class="row">

    <div class="col-lg-6">

        <?php include 'components/detail_info.php'; ?>

    </div>

    <div class="col-lg-6">

        <?php include 'components/detail_schedule.php'; ?>

    </div>

</div>

<div class="row mt-4">

    <div class="col-lg-6">

        <?php include 'components/detail_team.php'; ?>

    </div>

    <div class="col-lg-6">

        <?php include 'components/detail_scope.php'; ?>

    </div>

</div>

<div class="mt-4">

    <?php include 'components/detail_progress.php'; ?>

</div>

<div class="mt-4">

    <?php include 'components/detail_timeline.php'; ?>

</div>

<?php include 'components/detail_progress.php'; ?>

<div class="mt-4">

    <?php include 'components/detail_quick_action.php'; ?>

</div>

<div class="mt-4">

    <?php include 'components/detail_activity.php'; ?>

</div>

<div class="mt-4">

    <?php include 'components/detail_timeline.php'; ?>

</div>


<div class="mt-4">

    <?php include 'components/detail_timeline.php'; ?>

</div>
