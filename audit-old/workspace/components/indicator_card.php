<?php

if(!isset($repo)){
    $repo = new WorkspaceRepository($conn);
}

$assignmentId = $_GET['assignment'] ?? 1;

$standardId = $_GET['standard'] ?? 1;

$indicator = $repo->getCurrentIndicator(
    $assignmentId,
    $standardId
);

if(!$indicator){

    echo '

    <div class="workspace-card">

        <div class="workspace-card-body">

            <div class="text-center py-5">

                <i class="bi bi-info-circle display-5 text-secondary"></i>

                <h5 class="mt-3">

                    Belum ada indikator

                </h5>

            </div>

        </div>

    </div>

    ';

    return;

}

?>

<div class="workspace-card">

    <div class="workspace-card-header">

        <div>

            <i class="bi bi-list-check me-2 text-primary"></i>

            Informasi Indikator

        </div>

        <span class="badge bg-primary">

            <?= $indicator['item_code']; ?>

        </span>

    </div>

    <div class="workspace-card-body">

        <div class="row g-4">

            <div class="col-lg-3">

                <small class="text-muted">

                    Standar

                </small>

                <div class="fw-semibold">

                    <?= htmlspecialchars($indicator['standard_name']); ?>

                </div>

            </div>

            <div class="col-lg-3">

                <small class="text-muted">

                    Kode

                </small>

                <div class="fw-bold">

                    <?= htmlspecialchars($indicator['standard_code']); ?>

                </div>

            </div>

            <div class="col-lg-3">

                <small class="text-muted">

                    Bobot

                </small>

                <div class="fw-bold">

                    <?= $indicator['weight']; ?>

                </div>

            </div>

            <div class="col-lg-3">

                <small class="text-muted">

                    Verifikasi

                </small>

                <div>

                    <?= htmlspecialchars($indicator['verification_method']); ?>

                </div>

            </div>

        </div>

        <hr>

        <small class="text-muted">

            Pernyataan Standar

        </small>

        <div class="mt-2 fs-6">

            <?= nl2br(htmlspecialchars($indicator['statement'])); ?>

        </div>

        <hr>

        <div class="row g-4">

            <div class="col-md-4">

                <small class="text-muted">

                    Target

                </small>

                <div>

                    <?= htmlspecialchars($indicator['target']); ?>

                </div>

            </div>

            <div class="col-md-4">

                <small class="text-muted">

                    Benchmark

                </small>

                <div>

                    <?= htmlspecialchars($indicator['benchmark_value']); ?>

                </div>

            </div>

            <div class="col-md-4">

                <small class="text-muted">

                    Unit

                </small>

                <div>

                    <?= htmlspecialchars($indicator['unit']); ?>

                </div>

            </div>

        </div>

    </div>

</div>