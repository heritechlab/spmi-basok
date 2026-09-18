<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$repository = new AkreditasiInstitusiRepository($conn);
$service    = new AkreditasiInstitusiService($repository);

$kriteriaOptions = $service->getKriteriaOptions();
$stats = $service->getStatistics();

require_once __DIR__ . '/../layouts/app.php';

?>

<div class="container-fluid py-4">

<div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Data Dukung Akreditasi Institusi</h4>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>akreditasi_institusi/public.php" target="_blank" class="btn btn-outline-success btn-sm">
                <i class="bi bi-box-arrow-up-right"></i> Buka Halaman Publik
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="history.back()">
                <i class="bi bi-arrow-left"></i> Kembali
            </button>
        </div>
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
                <?= $greeting ?>, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Pengguna') ?>!
            </div>

            <div class="indicator-summary-greeting">
                Kelola dokumen bukti dukung akreditasi Institusi berdasarkan Kriteria penilaian.
            </div>

            <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Pengguna" class="standard-summary-hero">

        </div>

        <div class="info-card info-card-compact">

            <div class="info-card-header">
                <i class="bi bi-bank2"></i>
                Ringkasan Dokumen
            </div>

            <div class="info-card-body">

                <div class="info-card-total">
                    <div class="info-card-total-label">Total Dokumen Institusi</div>
                    <div class="info-card-total-value"><?= $stats['total'] ?? 0 ?></div>
                </div>

            </div>

        </div>

    </div>

    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-primary" id="btnUpload">
            <i class="bi bi-cloud-upload"></i> Upload Dokumen
        </button>
    </div>

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="row mb-3">

                <div class="col-md-6">
                    <label class="form-label">Filter Kriteria</label>
                    <select id="filterKriteria" class="form-select">
                        <option value="">Semua Kriteria</option>
                        <?php foreach ($kriteriaOptions as $k): ?>
                            <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Filter Tahun Akademik</label>
                    <input type="text" class="form-control" id="filterAcademicYear" placeholder="2025/2026">
                </div>

            </div>

            <table class="table table-bordered table-hover" id="tableAkreditasiInstitusi">
                <thead>
                    <tr>
                        <th width="260">Kriteria</th>
                        <th>Nama Dokumen</th>
                        <th width="100">TA</th>
                        <th width="150">Diunggah Oleh</th>
                        <th width="110">Tanggal</th>
                        <th width="115">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>

    </div>

</div>

<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <form id="uploadForm">

                <div class="modal-header">
                    <h5 class="modal-title">Upload Dokumen Akreditasi Institusi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Kriteria</label>
                        <select class="form-select" id="kriteria" name="kriteria" required>
                            <option value="">Pilih Kriteria</option>
                            <?php foreach ($kriteriaOptions as $k): ?>
                                <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Dokumen</label>
                        <input type="text" class="form-control" id="document_name" name="document_name" placeholder="Contoh: Dokumen Kebijakan Mutu Institusi" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tahun Akademik</label>
                        <input type="text" class="form-control" id="academic_year" name="academic_year" placeholder="2025/2026" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Upload Bukti (Opsional jika mengisi Link)</label>
                        <input type="file" class="form-control" id="bukti_file_input">
                        <small class="text-muted">Semua jenis file dokumen umum diterima.</small>
                    </div>

                    <div class="text-center text-muted my-2" style="font-size:12px;">— atau —</div>

                    <div class="mb-2">
                        <label class="form-label">Link (Opsional jika Upload File)</label>
                        <input type="url" class="form-control" id="link_url" name="link_url" placeholder="https://drive.google.com/...">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Upload
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script src="<?= BASE_URL ?>assets/js/akreditasi_institusi.js"></script>