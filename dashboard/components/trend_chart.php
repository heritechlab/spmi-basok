<div class="col-lg-6 mb-4">

    <div class="widget hud-card h-100" style="padding:20px;">

        <div class="widget-header mb-4">

            <h4 style="font-size:13px; text-transform:uppercase; letter-spacing:0.6px; font-weight:700; color:#3a3348;">
                <i class="bi bi-graph-up"></i>
                Tren Mutu 3 Tahun Terakhir
            </h4>

                <small class="text-muted">
                    Perbandingan Skor Capaian AMI (skala 1-4) dan Jumlah Temuan &mdash; 3 Periode Terakhir
                </small>

        </div>

        <?php if (empty($trendLabels)): ?>

            <p class="text-muted mb-0">Belum ada data Periode Audit dengan hasil LKA untuk ditampilkan.</p>

        <?php else: ?>

            <canvas
                id="trendChart"
                data-labels='<?= json_encode($trendLabels) ?>'
                data-scores='<?= json_encode($trendScores) ?>'
                data-findings='<?= json_encode($trendFindings) ?>'
                height="90">
            </canvas>

        <?php endif; ?>

    </div>

</div>