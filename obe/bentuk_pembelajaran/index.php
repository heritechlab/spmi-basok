<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';

$embedMode = isset($_GET['embed']) && $_GET['embed'] == '1';

if ($embedMode) {
    require_once __DIR__ . '/../../layouts/header.php';
} else {
    require_once __DIR__ . '/../../layouts/app.php';
}

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    #bpPage { font-size: 13px; }
    #bpPage .page-title { font-size: 17px; font-weight: 700; color: #1e1b3a; margin-bottom: 2px; }
    #bpPage .page-subtitle { font-size: 12px; color: #8a8698; margin-bottom: 16px; }
    #bpPage .kategori-card { border: 1px solid #eceaf5; border-radius: 10px; box-shadow: 0 1px 3px rgba(30,27,58,0.04); margin-bottom: 16px; }
    #bpPage .kategori-header { background: #faf9fd; border-bottom: 1px solid #eceaf5; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; }
    #bpPage .kategori-name { font-size: 13.5px; font-weight: 700; color: #3f3a5c; }
    #bpPage .kategori-waktu-badge { font-size: 10.5px; background: #f1edfc; color: #7c3aed; padding: 3px 10px; border-radius: 20px; font-weight: 600; margin-left: 6px; }
    #bpPage table { font-size: 12.5px; margin-bottom: 0; }
    #bpPage table thead th { background: #faf9fd; color: #6b6785; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; padding: 6px 12px; border-bottom: 1px solid #eceaf5; }
    #bpPage table tbody td { padding: 7px 12px; vertical-align: middle; color: #2d2a45; }
    #bpPage .btn-sm { font-size: 11.5px; padding: 3px 9px; }
</style>

<div class="container-fluid py-4" id="bpPage">

    <?php if (!$embedMode): ?>
    <div class="page-title">Master Bentuk Pembelajaran</div>
    <div class="page-subtitle">Daftar baku Bentuk Pembelajaran (SN-Dikti) berlaku untuk seluruh Program Studi</div>
    <?php endif; ?>

    <?php if (Auth::canManage()): ?>
    <div class="mb-3">
        <button class="btn btn-primary btn-sm" id="btnAddBentuk">
            <i class="bi bi-plus-circle"></i> Tambah Bentuk Pembelajaran
        </button>
    </div>
    <?php endif; ?>

    <div id="bpListArea">
        <p class="text-center text-muted">Memuat data...</p>
    </div>

</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="bpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bpForm">
                <input type="hidden" id="bp_id" name="id">
                <div class="modal-header">
                    <h5 class="modal-title" id="bpModalTitle">Tambah Bentuk Pembelajaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small">Kategori</label>
                        <select class="form-select form-select-sm" id="bp_kategori" name="kategori" required>
                            <option value="Kuliah/Responsi/Tutorial">Kuliah/Responsi/Tutorial (170 menit)</option>
                            <option value="Seminar">Seminar (170 menit)</option>
                            <option value="Praktik/Penelitian/Tugas Akhir">Praktik/Penelitian/Tugas Akhir (170 menit)</option>
                            <option value="Di Luar Program Studi (MBKM)">Di Luar Program Studi (MBKM) (170 menit)</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Nama Bentuk Pembelajaran</label>
                        <input type="text" class="form-control form-control-sm" id="bp_nama" name="nama_bentuk" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Urutan Tampil</label>
                        <input type="number" class="form-control form-control-sm" id="bp_sort_order" name="sort_order" value="0" style="max-width:140px;">
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

<?php if ($embedMode): ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const SIQUA = {
        BASE_URL : "<?= BASE_URL ?>",
        APP_NAME : "<?= APP_NAME ?>",
        VERSION : "<?= APP_VERSION ?>"
    };
</script>
<?php else: ?>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<?php endif; ?>
<script>
    BP_CAN_MANAGE = <?= Auth::canManage() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/obe_bentuk_pembelajaran.js"></script>