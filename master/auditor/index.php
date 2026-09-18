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

$repository = new AuditorRepository($conn);
$service    = new AuditorService($repository);
$totalAuditor = $repository->countAll();
$totalAktif   = $repository->countByStatus(1);
$totalNonaktif = $repository->countByStatus(0);

require_once __DIR__ . '/../../layouts/app.php';

?>

<style>
    #tableAuditor { font-size: 12.5px; border-collapse: separate; border-spacing: 0; }
    #tableAuditor thead th {
        background: #f6f3fc; color: #5b21b6; font-weight: 700; font-size: 10.5px;
        text-transform: uppercase; letter-spacing: .5px; padding: 11px 14px;
        border-bottom: 2px solid #ddd0f7; border-top: none; vertical-align: middle;
        position: sticky; top: 0; z-index: 2;
    }
    #tableAuditor tbody td {
        padding: 10px 14px; vertical-align: middle; color: #2d2a45; font-weight: 400;
        border-color: #f0eef7;
    }
    #tableAuditor tbody tr:nth-child(even) { background: #faf9fd; }
    #tableAuditor tbody tr:hover { background: #f1edfc; }
    #tableAuditor tbody td:first-child { border-left: 3px solid #ddd0f7; font-weight: 600; color: #14112b; }
    #tableAuditor tbody tr:hover td:first-child { border-left-color: #7c3aed; }
    #tableAuditor .badge { font-weight: 600; font-size: 10.5px; padding: 4px 10px; border-radius: 20px; }
    .card-header strong { font-size: 13.5px; font-weight: 700; color: #14112b; letter-spacing: -.1px; }
    .card:has(#tableAuditor) .card-header {
        background: #faf9fd; border-bottom: 1px solid #eceaf5; padding: 14px 20px;
    }
    .card:has(#tableAuditor) .card-body { padding: 20px; }

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

       <div class="container-fluid">

            <h3 class="fw-bold mb-1">


                Data Auditor 

            </h3>

            <p class="text-muted mb-0">

                Data kelola auditor mutu internal yang telah tersertifikasi

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
                Berikut daftar terkini Tim Auditor mutu yang dimiliki institusi anda.
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
                    <div class="info-card-total-label">Total Auditor</div>
                    <div class="info-card-total-value count-up" data-target="<?= $totalAuditor ?>">0</div>
                </div>

                <?php
                    $pctAuditorAktif = $totalAuditor > 0 ? round(($totalAktif / $totalAuditor) * 100) : 0;
                    $pctAuditorNonaktif = $totalAuditor > 0 ? round(($totalNonaktif / $totalAuditor) * 100) : 0;
                ?>

                <div class="info-card-items-grid info-card-items-grid-2">

                    <div class="info-card-item accent-green">
                        <div class="info-card-icon">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                        <div class="info-card-text">
                            <div class="info-card-label">Aktif</div>
                            <div class="info-card-value count-up" data-target="<?= $totalAktif ?>">0</div>
                            <div class="info-card-progress-track">
                                <div class="info-card-progress-fill" data-percent="<?= $pctAuditorAktif ?>"></div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card-item accent-orange">
                        <div class="info-card-icon">
                            <i class="bi bi-person-dash-fill"></i>
                        </div>
                        <div class="info-card-text">
                            <div class="info-card-label">Nonaktif</div>
                            <div class="info-card-value count-up" data-target="<?= $totalNonaktif ?>">0</div>
                            <div class="info-card-progress-track">
                                <div class="info-card-progress-fill" data-percent="<?= $pctAuditorNonaktif ?>"></div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>
    <?php require_once __DIR__ . '/../../audit/tabs.php'; ?>

    <div class="d-flex justify-content-end gap-2 flex-wrap">

            <a href="<?= BASE_URL ?>master/auditor/print.php" target="_blank" class="btn btn-outline-dark">
                <i class="bi bi-printer"></i>
                Print
            </a>

            <button class="btn btn-primary <?= Auth::canManage() ? '' : 'btn-locked' ?>" id="btnAddAuditor"
                <?= Auth::canManage() ? '' : 'title="Anda tidak memiliki akses untuk menambah data"' ?>>
                <i class="bi bi-plus-circle"></i>
                Tambah Auditor
            </button>

        </div>

    </div>

    <div class="card shadow-sm">

        <div class="card-header">
            <strong>Data Master Auditor</strong>
        </div>

<div class="card-body">

            <div class="row mb-3">

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">Semua</option>
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>

            </div>

            <table class="table table-bordered table-hover" id="tableAuditor">

                <thead>
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>NIDN/NIP</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Status</th>
                        <th>Sertifikat</th>
                        <th width="150">Aksi</th>
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

<script src="<?= BASE_URL ?>assets/js/auditor.js"></script>