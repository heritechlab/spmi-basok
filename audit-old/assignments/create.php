<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
/*
|--------------------------------------------------------------------------
| SIQUA
|--------------------------------------------------------------------------
| Modul : Create Assignment
|--------------------------------------------------------------------------
*/

require_once '../../config/config.php';
require_once 'functions.php';

if (!isLogin()) {
    redirect(BASE_URL . 'auth/login.php');
}

$pageTitle = "Tambah Penugasan Audit";
/*
|--------------------------------------------------------------------------
| Dropdown Data
|--------------------------------------------------------------------------
*/

$periods = getAuditPeriods();

$auditees = getAuditees();

$auditors = getAuditors();

$coordinators = getCoordinators();

/*
|--------------------------------------------------------------------------
| Save
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $result = createAssignment($_POST);

    if ($result['status']) {

        setAlert(
            'success',
            $result['message']
        );

        redirect('index.php');

    } else {

        setAlert(
            'danger',
            $result['message']
        );

    }

}
include '../../dashboard/layout.php';
?>

<div class="container-fluid">

<?php
include 'components/form_header.php';
?>

</div>

<form method="POST">

<?php
include 'components/form_information.php';
?>

<?php
include 'components/form_team.php';
?>

<?php
include 'components/form_schedule.php';
?>

<?php
include 'components/form_scope.php';
?>

<?php
include 'components/form_footer.php';
?>

<?php
include '../../dashboard/includes/footer.php';
?>