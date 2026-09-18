<?php

ini_set('display_errors',1);
error_reporting(E_ALL);
/*
|--------------------------------------------------------------------------
| SIQUA
|--------------------------------------------------------------------------
| Modul  : Audit Assignment
| File   : audit/assignments/index.php
|--------------------------------------------------------------------------
*/

require_once '../../config/config.php';
require_once 'functions.php';

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

if (!isLogin()) {
    redirect(BASE_URL . 'auth/login.php');
}

$pageTitle = "Penugasan Audit";

/*
|--------------------------------------------------------------------------
| Load Dashboard Data
|--------------------------------------------------------------------------
*/

$statistics  = assignmentStatistic();

$assignments = getAssignments();

/*
|--------------------------------------------------------------------------
| Filter
|--------------------------------------------------------------------------
*/

$keyword = $_GET['keyword'] ?? '';
$status  = $_GET['status'] ?? '';

if ($keyword != '' || $status != '') {
    $assignments = searchAssignments($keyword, $status);
}

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

include '../../dashboard/layout.php';
?>


<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h2 class="fw-bold mb-1">

            Penugasan Audit

        </h2>

        <p class="text-muted">

            Manajemen Penugasan Audit Mutu Internal

        </p>

    </div>

    <a href="create.php"
       class="btn btn-primary">

        <i class="bi bi-plus-circle"></i>

        Penugasan Baru

    </a>

</div>

<?php showAlert(); ?>

<!-- =======================================================
EXECUTIVE SUMMARY
======================================================== -->

<?php
include 'components/statistics.php';
?>

<?php include 'components/toolbar.php'; ?>

<?php include 'components/assignment_table.php'; ?>

<?php
include '../../dashboard/includes/footer.php';
?>
