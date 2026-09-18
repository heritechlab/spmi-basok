<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

require_once __DIR__ . '/../layouts/app.php';

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="container-fluid py-4">

    <div class="greeting-card mb-3">

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
            <?= Auth::canManage()
                ? 'Kelola dokumen resmi Kebijakan Mutu dan Peraturan Mutu institusi.'
                : 'Lihat dan unduh dokumen resmi Kebijakan Mutu dan Peraturan Mutu institusi.' ?>
        </div>

        <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Kebijakan" class="standard-summary-hero">

    </div>

    <h4 class="mb-3">Kebijakan Mutu</h4>

    <div class="row g-3">

        <!-- CARD: KEBIJAKAN MUTU -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100 kebijakan-color-card" style="background:linear-gradient(135deg, #a78bfa, #6d28d9);">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="kebijakan-icon-circle">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <div class="kebijakan-card-title">Kebijakan Mutu</div>
                                <div class="kebijakan-card-subtitle">Dokumen resmi kebijakan mutu institusi</div>
                            </div>
                        </div>
                        <?php if (Auth::canManage()): ?>
                            <button type="button" class="btn btn-sm kebijakan-upload-btn" onclick="Kebijakan.openUpload('kebijakan_mutu')">
                                <i class="bi bi-upload"></i> Unggah
                            </button>
                        <?php endif; ?>
                    </div>

                    <div id="listKebijakanMutu"><p class="mb-0" style="color:rgba(255,255,255,0.75); font-size:12.5px;">Memuat data...</p></div>

                </div>
            </div>
        </div>

        <!-- CARD: PERATURAN MUTU -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100 kebijakan-color-card" style="background:linear-gradient(135deg, #22d3ee, #0e7490);">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="kebijakan-icon-circle">
                                <i class="bi bi-journal-text"></i>
                            </div>
                            <div>
                                <div class="kebijakan-card-title">Peraturan Mutu</div>
                                <div class="kebijakan-card-subtitle">Dokumen resmi peraturan/pedoman mutu</div>
                            </div>
                        </div>
                        <?php if (Auth::canManage()): ?>
                            <button type="button" class="btn btn-sm kebijakan-upload-btn" onclick="Kebijakan.openUpload('peraturan_mutu')">
                                <i class="bi bi-upload"></i> Unggah
                            </button>
                        <?php endif; ?>
                    </div>

                    <div id="listPeraturanMutu"><p class="mb-0" style="color:rgba(255,255,255,0.75); font-size:12.5px;">Memuat data...</p></div>

                </div>
            </div>
        </div>

    </div>

</div>

<!-- Modal Upload -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="uploadForm" enctype="multipart/form-data">
                <input type="hidden" id="upload_category" name="category">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalTitle">Unggah Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small">Judul Dokumen</label>
                        <input type="text" class="form-control form-control-sm" name="title" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small">Nomor Dokumen (Opsional)</label>
                            <input type="text" class="form-control form-control-sm" name="nomor_dokumen" placeholder="Misal: SK 001/LPM/2026">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Tanggal Berlaku (Opsional)</label>
                            <input type="date" class="form-control form-control-sm" name="tanggal_berlaku">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Keterangan (Opsional)</label>
                        <textarea class="form-control form-control-sm" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">File Dokumen (PDF)</label>
                        <input type="file" class="form-control form-control-sm" name="file" accept="application/pdf" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Unggah</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
    KEBIJAKAN_CAN_MANAGE = <?= Auth::canManage() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/kebijakan.js"></script>