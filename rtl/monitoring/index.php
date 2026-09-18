<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';
require_once __DIR__ . '/../../master/periods/repository.php';

$config = require __DIR__ . '/config.php';

$periodRepo = new PeriodRepository($conn);
$periods = $periodRepo->getAll('', '', 100, 0);

require_once __DIR__ . '/../../layouts/app.php';

?>

<style>
    #tableMonitoringUnit { font-size: 12.5px; border-collapse: separate; border-spacing: 0; }
    #tableMonitoringUnit thead th {
        background: #f6f3fc; color: #5b21b6; font-weight: 700; font-size: 10.5px;
        text-transform: uppercase; letter-spacing: .5px; padding: 11px 14px;
        border-bottom: 2px solid #ddd0f7; border-top: none; vertical-align: middle;
    }
    #tableMonitoringUnit tbody td {
        padding: 12px 14px; vertical-align: middle; color: #2d2a45; font-weight: 400;
        border-color: #f0eef7;
    }
    #tableMonitoringUnit tbody tr:nth-child(even) { background: #faf9fd; }
    #tableMonitoringUnit tbody tr:hover { background: #f1edfc; }
    #tableMonitoringUnit tbody td:first-child { border-left: 3px solid #ddd0f7; font-weight: 600; color: #14112b; }
    #tableMonitoringUnit tbody tr:hover td:first-child { border-left-color: #7c3aed; }
    #tableMonitoringUnit .progress { height: 10px !important; border-radius: 20px; background: #f3f1f9; overflow: hidden; }
    #tableMonitoringUnit .progress-bar { border-radius: 20px; }

    .card:has(#tableMonitoringUnit) {
        border: 1px solid #eceaf5; border-radius: 14px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(20,17,43,0.04), 0 8px 20px rgba(20,17,43,0.03);
    }
    .card:has(#tableMonitoringUnit) .card-header {
        background: #faf9fd; border-bottom: 1px solid #eceaf5; padding: 14px 20px;
    }
    .card:has(#tableMonitoringUnit) .card-header strong {
        font-size: 13.5px; font-weight: 700; color: #14112b; letter-spacing: -.1px;
    }
    .card:has(#tableMonitoringUnit) .card-body { padding: 20px; }
    .segbar { display: flex; height: 8px; border-radius: 20px; overflow: hidden; background: #f3f1f9; margin-bottom: 8px; }
    .segbar-fill { transition: width .3s; }
    .segbar-legend { display: flex; gap: 10px; flex-wrap: wrap; }
    .segbar-legend span { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; color: #6b6785; font-weight: 600; }
    .segbar-legend span i { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
</style>

<div class="container-fluid py-4">

<h4 class="mb-3">Monitoring RTL</h4>

<?php require_once __DIR__ . '/../tabs.php'; ?>


<div class="dual-card-row mb-3">

        <div class="greeting-card">

            <?php
                $hour = (int) date('H');
                if ($hour < 11) { $greeting = 'Selamat Pagi'; }
                elseif ($hour < 15) { $greeting = 'Selamat Siang'; }
                elseif ($hour < 18) { $greeting = 'Selamat Sore'; }
                else { $greeting = 'Selamat Malam'; }
            ?>

            <div class="greeting-date">
                <i class="bi bi-calendar3"></i>
                <?php
                    $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                    echo date('d') . ' ' . $bulan[(int) date('n')] . ' ' . date('Y');
                ?>
            </div>

            <div class="indicator-summary-title">
                <?= $greeting ?>, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?>!
            </div>

            <div class="indicator-summary-greeting">
                Pantau progres Rencana Tindak Lanjut (RTL) di seluruh Unit Kerja secara transparan.
            </div>

            <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Admin" class="standard-summary-hero">

        </div>

        <div class="info-card info-card-compact">

            <div class="info-card-header">
                <i class="bi bi-bar-chart-fill"></i>
                Ringkasan Keseluruhan
            </div>

            <div class="info-card-body">

                <div class="info-card-total">
                    <div class="info-card-total-label">Total RTL</div>
                    <div class="info-card-total-value" id="overallTotal">0</div>
                </div>

                <div style="max-width: 280px; margin: 8px auto 0;">
                    <canvas id="chartOverallStatus" width="280" height="180"></canvas>
                </div>

            </div>

        </div>

    </div>
    
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Periode Audit</label>
                    <select id="filterPeriod" class="form-select">
                        <option value="">Semua Periode</option>
                        <?php foreach ($periods as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['period_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">

        <div class="card-header">
            <strong>Progres per Unit Kerja</strong>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="tableMonitoringUnit">
                    <thead>
                        <tr>
                            <th>Unit Kerja</th>
                            <th width="80">Total</th>
                            <th width="220">Progres Pelaksanaan</th>
                            <th width="220">Progres Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody id="unitTableBody">
                        <tr><td colspan="4" class="text-muted">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= BASE_URL ?>assets/js/rtm_monitoring.js"></script>