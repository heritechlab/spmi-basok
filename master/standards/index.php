<?php

declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../core/Auth.php';

$config = require __DIR__ . '/config.php';

$repository = new StandardRepository($conn);

$service = new StandardService($repository);

$totalStandar = $repository->countTotal();
$totalSndikti = $repository->countByTypeCode('SNDIKTI');
$totalSpt     = $repository->countByTypeCode('SPT');

require_once __DIR__ . '/../../layouts/app.php';

?>

<style>
    #tableStandards { font-size: 12.5px; border-collapse: separate; border-spacing: 0; }
    #tableStandards thead th {
        background: #f6f3fc; color: #5b21b6; font-weight: 700; font-size: 10.5px;
        text-transform: uppercase; letter-spacing: .5px; padding: 11px 14px;
        border-bottom: 2px solid #ddd0f7; border-top: none; vertical-align: middle;
        position: sticky; top: 0; z-index: 2;
    }
    #tableStandards tbody td {
        padding: 10px 14px; vertical-align: middle; color: #2d2a45; font-weight: 400;
        border-color: #f0eef7;
    }
    #tableStandards tbody tr:nth-child(even) { background: #faf9fd; }
    #tableStandards tbody tr:hover { background: #f1edfc; }
    #tableStandards tbody td:first-child { border-left: 3px solid #ddd0f7; font-weight: 600; color: #14112b; }
    #tableStandards tbody tr:hover td:first-child { border-left-color: #7c3aed; }
    #tableStandards .badge { font-weight: 600; font-size: 10.5px; padding: 4px 10px; border-radius: 20px; }
    #tableStandards .btn-sm { border-radius: 8px; padding: 5px 10px; font-size: 11px; }

    .card:has(#tableStandards) {
        border: 1px solid #eceaf5; border-radius: 14px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(20,17,43,0.04), 0 8px 20px rgba(20,17,43,0.03);
    }
    .card:has(#tableStandards) .card-body { padding: 20px; }

    .card:has(#tableStandards) .form-select,
    .card:has(#tableStandards) .form-control {
        font-size: 12.5px; font-weight: 500; color: #2d2a45;
        border: 1px solid #e2dff2; border-radius: 8px; padding: 8px 12px;
        transition: border-color .15s, box-shadow .15s;
    }
    .card:has(#tableStandards) .form-select:focus,
    .card:has(#tableStandards) .form-control:focus {
        border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.10); outline: none;
    }
    .card:has(#tableStandards) .form-label {
        font-size: 11px; font-weight: 700; color: #6b6785; text-transform: uppercase;
        letter-spacing: .4px; margin-bottom: 6px;
    }
</style>

<div class="container-fluid py-4">

    <!-- ===================================================== -->
    <!-- PAGE HEADER -->
    <!-- ===================================================== -->

<div class="container-fluid">

        <div>

            <h3 class="fw-bold mb-1">

                Data Standar Mutu

            </h3>

            <p class="text-muted mb-0">

                Pengelolaan Standar Nasional Pendidikan Tinggi dan Standar Nasional yang ditetapkan Perguruan Tinggi

            </p>

        </div>

    <div class="mb-4">

<div class="dual-card-row mb-3">

<div class="greeting-card">

            <?php
                $hour = (int) date('H');

                if ($hour < 11) {
                    $greeting = 'Selamat Pagi';
                } elseif ($hour < 15) {
                    $greeting = 'Selamat Siang';
                } elseif ($hour < 18) {
                    $greeting = 'Selamat Sore';
                } else {
                    $greeting = 'Selamat Malam';
                }
            ?>

            <div class="greeting-date">
                <i class="bi bi-calendar3"></i>
                <?php
                    setlocale(LC_TIME, 'id_ID');
                    $bulan = [
                        1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                        7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
                    ];
                    echo date('d') . ' ' . $bulan[(int) date('n')] . ' ' . date('Y');
                ?>
            </div>

            <div class="indicator-summary-title">
                <?= $greeting ?>, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?>!
            </div>

            <div class="indicator-summary-greeting">
                Berikut kondisi terkini Standar Mutu yang dimiliki Institusi Anda.
            </div>

            <img
                src="<?= BASE_URL ?>assets/img/dashboard/hero.png"
                alt="Admin"
                class="standard-summary-hero">

        </div>

<div class="info-card info-card-compact">

            <div class="info-card-header">
                <i class="bi bi-bar-chart-fill"></i>
                Ringkasan Data
            </div>

            <div class="info-card-body">

                <div class="info-card-total">
                    <div class="info-card-total-label">Total Standar</div>
                    <div class="info-card-total-value count-up" data-target="<?= $totalStandar ?>">0</div>
                </div>

                <?php
                    $pctSndikti = $totalStandar > 0 ? round(($totalSndikti / $totalStandar) * 100) : 0;
                    $pctSpt = $totalStandar > 0 ? round(($totalSpt / $totalStandar) * 100) : 0;
                ?>

                <div class="info-card-items-grid info-card-items-grid-2">

                    <div class="info-card-item accent-blue">
                        <div class="info-card-icon">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <div class="info-card-text">
                            <div class="info-card-label">SNDIKTI</div>
                            <div class="info-card-value count-up" data-target="<?= $totalSndikti ?>">0</div>
                        </div>
                    </div>

                    <div class="info-card-item accent-orange">
                        <div class="info-card-icon">
                            <i class="bi bi-bank"></i>
                        </div>
                        <div class="info-card-text">
                            <div class="info-card-label">SPT</div>
                            <div class="info-card-value count-up" data-target="<?= $totalSpt ?>">0</div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="d-flex justify-content-end gap-2 flex-wrap">

            <a href="<?= BASE_URL ?>master/standards/print.php" target="_blank" class="btn btn-outline-dark">
                <i class="bi bi-printer"></i>
                Print
            </a>

            <a href="<?= BASE_URL ?>master/standards/export.php" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel"></i>
                Export
            </a>

            <?php if (Auth::canManage()): ?>

                <a href="<?= BASE_URL ?>master/standards/template.php" class="btn btn-outline-secondary">
                    <i class="bi bi-download"></i>
                    Template
                </a>

                <button class="btn btn-outline-primary" id="btnImportStandard">
                    <i class="bi bi-upload"></i>
                    Import
                </button>

                <input type="file" id="importFile" accept=".csv" style="display:none">

                <button class="btn btn-primary" id="btnAddStandard">
                    <i class="bi bi-plus-circle"></i>
                    Tambah Standar
                </button>

            <?php endif; ?>

        </div>
    <!-- ===================================================== -->
    <!-- CARD -->

        <div class="card shadow-sm">

            <div class="card-body">

 <table
    class="table table-bordered table-hover"
    id="tableStandards">

    <thead>

        <tr>

            <th width="50">No</th>

            <th>Kode</th>

            <th>Nama Standar</th>

            <th>Tanggal Terbit</th>

            <th>Revisi</th>

            <th>Status</th>
            <th width="130">Level Risiko</th>
            <th width="90">Dokumen</th>

            <th width="150">Aksi</th>

        </tr>

    </thead>

    <tbody>

    </tbody>

</table>

            </div>

        </div>

    </div>

</div>

<!-- ========================================================= -->
<!-- MODAL -->
<!-- ========================================================= -->

<?php require_once __DIR__ . '/views/modal.php'; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.count-up').forEach(function (el) {
        const target = parseInt(el.getAttribute('data-target'), 10) || 0;
        let current = 0;
        const step = Math.max(1, Math.ceil(target / 30));
        const timer = setInterval(function () {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            el.textContent = current;
        }, 30);
    });

    document.querySelectorAll('.info-card-progress-fill').forEach(function (el) {
        const pct = el.getAttribute('data-percent') || 0;
        setTimeout(function () {
            el.style.width = pct + '%';
        }, 100);
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<script src="<?= BASE_URL ?>assets/js/standards.js"></script>