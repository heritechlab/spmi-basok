<?php

if (!isset($repo)) {
    $repo = new WorkspaceRepository($conn);
}

$assignmentId = $_GET['assignment'] ?? 1;

$navigator = $repo->getNavigator($assignmentId);

$currentStandard = isset($_GET['standard'])
    ? (int)$_GET['standard']
    : 0;

$totalStandar = count($navigator);

?>

<div class="workspace-card navigator-card">

    <div class="workspace-card-header">

        <div>

            <i class="bi bi-diagram-3-fill text-primary me-2"></i>

            <strong>Audit Navigator</strong>

        </div>

        <span class="badge bg-primary rounded-pill">

            <?= $totalStandar ?>

        </span>

    </div>

    <div class="workspace-card-body">

        <!-- SEARCH -->

        <div class="mb-4">

            <div class="input-group">

                <span class="input-group-text bg-white">

                    <i class="bi bi-search"></i>

                </span>

                <input
                    type="text"
                    id="searchStandard"
                    class="form-control border-start-0"
                    placeholder="Cari Standar...">

            </div>

        </div>

        <!-- SUMMARY -->

        <div class="row text-center mb-4">

            <div class="col-4">

                <h4 class="fw-bold text-primary">

                    <?= $totalStandar ?>

                </h4>

                <small class="text-muted">

                    Standar

                </small>

            </div>

            <div class="col-4">

                <h4
                    class="fw-bold text-success"
                    id="overallProgress">

                    0%

                </h4>

                <small class="text-muted">

                    Progress

                </small>

            </div>

            <div class="col-4">

                <h4
                    class="fw-bold text-danger"
                    id="findingTotal">

                    0

                </h4>

                <small class="text-muted">

                    Temuan

                </small>

            </div>

        </div>

        <hr>

        <div class="navigator-list">

        <?php foreach($navigator as $row): ?>

            <?php

            $selected = "";

            if ($currentStandard == 0) {

                if ($row['id'] == $navigator[0]['id']) {

                    $selected = "selected";

                }

            } elseif ($currentStandard == $row['id']) {

                $selected = "selected";

            }

            ?>

            <div

                class="standard-item <?= $selected ?>"

                data-standard="<?= $row['id'] ?>"

                onclick="window.location='?assignment=<?= $assignmentId ?>&standard=<?= $row['id'] ?>'">

                <div class="d-flex justify-content-between">

                    <strong>

                        <?= htmlspecialchars($row['code']) ?>

                    </strong>

                    <span class="badge bg-light text-dark">

                        <?= $row['weight'] ?>

                    </span>

                </div>

                <div class="mt-2 fw-semibold">

                    <?= htmlspecialchars($row['name']) ?>

                </div>

                <div class="progress mt-3">

                    <div

                        class="progress-bar bg-primary"

                        style="width:0%">

                    </div>

                </div>

                <div class="d-flex justify-content-between mt-2">

                    <small class="text-muted">

                        Indikator

                    </small>

                    <strong>

                        <?= $row['total_indicator'] ?>

                    </strong>

                </div>

                <div class="d-flex justify-content-between">

                    <small class="text-muted">

                        Checklist

                    </small>

                    <strong>

                        <?= $row['total_checklist'] ?>

                    </strong>

                </div>

                <div class="mt-3">

                    <span class="badge bg-secondary rounded-pill">

                        Belum Dimulai

                    </span>

                </div>

            </div>

        <?php endforeach; ?>

        </div>

    </div>

</div>