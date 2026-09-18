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

<style>
    #cpPage { font-size: 13px; }
    #cpPage .page-title { font-size: 17px; font-weight: 700; color: #1e1b3a; margin-bottom: 2px; }
    #cpPage .page-subtitle { font-size: 12px; color: #8a8698; margin-bottom: 16px; }
    #cpPage .card { border: 1px solid #eceaf5; border-radius: 10px; box-shadow: 0 1px 3px rgba(30,27,58,0.04); }
    #cpPage .card-header { background: #faf9fd; border-bottom: 1px solid #eceaf5; font-size: 13px; font-weight: 600; color: #3f3a5c; padding: 10px 14px; }
    #cpPage .card-body { padding: 14px; }
    #cpPage .form-label.small { font-size: 11.5px; font-weight: 600; color: #6b6785; text-transform: uppercase; letter-spacing: .3px; margin-bottom: 4px; }
    #cpPage .form-select-sm, #cpPage .form-control-sm { font-size: 12.5px; }
    #cpPage .nav-pills .nav-link { font-size: 12px; padding: 6px 14px; border-radius: 20px; color: #6b6785; font-weight: 500; }
    #cpPage .nav-pills .nav-link.active { background: #7c3aed; color: #fff; }
    #cpPage .mk-selector-icon { width: 42px; height: 42px; flex-shrink: 0; background: #f1edfc; color: #7c3aed; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    #cpPage #cpMkSelector { font-size: 13.5px; font-weight: 600; color: #2d2a45; border: 1px solid #e2dff2; padding: 8px 12px; border-radius: 8px; }
    #cpPage #cpMkSelector:focus { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.1); }
    #cpPage #cpMkInfoCard { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); border: none; }
    #cpPage #cpMkInfoCard .card-body { padding: 16px 18px; }
    #cpPage #cpMkInfoCard .info-label { font-size: 10.5px; text-transform: uppercase; letter-spacing: .4px; color: rgba(255,255,255,.7); font-weight: 600; margin-bottom: 2px; }
    #cpPage #cpMkInfoCard .info-value { font-size: 13.5px; font-weight: 700; color: #fff; }
    #cpPage table { font-size: 12.5px; }
    #cpPage table thead th { background: #faf9fd; color: #6b6785; font-weight: 600; font-size: 11.5px; text-transform: uppercase; letter-spacing: .3px; padding: 8px 10px; border-bottom: 1px solid #eceaf5; }
    #cpPage table tbody td { padding: 8px 10px; vertical-align: middle; color: #2d2a45; }
    #cpPage table tbody tr:hover { background: #faf9fd; }
    #cpPage .btn-sm { font-size: 11.5px; padding: 3px 9px; }
    #cpPage .badge-pertemuan { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; background:#f1edfc; color:#7c3aed; font-weight:700; border-radius:50%; font-size:11.5px; }
</style>

<div class="container-fluid py-4" id="cpPage">

    <div class="page-title">Capaian Pembelajaran</div>
    <div class="page-subtitle">Pemetaan CPMK, Sub-CPMK, dan Bobot Penilaian per pertemuan</div>

    <?php if (!Auth::isAuditee()): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label small">Program Studi</label>
            <select class="form-select" id="cpUnitSelector" style="max-width:400px;">
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div id="cpKurikulumTabsWrap" class="mb-3" style="display:none;">
        <ul class="nav nav-pills" id="cpKurikulumTabs"></ul>
    </div>

    <div class="card shadow-sm mb-3" id="cpMkSelectorCard" style="display:none;">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="mk-selector-icon"><i class="bi bi-journal-bookmark-fill"></i></div>
            <div class="flex-grow-1">
                <label class="form-label small mb-1">Pilih Mata Kuliah</label>
                <select class="form-select" id="cpMkSelector">
                    <option value="">-- Pilih Mata Kuliah --</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3" id="cpMkInfoCard" style="display:none;">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><div class="info-label">Tahun Kurikulum</div><div class="info-value" id="cpInfoTahun">-</div></div>
                <div class="col-md-3"><div class="info-label">Kode Mata Kuliah</div><div class="info-value" id="cpInfoKode">-</div></div>
                <div class="col-md-3"><div class="info-label">Nama Mata Kuliah</div><div class="info-value" id="cpInfoNama">-</div></div>
                <div class="col-md-3"><div class="info-label">SKS Total</div><div class="info-value" id="cpInfoSks">-</div></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm" id="cpListCard" style="display:none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-clipboard-data me-1"></i> Capaian Pembelajaran per Pertemuan</span>
            <button class="btn btn-primary btn-sm" id="btnAddCp">
                <i class="bi bi-plus-circle"></i> Tambah Capaian
            </button>
        </div>
        <div class="card-body">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th width="70" rowspan="2" class="align-middle">Pertemuan</th>
                        <th width="90" rowspan="2" class="align-middle">CPMK</th>
                        <th width="100" rowspan="2" class="align-middle">Sub-CPMK</th>
                        <th rowspan="2" class="align-middle">Indikator Penilaian</th>
                        <th width="150" rowspan="2" class="align-middle">Bentuk Penilaian & Evaluasi</th>
                        <th width="140" colspan="2" class="text-center">Bobot</th>
                        <th width="100" rowspan="2" class="align-middle">Aksi</th>
                    </tr>
                    <tr>
                        <th width="70" class="text-center">Per Evaluasi</th>
                        <th width="70" class="text-center">Penilaian</th>
                    </tr>
                </thead>
                <tbody id="cpTableBody">
                    <tr><td colspan="8" class="text-center text-muted">Belum ada data.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="cpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="cpForm">
                <input type="hidden" id="cp_id" name="id">
                <input type="hidden" id="cp_mata_kuliah_id" name="mata_kuliah_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="cpModalTitle">Tambah Capaian Pembelajaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">Pertemuan Ke-</label>
                            <select class="form-select form-select-sm" id="cp_pertemuan" name="pertemuan" required>
                                <?php for ($i = 1; $i <= 16; $i++): ?>
                                    <option value="<?= $i ?>">Pertemuan <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">CPMK</label>
                            <select class="form-select form-select-sm" id="cp_cpmk_id" name="cpmk_id" required>
                                <option value="">-- Pilih CPMK --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Sub-CPMK</label>
                            <select class="form-select form-select-sm" id="cp_sub_cpmk_id" name="sub_cpmk_id">
                                <option value="">-- Pilih CPMK dulu --</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2 mt-2">
                        <label class="form-label small">Indikator Penilaian</label>
                        <textarea class="form-control form-control-sm" id="cp_indikator" name="indikator_penilaian" rows="2"></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small">Bentuk Penilaian & Evaluasi</label>
                        <input type="text" class="form-control form-control-sm" id="cp_bentuk_evaluasi" name="bentuk_evaluasi" placeholder="Contoh: Tugas, Quiz, Presentasi">
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Bobot per Evaluasi (%)</label>
                            <input type="number" class="form-control form-control-sm" id="cp_bobot_per_evaluasi" name="bobot_per_evaluasi" value="0" min="0" max="100" step="0.01" readonly style="background:#f1f0f8;">
                            <small class="text-muted">Otomatis dari Bobot Sub-CPMK</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Bobot Penilaian (%)</label>
                            <input type="number" class="form-control form-control-sm" id="cp_bobot_penilaian" name="bobot_penilaian" value="0" min="0" max="100" step="0.01" required>
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
<script>
    CP_IS_AUDITEE = <?= Auth::isAuditee() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/obe_capaian_pembelajaran.js"></script>