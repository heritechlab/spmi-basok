<?php

require_once '../../config/config.php';

if (!isLogin()) {
    redirect(BASE_URL.'auth/login.php');
}

$pageTitle = "Audit Workspace";

include '../../dashboard/layout.php';

?>

<div class="container-fluid">

    <?php include 'components/header.php'; ?>

    <div class="row g-3">

        <div class="col-lg-3">

            <?php include 'components/sidebar.php'; ?>

        </div>

        <div class="col-lg-6">

            <?php include 'components/content.php'; ?>

        </div>

        <div class="col-lg-3">

            <?php include 'components/summary.php'; ?>

        </div>

    </div>

    <?php include 'components/footer_action.php'; ?>

</div>

<?php
include '../../dashboard/includes/footer.php';