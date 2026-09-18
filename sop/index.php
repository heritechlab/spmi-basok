<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$config = require __DIR__ . '/config.php';

$repository = new SopRepository($conn);
$service    = new SopService($repository);

$statistics = $service->getStatistics();

require_once __DIR__ . '/../layouts/app.php';

?>

<style>
    #tableSop { font-size: 12.5px; border-collapse: separate; border-spacing: 0; }
    #tableSop thead th {
        background: #f6f3fc; color: #5b21b6; font-weight: 700; font-size: 10.5px;
        text-transform: uppercase; letter-spacing: .5px; padding: 11px 14px;
        border-bottom: 2px solid #ddd0f7; border-top: none; vertical-align: middle;
        position: sticky; top: 0; z-index: 2;
    }
    #tableSop tbody td {
        padding: 10px 14px; vertical-align: middle; color: #2d2a45; font-weight: 400;
        border-color: #f0eef7;
    }
    #tableSop tbody tr:nth-child(even) { background: #faf9fd; }
    #tableSop tbody tr:hover { background: #f1edfc; }
    #tableSop tbody td:first-child { border-left: 3px solid #ddd0f7; font-weight: 600; color: #14112b; }
    #tableSop tbody tr:hover td:first-child { border-left-color: #7c3aed; }
    #tableSop .badge { font-weight: 600; font-size: 10.5px; padding: 4px 10px; border-radius: 20px; }
    #tableSop .btn-sm { border-radius: 8px; padding: 5px 10px; font-size: 11px; }

    .card:has(#tableSop) {
        border: 1px solid #eceaf5; border-radius: 14px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(20,17,43,0.04), 0 8px 20px rgba(20,17,43,0.03);
    }
    .card:has(#tableSop) .card-body { padding: 20px; }

    .card:has(#tableSop) .form-select,
    .card:has(#tableSop) .form-control {
        font-size: 12.5px; font-weight: 500; color: #2d2a45;
        border: 1px solid #e2dff2; border-radius: 8px; padding: 8px 12px;
        transition: border-color .15s, box-shadow .15s;
    }
    .card:has(#tableSop) .form-select:focus,
    .card:has(#tableSop) .form-control:focus {
        border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.10); outline: none;
    }
    .card:has(#tableSop) .form-label {
        font-size: 11px; font-weight: 700; color: #6b6785; text-transform: uppercase;
        letter-spacing: .4px; margin-bottom: 6px;
    }
</style>

<div class="container-fluid py-4">

    <h4 class="mb-3">Pengajuan SOP Mutu</h4>

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
                Susun dan kelola dokumen SOP Mutu sesuai format resmi institusi, siap cetak.
            </div>

            <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Admin" class="standard-summary-hero">

        </div>

        <div class="info-card info-card-compact">

            <div class="info-card-header">
                <i class="bi bi-journal-text"></i>
                Ringkasan SOP
            </div>

            <div class="info-card-body">

                <div class="info-card-total">
                    <div class="info-card-total-label">Total Dokumen SOP</div>
                    <div class="info-card-total-value"><?= $statistics['data']['total'] ?? 0 ?></div>
                </div>

            <div class="info-card-items-grid info-card-items-grid-2">

                    <div class="info-card-item accent-green">
                        <div class="info-card-icon"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="info-card-text">
                            <div class="info-card-label">Disahkan</div>
                            <div class="info-card-value"><?= $statistics['data']['disahkan'] ?? 0 ?></div>
                        </div>
                    </div>

                    <div class="info-card-item accent-orange">
                        <div class="info-card-icon"><i class="bi bi-hourglass"></i></div>
                        <div class="info-card-text">
                            <div class="info-card-label">Menunggu</div>
                            <div class="info-card-value"><?= $statistics['data']['menunggu'] ?? 0 ?></div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-primary" id="btnAddSop">
            <i class="bi bi-plus-circle"></i>
            Ajukan SOP
        </button>
    </div>

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="row mb-3">

            <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">Semua</option>
                        <option value="Menunggu Persetujuan">Menunggu Persetujuan</option>
                        <option value="Disahkan">Disahkan</option>
                        <option value="Ditolak">Ditolak</option>
                    </select>
                </div>

            </div>

            <table class="table table-bordered table-hover" id="tableSop">

                <thead>
                    <tr>
                        <th width="140">No. Dokumen</th>
                        <th>Judul SOP</th>
                        <th width="110">Berlaku Sejak</th>
                        <th width="70">Revisi</th>
                        <th width="90">Status</th>
                        <th width="140">Aksi</th>
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
    const CAN_VERIFY_SOP = <?= Auth::canVerifyRtl() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/sop.js"></script>