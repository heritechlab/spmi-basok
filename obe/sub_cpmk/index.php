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

    <h4 class="mb-3">Sub-Capaian Pembelajaran Mata Kuliah (Sub-CPMK)</h4>

    <?php if (!Auth::isAuditee()): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label small">Program Studi</label>
            <select class="form-select" id="subCpmkUnitSelector" style="max-width:400px;">
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div id="subCpmkKurikulumTabsWrap" class="mb-3" style="display:none;">
        <ul class="nav nav-pills" id="subCpmkKurikulumTabs"></ul>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <strong>Daftar Sub-CPMK per Mata Kuliah &amp; CPMK</strong>
        </div>
        <div class="card-body">
            <div id="subCpmkListArea">
                <p class="text-center text-muted">Pilih Program Studi terlebih dahulu.</p>
            </div>
        </div>
    </div>

</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="subCpmkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="subCpmkForm">
                <input type="hidden" id="sub_cpmk_id" name="id">
                <input type="hidden" id="sub_cpmk_cpmk_id" name="cpmk_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="subCpmkModalTitle">Tambah Sub-CPMK</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2" id="subCpmkModalCpmkLabel"></p>
                    <div class="mb-2">
                        <label class="form-label small">Kode</label>
                        <input type="text" class="form-control form-control-sm" id="sub_cpmk_code" name="code" placeholder="Contoh: Sub-CPMK1.1" style="max-width:220px;" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Deskripsi</label>
                        <textarea class="form-control form-control-sm" id="sub_cpmk_description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Bobot (%)</label>
                            <input type="number" class="form-control form-control-sm" id="sub_cpmk_bobot" name="bobot" value="0" min="0" max="100" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Urutan Tampil</label>
                            <input type="number" class="form-control form-control-sm" id="sub_cpmk_sort_order" name="sort_order" value="0">
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
    SUBCPMK_IS_AUDITEE = <?= Auth::isAuditee() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/obe_sub_cpmk.js"></script>