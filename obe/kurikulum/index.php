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

    <h4 class="mb-3">Kelola Kurikulum</h4>

    <?php if (!Auth::isAuditee()): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label small">Program Studi</label>
            <select class="form-select" id="kurikulumUnitSelector" style="max-width:400px;">
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
            <strong>Daftar Kurikulum</strong>
            <button class="btn btn-primary btn-sm" id="btnAddKurikulum">
                <i class="bi bi-plus-circle"></i> Tambah Kurikulum
            </button>
        </div>
        <div class="card-body">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th width="80">Tahun</th>
                        <th>Nama Kurikulum</th>
                        <th width="120">Jumlah MK</th>
                        <th width="100">Status</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody id="kurikulumTableBody">
                    <tr><td colspan="5" class="text-center text-muted">Pilih Program Studi terlebih dahulu.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="kurikulumModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="kurikulumForm">
                <input type="hidden" id="kurikulum_id" name="id">
                <div class="modal-header">
                    <h5 class="modal-title" id="kurikulumModalTitle">Tambah Kurikulum</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">Tahun</label>
                            <input type="number" class="form-control form-control-sm" id="kurikulum_tahun" name="tahun" min="2000" max="2100" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small">Nama Kurikulum</label>
                            <input type="text" class="form-control form-control-sm" id="kurikulum_nama" name="nama" placeholder="Contoh: S1 Ilmu Keperawatan - 2026" required>
                        </div>
                    </div>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="kurikulum_is_active" name="is_active" value="1">
                        <label class="form-check-label small" for="kurikulum_is_active">Jadikan Kurikulum Aktif (yang sedang berjalan)</label>
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
    KURIKULUM_IS_AUDITEE = <?= Auth::isAuditee() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/obe_kurikulum.js"></script>