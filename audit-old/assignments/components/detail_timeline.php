<div class="card border-0 shadow-sm">

    <div class="card-header bg-white">

        <h5 class="mb-0 fw-bold">
            <i class="bi bi-clock-history text-primary me-2"></i>
            Timeline Audit
        </h5>

    </div>

    <div class="card-body">

<?php

$status = $assignment['status'];

function timelineIcon($active)
{
    return $active
        ? '<span class="badge bg-success rounded-circle p-2"><i class="bi bi-check-lg"></i></span>'
        : '<span class="badge bg-light text-secondary border rounded-circle p-2"><i class="bi bi-circle"></i></span>';
}

?>

<div class="timeline">

    <!-- Draft -->

    <div class="d-flex mb-4">

        <div class="me-3">

            <?= timelineIcon(true); ?>

        </div>

        <div>

            <strong>Draft Penugasan Dibuat</strong><br>

            <small class="text-muted">

                <?= formatDateTime($assignment['created_at']); ?>

            </small>

        </div>

    </div>

    <!-- Dijadwalkan -->

    <div class="d-flex mb-4">

        <div class="me-3">

            <?= timelineIcon(
                in_array($status,['Dijadwalkan','Berlangsung','Selesai'])
            ); ?>

        </div>

        <div>

            <strong>Audit Dijadwalkan</strong><br>

            <small class="text-muted">

                <?= formatDate($assignment['audit_date']); ?>

            </small>

        </div>

    </div>

    <!-- Mulai Audit -->

    <div class="d-flex mb-4">

        <div class="me-3">

            <?= timelineIcon(
                in_array($status,['Berlangsung','Selesai'])
            ); ?>

        </div>

        <div>

            <strong>Audit Dimulai</strong><br>

            <small class="text-muted">

                <?= !empty($assignment['audit_start'])
                    ? formatDateTime($assignment['audit_start'])
                    : '-'; ?>

            </small>

        </div>

    </div>

    <!-- Opening Meeting -->

    <div class="d-flex mb-4">

        <div class="me-3">

            <?= timelineIcon(
                !empty($assignment['opening_meeting'])
            ); ?>

        </div>

        <div>

            <strong>Opening Meeting</strong><br>

            <small class="text-muted">

                <?= !empty($assignment['opening_meeting'])
                    ? formatDateTime($assignment['opening_meeting'])
                    : '-'; ?>

            </small>

        </div>

    </div>

    <!-- Closing Meeting -->

    <div class="d-flex mb-4">

        <div class="me-3">

            <?= timelineIcon(
                !empty($assignment['closing_meeting'])
            ); ?>

        </div>

        <div>

            <strong>Closing Meeting</strong><br>

            <small class="text-muted">

                <?= !empty($assignment['closing_meeting'])
                    ? formatDateTime($assignment['closing_meeting'])
                    : '-'; ?>

            </small>

        </div>

    </div>

    <!-- Selesai -->

    <div class="d-flex">

        <div class="me-3">

            <?= timelineIcon(
                $status=='Selesai'
            ); ?>

        </div>

        <div>

            <strong>Audit Selesai</strong><br>

            <small class="text-muted">

                <?= !empty($assignment['completion_date'])
                    ? formatDate($assignment['completion_date'])
                    : '-'; ?>

            </small>

        </div>

    </div>

</div>

    </div>

</div>