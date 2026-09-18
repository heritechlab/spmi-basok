<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$repository = new AccRepository($conn);
$service    = new AccService($repository);

$criteriaList = $service->getCriteria()['data'];
$units = Auth::isAuditee() ? [] : $service->getAllUnits()['data'];

require_once __DIR__ . '/../layouts/app.php';

?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0">Monitoring Data Dukung Akreditasi</h4>
        <?php if (Auth::canManage()): ?>
            <a href="<?= BASE_URL ?>acc/manage/" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-gear"></i> Kelola Tabel Monitoring Data Akreditasi
            </a>
        <?php endif; ?>
    </div>

<div class="dual-card-row mb-3">

        <div class="greeting-card" style="flex: 1 1 100%; max-width: 100%;">

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
                Kelola Daftar Dokumen dan Tabel Data Dukung akreditasi per Kriteria, tersimpan otomatis realtime.
            </div>

            <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Pengguna" class="standard-summary-hero">

        </div>

</div>

<?php if (!Auth::isAuditee()): ?>
    <div class="card shadow-sm mb-3" id="accUnitCard">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">Unit Kerja</label>
                    <select class="form-select" id="unitSelector">
                        <option value="">-- Pilih Unit Kerja --</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?><?= !empty($u['type']) ? ' (' . htmlspecialchars($u['type']) . ')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <button type="button" id="btnBackToCards" class="btn btn-link ps-0 mb-2" style="display:none;">
        <i class="bi bi-arrow-left"></i> Kembali ke Ringkasan
    </button>

    <div class="card shadow-sm mb-3" id="accFilterCard" style="display:none;">
        <div class="card-body">
            <div class="row g-2">

                <div class="col-md-4">
                    <label class="form-label">Pilih Kriteria</label>
                    <select class="form-select" id="criteriaSelector">
                        <option value="">-- Pilih Kriteria --</option>
                        <?php foreach ($criteriaList as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Semester</label>
                    <select class="form-select" id="semesterSelector">
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Tahun Akademik</label>
                    <select class="form-select" id="academicYearInput">
                        <option value="">-- Pilih --</option>
                        <?php foreach ($service->getAcademicYears()['data'] as $y): ?>
                            <option value="<?= htmlspecialchars($y['label']) ?>"><?= htmlspecialchars($y['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
        </div>
    </div>

<div id="criteriaCardsContainer" class="row g-3 mb-4"></div>

    <div id="tableSelectorWrapper" style="display:none;" class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-9">
                    <label class="form-label">Lengkapi Tabel Data Dukung</label>
                    <select class="form-select" id="tableSelector">
                        <option value="">-- Pilih Tabel --</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" id="btnExportExcel" class="btn btn-success w-100" style="display:none;">
                        <i class="bi bi-file-earmark-excel"></i> Export Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="tableContainer" class="mb-3"></div>

    <div id="checklistContainer" style="display:none;">
    <p class="text-muted">Silakan pilih Kriteria<?= Auth::isAuditee() ? '' : ', Program Studi,' ?> dan Tahun Akademik terlebih dahulu.</p>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
    const ACC_IS_AUDITEE = <?= Auth::isAuditee() ? 'true' : 'false' ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/acc.js"></script>