<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';

if (!Auth::isAdmin()) {
    die('Halaman ini hanya untuk Administrator.');
}

require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$config = require __DIR__ . '/config.php';

$repository = new UserRepository($conn);
$service    = new UserService($repository);

$statistics = $service->getStatistics();

require_once __DIR__ . '/../layouts/app.php';

?>

<div class="container-fluid py-4">

    <h4 class="mb-3">Manajemen User</h4>

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
                Kelola akun pengguna sistem: Administrator, Ketua LPM, Auditor, dan Auditee.
            </div>

            <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Admin" class="standard-summary-hero">

        </div>

        <div class="info-card info-card-compact">

            <div class="info-card-header">
                <i class="bi bi-people-fill"></i>
                Ringkasan Pengguna
            </div>

            <div class="info-card-body">

                <div class="info-card-total">
                    <div class="info-card-total-label">Total Pengguna</div>
                    <div class="info-card-total-value"><?= $statistics['data']['total'] ?? 0 ?></div>
                </div>

                <div class="info-card-items-grid info-card-items-grid-2">

                    <div class="info-card-item accent-green">
                        <div class="info-card-icon"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="info-card-text">
                            <div class="info-card-label">Aktif</div>
                            <div class="info-card-value"><?= $statistics['data']['aktif'] ?? 0 ?></div>
                        </div>
                    </div>

                    <div class="info-card-item accent-orange">
                        <div class="info-card-icon"><i class="bi bi-x-circle-fill"></i></div>
                        <div class="info-card-text">
                            <div class="info-card-label">Nonaktif</div>
                            <div class="info-card-value"><?= $statistics['data']['nonaktif'] ?? 0 ?></div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-primary" id="btnAddUser">
            <i class="bi bi-plus-circle"></i>
            Tambah User
        </button>
    </div>

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="row mb-3">

                <div class="col-md-3">
                    <label class="form-label">Peran (Role)</label>
                    <select id="filterRole" class="form-select">
                        <option value="">Semua Role</option>
                        <option value="1">Administrator</option>
                        <option value="2">Ketua LPM</option>
                        <option value="3">Auditor</option>
                        <option value="4">Auditee</option>
                        <option value="5">Pimpinan</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">Semua</option>
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>

            </div>

            <table class="table table-bordered table-hover" id="tableUsers">

                <thead>
                    <tr>
                        <th>Nama Lengkap</th>
                        <th width="120">Username</th>
                        <th width="120">Role</th>
                        <th>Unit Kerja</th>
                        <th>Email</th>
                        <th width="90">Status</th>
                        <th width="90">Aksi</th>
                    </tr>
                </thead>

                <tbody></tbody>

            </table>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/views/modal.php'; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script src="<?= BASE_URL ?>assets/js/users.js"></script>