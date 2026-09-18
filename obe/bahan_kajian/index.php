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
    #bkPage { font-size: 13px; }
    #bkPage .page-title { font-size: 17px; font-weight: 700; color: #1e1b3a; margin-bottom: 2px; }
    #bkPage .page-subtitle { font-size: 12px; color: #8a8698; margin-bottom: 16px; }
    #bkPage .mk-title { font-size: 14.5px; font-weight: 700; color: #7c3aed; margin-top: 18px; margin-bottom: 8px; }
    #bkPage .cpmk-card { border: 1px solid #eceaf5; border-radius: 10px; box-shadow: 0 1px 3px rgba(30,27,58,0.04); margin-bottom: 12px; }
    #bkPage .cpmk-header { background: #faf9fd; border-bottom: 1px solid #eceaf5; padding: 10px 14px; font-weight: 700; font-size: 12.5px; color: #3f3a5c; }
    #bkPage .subcpmk-block { padding: 10px 14px; border-bottom: 1px solid #f2f0f9; }
    #bkPage .subcpmk-block:last-child { border-bottom: none; }
    #bkPage .subcpmk-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
    #bkPage .subcpmk-name { font-size: 12.5px; font-weight: 600; color: #2d2a45; }
    #bkPage table { font-size: 12px; margin-bottom: 0; }
    #bkPage table thead th { background: #faf9fd; color: #6b6785; font-weight: 600; font-size: 10.5px; text-transform: uppercase; letter-spacing: .3px; padding: 5px 10px; border-bottom: 1px solid #eceaf5; }
    #bkPage table tbody td { padding: 6px 10px; vertical-align: middle; color: #2d2a45; }
    #bkPage .btn-sm { font-size: 11px; padding: 2px 8px; }
</style>

<div class="container-fluid py-4" id="bkPage">

    <div class="page-title">Master Bahan Kajian</div>
    <div class="page-subtitle">Materi/topik yang dikaitkan ke tiap Sub-CPMK, sebagai dasar penyusunan RPS</div>

    <?php if (!Auth::isAuditee()): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label small">Program Studi</label>
            <select class="form-select" id="bkUnitSelector" style="max-width:400px;">
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div id="bkListArea">
        <p class="text-center text-muted">Pilih Program Studi terlebih dahulu.</p>
    </div>

</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="bkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bkForm">
                <input type="hidden" id="bk_id" name="id">
                <input type="hidden" id="bk_sub_cpmk_id" name="sub_cpmk_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="bkModalTitle">Tambah Bahan Kajian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2" id="bkModalSubLabel"></p>
                    <div class="mb-2">
                        <label class="form-label small">Nama Bahan Kajian</label>
                        <input type="text" class="form-control form-control-sm" id="bk_nama" name="nama_bahan_kajian" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Deskripsi (Opsional)</label>
                        <textarea class="form-control form-control-sm" id="bk_deskripsi" name="deskripsi" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Urutan Tampil</label>
                        <input type="number" class="form-control form-control-sm" id="bk_sort_order" name="sort_order" value="0" style="max-width:140px;">
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
    BK_IS_AUDITEE = <?= Auth::isAuditee() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/obe_bahan_kajian.js"></script>