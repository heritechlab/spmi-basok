<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../master/periods/repository.php';

$config = require __DIR__ . '/config.php';

$repository = new RtmRepository($conn);
$service    = new RtmService($repository);

$statistics = $service->getStatistics();

$periodRepo = new PeriodRepository($conn);
$periods = $periodRepo->getAll('', '', 100, 0);

require_once __DIR__ . '/../layouts/app.php';

?>

<style>
    #tableRtm { font-size: 12.5px; border-collapse: separate; border-spacing: 0; }
    #tableRtm thead th {
        background: #f6f3fc; color: #5b21b6; font-weight: 700; font-size: 10.5px;
        text-transform: uppercase; letter-spacing: .5px; padding: 11px 14px;
        border-bottom: 2px solid #ddd0f7; border-top: none; vertical-align: middle;
        position: sticky; top: 0; z-index: 2;
    }
    #tableRtm tbody td {
        padding: 10px 14px; vertical-align: middle; color: #2d2a45; font-weight: 400;
        border-color: #f0eef7;
    }
    #tableRtm tbody tr:nth-child(even) { background: #faf9fd; }
    #tableRtm tbody tr:hover { background: #f1edfc; }
    #tableRtm tbody td:first-child { border-left: 3px solid #ddd0f7; font-weight: 600; color: #14112b; }
    #tableRtm tbody tr:hover td:first-child { border-left-color: #7c3aed; }
    #tableRtm .badge { font-weight: 600; font-size: 10.5px; padding: 4px 10px; border-radius: 20px; }
    #tableRtm .btn-sm { border-radius: 8px; padding: 5px 10px; font-size: 11px; }

    .card:has(#tableRtm) {
        border: 1px solid #eceaf5; border-radius: 14px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(20,17,43,0.04), 0 8px 20px rgba(20,17,43,0.03);
    }
    .card:has(#tableRtm) .card-body { padding: 20px; }

    .card:has(#tableRtm) .form-select,
    .card:has(#tableRtm) .form-control {
        font-size: 12.5px; font-weight: 500; color: #2d2a45;
        border: 1px solid #e2dff2; border-radius: 8px; padding: 8px 12px;
        transition: border-color .15s, box-shadow .15s;
    }
    .card:has(#tableRtm) .form-select:focus,
    .card:has(#tableRtm) .form-control:focus {
        border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.10); outline: none;
    }
    .card:has(#tableRtm) .form-label {
        font-size: 11px; font-weight: 700; color: #6b6785; text-transform: uppercase;
        letter-spacing: .4px; margin-bottom: 6px;
    }
</style>

<div class="container-fluid py-4">

    <h4 class="mb-3">Rapat Tinjauan Manajemen (RTM) Pengendalian</h4>

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
                Berikut kondisi terkini Rapat Tinjauan Manajemen yang sedang Anda kelola.
            </div>

            <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Admin" class="standard-summary-hero">

        </div>

        <div class="info-card info-card-compact">

            <div class="info-card-header">
                <i class="bi bi-bar-chart-fill"></i>
                Ringkasan Data
            </div>

            <div class="info-card-body">

                <div class="info-card-total">
                    <div class="info-card-total-label">Total Rapat</div>
                    <div class="info-card-total-value count-up" data-target="<?= $statistics['data']['total'] ?? 0 ?>">0</div>
                </div>

                <?php
                    $totalRtm = $statistics['data']['total'] ?? 0;
                    $pctDraft = $totalRtm > 0 ? round((($statistics['data']['draft'] ?? 0) / $totalRtm) * 100) : 0;
                    $pctSelesai = $totalRtm > 0 ? round((($statistics['data']['selesai'] ?? 0) / $totalRtm) * 100) : 0;
                ?>

                <div class="info-card-items-grid info-card-items-grid-2">

                    <div class="info-card-item accent-purple">
                        <div class="info-card-icon"><i class="bi bi-file-earmark-text"></i></div>
                        <div class="info-card-text">
                            <div class="info-card-label">Draft</div>
                            <div class="info-card-value count-up" data-target="<?= $statistics['data']['draft'] ?? 0 ?>">0</div>
                            <div class="info-card-progress-track">
                                <div class="info-card-progress-fill" data-percent="<?= $pctDraft ?>"></div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card-item accent-green">
                        <div class="info-card-icon"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="info-card-text">
                            <div class="info-card-label">Selesai</div>
                            <div class="info-card-value count-up" data-target="<?= $statistics['data']['selesai'] ?? 0 ?>">0</div>
                            <div class="info-card-progress-track">
                                <div class="info-card-progress-fill" data-percent="<?= $pctSelesai ?>"></div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="d-flex justify-content-end mb-3">

    <button class="btn btn-primary <?= Auth::canManageRtm() ? '' : 'btn-locked' ?>" id="btnAddRtm"
        <?= Auth::canManageRtm() ? '' : 'title="Hanya Auditee yang dapat menambah data"' ?>>
        <i class="bi bi-plus-circle"></i>
        Tambah Rapat RTM
    </button>

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

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">Semua</option>
                        <option value="Draft">Draft</option>
                        <option value="Selesai">Selesai</option>
                    </select>
                </div>

            </div>

            <table class="table table-bordered table-hover" id="tableRtm">

                <thead>
                    <tr>
                        <th>No. BAP/Rapat</th>
                        <th>Periode</th>
                        <th width="110">Tanggal</th>
                        <th width="80">Jml RTL</th>
                        <th width="100">Status</th>
                        <th width="160">Aksi</th>
                    </tr>
                </thead>

                <tbody></tbody>

            </table>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/views/modal.php'; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.count-up').forEach(function (el) {
        const target = parseInt(el.getAttribute('data-target'), 10) || 0;
        let current = 0;
        const step = Math.max(1, Math.ceil(target / 30));
        const timer = setInterval(function () {
            current += step;
            if (current >= target) { current = target; clearInterval(timer); }
            el.textContent = current;
        }, 30);
    });

    document.querySelectorAll('.info-card-progress-fill').forEach(function (el) {
        const pct = el.getAttribute('data-percent') || 0;
        setTimeout(function () { el.style.width = pct + '%'; }, 100);
    });
});
</script>

<script src="<?= BASE_URL ?>assets/js/rtm.js"></script>