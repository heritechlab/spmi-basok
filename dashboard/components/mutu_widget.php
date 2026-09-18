<div class="row mt-4">

    <!-- WIDGET 1: KONDISI MUTU INSTITUSI (GRAFIK GARIS 6 KRITERIA) -->
    <div class="col-lg-7 mb-3">
        <div class="card shadow-sm hud-card h-100">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-muted" style="font-size:12px; text-transform:uppercase; letter-spacing:0.8px; font-weight:700;">
                        <i class="bi bi-graph-up"></i> Kondisi Mutu Institusi &mdash; Capaian per Kriteria
                    </div>
                    <div class="text-end">
                        <div style="font-size:22px; font-weight:800; color:<?= $mutuSkorInstitusi !== null ? siquaGetWarnaMutu($mutuSkorInstitusi) : '#9ca3af' ?>;">
                            <?= $mutuSkorInstitusi ?? '-' ?>
                        </div>
                        <div class="text-muted" style="font-size:10.5px;">Rata-rata Keseluruhan</div>
                    </div>
                </div>

                <?php if (empty($mutuKriteriaLabels)): ?>

                    <div class="text-muted text-center py-5">Belum ada data capaian audit untuk Periode ini.</div>

                <?php else: ?>

                    <div style="height:280px;">
                        <canvas id="mutuKriteriaLineChart"
                            data-labels='<?= json_encode($mutuKriteriaLabels) ?>'
                            data-scores='<?= json_encode($mutuKriteriaScores) ?>'></canvas>
                    </div>

                    <div class="mt-3 p-3 rounded d-flex align-items-start gap-2"
                        style="background:<?= empty($mutuKriteriaLemah) ? '#22c55e' : '#ef4444' ?>0d; border:1px solid <?= empty($mutuKriteriaLemah) ? '#22c55e' : '#ef4444' ?>33;">

                        <?php if (empty($mutuKriteriaLemah)): ?>

                            <i class="bi bi-check-circle-fill" style="color:#22c55e; font-size:17px; margin-top:1px;"></i>
                            <span style="font-size:12.5px; color:#3a3348;">
                                Seluruh Kriteria berada pada kategori aman (skor &ge; 2) untuk Periode
                                <strong><?= htmlspecialchars($mutuPeriodName ?? '-') ?></strong>.
                            </span>

                        <?php else: ?>

                            <i class="bi bi-exclamation-triangle-fill" style="color:#ef4444; font-size:17px; margin-top:1px;"></i>
                            <span style="font-size:12.5px; color:#3a3348;">
                                Peringatan &mdash; pada Periode <strong><?= htmlspecialchars($mutuPeriodName ?? '-') ?></strong>,
                                Kriteria berikut berada pada kategori <strong>Sangat Kurang Baik</strong> (skor &lt; 2) dan perlu
                                perhatian segera:
                                <?php foreach ($mutuKriteriaLemah as $i => $l): ?>
                                    <strong><?= htmlspecialchars($l['label']) ?> (<?= $l['skor'] ?>)</strong><?= $i < count($mutuKriteriaLemah) - 1 ? ', ' : '' ?>
                                <?php endforeach; ?>.
                            </span>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>

     <!-- WIDGET BARU: CAPAIAN IKU DIKTI - TARGET vs REALISASI -->
    <div class="col-lg-5 mb-3">
        <div class="card shadow-sm hud-card h-100">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-muted" style="font-size:12px; text-transform:uppercase; letter-spacing:0.8px; font-weight:700;">
                        <i class="bi bi-bullseye"></i> Capaian IKU DIKTI &mdash; <?= $mutuTahunAktif ?? '-' ?>
                    </div>
                </div>

                <?php if (empty($ikuComparisonList)): ?>

                    <div class="text-muted text-center py-4">Belum ada data Periode Aktif.</div>

                <?php else: ?>

                    <?php
                        $totalIkuTercapai = count(array_filter($ikuComparisonList, fn($i) => $i['tercapai'] === true));
                        $totalIkuBelum = count(array_filter($ikuComparisonList, fn($i) => $i['tercapai'] === false));
                        $totalIkuKosong = count(array_filter($ikuComparisonList, fn($i) => $i['tercapai'] === null));
                    ?>

                    <div class="d-flex gap-2 mb-3">
                        <div class="flex-fill text-center py-2 rounded" style="background:#22c55e0d; border:1px solid #22c55e33;">
                            <div style="font-size:18px; font-weight:800; color:#22c55e;"><?= $totalIkuTercapai ?></div>
                            <div class="text-muted" style="font-size:9.5px; text-transform:uppercase; letter-spacing:0.3px;">Tercapai</div>
                        </div>
                        <div class="flex-fill text-center py-2 rounded" style="background:#ef44440d; border:1px solid #ef444433;">
                            <div style="font-size:18px; font-weight:800; color:#ef4444;"><?= $totalIkuBelum ?></div>
                            <div class="text-muted" style="font-size:9.5px; text-transform:uppercase; letter-spacing:0.3px;">Belum Tercapai</div>
                        </div>
                        <div class="flex-fill text-center py-2 rounded" style="background:#f1f0f8; border:1px solid #e5e0f5;">
                            <div style="font-size:18px; font-weight:800; color:#8b8398;"><?= $totalIkuKosong ?></div>
                            <div class="text-muted" style="font-size:9.5px; text-transform:uppercase; letter-spacing:0.3px;">Belum Ada Data</div>
                        </div>
                    </div>

                    <div class="iku-scroll-list" style="max-height:260px; overflow-y:auto; padding-right:4px;">
                        <?php foreach ($ikuComparisonList as $iku): ?>

                            <?php
                                $statusIcon = 'bi-dash-circle-fill';
                                $statusColor = '#9ca3af';
                                $statusBg = '#f1f0f8';

                                if ($iku['tercapai'] === true) {
                                    $statusIcon = 'bi-check-circle-fill';
                                    $statusColor = '#22c55e';
                                    $statusBg = '#22c55e0d';
                                } elseif ($iku['tercapai'] === false) {
                                    $statusIcon = 'bi-x-circle-fill';
                                    $statusColor = '#ef4444';
                                    $statusBg = '#ef44440d';
                                }
                            ?>

                            <div class="d-flex align-items-center gap-2 p-2 rounded mb-2" style="background:<?= $statusBg ?>; border:1px solid <?= $statusColor ?>22; transition:transform 0.15s ease;">

                                <div class="d-flex align-items-center justify-content-center flex-shrink-0" style="width:30px; height:30px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                                    <i class="bi <?= $statusIcon ?>" style="color:<?= $statusColor ?>; font-size:15px;"></i>
                                </div>

                                <div class="flex-grow-1" style="min-width:0;">
                                    <div class="text-truncate" style="font-size:11.5px; font-weight:700; color:#3a3348;" title="<?= htmlspecialchars($iku['name']) ?>">
                                        <span style="color:#7c3aed;"><?= htmlspecialchars($iku['code']) ?></span> &mdash; <?= htmlspecialchars($iku['name']) ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mt-1" style="font-size:10px;">
                                        <span class="text-muted">Target: <strong style="color:#3a3348;"><?= $iku['target'] !== null ? htmlspecialchars($iku['target']) : '-' ?></strong></span>
                                        <i class="bi bi-arrow-right text-muted" style="font-size:9px;"></i>
                                        <span class="text-muted">
                                            Realisasi<?= $iku['realisasi_tw'] ? ' (' . $iku['realisasi_tw'] . ')' : '' ?>:
                                            <strong style="color:<?= $statusColor ?>;"><?= $iku['realisasi'] !== null ? htmlspecialchars($iku['realisasi']) : '-' ?></strong>
                                        </span>
                                        <?php if ($iku['satuan']): ?>
                                            <span class="text-muted" style="font-size:9px;"><?= htmlspecialchars($iku['satuan']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            </div>

                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- WIDGET 2: PERBANDINGAN MUTU ANTAR PRODI (SPIDERWEB) -->
    <div class="col-12 mb-3">
        <div class="card shadow-sm hud-card">
            <div class="card-body">

                <div class="mb-3 text-muted" style="font-size:11.5px; text-transform:uppercase; letter-spacing:0.8px; font-weight:700;">
                    <i class="bi bi-diagram-3"></i> Perbandingan Mutu Antar Program Studi
                </div>

                <?php if (empty($mutuProdiSpiderDatasets)): ?>

                    <div class="text-muted text-center py-4">Belum ada data Program Studi yang dinilai.</div>

                <?php else: ?>

                    <div class="row align-items-center">

                        <div class="col-lg-7">
                            <div style="height:340px; background:radial-gradient(circle at center, #f8f6ff 0%, #f3f0fb 70%, #eee9f9 100%); border-radius:16px; padding:12px; border:1px solid #ece5fb;">
                                <canvas id="mutuProdiSpiderChart"
                                    data-labels='<?= json_encode($mutuProdiSpiderLabels) ?>'
                                    data-datasets='<?= json_encode($mutuProdiSpiderDatasets) ?>'></canvas>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <?php foreach ($mutuProdiSpiderDatasets as $ds): ?>
                                    <span class="d-inline-flex align-items-center gap-1" style="font-size:11.5px; color:#3a3348;">
                                        <span style="display:inline-block; width:9px; height:9px; border-radius:50%; background:<?= $ds['color'] ?>;"></span>
                                        <?= htmlspecialchars($ds['label']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>

                            <?php if (!empty($mutuProdiTerendah)): ?>
                                <?php $skorTerendahAngka = (float) $mutuProdiTerendah[0]['skor']; ?>

                                <?php if ($skorTerendahAngka < 3): ?>

                                    <div class="p-3 rounded" style="background:#ef44440d; border:1px solid #ef444433;">
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="bi bi-exclamation-triangle-fill" style="color:#ef4444; font-size:16px; margin-top:1px;"></i>
                                            <div style="font-size:12.5px; color:#3a3348;">
                                                Program Studi dengan mutu terendah saat ini adalah
                                                <strong><?= htmlspecialchars($mutuProdiTerendah[0]['name']) ?></strong>
                                                dengan skor <strong><?= $mutuProdiTerendah[0]['skor'] ?></strong>.
                                                <?php if ($mutuProdiTerendahStandar): ?>
                                                    Perhatian utama perlu diberikan pada
                                                    <strong><?= htmlspecialchars($mutuProdiTerendahStandar['name']) ?></strong>
                                                    (skor <strong><?= $mutuProdiTerendahStandar['skor'] ?></strong>), yang menjadi
                                                    titik terlemah pada Program Studi ini.
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                <?php else: ?>

                                    <div class="p-3 rounded" style="background:#22c55e0d; border:1px solid #22c55e33;">
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="bi bi-check-circle-fill" style="color:#22c55e; font-size:16px; margin-top:1px;"></i>
                                            <div style="font-size:12.5px; color:#3a3348;">
                                                Seluruh Program Studi yang tercatat menunjukkan capaian mutu pada kategori
                                                Baik hingga Sangat Baik pada Periode ini. Tidak ada Program Studi dengan
                                                capaian di bawah kategori Baik saat ini.
                                            </div>
                                        </div>
                                    </div>

                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

        document.querySelectorAll('.hud-ring-progress, .iku-ring-progress').forEach(function (el) {
        const width = el.getAttribute('data-width') || 0;
        setTimeout(function () {
            el.style.width = width + '%';
        }, 200);
    });

    const mutuChartCanvas = document.getElementById('mutuKriteriaLineChart');

    if (mutuChartCanvas) {

        const labels = JSON.parse(mutuChartCanvas.dataset.labels || '[]');
        const scores = JSON.parse(mutuChartCanvas.dataset.scores || '[]');

        const pointColors = scores.map(function (s) {
            if (s === null) return '#9ca3af';
            if (s >= 4) return '#22c55e';
            if (s >= 3) return '#84cc16';
            if (s >= 2) return '#f59e0b';
            return '#ef4444';
        });

        const ctx = mutuChartCanvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, 'rgba(124, 58, 237, 0.32)');
        gradient.addColorStop(0.35, 'rgba(124, 58, 237, 0.16)');
        gradient.addColorStop(0.7, 'rgba(124, 58, 237, 0.05)');
        gradient.addColorStop(1, 'rgba(124, 58, 237, 0.00)');

        const glowLinePlugin = {
            id: 'glowLinePlugin',
            beforeDatasetsDraw(chart) {
                const { ctx } = chart;
                ctx.save();
                ctx.shadowColor = 'rgba(124, 58, 237, 0.45)';
                ctx.shadowBlur = 12;
                ctx.shadowOffsetY = 3;
            },
            afterDatasetsDraw(chart) {
                chart.ctx.restore();
            },
        };

        const thresholdLinePlugin = {
            id: 'thresholdLinePlugin',
            afterDraw(chart) {
                const { ctx, scales } = chart;
                const yScale = scales.y;
                const yPixel = yScale.getPixelForValue(2);

                ctx.save();
                ctx.setLineDash([5, 5]);
                ctx.strokeStyle = 'rgba(239, 68, 68, 0.35)';
                ctx.lineWidth = 1.5;
                ctx.beginPath();
                ctx.moveTo(chart.chartArea.left, yPixel);
                ctx.lineTo(chart.chartArea.right, yPixel);
                ctx.stroke();

                ctx.setLineDash([]);
                ctx.fillStyle = 'rgba(239, 68, 68, 0.6)';
                ctx.font = '10px sans-serif';
                ctx.fillText('Ambang Batas Aman', chart.chartArea.left + 4, yPixel - 6);
                ctx.restore();
            },
        };

        window.mutuLineChartInstance = new Chart(mutuChartCanvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Skor Capaian',
                    data: scores,
                    borderColor: '#7c3aed',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.45,
                    cubicInterpolationMode: 'monotone',
                    pointBackgroundColor: pointColors,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2.5,
                    pointRadius: 6,
                    pointHoverRadius: 9,
                    pointHoverBorderWidth: 3,
                    spanGaps: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 20, bottom: 4 } },
                scales: {
                    y: {
                        min: 0, max: 4,
                        ticks: { stepSize: 1, font: { size: 11 } },
                        grid: { color: 'rgba(0,0,0,0.04)', drawTicks: false },
                        border: { display: false },
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } },
                        border: { display: false },
                    },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        padding: 10,
                        cornerRadius: 8,
                        titleFont: { size: 12, weight: '600' },
                        bodyFont: { size: 12 },
                        callbacks: {
                            label: function (ctx) {
                                return ctx.raw === null ? 'Belum ada data' : 'Skor: ' + ctx.raw;
                            }
                        }
                    }
                },
            },
            plugins: [glowLinePlugin, thresholdLinePlugin],
        });
    }

    const prodiSpiderCanvas = document.getElementById('mutuProdiSpiderChart');

    if (prodiSpiderCanvas) {

        const spiderLabels = JSON.parse(prodiSpiderCanvas.dataset.labels || '[]');
        const spiderDatasets = JSON.parse(prodiSpiderCanvas.dataset.datasets || '[]');

        const spiderGlowPlugin = {
            id: 'spiderGlowPlugin',
            beforeDatasetsDraw(chart) {
                const { ctx } = chart;
                ctx.save();
                ctx.shadowBlur = 8;
                ctx.shadowOffsetY = 2;
            },
            afterDatasetsDraw(chart) {
                chart.ctx.restore();
            },
        };

        window.mutuSpiderChartInstance = new Chart(prodiSpiderCanvas, {
            type: 'radar',
            data: {
                labels: spiderLabels,
                datasets: spiderDatasets.map(function (ds) {
                    return {
                        label: ds.label,
                        data: ds.data,
                        borderColor: ds.color,
                        backgroundColor: ds.color + '0d',
                        borderWidth: 2.5,
                        pointBackgroundColor: ds.color,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 1.5,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointHoverBorderWidth: 2,
                        tension: 0.15,
                        spanGaps: true,
                    };
                }),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1200,
                    easing: 'easeOutQuart',
                },
                scales: {
                    r: {
                        min: 0, max: 4,
                        ticks: {
                            stepSize: 1,
                            font: { size: 10 },
                            backdropColor: 'rgba(255,255,255,0.85)',
                            backdropPadding: 3,
                        },
                        pointLabels: {
                            font: { size: 11, weight: '600' },
                            color: '#3a3348',
                        },
                        grid: { color: 'rgba(124, 58, 237, 0.22)' },
                        angleLines: { color: 'rgba(124, 58, 237, 0.25)' },
                    },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        padding: 10,
                        cornerRadius: 8,
                        titleFont: { size: 12, weight: '600' },
                        bodyFont: { size: 12 },
                        callbacks: {
                            label: function (ctx) {
                                return ctx.dataset.label + ': ' + (ctx.raw === null ? 'Belum ada data' : ctx.raw);
                            }
                        }
                    },
                },
            },
            plugins: [spiderGlowPlugin],
        });
    }


    let mutuResizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(mutuResizeTimer);
        mutuResizeTimer = setTimeout(function () {
            if (window.mutuLineChartInstance) window.mutuLineChartInstance.resize();
            if (window.mutuSpiderChartInstance) window.mutuSpiderChartInstance.resize();
        }, 150);
    });

});
</script>