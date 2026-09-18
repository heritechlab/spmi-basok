<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../layouts/app.php';

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    #padPage { font-size: 13px; }
    #padPage .page-title { font-size: 19px; font-weight: 800; color: #14112b; margin-bottom: 2px; }
    #padPage .page-subtitle { font-size: 12.5px; color: #8a8698; margin-bottom: 20px; }
    #padPage table { font-size: 12.5px; }
    #padPage table thead th { background: #faf9fd; color: #6b6785; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; padding: 8px 10px; }
    #padPage .badge-aktif { background: #16a34a; color: #fff; font-size: 10.5px; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
    #padPage .badge-nonaktif { background: #f1edfc; color: #7c3aed; font-size: 10.5px; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
</style>

<div class="container-fluid py-4" id="padPage">

    <div class="page-title">Periode Akademik</div>
    <div class="page-subtitle">Kelola Tahun Ajaran &amp; Semester berjalan (Ganjil/Genap) untuk seluruh sistem OBE</div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-calendar3"></i> Daftar Periode Akademik</span>
            <button class="btn btn-primary btn-sm" id="btnAddPeriode">
                <i class="bi bi-plus-circle"></i> Tambah Periode
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Tahun Ajaran</th>
                            <th>Semester</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th class="text-center">Status</th>
                            <th width="200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="padTableBody">
                        <tr><td colspan="6" class="text-center text-muted">Memuat...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

    <div class="card shadow-sm mt-3">
        <div class="card-header"><i class="bi bi-clipboard-plus"></i> Salin Data dari Periode Lain</div>
        <div class="card-body">
            <p class="text-muted small mb-3">Menyalin RPS, Rencana Evaluasi, Tim Teaching, dan Rubrik Penilaian dari satu Periode ke Periode lain (untuk semua Mata Kuliah sekaligus). Data Nilai Mahasiswa &amp; Jadwal Dosen TIDAK ikut disalin (memang harus mulai kosong tiap Periode).</p>
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">Salin DARI Periode</label>
                    <select class="form-select" id="padCopySource"></select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small">Salin KE Periode</label>
                    <select class="form-select" id="padCopyTarget"></select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-warning w-100" id="btnCopyPeriode"><i class="bi bi-clipboard-plus"></i> Salin</button>
                </div>
            </div>
        </div>
    </div>

<!-- Modal Tambah Periode -->
<div class="modal fade" id="padModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="padForm">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Periode Akademik</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Tahun Ajaran</label>
                        <input type="text" class="form-control" name="tahun_ajaran" placeholder="contoh: 2026/2027" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Jenis Semester</label>
                        <select class="form-select" name="jenis_semester" required>
                            <option value="">-- Pilih --</option>
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Tanggal Mulai (opsional)</label>
                            <input type="date" class="form-control" name="tanggal_mulai">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Tanggal Selesai (opsional)</label>
                            <input type="date" class="form-control" name="tanggal_selesai">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>assets/js/obe_periode_akademik.js"></script>