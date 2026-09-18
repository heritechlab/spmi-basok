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

/*
|--------------------------------------------------------------------------
| Repository & Service
|--------------------------------------------------------------------------
*/

$repository = new IndicatorRepository($conn);

$service = new IndicatorService($repository);

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

$statistics = $service->statistics();

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

require_once __DIR__.'/../../layouts/app.php';

?>

<style>
    #indicatorTable { font-size: 12.5px; border-collapse: separate; border-spacing: 0; }
    #indicatorTable thead th {
        background: #f6f3fc; color: #5b21b6; font-weight: 700; font-size: 10.5px;
        text-transform: uppercase; letter-spacing: .5px; padding: 11px 14px;
        border-bottom: 2px solid #ddd0f7; border-top: none; vertical-align: middle;
        position: sticky; top: 0; z-index: 2;
    }
    #indicatorTable tbody td {
        padding: 10px 14px; vertical-align: middle; color: #2d2a45; font-weight: 400;
        border-color: #f0eef7;
    }
    #indicatorTable tbody tr:nth-child(even) { background: #faf9fd; }
    #indicatorTable tbody tr:hover { background: #f1edfc; }
    #indicatorTable tbody td:first-child { border-left: 3px solid #ddd0f7; font-weight: 600; color: #14112b; }
    #indicatorTable tbody tr:hover td:first-child { border-left-color: #7c3aed; }
    #indicatorTable .badge { font-weight: 600; font-size: 10.5px; padding: 4px 10px; border-radius: 20px; }
    #indicatorTable .btn-sm { border-radius: 8px; padding: 5px 10px; font-size: 11px; }

    .card:has(#indicatorTable) {
        border: 1px solid #eceaf5; border-radius: 14px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(20,17,43,0.04), 0 8px 20px rgba(20,17,43,0.03);
    }
    .card:has(#indicatorTable) .card-body { padding: 20px; }

    .card:has(#indicatorTable) .form-select,
    .card:has(#indicatorTable) .form-control {
        font-size: 12.5px; font-weight: 500; color: #2d2a45;
        border: 1px solid #e2dff2; border-radius: 8px; padding: 8px 12px;
        transition: border-color .15s, box-shadow .15s;
    }
    .card:has(#indicatorTable) .form-select:focus,
    .card:has(#indicatorTable) .form-control:focus {
        border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.10); outline: none;
    }
    .card:has(#indicatorTable) .form-label {
        font-size: 11px; font-weight: 700; color: #6b6785; text-transform: uppercase;
        letter-spacing: .4px; margin-bottom: 6px;
    }
</style>

<div class="container-fluid py-4">

        <div class="container-fluid">

            <h3 class="fw-bold mb-1">
                <?= htmlspecialchars($config['title']) ?>
            </h3>

            <p class="text-muted mb-4">
                <?= htmlspecialchars($config['subtitle']) ?>
            </p>

            <div class="row mb-4">

                <div class="col-12">
                    <div class="dual-card-row mb-3">

        <div class="greeting-card" style="flex: 0 0 68%;">

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
                Berikut kondisi terkini Indikator Mutu yang dimiliki Institusi anda.
            </div>

            <img
                src="<?= BASE_URL ?>assets/img/dashboard/hero.png"
                alt="Admin"
                class="indicator-summary-hero">

        </div>

        <div class="info-card info-card-compact">

            <div class="info-card-header">
                <i class="bi bi-bar-chart-fill"></i>
                Ringkasan Data
            </div>

            <div class="info-card-body">

                <div class="info-card-total">
                    <div class="info-card-total-label">Total Indikator</div>
                    <div class="info-card-total-value count-up" data-target="<?= $statistics['data']['active'] ?? 0 ?>">0</div>
                </div>

                <?php
                    $totalInd = $statistics['data']['active'] ?? 0;
                    $pctWajib = $totalInd > 0 ? round((($statistics['data']['iku_wajib'] ?? 0) / $totalInd) * 100) : 0;
                    $pctPilihan = $totalInd > 0 ? round((($statistics['data']['iku_pilihan'] ?? 0) / $totalInd) * 100) : 0;
                    $pctPt = $totalInd > 0 ? round((($statistics['data']['iku_pt'] ?? 0) / $totalInd) * 100) : 0;
                    $pctIkt = $totalInd > 0 ? round((($statistics['data']['ikt'] ?? 0) / $totalInd) * 100) : 0;
                ?>
            
            <div class="info-card-items-grid">
                <div class="info-card-item accent-blue">
                    <div class="info-card-icon">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <div class="info-card-text">
                        <div class="info-card-label">IKU Wajib</div>
                        <div class="info-card-value count-up" data-target="<?= $statistics['data']['iku_wajib'] ?? 0 ?>">0</div>
                        <div class="info-card-progress-track">
                            <div class="info-card-progress-fill" data-percent="<?= $pctWajib ?>"></div>
                        </div>
                    </div>
                </div>

                <div class="info-card-item accent-green">
                    <div class="info-card-icon">
                        <i class="bi bi-stars"></i>
                    </div>
                    <div class="info-card-text">
                        <div class="info-card-label">IKU Pilihan</div>
                        <div class="info-card-value count-up" data-target="<?= $statistics['data']['iku_pilihan'] ?? 0 ?>">0</div>
                        <div class="info-card-progress-track">
                            <div class="info-card-progress-fill" data-percent="<?= $pctPilihan ?>"></div>
                        </div>
                    </div>
                </div>

                <div class="info-card-item accent-orange">
                    <div class="info-card-icon">
                        <i class="bi bi-building"></i>
                    </div>
                    <div class="info-card-text">
                        <div class="info-card-label">IKU PT</div>
                        <div class="info-card-value count-up" data-target="<?= $statistics['data']['iku_pt'] ?? 0 ?>">0</div>
                        <div class="info-card-progress-track">
                            <div class="info-card-progress-fill" data-percent="<?= $pctPt ?>"></div>
                        </div>
                    </div>
                </div>

                <div class="info-card-item accent-purple">
                    <div class="info-card-icon">
                        <i class="bi bi-flag-fill"></i>
                    </div>
                    <div class="info-card-text">
                        <div class="info-card-label">IKT</div>
                        <div class="info-card-value count-up" data-target="<?= $statistics['data']['ikt'] ?? 0 ?>">0</div>
                        <div class="info-card-progress-track">
                            <div class="info-card-progress-fill" data-percent="<?= $pctIkt ?>"></div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>



                </div>

            </div>

            <div class="card mt-4 shadow-sm">

                    <div class="card-header d-flex justify-content-between align-items-center">

                        <strong>Data Master Indikator</strong>

                    </div>

                    <div class="card-body">

                     <div class="row mb-4 align-items-end">

                            <div class="col-md-4">

                                <label class="form-label">

                                    Filter Standar

                                </label>

                                <select
                                    id="filterStandard"
                                    class="form-select">

                                    <option value="">
                                        Semua Standar
                                    </option>

                                    <?php

                                    foreach ($repository->getStandards() as $row):

                                    ?>

                                        <option
                                            value="<?= $row['id']; ?>">

                                            <?= htmlspecialchars($row['code']); ?>

                                            -

                                            <?= htmlspecialchars($row['name']); ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <div class="col-md-3">

                                <label class="form-label">

                                    Jenis Indikator

                                </label>

                                <select
                                    id="filterType"
                                    class="form-select">

                                    <option value="">
                                        Semua Jenis
                                    </option>

                                    <option value="IKU Wajib">IKU Wajib</option>
                                    <option value="IKU Pilihan">IKU Pilihan</option>
                                    <option value="IKU PT">IKU PT</option>
                                    <option value="IKT">IKT</option>

                                </select>

                            </div>

                            <div class="col-md-2">

                                <button
                                    id="btnResetFilter"
                                    class="btn btn-secondary w-100">

                                    <i class="bi bi-arrow-clockwise"></i>

                                    Reset

                                </button>

                            </div>

                            <div class="col-md-3 text-end">
                                 
                                <a
                                    href="<?= BASE_URL ?>master/indicators/print.php"
                                    target="_blank"
                                    class="btn btn-outline-dark me-1">

                                    <i class="bi bi-printer"></i>

                                </a>

                                <a
                                    href="<?= Auth::canManage() ? BASE_URL . 'master/indicators/create.php' : '#' ?>"
                                    class="btn btn-primary <?= Auth::canManage() ? '' : 'btn-locked' ?>"
                                    <?= Auth::canManage() ? '' : 'title="Anda tidak memiliki akses untuk menambah data" onclick="return false;"' ?>>

                                    <i class="bi bi-plus-circle"></i>

                                    Tambah Indikator

                                </a>
                            </div>

                        </div>

                        <table
                            class="table table-bordered table-hover"
                            id="indicatorTable">

                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Butir Standar</th>
                                <th>Indikator</th>
                                <th>Target</th>
                                <th width="80">Jenis Indikator</th>
                                <th>Status</th>
                                <th width="120">Level Risiko</th>
                                <th width="80">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                        </tbody>

                    </table>

                </div>

            </div>

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

<?php
        include __DIR__ . '/../../layouts/footer.php';

?>

<script>
    SIQUA.API_URL = "<?= $config['api'] ?>";
</script>

<script src="<?= BASE_URL ?>assets/js/indicators.js"></script>