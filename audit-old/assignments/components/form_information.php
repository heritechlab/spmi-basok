<!-- ==========================================================
INFORMASI PENUGASAN
========================================================== -->

<div class="card border-0 shadow-sm mb-4">

    <div class="card-header bg-white">

        <h5 class="mb-0 fw-bold">

            <i class="bi bi-file-earmark-text text-primary me-2"></i>

            Informasi Penugasan Audit

        </h5>

    </div>

    <div class="card-body">

        <div class="row g-4">

            <!-- Nomor Assignment -->

            <div class="col-md-4">

                <label class="form-label fw-semibold">

                    Nomor Assignment

                </label>

                <input
                    type="text"
                    name="assignment_number"
                    class="form-control"
                    value="<?= generateAssignmentNumber(); ?>"
                    readonly>

            </div>

            <!-- Kode Audit -->

            <div class="col-md-4">

                <label class="form-label fw-semibold">

                    Kode Audit

                </label>

                <input
                    type="text"
                    name="audit_code"
                    class="form-control"
                    placeholder="Contoh : AMI-2026-001">

            </div>

            <!-- Jenis Audit -->

            <div class="col-md-4">

                <label class="form-label fw-semibold">

                    Jenis Audit

                </label>

                <select
                    name="audit_type"
                    class="form-select">

                    <option value="AMI">Audit Mutu Internal</option>

                    <option value="Audit Internal">
                        Audit Internal
                    </option>

                    <option value="Audit Eksternal">
                        Audit Eksternal
                    </option>

                </select>

            </div>

            <!-- Periode -->

            <div class="col-md-6">

                <label class="form-label fw-semibold">

                    Periode Audit

                </label>

                <select
                    name="period_id"
                    class="form-select"
                    required>

                    <option value="">
                        -- Pilih Periode --
                    </option>

                    <?php while($p=$periods->fetch_assoc()): ?>

                        <option value="<?= $p['id'] ?>">

                            <?= htmlspecialchars($p['period_name']) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <!-- Auditee -->

            <div class="col-md-6">

                <label class="form-label fw-semibold">

                    Auditee

                </label>

                <select
                    name="auditee_id"
                    class="form-select"
                    required>

                    <option value="">
                        -- Pilih Auditee --
                    </option>

                    <?php while($a=$auditees->fetch_assoc()): ?>

                        <option value="<?= $a['id'] ?>">

                            <?= htmlspecialchars($a['name']) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <!-- Level Risiko -->

            <div class="col-md-4">

                <label class="form-label fw-semibold">

                    Level Risiko

                </label>

                <select
                    name="risk_level"
                    class="form-select">

                    <option value="Rendah">Rendah</option>

                    <option value="Sedang" selected>Sedang</option>

                    <option value="Tinggi">Tinggi</option>

                </select>

            </div>

        </div>

    </div>

</div>