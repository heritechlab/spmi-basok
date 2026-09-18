<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../master/periods/repository.php';
require_once __DIR__ . '/../../core/Auth.php';

$config = require __DIR__ . '/config.php';

$repository = new AssignmentRepository($conn);
$service    = new AssignmentService($repository);

$statistics = $service->getStatistics();

$periodRepo = new PeriodRepository($conn);
$periods = $periodRepo->getAll('', '', 100, 0);

require_once __DIR__ . '/../../layouts/app.php';

?>

<style>
    #tableAssignment { font-size: 12.5px; border-collapse: separate; border-spacing: 0; }
    #tableAssignment thead th {
        background: #f6f3fc; color: #5b21b6; font-weight: 700; font-size: 10.5px;
        text-transform: uppercase; letter-spacing: .5px; padding: 11px 14px;
        border-bottom: 2px solid #ddd0f7; border-top: none; vertical-align: middle;
        position: sticky; top: 0; z-index: 2;
    }
    #tableAssignment tbody td {
        padding: 10px 14px; vertical-align: middle; color: #2d2a45; font-weight: 400;
        border-color: #f0eef7;
    }
    #tableAssignment tbody tr:nth-child(even) { background: #faf9fd; }
    #tableAssignment tbody tr:hover { background: #f1edfc; }
    #tableAssignment tbody td:first-child { border-left: 3px solid #ddd0f7; font-weight: 600; color: #14112b; }
    #tableAssignment tbody tr:hover td:first-child { border-left-color: #7c3aed; }
    #tableAssignment .badge { font-weight: 600; font-size: 10.5px; padding: 4px 10px; border-radius: 20px; }
    #tableAssignment .btn-sm { border-radius: 8px; padding: 5px 10px; font-size: 11px; }

    #tableAssignment + * .card,
    .card:has(#tableAssignment) {
        border: 1px solid #eceaf5; border-radius: 14px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(20,17,43,0.04), 0 8px 20px rgba(20,17,43,0.03);
    }
    .card:has(#tableAssignment) .card-body { padding: 20px; }

    #filterPeriod, #filterStatus {
        font-size: 12.5px; font-weight: 500; color: #2d2a45;
        border: 1px solid #e2dff2; border-radius: 8px; padding: 8px 12px;
        transition: border-color .15s, box-shadow .15s;
    }
    #filterPeriod:focus, #filterStatus:focus {
        border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.10); outline: none;
    }
    #tableAssignment ~ * .form-label,
    .card:has(#tableAssignment) .form-label {
        font-size: 11px; font-weight: 700; color: #6b6785; text-transform: uppercase;
        letter-spacing: .4px; margin-bottom: 6px;
    }
</style>

<div class="container-fluid py-4">

    <h4 class="fw-bold mb-3">Data Penugasan Audit</h4>

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
                Berikut kondisi terkini Penugasan Audit di Institusi Anda.
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
                    <div class="info-card-total-label">Total Penugasan</div>
                    <div class="info-card-total-value count-up" data-target="<?= $statistics['data']['total'] ?? 0 ?>">0</div>
                </div>

                <?php
                    $totalPenugasan = $statistics['data']['total'] ?? 0;
                    $pctDraft = $totalPenugasan > 0 ? round((($statistics['data']['draft'] ?? 0) / $totalPenugasan) * 100) : 0;
                    $pctJadwal = $totalPenugasan > 0 ? round((($statistics['data']['dijadwalkan'] ?? 0) / $totalPenugasan) * 100) : 0;
                    $pctBerlangsung = $totalPenugasan > 0 ? round((($statistics['data']['berlangsung'] ?? 0) / $totalPenugasan) * 100) : 0;
                    $pctSelesai = $totalPenugasan > 0 ? round((($statistics['data']['selesai'] ?? 0) / $totalPenugasan) * 100) : 0;
                ?>

                <div class="info-card-items-grid">

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

                <div class="info-card-item accent-blue">
                    <div class="info-card-icon">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div class="info-card-text">
                        <div class="info-card-label">Dijadwalkan</div>
                        <div class="info-card-value count-up" data-target="<?= $statistics['data']['dijadwalkan'] ?? 0 ?>">0</div>
                        <div class="info-card-progress-track">
                            <div class="info-card-progress-fill" data-percent="<?= $pctJadwal ?>"></div>
                        </div>
                    </div>
                </div>

                <div class="info-card-item accent-orange">
                    <div class="info-card-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="info-card-text">
                        <div class="info-card-label">Berlangsung</div>
                        <div class="info-card-value count-up" data-target="<?= $statistics['data']['berlangsung'] ?? 0 ?>">0</div>
                        <div class="info-card-progress-track">
                            <div class="info-card-progress-fill" data-percent="<?= $pctBerlangsung ?>"></div>
                        </div>
                    </div>
                </div>

                <div class="info-card-item accent-green">
                    <div class="info-card-icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
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



<?php require_once __DIR__ . '/../tabs.php'; ?>

        <div class="d-flex justify-content-end">

            <button class="btn btn-primary <?= Auth::canManage() ? '' : 'btn-locked' ?>" id="btnAddAssignment"
                <?= Auth::canManage() ? '' : 'title="Anda tidak memiliki akses untuk menambah data"' ?>>
                <i class="bi bi-plus-circle"></i>
                Tambah Penugasan
            </button>

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

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">Semua</option>
                        <option value="Draft">Draft</option>
                        <option value="Dijadwalkan">Dijadwalkan</option>
                        <option value="Berlangsung">Berlangsung</option>
                        <option value="Selesai">Selesai</option>
                    </select>
                </div>

            </div>

            <table class="table table-bordered table-hover" id="tableAssignment">

                <thead>
                    <tr>
                        <th>No. Penugasan</th>
                        <th>Unit Kerja (Auditee)</th>
                        <th>Ketua Tim Audit</th>
                        <th>Periode</th>
                        <th width="100">Jenis Audit</th>
                        <th width="100">Tanggal</th>
                        <th width="95">Surat Tugas</th>
                        <th width="130">Persetujuan</th>
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

<script src="<?= BASE_URL ?>assets/js/assignment.js"></script>