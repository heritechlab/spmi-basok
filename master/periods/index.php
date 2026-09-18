<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../core/Auth.php';

$config = require __DIR__ . '/config.php';

$repository = new PeriodRepository($conn);
$service    = new PeriodService($repository);

$statistics = $service->getStatistics();

require_once __DIR__ . '/../../layouts/app.php';

?>

<style>
    #tablePeriod { font-size: 12.5px; border-collapse: separate; border-spacing: 0; }
    #tablePeriod thead th {
        background: #f6f3fc; color: #5b21b6; font-weight: 700; font-size: 10.5px;
        text-transform: uppercase; letter-spacing: .5px; padding: 11px 14px;
        border-bottom: 2px solid #ddd0f7; border-top: none; vertical-align: middle;
        position: sticky; top: 0; z-index: 2;
    }
    #tablePeriod tbody td {
        padding: 10px 14px; vertical-align: middle; color: #2d2a45; font-weight: 400;
        border-color: #f0eef7;
    }
    #tablePeriod tbody tr { border-left: 3px solid transparent; transition: border-color .15s, background .15s; }
    #tablePeriod tbody tr:nth-child(even) { background: #faf9fd; }
    #tablePeriod tbody tr:hover { background: #f1edfc; border-left-color: #7c3aed; }
    #tablePeriod tbody td:first-child { border-left: 3px solid #ddd0f7; font-weight: 600; color: #14112b; }
    #tablePeriod tbody tr:hover td:first-child { border-left-color: #7c3aed; }
    .badge-year {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 42px; padding: 4px 10px; border-radius: 20px;
        background: #f1edfc; color: #5b21b6; font-weight: 700; font-size: 11.5px;
    }
    #tablePeriod .badge { font-weight: 600; font-size: 10.5px; padding: 4px 10px; border-radius: 20px; }

    .card:has(#tablePeriod) {
        border: 1px solid #eceaf5; border-radius: 14px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(20,17,43,0.04), 0 8px 20px rgba(20,17,43,0.03);
    }
    .card:has(#tablePeriod) .card-body { padding: 20px; }
    #tablePeriod .btn-sm { border-radius: 8px; padding: 5px 10px; font-size: 11px; }

    #filterStatus {
        font-size: 12.5px; font-weight: 500; color: #2d2a45;
        border: 1px solid #e2dff2; border-radius: 8px; padding: 8px 12px;
        transition: border-color .15s, box-shadow .15s;
    }
    #filterStatus:focus {
        border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.10); outline: none;
    }
    .form-label {
        font-size: 11px; font-weight: 700; color: #6b6785; text-transform: uppercase;
        letter-spacing: .4px; margin-bottom: 6px;
    }
</style>

<div class="container-fluid py-4">

    <h4 class="fw-bold mb-3">Data Periode Audit</h4>

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
                Berikut siklus periode audit yang telah berjalan diinstitusi anda.
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
                    <div class="info-card-total-label">Total Periode</div>
                    <div class="info-card-total-value count-up" data-target="<?= $statistics['data']['total'] ?? 0 ?>">0</div>
                </div>

                <?php
                    $totalPeriode = $statistics['data']['total'] ?? 0;
                    $pctAktif = $totalPeriode > 0 ? round((($statistics['data']['aktif'] ?? 0) / $totalPeriode) * 100) : 0;
                    $pctDraft = $totalPeriode > 0 ? round((($statistics['data']['draft'] ?? 0) / $totalPeriode) * 100) : 0;
                    $pctDitutup = $totalPeriode > 0 ? round((($statistics['data']['ditutup'] ?? 0) / $totalPeriode) * 100) : 0;
                ?>

                
                <div class="info-card-items-grid info-card-items-grid-3">
                <div class="info-card-item accent-green">
                    <div class="info-card-icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="info-card-text">
                        <div class="info-card-label">Aktif</div>
                        <div class="info-card-value count-up" data-target="<?= $statistics['data']['aktif'] ?? 0 ?>">0</div>
                        <div class="info-card-progress-track">
                            <div class="info-card-progress-fill" data-percent="<?= $pctAktif ?>"></div>
                        </div>
                    </div>
                </div>

                <div class="info-card-item accent-purple">
                    <div class="info-card-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div class="info-card-text">
                        <div class="info-card-label">Draft</div>
                        <div class="info-card-value count-up" data-target="<?= $statistics['data']['draft'] ?? 0 ?>">0</div>
                        <div class="info-card-progress-track">
                            <div class="info-card-progress-fill" data-percent="<?= $pctDraft ?>"></div>
                        </div>
                    </div>
                </div>

                <div class="info-card-item accent-orange">
                    <div class="info-card-icon">
                        <i class="bi bi-lock-fill"></i>
                    </div>
                    <div class="info-card-text">
                        <div class="info-card-label">Ditutup</div>
                        <div class="info-card-value count-up" data-target="<?= $statistics['data']['ditutup'] ?? 0 ?>">0</div>
                        <div class="info-card-progress-track">
                            <div class="info-card-progress-fill" data-percent="<?= $pctDitutup ?>"></div>
                        </div>
                    </div>
                </div>

            </div>
            </div>

        </div>

    </div>
    <?php require_once __DIR__ . '/../../audit/tabs.php'; ?>
        <div class="d-flex justify-content-end">

            <button class="btn btn-primary <?= Auth::canManage() ? '' : 'btn-locked' ?>" id="btnAddPeriod"
                <?= Auth::canManage() ? '' : 'title="Anda tidak memiliki akses untuk menambah data"' ?>>
                <i class="bi bi-plus-circle"></i>
                Tambah Periode
            </button>

        </div>

    </div>

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="row mb-3">

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">Semua</option>
                        <option value="Draft">Draft</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Ditutup">Ditutup</option>
                    </select>
                </div>

            </div>

            <table class="table table-bordered table-hover" id="tablePeriod">

                <thead>
                    <tr>
                        <th>Siklus Audit</th>
                        <th width="80">Tahun</th>
                        <th width="150" style="white-space: nowrap;">Tahun Akademik</th>
                        <th>Rentang Tanggal</th>
                        <th width="100">Status</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>

                <tbody></tbody>

            </table>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/views/modal.php'; ?>

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

<?php include __DIR__ . '/../../layouts/footer.php'; ?>

<script src="<?= BASE_URL ?>assets/js/periods.js"></script>