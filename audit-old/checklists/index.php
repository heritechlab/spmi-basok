<?php

require_once '../../config/config.php';
require_once 'functions.php';

if (!isLogin()) {
    redirect(BASE_URL.'auth/login.php');
}

$pageTitle = "Checklist Audit";

$assignment_id = (int)($_GET['assignment'] ?? 0);

if ($assignment_id <= 0) {
    setAlert('danger','Assignment tidak ditemukan');
    redirect('../assignments/index.php');
}

$assignment = getAssignmentDetail($assignment_id);

$standards  = getChecklistStandards($assignment_id);

include '../../dashboard/layout.php';
?>

<div class="container-fluid">

    <?php showAlert(); ?>

    <?php include 'components/checklist_header.php'; ?>

    <?php include 'components/checklist_filter.php'; ?>

    <?php include 'components/checklist_table.php'; ?>

</div>

<?php include '../../dashboard/includes/footer.php'; ?>