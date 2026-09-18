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

    <h4 class="mb-3">Capaian Pembelajaran Lulusan (CPL)</h4>

    <?php if (!Auth::isAuditee()): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label small">Program Studi</label>
            <select class="form-select" id="cplUnitSelector" style="max-width:400px;">
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Daftar CPL</strong>
            <div>
                <button class="btn btn-outline-secondary btn-sm" id="btnViewMatriks">
                    <i class="bi bi-grid-3x3"></i> Lihat Matriks Kegayutan PL-CPL
                </button>
                <button class="btn btn-primary btn-sm" id="btnAddCpl">
                    <i class="bi bi-plus-circle"></i> Tambah CPL
                </button>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th width="100">Kode</th>
                        <th width="140">Aspek</th>
                        <th>Deskripsi</th>
                        <th width="180">Profil Lulusan Terkait</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody id="cplTableBody">
                    <tr><td colspan="5" class="text-center text-muted">Pilih Program Studi terlebih dahulu.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="cplModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="cplForm">
                <input type="hidden" id="cpl_id" name="id">
                <div class="modal-header">
                    <h5 class="modal-title" id="cplModalTitle">Tambah CPL</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small">Kode</label>
                        <input type="text" class="form-control form-control-sm" id="cpl_code" name="code" placeholder="Contoh: CPL1" required style="max-width:200px;">
                    </div>

                    <div class="mb-2">
                        <label class="form-label small">Aspek (boleh lebih dari 1 — centang semua yang relevan)</label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input cpl-aspek-checkbox" type="checkbox" value="Sikap" id="aspek_sikap">
                                <label class="form-check-label small" for="aspek_sikap">Sikap</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input cpl-aspek-checkbox" type="checkbox" value="Pengetahuan" id="aspek_pengetahuan">
                                <label class="form-check-label small" for="aspek_pengetahuan">Pengetahuan</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input cpl-aspek-checkbox" type="checkbox" value="Keterampilan Umum" id="aspek_ku">
                                <label class="form-check-label small" for="aspek_ku">Keterampilan Umum</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input cpl-aspek-checkbox" type="checkbox" value="Keterampilan Khusus" id="aspek_kk">
                                <label class="form-check-label small" for="aspek_kk">Keterampilan Khusus</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2 mt-2">
                        <label class="form-label small">Deskripsi</label>
                        <textarea class="form-control form-control-sm" id="cpl_description" name="description" rows="3" required></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small">Urutan Tampil</label>
                        <input type="number" class="form-control form-control-sm" id="cpl_sort_order" name="sort_order" value="0" style="max-width:120px;">
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Pemetaan ke Profil Lulusan</label>
                        <div id="cplProfilLulusanChecklist" class="border rounded p-2" style="max-height:200px; overflow-y:auto;">
                            <p class="text-muted small mb-0">Memuat daftar Profil Lulusan...</p>
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

<!-- Modal Matriks Kegayutan -->
<div class="modal fade" id="matriksModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-grid-3x3"></i> Matriks Kegayutan Profil Lulusan &amp; CPL</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center" id="matriksTable">
                        <thead class="table-light">
                            <tr><td colspan="2">Memuat...</td></tr>
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
    CPL_IS_AUDITEE = <?= Auth::isAuditee() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/obe_cpl.js"></script>