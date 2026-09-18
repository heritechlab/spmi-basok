<div class="card border-0 shadow-sm">

    <div class="card-header bg-white">

        <h5 class="mb-0 fw-bold">

            <i class="bi bi-speedometer2 text-primary me-2"></i>

            Progress Audit

        </h5>

    </div>

    <div class="card-body">

<?php

$progress = (int)$assignment['progress_percent'];

?>

<!-- Progress Utama -->

<div class="mb-4">

    <div class="d-flex justify-content-between mb-2">

        <strong>Progress Keseluruhan</strong>

        <strong><?= $progress ?>%</strong>

    </div>

    <div class="progress" style="height:12px;">

        <div class="progress-bar bg-success"

             style="width:<?= $progress ?>%">

        </div>

    </div>

</div>

<hr>

<!-- Checklist -->

<div class="row g-3">

    <div class="col-md-6">

        <div class="form-check">

            <input class="form-check-input"

                   type="checkbox"

                   checked>

            <label class="form-check-label">

                Penugasan dibuat

            </label>

        </div>

    </div>

    <div class="col-md-6">

        <div class="form-check">

            <input class="form-check-input"

                   type="checkbox"

                   <?= $assignment['status']!='Draft'?'checked':''; ?>

                   disabled>

            <label class="form-check-label">

                Audit dijadwalkan

            </label>

        </div>

    </div>

    <div class="col-md-6">

        <div class="form-check">

            <input class="form-check-input"

                   type="checkbox"

                   <?= in_array($assignment['status'],['Berlangsung','Selesai'])?'checked':''; ?>

                   disabled>

            <label class="form-check-label">

                Audit dimulai

            </label>

        </div>

    </div>

    <div class="col-md-6">

        <div class="form-check">

            <input class="form-check-input"

                   type="checkbox"

                   <?= !empty($assignment['opening_meeting'])?'checked':''; ?>

                   disabled>

            <label class="form-check-label">

                Opening Meeting

            </label>

        </div>

    </div>

    <div class="col-md-6">

        <div class="form-check">

            <input class="form-check-input"

                   type="checkbox"

                   <?= !empty($assignment['closing_meeting'])?'checked':''; ?>

                   disabled>

            <label class="form-check-label">

                Closing Meeting

            </label>

        </div>

    </div>

    <div class="col-md-6">

        <div class="form-check">

            <input class="form-check-input"

                   type="checkbox"

                   <?= $assignment['status']=='Selesai'?'checked':''; ?>

                   disabled>

            <label class="form-check-label">

                Audit selesai

            </label>

        </div>

    </div>

</div>

    </div>

</div>