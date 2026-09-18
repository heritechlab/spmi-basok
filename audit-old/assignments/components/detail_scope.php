<div class="card border-0 shadow-sm h-100">

    <div class="card-header bg-white">

        <h5 class="mb-0 fw-bold">
            <i class="bi bi-bullseye text-primary me-2"></i>
            Scope Audit
        </h5>

    </div>

    <div class="card-body">

        <!-- Tujuan Audit -->

        <div class="mb-4">

            <label class="text-muted small fw-semibold">
                Tujuan Audit
            </label>

            <div class="border rounded p-3 bg-light mt-2">

                <?= !empty($assignment['objective'])
                        ? nl2br(htmlspecialchars($assignment['objective']))
                        : '<span class="text-muted">Belum ditentukan</span>'; ?>

            </div>

        </div>

        <!-- Ruang Lingkup -->

        <div class="mb-4">

            <label class="text-muted small fw-semibold">
                Ruang Lingkup Audit
            </label>

            <div class="border rounded p-3 bg-light mt-2">

                <?= !empty($assignment['scope'])
                        ? nl2br(htmlspecialchars($assignment['scope']))
                        : '<span class="text-muted">Belum ditentukan</span>'; ?>

            </div>

        </div>

        <!-- Kriteria -->

        <div class="mb-4">

            <label class="text-muted small fw-semibold">
                Kriteria Audit
            </label>

            <div class="border rounded p-3 bg-light mt-2">

                <?= !empty($assignment['criteria'])
                        ? nl2br(htmlspecialchars($assignment['criteria']))
                        : '<span class="text-muted">Belum ditentukan</span>'; ?>

            </div>

        </div>

        <!-- Risk Level -->

        <div class="row">

            <div class="col-md-6">

                <label class="text-muted small fw-semibold">
                    Tingkat Risiko
                </label>

                <div class="mt-2">

                    <?php

                    switch($assignment['risk_level']){

                        case 'Tinggi':
                            echo '<span class="badge bg-danger px-3 py-2">Tinggi</span>';
                            break;

                        case 'Sedang':
                            echo '<span class="badge bg-warning text-dark px-3 py-2">Sedang</span>';
                            break;

                        case 'Rendah':
                            echo '<span class="badge bg-success px-3 py-2">Rendah</span>';
                            break;

                        default:
                            echo '<span class="badge bg-secondary px-3 py-2">-</span>';

                    }

                    ?>

                </div>

            </div>

            <div class="col-md-6">

                <label class="text-muted small fw-semibold">
                    Metode Audit
                </label>

                <div class="mt-2">

                    <span class="badge bg-primary px-3 py-2">

                        <?= htmlspecialchars($assignment['audit_method']) ?>

                    </span>

                </div>

            </div>

        </div>

    </div>

</div>