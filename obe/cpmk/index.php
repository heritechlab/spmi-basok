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

<div class="container-fluid py-4">

    <h4 class="mb-3">Capaian Pembelajaran Mata Kuliah (CPMK)</h4>

    <?php if (!Auth::isAuditee()): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label small">Program Studi</label>
            <select class="form-select" id="cpmkUnitSelector" style="max-width:400px;">
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div id="cpmkKurikulumTabsWrap" class="mb-3" style="display:none;">
        <ul class="nav nav-pills" id="cpmkKurikulumTabs"></ul>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Daftar CPMK per Mata Kuliah</strong>
            <button class="btn btn-outline-secondary btn-sm" id="btnViewMatriksCpmkCpl">
                <i class="bi bi-grid-3x3"></i> Lihat Matriks Kegayutan CPMK-CPL
            </button>
        </div>
        <div class="card-body">
            <div id="cpmkListArea">
                <p class="text-center text-muted">Pilih Program Studi terlebih dahulu.</p>
            </div>
        </div>
    </div>

</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="cpmkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="cpmkForm">
                <input type="hidden" id="cpmk_id" name="id">
                <div class="modal-header">
                    <h5 class="modal-title" id="cpmkModalTitle">Tambah CPMK</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cpmk_mata_kuliah_id" name="mata_kuliah_id">
                    <p class="text-muted small mb-2" id="cpmkModalMkLabel"></p>
                    <div class="mb-2">
                        <label class="form-label small">Kode</label>
                        <input type="text" class="form-control form-control-sm" id="cpmk_code" name="code" placeholder="Contoh: CPMK1" style="max-width:200px;" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Deskripsi</label>
                        <textarea class="form-control form-control-sm" id="cpmk_description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Bobot (%)</label>
                            <input type="number" class="form-control form-control-sm" id="cpmk_bobot" name="bobot" value="0" min="0" max="100" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Urutan Tampil</label>
                            <input type="number" class="form-control form-control-sm" id="cpmk_sort_order" name="sort_order" value="0">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">CPL Terkait</label>
                        <select class="form-select form-select-sm" id="cpmk_cpl_id" name="cpl_id" required>
                            <option value="">-- Pilih CPL --</option>
                        </select>
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

<!-- Modal Matriks CPMK-CPL -->
<div class="modal fade" id="matriksCpmkCplModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-grid-3x3"></i> Matriks Kegayutan CPMK &amp; CPL</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small">Tampilkan Mata Kuliah</label>
                    <select class="form-select form-select-sm" id="matriksCpmkMkFilter" style="max-width:320px;">
                        <option value="">Semua Mata Kuliah</option>
                    </select>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center" id="matriksCpmkCplTable" style="font-size:12px;">
                        <thead class="table-light">
                            <tr><td>Memuat...</td></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script>
    CPMK_IS_AUDITEE = <?= Auth::isAuditee() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/obe_cpmk.js"></script>