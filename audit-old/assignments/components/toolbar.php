<!-- ==========================================================
TOOLBAR
========================================================== -->

<div class="card border-0 shadow-sm mb-4">

    <div class="card-body">

        <form method="GET">

            <div class="row g-3 align-items-end">

                <!-- Search -->

                <div class="col-lg-4">

                    <label class="form-label">

                        Cari Assignment

                    </label>

                    <input
                        type="text"
                        name="keyword"
                        class="form-control"
                        placeholder="Nomor assignment / Auditee / Auditor"
                        value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">

                </div>

                <!-- Status -->

                <div class="col-lg-2">

                    <label class="form-label">

                        Status

                    </label>

                    <select
                        name="status"
                        class="form-select">

                        <option value="">Semua</option>

                        <?php foreach(getAssignmentStatus() as $status): ?>

                            <option
                                value="<?= $status ?>"
                                <?= (($_GET['status'] ?? '') == $status) ? 'selected' : '' ?>>

                                <?= $status ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- Periode -->

                <div class="col-lg-3">

                    <label class="form-label">

                        Periode Audit

                    </label>

                    <select
                        name="period"
                        class="form-select">

                        <option value="">Semua Periode</option>

                        <?php

                        $periods = getAuditPeriods();

                        while($row = $periods->fetch_assoc()):

                        ?>

                            <option
                                value="<?= $row['id'] ?>"
                                <?= (($_GET['period'] ?? '') == $row['id']) ? 'selected' : '' ?>>

                                <?= htmlspecialchars($row['period_name']) ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>

                <!-- Tombol -->

                <div class="col-lg-3">

                    <div class="d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-primary flex-fill">

                            <i class="bi bi-search"></i>

                            Cari

                        </button>

                        <a
                            href="index.php"
                            class="btn btn-outline-secondary">

                            <i class="bi bi-arrow-clockwise"></i>

                        </a>

                    </div>

                </div>

            </div>

        </form>

    </div>

</div>