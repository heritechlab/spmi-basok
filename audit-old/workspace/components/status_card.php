<?php

if (!isset($repo)) {
    $repo = new WorkspaceRepository($conn);
}

$assignmentId = $_GET['assignment'] ?? 1;
$standardId   = $_GET['standard'] ?? 1;

$indicator = $repo->getCurrentIndicator(
    $assignmentId,
    $standardId
);

$result = null;

if ($indicator) {
    $result = $repo->getResult($indicator['checklist_id']);
}

$currentStatus = $result['audit_status'] ?? "";
?>

<div class="workspace-card">

    <div class="workspace-card-header">

        <div>

            <i class="bi bi-check2-square text-primary me-2"></i>

            <strong>Status Audit</strong>

        </div>

        <span class="badge bg-primary">

            Checklist

        </span>

    </div>

    <div class="workspace-card-body">

        <input
            type="hidden"
            id="checklistId"
            value="<?= $indicator['checklist_id'] ?? 0 ?>">

        <div class="row g-4">

            <!-- Tidak Terpenuhi -->

            <div class="col-md-6">

                <div
                    class="status-card danger <?= ($currentStatus=="Tidak Terpenuhi")?'active':'';?>"
                    data-value="Tidak Terpenuhi">

                    <i class="bi bi-x-circle-fill text-danger display-5"></i>

                    <h5 class="mt-3">

                        Tidak Terpenuhi

                    </h5>

                    <small>

                        Standar tidak dipenuhi

                    </small>

                </div>

            </div>

            <!-- Memenuhi Sebagian -->

            <div class="col-md-6">

                <div
                    class="status-card warning <?= ($currentStatus=="Memenuhi Sebagian")?'active':'';?>"
                    data-value="Memenuhi Sebagian">

                    <i class="bi bi-exclamation-circle-fill text-warning display-5"></i>

                    <h5 class="mt-3">

                        Memenuhi Sebagian

                    </h5>

                    <small>

                        Masih terdapat gap

                    </small>

                </div>

            </div>

            <!-- Memenuhi -->

            <div class="col-md-6">

                <div
                    class="status-card success <?= ($currentStatus=="Memenuhi")?'active':'';?>"
                    data-value="Memenuhi">

                    <i class="bi bi-check-circle-fill text-success display-5"></i>

                    <h5 class="mt-3">

                        Memenuhi

                    </h5>

                    <small>

                        Sesuai indikator

                    </small>

                </div>

            </div>

            <!-- Melampaui -->

            <div class="col-md-6">

                <div
                    class="status-card primary <?= ($currentStatus=="Melampaui")?'active':'';?>"
                    data-value="Melampaui">

                    <i class="bi bi-award-fill text-primary display-5"></i>

                    <h5 class="mt-3">

                        Melampaui

                    </h5>

                    <small>

                        Best Practice

                    </small>

                </div>

            </div>

        </div>

    </div>

</div>