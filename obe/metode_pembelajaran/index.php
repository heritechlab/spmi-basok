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
    #mpPage { font-size: 13px; }
    #mpPage .page-title { font-size: 17px; font-weight: 700; color: #1e1b3a; margin-bottom: 2px; }
    #mpPage .page-subtitle { font-size: 12px; color: #8a8698; margin-bottom: 16px; }
    #mpPage .kategori-card { border: 1px solid #eceaf5; border-radius: 10px; box-shadow: 0 1px 3px rgba(30,27,58,0.04); margin-bottom: 16px; }
    #mpPage .kategori-header { background: #faf9fd; border-bottom: 1px solid #eceaf5; padding: 12px 16px; }
    #mpPage .kategori-name { font-size: 13.5px; font-weight: 700; color: #3f3a5c; }
    #mpPage table { font-size: 12.5px; margin-bottom: 0; }
    #mpPage table thead th { background: #faf9fd; color: #6b6785; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; padding: 6px 12px; border-bottom: 1px solid #eceaf5; }
    #mpPage table tbody td { padding: 7px 12px; vertical-align: middle; color: #2d2a45; }
    #mpPage .btn-sm { font-size: 11.5px; padding: 3px 9px; }
    #mpPage .detail-row { display: none; background: #faf9fd; }
    #mpPage .detail-row td { padding: 12px 16px; }
    #mpPage .detail-label { font-size: 10.5px; text-transform: uppercase; letter-spacing: .3px; color: #7c3aed; font-weight: 700; margin-bottom: 4px; }
    #mpPage .detail-text { white-space: pre-line; font-size: 12px; color: #3f3a5c; margin-bottom: 10px; }
</style>

<div class="container-fluid py-4" id="mpPage">

    <?php if (!$embedMode): ?>
    <div class="page-title">Master Metode Pembelajaran</div>
    <div class="page-subtitle">Daftar baku Metode Pembelajaran (SN-Dikti) beserta Aktivitas Mahasiswa &amp; Dosen</div>
    <?php endif; ?>

    <?php if (Auth::canManage()): ?>
    <div class="mb-3">
        <button class="btn btn-primary btn-sm" id="btnAddMetode">
            <i class="bi bi-plus-circle"></i> Tambah Metode Pembelajaran
        </button>
    </div>
    <?php endif; ?>

    <div id="mpListArea">
        <p class="text-center text-muted">Memuat data...</p>
    </div>

</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="mpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="mpForm">
                <input type="hidden" id="mp_id" name="id">
                <div class="modal-header">
                    <h5 class="modal-title" id="mpModalTitle">Tambah Metode Pembelajaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label small">Kategori</label>
                            <select class="form-select form-select-sm" id="mp_kategori" name="kategori" required>
                                <option value="Reguler">Reguler</option>
                                <option value="Program Studi Profesi">Program Studi Profesi</option>
                                <option value="Daring">Daring</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small">Nama Metode</label>
                            <input type="text" class="form-control form-control-sm" id="mp_nama" name="nama_metode" required>
                        </div>
                    </div>
                    <div class="mb-2 mt-2">
                        <label class="form-label small">Aktivitas Mahasiswa</label>
                        <textarea class="form-control form-control-sm" id="mp_aktivitas_mahasiswa" name="aktivitas_mahasiswa" rows="3"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Aktivitas Dosen</label>
                        <textarea class="form-control form-control-sm" id="mp_aktivitas_dosen" name="aktivitas_dosen" rows="3"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Urutan Tampil</label>
                        <input type="number" class="form-control form-control-sm" id="mp_sort_order" name="sort_order" value="0" style="max-width:140px;">
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
    MP_CAN_MANAGE = <?= Auth::canManage() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/obe_metode_pembelajaran.js"></script>