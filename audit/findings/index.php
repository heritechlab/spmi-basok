<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../master/periods/repository.php';
require_once __DIR__ . '/../../master/unit/repository.php';
require_once __DIR__ . '/../../core/Auth.php';

$config = require __DIR__ . '/config.php';

$repository = new FindingRepository($conn);
$service    = new FindingService($repository);

$myUnitId = Auth::isAuditee() ? (int)($_SESSION['unit_id'] ?? 0) : 0;
$statistics = $service->getStatistics($myUnitId);

$periodRepo = new PeriodRepository($conn);
$periods = $periodRepo->getAll('', '', 100, 0);

$unitRepo = new UnitRepository($conn);
$units = $unitRepo->getAll();

require_once __DIR__ . '/../../layouts/app.php';

?>

<style>
    #tableFinding { font-size: 12.5px; border-collapse: separate; border-spacing: 0; }
    #tableFinding thead th {
        background: #f6f3fc; color: #5b21b6; font-weight: 700; font-size: 10.5px;
        text-transform: uppercase; letter-spacing: .5px; padding: 11px 14px;
        border-bottom: 2px solid #ddd0f7; border-top: none; vertical-align: middle;
        position: sticky; top: 0; z-index: 2;
    }
    #tableFinding tbody td {
        padding: 10px 14px; vertical-align: middle; color: #2d2a45; font-weight: 400;
        border-color: #f0eef7;
    }
    #tableFinding tbody tr:nth-child(even) { background: #faf9fd; }
    #tableFinding tbody tr:hover { background: #f1edfc; }
    #tableFinding tbody td:first-child { border-left: 3px solid #ddd0f7; font-weight: 600; color: #14112b; }
    #tableFinding tbody tr:hover td:first-child { border-left-color: #7c3aed; }
    #tableFinding .badge { font-weight: 600; font-size: 10.5px; padding: 4px 10px; border-radius: 20px; }
    #tableFinding .btn-sm { border-radius: 8px; padding: 5px 10px; font-size: 11px; }

    .card:has(#tableFinding) {
        border: 1px solid #eceaf5; border-radius: 14px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(20,17,43,0.04), 0 8px 20px rgba(20,17,43,0.03);
    }
    .card:has(#tableFinding) .card-body { padding: 20px; }

    .card:has(#tableFinding) .form-select,
    .card:has(#tableFinding) .form-control {
        font-size: 12.5px; font-weight: 500; color: #2d2a45;
        border: 1px solid #e2dff2; border-radius: 8px; padding: 8px 12px;
        transition: border-color .15s, box-shadow .15s;
    }
    .card:has(#tableFinding) .form-select:focus,
    .card:has(#tableFinding) .form-control:focus {
        border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.10); outline: none;
    }
    .card:has(#tableFinding) .form-label {
        font-size: 11px; font-weight: 700; color: #6b6785; text-transform: uppercase;
        letter-spacing: .4px; margin-bottom: 6px;
    }
</style>

<div class="container-fluid py-4">

    <h4 class="mb-3">Temuan Audit</h4>

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
                Berikut rekap Temuan hasil Audit Mutu Internal.
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
                    <div class="info-card-total-label">Total Temuan</div>
                    <div class="info-card-total-value count-up" data-target="<?= $statistics['data']['total'] ?? 0 ?>">0</div>
                </div>

                <?php
                    $totalF = $statistics['data']['total'] ?? 0;
                    $pctTidak = $totalF > 0 ? round((($statistics['data']['tidak_terpenuhi'] ?? 0) / $totalF) * 100) : 0;
                    $pctSebagian = $totalF > 0 ? round((($statistics['data']['sebagian'] ?? 0) / $totalF) * 100) : 0;
                    $pctMemenuhi = $totalF > 0 ? round((($statistics['data']['memenuhi'] ?? 0) / $totalF) * 100) : 0;
                    $pctMelampaui = $totalF > 0 ? round((($statistics['data']['melampaui'] ?? 0) / $totalF) * 100) : 0;
                ?>

                <div class="info-card-items-grid">

<div class="info-card-item accent-orange">
                        <div class="info-card-icon"><i class="bi bi-x-circle-fill"></i></div>
                        <div class="info-card-text">
                            <div class="info-card-label">Menyimpang</div>
                            <div class="info-card-value count-up" data-target="<?= $statistics['data']['tidak_terpenuhi'] ?? 0 ?>">0</div>
                            <div class="info-card-progress-track">
                                <div class="info-card-progress-fill" data-percent="<?= $pctTidak ?>"></div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card-item accent-purple">
                        <div class="info-card-icon"><i class="bi bi-dash-circle-fill"></i></div>
                        <div class="info-card-text">
                            <div class="info-card-label">Belum Mencapai</div>
                            <div class="info-card-value count-up" data-target="<?= $statistics['data']['sebagian'] ?? 0 ?>">0</div>
                            <div class="info-card-progress-track">
                                <div class="info-card-progress-fill" data-percent="<?= $pctSebagian ?>"></div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card-item accent-green">
                        <div class="info-card-icon"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="info-card-text">
                            <div class="info-card-label">Mencapai</div>
                            <div class="info-card-value count-up" data-target="<?= $statistics['data']['memenuhi'] ?? 0 ?>">0</div>
                            <div class="info-card-progress-track">
                                <div class="info-card-progress-fill" data-percent="<?= $pctMemenuhi ?>"></div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card-item accent-blue">
                        <div class="info-card-icon"><i class="bi bi-star-fill"></i></div>
                        <div class="info-card-text">
                            <div class="info-card-label">Melampaui</div>
                            <div class="info-card-value count-up" data-target="<?= $statistics['data']['melampaui'] ?? 0 ?>">0</div>
                            <div class="info-card-progress-track">
                                <div class="info-card-progress-fill" data-percent="<?= $pctMelampaui ?>"></div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>
    <?php require_once __DIR__ . '/../tabs.php'; ?>

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
                    <label class="form-label">Status Capaian</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="Menyimpang">Menyimpang</option>
                        <option value="Belum Mencapai">Belum Mencapai</option>
                        <option value="Mencapai">Mencapai</option>
                        <option value="Melampaui">Melampaui</option>
                    </select>
                </div>

            </div>

            <table class="table table-bordered table-hover" id="tableFinding">

                <thead>
                    <tr>
                        <th width="90">Standar</th>
                        <th>Indikator</th>
                        <th>Unit Kerja</th>
                        <th width="110">Periode</th>
                        <th width="110">Capaian/Target</th>
                        <th width="130">Status</th>
                        <th>Hasil Audit Lapangan</th>
                        <th>Rekomendasi</th>
                        <th width="110">Tindak Lanjut</th>
                        <th width="80">Aksi</th>
                    </tr>
                </thead>

                <tbody></tbody>

            </table>

        </div>

    </div>

</div>

<div class="modal fade" id="findingDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Detail Temuan Audit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="findingDetailBody">
                <p class="text-muted">Memuat data...</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

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

<script src="<?= BASE_URL ?>assets/js/finding.js"></script>