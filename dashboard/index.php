<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../layouts/app.php';

if (Auth::isMahasiswa()) {
    include __DIR__ . '/dashboard_mahasiswa.php';
    require_once __DIR__ . '/../layouts/footer.php';
    exit;
}

?>

<div class="container-fluid py-4">

    <?php include __DIR__ . '/home.php'; ?>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script src="<?= BASE_URL ?>assets/js/dashboard.js"></script>