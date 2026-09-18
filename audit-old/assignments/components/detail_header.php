<!-- ==========================================================
DETAIL HEADER
========================================================== -->

<div class="card border-0 shadow-sm mb-4">

    <div class="card-body">

        <div class="row align-items-center">

            <!-- Judul -->

            <div class="col-lg-8">

                <div class="d-flex align-items-center mb-3">

                    <a href="index.php"
                       class="btn btn-light border me-3">

                        <i class="bi bi-arrow-left"></i>

                    </a>

                    <div>

                        <h3 class="fw-bold mb-1">

                            <?= htmlspecialchars($assignment['assignment_number']) ?>

                        </h3>

                        <small class="text-muted">
                             <?= htmlspecialchars($assignment['auditee_name']) ?>
                                •
                            <?= htmlspecialchars($assignment['period_name']) ?>
                        </small>

                    </div>

                </div>
                
                <!-- Persentase di dalam Progress -->

                <div class="progress mt-3"
                    style="height:14px;
                        border-radius:30px;
                        background:#edf2f7;">

                    <div class="progress-bar bg-success"
                         role="progressbar"
                         style="width:<?= $assignment['progress_percent'] ?>%">

                    </div>

                </div>

                <div class="small text-end mt-2 fw-semibold">

                    <?= (int)$assignment['progress_percent'] ?>%

                </div>

            </div>

            <!-- Tombol -->
        <div class="col-lg-4 text-end">

            <div class="d-grid gap-2">

                <a href="edit.php?id=<?= $assignment['id'] ?>"
                class="btn btn-warning">

                <i class="bi bi-pencil-square"></i>

                Edit Penugasan

                </a>

                <a href="index.php"
                class="btn btn-outline-secondary">

                <i class="bi bi-arrow-left"></i>

                Kembali

                </a>

                </div>

                <a href="index.php"
                   class="btn btn-secondary">

                    <i class="bi bi-list"></i>

                    Daftar

                </a>

            </div>

        </div>

    </div>

</div>