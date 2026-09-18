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

$repository = new GkmRepository($conn);
$service    = new GkmService($repository);

require_once __DIR__ . '/../layouts/app.php';

?>

<style>
    #tableGkm { font-size: 12.5px; border-collapse: separate; border-spacing: 0; }
    #tableGkm thead th {
        background: #f6f3fc; color: #5b21b6; font-weight: 700; font-size: 10.5px;
        text-transform: uppercase; letter-spacing: .5px; padding: 11px 14px;
        border-bottom: 2px solid #ddd0f7; border-top: none; vertical-align: middle;
        position: sticky; top: 0; z-index: 2;
    }
    #tableGkm tbody td {
        padding: 10px 14px; vertical-align: middle; color: #2d2a45; font-weight: 400;
        border-color: #f0eef7;
    }
    #tableGkm tbody tr:nth-child(even) { background: #faf9fd; }
    #tableGkm tbody tr:hover { background: #f1edfc; }
    #tableGkm tbody td:first-child { border-left: 3px solid #ddd0f7; font-weight: 600; color: #14112b; }
    #tableGkm tbody tr:hover td:first-child { border-left-color: #7c3aed; }
    #tableGkm .badge { font-weight: 600; font-size: 10.5px; padding: 4px 10px; border-radius: 20px; }
    #tableGkm .btn-sm { border-radius: 8px; padding: 5px 10px; font-size: 11px; }

    .card:has(#tableGkm) {
        border: 1px solid #eceaf5; border-radius: 14px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(20,17,43,0.04), 0 8px 20px rgba(20,17,43,0.03);
    }
    .card:has(#tableGkm) .card-body { padding: 20px; }

    .card:has(#tableGkm) .form-select,
    .card:has(#tableGkm) .form-control {
        font-size: 12.5px; font-weight: 500; color: #2d2a45;
        border: 1px solid #e2dff2; border-radius: 8px; padding: 8px 12px;
        transition: border-color .15s, box-shadow .15s;
    }
    .card:has(#tableGkm) .form-select:focus,
    .card:has(#tableGkm) .form-control:focus {
        border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.10); outline: none;
    }
    .card:has(#tableGkm) .form-label {
        font-size: 11px; font-weight: 700; color: #6b6785; text-transform: uppercase;
        letter-spacing: .4px; margin-bottom: 6px;
    }
</style>

<div class="container-fluid py-4">

    <h4 class="mb-3">Monitoring GKM (Gugus Kendali Mutu)</h4>

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
                <?= $greeting ?>, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Pengguna') ?>!
            </div>

            <div class="indicator-summary-greeting">
                Pantau kesiapan Perencanaan, Proses, dan Pelaporan Pembelajaran tiap Program Studi setiap semester, sesuai peran Gugus Kendali Mutu (GKM).
            </div>

            <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Pengguna" class="standard-summary-hero">

        </div>

        <div class="info-card info-card-compact">

            <div class="info-card-header">
                <i class="bi bi-clipboard2-check"></i>
                Tentang Monitoring GKM
            </div>

            <div class="info-card-body">
                <p class="mb-2" style="font-size:13px;">Checklist mencakup 3 tahap penilaian:</p>
                <div class="info-card-items-grid info-card-items-grid-1">
                    <div class="info-card-item accent-purple">
                        <div class="info-card-icon"><i class="bi bi-1-circle"></i></div>
                        <div class="info-card-text"><div class="info-card-label">Perencanaan Pembelajaran (8 item)</div></div>
                    </div>
                    <div class="info-card-item accent-blue">
                        <div class="info-card-icon"><i class="bi bi-2-circle"></i></div>
                        <div class="info-card-text"><div class="info-card-label">Proses Pembelajaran (11 item)</div></div>
                    </div>
                    <div class="info-card-item accent-green">
                        <div class="info-card-icon"><i class="bi bi-3-circle"></i></div>
                        <div class="info-card-text"><div class="info-card-label">Pelaporan Akhir Semester (4 item)</div></div>
                    </div>
                </div>
            </div>

        </div>

    </div>

<div class="d-flex justify-content-end mb-3">
        <button class="btn btn-primary" id="btnAddMonitoring">
            <i class="bi bi-plus-circle"></i>
            Buat Monitoring Baru
        </button>
    </div>

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="row mb-3">

                <?php if (!Auth::isAuditee()): ?>
                <div class="col-md-4">
                    <label class="form-label">Filter Unit Kerja</label>
                    <select id="filterUnit" class="form-select">
                        <option value="">Semua Unit</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-md-3">
                    <label class="form-label">Filter Semester</label>
                    <select id="filterSemester" class="form-select">
                        <option value="">Semua</option>
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Filter TA</label>
                    <input type="text" class="form-control" id="filterAcademicYear" placeholder="2025/2026">
                </div>

            </div>

            <table class="table table-bordered table-hover" id="tableGkm">
                <thead>
                    <tr>
                        <th>Unit Kerja</th>
                        <th width="110">Semester</th>
                        <th width="110">Tahun Akademik</th>
                        <th width="120">Dibuat</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>

    </div>

</div>

<div class="modal fade" id="addMonitoringModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <form id="addMonitoringForm">

                <div class="modal-header">
                    <h5 class="modal-title">Buat Monitoring GKM Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <?php if (!Auth::isAuditee()): ?>
                    <div class="mb-3">
                        <label class="form-label">Unit Kerja</label>
                        <select class="form-select" id="new_unit_id" name="unit_id" required>
                            <option value="">Pilih Unit Kerja</option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Semester</label>
                        <select class="form-select" id="new_semester" name="semester" required>
                            <option value="">Pilih Semester</option>
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>

                <div class="mb-2">
                        <label class="form-label">Tahun Akademik</label>
                        <input type="text" class="form-control" id="new_academic_year" name="academic_year" placeholder="2025/2026" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Nama Ketua GKM</label>
                        <input type="text" class="form-control" id="new_gkm_nama" name="gkm_nama" placeholder="Nama lengkap perwakilan GKM">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Buat & Lanjut Isi Checklist
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script src="<?= BASE_URL ?>assets/js/gkm.js"></script>