<?php

include "components/dashboard_data.php";

?>

<div class="content">

<?php

include "components/welcome.php";

include "components/quick_action.php";

include "components/auditor_assignments.php";

?>

<?php include "components/mutu_widget.php"; ?>

<div class="row mt-2">
    <?php include "components/trend_chart.php"; ?>
    <?php include "components/iku_status_chart.php"; ?>
</div>

<div class="row mt-2">
    <?php include "components/standard_radar.php"; ?>
</div>

<div class="row row-tight-top">

    <?php
    include "components/chart.php";

    include "components/activity.php";
    ?>

</div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.progress-fill, .mini-fill').forEach(function (el) {
        const width = el.getAttribute('data-width') || 0;
        setTimeout(function () {
            el.style.width = width + '%';
        }, 100);
    });

    const clockEl = document.getElementById('liveClock');
    if (clockEl) {
        setInterval(function () {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            clockEl.textContent = h + ':' + m;
        }, 1000);
    }
});
</script>