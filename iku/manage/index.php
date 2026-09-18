<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';

if (!Auth::canManage()) {
    die('Anda tidak memiliki akses ke halaman ini.');
}

$repository = new IkuRepository($conn);
$service    = new IkuService($repository);

require_once __DIR__ . '/../../layouts/app.php';

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Kelola Indikator Kinerja Utama (IKU)</h4>
        <a href="<?= BASE_URL ?>iku/" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label small">Tahun Target</label>
            <select class="form-select" id="ikuManageTahun" style="max-width:200px;">
                <?php for ($y = (int) date('Y') + 1; $y >= (int) date('Y') - 1; $y--): ?>
                    <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" style="font-size:13px;" id="ikuManageTable">
                    <thead class="table-light">
                        <tr>
                            <th width="90">Kode</th>
                            <th>Nama Indikator</th>
                            <th width="90">Satuan</th>
                            <th width="90">Arah</th>
                            <th width="100">Kategori</th>
                            <th width="90">Baseline</th>
                            <th width="90">Target</th>
                            <th width="100">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="ikuManageTbody">
                        <tr><td colspan="8" class="text-center text-muted">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Modal Penugasan Unit Kerja -->
<div class="modal fade" id="ikuUnitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Penugasan Unit Kerja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="unit_indicator_id">
                <p class="small text-muted" id="unitModalIndicatorName"></p>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="iku_all_units" checked>
                    <label class="form-check-label small" for="iku_all_units">Berlaku untuk Semua Unit Kerja</label>
                </div>

                <div id="ikuUnitChecklistWrapper" style="display:none;">
                    <div class="border rounded p-2" style="max-height:250px; overflow-y:auto;" id="ikuUnitCheckboxList"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSaveIkuUnits">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Indikator -->
<div class="modal fade" id="ikuEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="ikuEditForm">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Indikator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small">Kode</label>
                        <input type="text" class="form-control form-control-sm" id="edit_code" name="code">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Nama Indikator</label>
                        <textarea class="form-control form-control-sm" id="edit_name" name="name" rows="2"></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">Satuan</label>
                            <input type="text" class="form-control form-control-sm" id="edit_satuan" name="satuan">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Arah</label>
                            <select class="form-select form-select-sm" id="edit_direction" name="direction">
                                <option value="tinggi">Tinggi (↑)</option>
                                <option value="rendah">Rendah (↓)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Kategori</label>
                            <select class="form-select form-select-sm" id="edit_kategori" name="kategori">
                                <option value="wajib">Wajib</option>
                                <option value="pilihan">Pilihan</option>
                                <option value="partisipatif">Partisipatif</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="edit_is_selected" name="is_selected">
                        <label class="form-check-label small" for="edit_is_selected">Dipilih/Aktif untuk Institusi ini</label>
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
<script src="<?= BASE_URL ?>assets/js/iku_manage.js"></script>