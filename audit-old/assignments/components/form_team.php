<!-- ==========================================================
TIM AUDIT
========================================================== -->

<div class="card border-0 shadow-sm mb-4">

    <div class="card-header bg-white">

        <h5 class="mb-0 fw-bold">

            <i class="bi bi-people text-success me-2"></i>

            Tim Auditor

        </h5>

    </div>

    <div class="card-body">

        <div class="row g-4">

            <!-- Lead Auditor -->

            <div class="col-md-6">

                <label class="form-label fw-semibold">

                    Lead Auditor

                </label>

                <select
                    name="lead_auditor"
                    class="form-select"
                    required>

                    <option value="">-- Pilih Lead Auditor --</option>

                    <?php while($a = $auditors->fetch_assoc()): ?>

                        <option value="<?= $a['id']; ?>">

                            <?= htmlspecialchars($a['full_name']); ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <!-- Koordinator -->

            <div class="col-md-6">

                <label class="form-label fw-semibold">

                    Koordinator Audit

                </label>

                <select
                    name="coordinator"
                    class="form-select">

                    <option value="">-- Pilih Koordinator --</option>

                    <?php while($c = $coordinators->fetch_assoc()): ?>

                        <option value="<?= $c['id']; ?>">

                            <?= htmlspecialchars($c['full_name']); ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

        </div>

    </div>

</div>