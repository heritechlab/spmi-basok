<div class="col-12 mb-2">

    <div class="widget hud-card" style="padding:20px;">

        <div class="widget-header mb-4">

            <h4 style="font-size:13px; text-transform:uppercase; letter-spacing:0.6px; font-weight:700; color:#3a3348;">
                <i class="bi bi-bullseye"></i>
                Profil Capaian Standar Mutu
            </h4>

            <small class="text-muted">
                Skor rata-rata capaian AMI per Standar (skala 1-4) &mdash; Periode: <?= htmlspecialchars($mutuPeriodName ?? '-') ?>
            </small>

        </div>

        <?php if (empty($standardScores)): ?>

            <p class="text-muted mb-0">Belum ada data hasil audit untuk ditampilkan pada Periode ini.</p>

        <?php else: ?>

            <div class="row align-items-start">

                <div class="col-lg-8">
                    <div style="width: 100%; max-width: 550px; height: 400px; margin: 0 auto;">
                        <canvas
                            id="standardRadarChart"
                            data-labels='<?= json_encode(array_column($standardScores, "label")) ?>'
                            data-scores='<?= json_encode(array_column($standardScores, "score")) ?>'>
                        </canvas>
                    </div>
                </div>

                <div class="col-lg-4">

                    <div class="mb-2 text-muted" style="font-size:11.5px; text-transform:uppercase; letter-spacing:0.5px; font-weight:700;">
                        <i class="bi bi-exclamation-triangle"></i> Standar Perlu Perhatian
                    </div>

                    <?php if (empty($standardScoresLemah)): ?>

                        <div class="p-3 rounded" style="background:#22c55e0d; border:1px solid #22c55e33;">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-check-circle-fill" style="color:#22c55e; font-size:16px; margin-top:1px;"></i>
                                <span style="font-size:12.5px; color:#3a3348;">
                                    Seluruh Standar berada pada kategori aman (skor &ge; 2).
                                </span>
                            </div>
                        </div>

                    <?php else: ?>

                        <?php foreach (array_slice($standardScoresLemah, 0, 6) as $s): ?>
                            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px dashed #eee;">
                                <span style="font-size:12px; color:#3a3348;"><?= htmlspecialchars($s['label']) ?></span>
                                <span class="hud-badge" style="background:#ef44441a; color:#ef4444;">
                                    <?= $s['skor'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>

                        <?php if (count($standardScoresLemah) > 6): ?>
                            <div class="text-muted text-center mt-2" style="font-size:11px;">
                                +<?= count($standardScoresLemah) - 6 ?> Standar lainnya juga perlu perhatian
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    </div>

</div>