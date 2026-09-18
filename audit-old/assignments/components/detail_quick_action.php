<div class="card border-0 shadow-sm">

    <div class="card-header bg-white">

        <h5 class="fw-bold mb-0">

            <i class="bi bi-lightning-charge-fill text-warning me-2"></i>

            Quick Action

        </h5>

    </div>

    <div class="card-body">

        <div class="row g-4">

            <!-- Checklist -->
            <div class="col-lg col-md-4 col-sm-6">

                <a href="../instruments/index.php?assignment=<?= $assignment['id'] ?>"
                   class="text-decoration-none">

                    <div class="quick-card quick-primary">

                        <div class="quick-icon bg-primary">

                            <i class="bi bi-ui-checks-grid"></i>

                        </div>

                        <h6>Checklist</h6>

                        <small>Instrumen Audit</small>

                    </div>

                </a>

            </div>

            <!-- Temuan -->
            <div class="col-lg col-md-4 col-sm-6">

                <a href="../findings/index.php?assignment=<?= $assignment['id'] ?>"
                   class="text-decoration-none">

                    <div class="quick-card quick-danger">

                        <div class="quick-icon bg-danger">

                            <i class="bi bi-search"></i>

                        </div>

                        <h6>Temuan</h6>

                        <small>Audit Finding</small>

                    </div>

                </a>

            </div>

            <!-- Eviden -->
            <div class="col-lg col-md-4 col-sm-6">

                <a href="../documents/index.php?assignment=<?= $assignment['id'] ?>"
                   class="text-decoration-none">

                    <div class="quick-card quick-info">

                        <div class="quick-icon bg-info">

                            <i class="bi bi-cloud-upload"></i>

                        </div>

                        <h6>Eviden</h6>

                        <small>Upload Dokumen</small>

                    </div>

                </a>

            </div>

            <!-- RTL -->
            <div class="col-lg col-md-4 col-sm-6">

                <a href="../rtl/index.php?assignment=<?= $assignment['id'] ?>"
                   class="text-decoration-none">

                    <div class="quick-card quick-success">

                        <div class="quick-icon bg-success">

                            <i class="bi bi-arrow-repeat"></i>

                        </div>

                        <h6>RTL</h6>

                        <small>Tindak Lanjut</small>

                    </div>

                </a>

            </div>

            <!-- Laporan -->
            <div class="col-lg col-md-4 col-sm-6">

                <a href="../reports/index.php?assignment=<?= $assignment['id'] ?>"
                   class="text-decoration-none">

                    <div class="quick-card quick-warning">

                        <div class="quick-icon bg-warning">

                            <i class="bi bi-file-earmark-bar-graph"></i>

                        </div>

                        <h6>Laporan</h6>

                        <small>Generate Report</small>

                    </div>

                </a>

            </div>

        </div>

    </div>

</div>