<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';
require_once __DIR__ . '/../../master/periods/repository.php';
require_once __DIR__ . '/../../master/unit/repository.php';

$config = require __DIR__ . '/config.php';

$periodRepo = new PeriodRepository($conn);
$periods = $periodRepo->getAll('', '', 100, 0);

$unitRepo = new UnitRepository($conn);
$units = $unitRepo->getAll();

require_once __DIR__ . '/../../layouts/app.php';

?>

<style>
    #tableImplementation { font-size: 12.5px; border-collapse: separate; border-spacing: 0; }
    #tableImplementation thead th {
        background: #f6f3fc; color: #5b21b6; font-weight: 700; font-size: 10.5px;
        text-transform: uppercase; letter-spacing: .5px; padding: 11px 14px;
        border-bottom: 2px solid #ddd0f7; border-top: none; vertical-align: middle;
        position: sticky; top: 0; z-index: 2;
    }
    #tableImplementation tbody td {
        padding: 10px 14px; vertical-align: middle; color: #2d2a45; font-weight: 400;
        border-color: #f0eef7;
    }
    #tableImplementation tbody tr:nth-child(even) { background: #faf9fd; }
    #tableImplementation tbody tr:hover { background: #f1edfc; }
    #tableImplementation tbody td:first-child { border-left: 3px solid #ddd0f7; font-weight: 600; color: #14112b; }
    #tableImplementation tbody tr:hover td:first-child { border-left-color: #7c3aed; }
    #tableImplementation .badge { font-weight: 600; font-size: 10.5px; padding: 4px 10px; border-radius: 20px; }
    #tableImplementation .btn-sm { border-radius: 8px; padding: 5px 10px; font-size: 11px; }

    .card:has(#tableImplementation) {
        border: 1px solid #eceaf5; border-radius: 14px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(20,17,43,0.04), 0 8px 20px rgba(20,17,43,0.03);
    }
    .card:has(#tableImplementation) .card-body { padding: 20px; }

    .card:has(#tableImplementation) .form-select,
    .card:has(#tableImplementation) .form-control {
        font-size: 12.5px; font-weight: 500; color: #2d2a45;
        border: 1px solid #e2dff2; border-radius: 8px; padding: 8px 12px;
        transition: border-color .15s, box-shadow .15s;
    }
    .card:has(#tableImplementation) .form-select:focus,
    .card:has(#tableImplementation) .form-control:focus {
        border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.10); outline: none;
    }
    .card:has(#tableImplementation) .form-label {
        font-size: 11px; font-weight: 700; color: #6b6785; text-transform: uppercase;
        letter-spacing: .4px; margin-bottom: 6px;
    }
</style>

<div class="container-fluid py-4">

<h4 class="mb-3">Pelaksanaan RTL</h4>

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
                Pantau dan tindak lanjuti pelaksanaan Rencana Tindak Lanjut (RTL) di sini.
            </div>

            <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Admin" class="standard-summary-hero">

        </div>

        <div class="info-card info-card-compact">

            <div class="info-card-header">
                <i class="bi bi-pie-chart-fill"></i>
                Status Pelaksanaan
            </div>

            <div class="info-card-body">
                <div style="max-width: 220px; margin: 0 auto;">
                    <canvas id="chartImplStatus" width="220" height="220"></canvas>
                </div>
            </div>

        </div>

    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <p class="mb-0 text-muted">
                <i class="bi bi-info-circle"></i>
                Auditee mengisi progres pelaksanaan & mengunggah bukti. Admin/Ka. LPM memverifikasi hasil pelaksanaan.
            </p>
        </div>
    </div>



    <div class="card shadow-sm">

        <div class="card-body">

            <div class="row mb-3">

                <div class="col-md-3">
                    <label class="form-label">Periode Audit</label>
                    <select id="filterPeriod" class="form-select">
                        <option value="">Semua Periode</option>
                        <?php foreach ($periods as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['period_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (!Auth::isAuditee()): ?>
                <div class="col-md-3">
                    <label class="form-label">Unit Kerja</label>
                    <select id="filterUnit" class="form-select">
                        <option value="">Semua Unit</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-md-3">
                    <label class="form-label">Status Pelaksanaan</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">Semua</option>
                        <option value="Belum">Belum</option>
                        <option value="Proses">Proses</option>
                        <option value="Selesai">Selesai</option>
                    </select>
                </div>

            </div>

            <table class="table table-bordered table-hover" id="tableImplementation">

                <thead>
                    <tr>
                        <th>Temuan</th>
                        <th>Kegiatan</th>
                        <th width="100">Status</th>
                        <th width="140">Verifikasi</th>
                        <th width="80">Aksi</th>
                    </tr>
                </thead>

                <tbody></tbody>

            </table>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/views/detail_modal.php'; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<script>
    const CAN_MANAGE_RTM = <?= Auth::canManageRtm() ? 'true' : 'false' ?>;
    const CAN_VERIFY_RTL = <?= Auth::canVerifyRtl() ? 'true' : 'false' ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= BASE_URL ?>assets/js/rtm_implementation.js"></script>