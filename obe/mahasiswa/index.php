<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';

$units = [];

if (!Auth::isAuditee()) {
    $stmtUnits = $conn->prepare("SELECT id, code, name FROM units WHERE type = 'Program Studi' ORDER BY name ASC");
    $stmtUnits->execute();
    $units = $stmtUnits->get_result()->fetch_all(MYSQLI_ASSOC);
}

require_once __DIR__ . '/../../layouts/app.php';

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<style>
    #mhsPage { font-size: 13px; }
    #mhsPage .page-title { font-size: 17px; font-weight: 700; color: #1e1b3a; margin-bottom: 2px; }
    #mhsPage .page-subtitle { font-size: 12px; color: #8a8698; margin-bottom: 16px; }
    #mhsPage .nav-pills .nav-link { font-size: 12px; padding: 6px 14px; border-radius: 20px; color: #6b6785; font-weight: 500; }
    #mhsPage .nav-pills .nav-link.active { background: #7c3aed; color: #fff; }
    #mhsPage table { font-size: 12.5px; }
    #mhsPage table thead th { background: #faf9fd; color: #6b6785; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; padding: 5px 8px; border-bottom: 1px solid #eceaf5; }
    #mhsPage table tbody td { padding: 4px 8px; vertical-align: middle; color: #2d2a45; }
    #mhsPage .btn-sm { font-size: 11.5px; padding: 3px 9px; }
    #mhsPage .status-badge { font-size: 10px; padding: 2px 9px; border-radius: 20px; font-weight: 600; }
</style>

<div class="container-fluid py-4" id="mhsPage">

    <div class="page-title">Master Mahasiswa</div>
    <div class="page-subtitle">Data mahasiswa per Kurikulum, dasar untuk Penilaian dan perhitungan Ketercapaian CPL</div>

    <?php if (!Auth::isAuditee()): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label small">Program Studi</label>
            <select class="form-select" id="mhsUnitSelector" style="max-width:400px;">
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div id="mhsKurikulumTabsWrap" class="mb-3" style="display:none;">
        <ul class="nav nav-pills" id="mhsKurikulumTabs"></ul>
    </div>

    <div class="card shadow-sm" id="mhsListCard" style="display:none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-people-fill me-1"></i> Daftar Mahasiswa <span class="badge bg-secondary ms-1" id="mhsCountBadge">0</span></span>
            <div>
                <button class="btn btn-outline-secondary btn-sm" id="btnImportExcel">
                    <i class="bi bi-file-earmark-excel"></i> Import Excel
                </button>
                <button class="btn btn-primary btn-sm" id="btnAddMhs">
                    <i class="bi bi-plus-circle"></i> Tambah Mahasiswa
                </button>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th width="50">No.</th>
                        <th width="140">NIM</th>
                        <th>Nama</th>
                        <th width="100">Angkatan</th>
                        <th width="110">Status</th>
                        <th width="90">Aksi</th>
                    </tr>
                </thead>
                <tbody id="mhsTableBody">
                    <tr><td colspan="6" class="text-center text-muted">Belum ada data.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="mhsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="mhsForm">
                <input type="hidden" id="mhs_id" name="id">
                <input type="hidden" id="mhs_kurikulum_id" name="kurikulum_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="mhsModalTitle">Tambah Mahasiswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small">NIM</label>
                        <input type="text" class="form-control form-control-sm" id="mhs_nim" name="nim" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Nama Lengkap</label>
                        <input type="text" class="form-control form-control-sm" id="mhs_nama" name="nama" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Angkatan</label>
                            <input type="number" class="form-control form-control-sm" id="mhs_angkatan" name="angkatan" min="2000" max="2100" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">PA (Pembimbing Akademik)</label>
                            <select class="form-select form-select-sm" id="mhs_pa_dosen_id" name="pa_dosen_id">
                                <option value="">-- Belum ditentukan --</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Status</label>
                            <select class="form-select form-select-sm" id="mhs_status" name="status">
                                <option value="Aktif">Aktif</option>
                                <option value="Cuti">Cuti</option>
                                <option value="Lulus">Lulus</option>
                                <option value="DO">DO</option>
                                <option value="Non-Aktif">Non-Aktif</option>
                            </select>
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

<!-- Modal Import Excel -->
<div class="modal fade" id="mhsImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-excel"></i> Import Mahasiswa dari Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">
                    File Excel wajib punya kolom header: <strong>nim</strong>, <strong>nama</strong>, <strong>angkatan</strong> (kolom <strong>status</strong> opsional, default "Aktif").
                </p>
                <input type="file" class="form-control form-control-sm mb-3" id="mhsImportFile" accept=".xlsx,.xls,.csv">
                <div id="mhsImportPreview"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnConfirmImport" style="display:none;">
                    <i class="bi bi-upload"></i> Import Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script>
    MHS_IS_AUDITEE = <?= Auth::isAuditee() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/obe_mahasiswa.js"></script>