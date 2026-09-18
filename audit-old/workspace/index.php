<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../../config/config.php';
require_once 'functions.php';

if(!isLogin()){
    redirect(BASE_URL.'auth/login.php');
}

$pageTitle="Audit Workspace";

include '../../dashboard/layout.php';

?>

<div class="container-fluid workspace-container">

    <?php include 'components/header.php'; ?>

    <div class="row g-4 mt-1">

        <div class="col-xl-3">

            <?php include 'components/navigator_card.php'; ?>

        </div>

        <div class="col-xl-6">

            <?php include 'components/indicator_card.php'; ?>

            <?php include 'components/status_card.php'; ?>

            <?php include 'components/evidence_card.php'; ?>

            <?php include 'components/note_card.php'; ?>

            <?php include 'components/analysis_card.php'; ?>

            <?php include 'components/recommendation_card.php'; ?>

        </div>

        <div class="col-xl-3">

            <?php include 'components/summary_card.php'; ?>

            <?php include 'components/timeline_card.php'; ?>

        </div>

    </div>

    <?php include 'components/footer_action.php'; ?>

</div>

<?php include '../../dashboard/includes/footer.php'; ?>