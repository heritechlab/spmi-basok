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
    #bpnPage { font-size: 13px; }
    #bpnPage .page-title { font-size: 17px; font-weight: 700; color: #1e1b3a; margin-bottom: 2px; }
    #bpnPage .page-subtitle { font-size: 12px; color: #8a8698; margin-bottom: 16px; }
    #bpnPage table { font-size: 12.5px; }
    #bpnPage table thead th { background: #faf9fd; color: #6b6785; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; padding: 8px 12px; border-bottom: 1px solid #eceaf5; }
    #bpnPage table tbody td { padding: 8px 12px; vertical-align: middle; color: #2d2a45; white-space: pre-line; }
    #bpnPage .btn-sm { font-size: 11.5px; padding: 3px 9px; }
</style>

<div class="container-fluid py-4" id="bpnPage">

    <div class="page-title">Master Bentuk Penilaian</div>
    <div class="page-subtitle">Komponen Bentuk Penilaian berlaku global, dengan deskripsi cakupannya masing-masing</div>

    <?php if (Auth::canManage()): ?>
    <div class="mb-3">
        <button class="btn btn-primary btn-sm" id="btnAddBentukPenilaian">
            <i class="bi bi-plus-circle"></i> Tambah Bentuk Penilaian
        </button>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th width="50">No.</th>
                        <th width="220">Nama Bentuk Penilaian</th>
                        <th>Deskripsi</th>
                        <?php if (Auth::canManage()): ?><th width="100">Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="bpnTableBody">
                    <tr><td colspan="4" class="text-center text-muted">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="bpnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bpnForm">
                <input type="hidden" id="bpn_id" name="id">
                <div class="modal-header">
                    <h5 class="modal-title" id="bpnModalTitle">Tambah Bentuk Penilaian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small">Nama Bentuk Penilaian</label>
                        <input type="text" class="form-control form-control-sm" id="bpn_nama" name="nama_bentuk" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Deskripsi</label>
                        <textarea class="form-control form-control-sm" id="bpn_deskripsi" name="deskripsi" rows="3"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Urutan Tampil</label>
                        <input type="number" class="form-control form-control-sm" id="bpn_sort_order" name="sort_order" value="0" style="max-width:140px;">
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
    BPN_CAN_MANAGE = <?= Auth::canManage() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/obe_bentuk_penilaian.js"></script>