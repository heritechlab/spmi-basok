<div class="card border-0 shadow-sm">

    <div class="card-header bg-white">

        <h5 class="fw-bold mb-0">

            <i class="bi bi-speedometer2 text-primary me-2"></i>

            Audit Activity Dashboard

        </h5>

    </div>

    <div class="card-body">

        <div class="row g-4">

            <!-- Instrumen -->

            <div class="col-lg-2 col-md-4">

                <div class="activity-card">

                    <div class="activity-icon bg-primary">

                        <i class="bi bi-ui-checks-grid"></i>

                    </div>

                    <h3>

                        <?= $activity['instrument'] ?? 0 ?>

                    </h3>

                    <small>Instrumen</small>

                </div>

            </div>

            <!-- Checklist -->

            <div class="col-lg-2 col-md-4">

                <div class="activity-card">

                    <div class="activity-icon bg-success">

                        <i class="bi bi-check2-square"></i>

                    </div>

                    <h3>

                        <?= $activity['checklist'] ?? 0 ?>

                    </h3>

                    <small>Checklist</small>

                </div>

            </div>

            <!-- Temuan -->

            <div class="col-lg-2 col-md-4">

                <div class="activity-card">

                    <div class="activity-icon bg-danger">

                        <i class="bi bi-search"></i>

                    </div>

                    <h3>

                        <?= $activity['finding'] ?? 0 ?>

                    </h3>

                    <small>Temuan</small>

                </div>

            </div>

            <!-- Eviden -->

            <div class="col-lg-2 col-md-4">

                <div class="activity-card">

                    <div class="activity-icon bg-info">

                        <i class="bi bi-cloud-upload"></i>

                    </div>

                    <h3>

                        <?= $activity['evidence'] ?? 0 ?>

                    </h3>

                    <small>Eviden</small>

                </div>

            </div>

            <!-- RTL -->

            <div class="col-lg-2 col-md-4">

                <div class="activity-card">

                    <div class="activity-icon bg-warning">

                        <i class="bi bi-arrow-repeat"></i>

                    </div>

                    <h3>

                        <?= $activity['rtl'] ?? 0 ?>

                    </h3>

                    <small>RTL</small>

                </div>

            </div>

            <!-- Laporan -->

            <div class="col-lg-2 col-md-4">

                <div class="activity-card">

                    <div class="activity-icon bg-secondary">

                        <i class="bi bi-file-earmark-bar-graph"></i>

                    </div>

                    <h3>

                        <?= $activity['report'] ?? 0 ?>

                    </h3>

                    <small>Laporan</small>

                </div>

            </div>

        </div>

    </div>

</div>