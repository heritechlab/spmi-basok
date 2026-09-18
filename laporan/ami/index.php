<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../master/periods/repository.php';
require_once __DIR__ . '/../../master/unit/repository.php';

$periodRepo = new PeriodRepository($conn);
$periods = $periodRepo->getAll('', '', 100, 0);

$unitRepo = new UnitRepository($conn);
$units = $unitRepo->getAll();

require_once __DIR__ . '/../../layouts/app.php';

?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Laporan Audit Mutu Internal (AMI) per Unit Kerja</h4>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="history.back()">
            <i class="bi bi-arrow-left"></i> Kembali
        </button>
    </div>

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
                Susun Laporan Audit Mutu Internal resmi untuk Unit Kerja tertentu, siap cetak.
            </div>

            <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Admin" class="standard-summary-hero">

        </div>

        <div class="info-card info-card-compact">

            <div class="info-card-header">
                <i class="bi bi-file-earmark-text"></i>
                Ringkasan Laporan
            </div>

            <div class="info-card-body">

                <div class="info-card-total">
                    <div class="info-card-total-label">Total Unit Kerja</div>
                    <div class="info-card-total-value"><?= count($units) ?></div>
                </div>

                <div class="info-card-items-grid info-card-items-grid-1">

                    <div class="info-card-item accent-purple">
                        <div class="info-card-icon"><i class="bi bi-calendar3"></i></div>
                        <div class="info-card-text">
                            <div class="info-card-label">Periode Tersedia</div>
                            <div class="info-card-value"><?= count($periods) ?></div>
                        </div>
                    </div>

                    <div class="info-card-item accent-blue">
                        <div class="info-card-icon"><i class="bi bi-file-earmark-pdf"></i></div>
                        <div class="info-card-text">
                            <div class="info-card-label">Format</div>
                            <div class="info-card-value" style="font-size:14px;">PDF Siap Cetak</div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="card shadow-sm">

        <div class="card-body">

            <p class="text-muted">
                Pilih Unit Kerja dan Periode Audit untuk menyusun Laporan AMI resmi (Cover, Halaman Pengesahan,
                Kata Pengantar, BAB I s.d. BAB V).
            </p>

            <form id="reportForm" target="_blank" action="<?= BASE_URL ?>laporan/ami/print.php" method="get">

                <div class="row">

                    <div class="col-md-5 mb-3">
                        <label class="form-label">Unit Kerja</label>
                        <select class="form-select" name="unit_id" required>
                            <option value="">Pilih Unit Kerja</option>
                            <?php foreach ($units as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-5 mb-3">
                        <label class="form-label">Periode Audit</label>
                        <select class="form-select" name="period_id" required>
                            <option value="">Pilih Periode</option>
                            <?php foreach ($periods as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['period_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-file-earmark-text"></i> Buat Laporan
                        </button>
                    </div>

                </div>

</form>

            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btnManageSignature">
                <i class="bi bi-pen"></i> Kelola TTD untuk Unit &amp; Periode di atas
            </button>

        </div>

    </div>

</div>

<script>
document.getElementById('btnManageSignature').addEventListener('click', function () {
    const unitId = document.querySelector('select[name="unit_id"]').value;
    const periodId = document.querySelector('select[name="period_id"]').value;

    if (!unitId || !periodId) {
        alert('Pilih Unit Kerja dan Periode terlebih dahulu.');
        return;
    }

    window.location.href = '<?= BASE_URL ?>laporan/signatures/manage.php?type=unit&unit_id=' + unitId + '&period_id=' + periodId;
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>