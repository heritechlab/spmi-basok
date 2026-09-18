<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

if (!Auth::canManage()) {
    die('Anda tidak memiliki akses ke halaman ini.');
}

$accRepository = new AccRepository($conn);
$manageRepository = new AccManageRepository($conn);
$service = new AccManageService($manageRepository, $accRepository);

$tables = $service->getAllTables()['data'];
$criteriaList = $service->getCriteria()['data'];
$prodiUnits = $accRepository->getAllUnits();

require_once __DIR__ . '/../../layouts/app.php';

?>

<div class="container-fluid py-4">

    <h4 class="mb-3">Kelola Monitoring Data Akreditasi</h4>

    <ul class="nav nav-tabs mb-3" id="manageTabs">
        <li class="nav-item">
            <a class="nav-link active" href="#" data-tab="tabelKolom">Tabel &amp; Kolom</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-tab="daftarDokumen">Daftar Dokumen</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-tab="tahunAkademik">Tahun Akademik</a>
        </li>
    </ul>

    <!-- ===================== TAB: TABEL & KOLOM ===================== -->
    <div class="manage-tab-pane" id="pane-tabelKolom">

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Pilih Tabel</label>
                        <select class="form-select" id="tableSelector">
                            <option value="">-- Pilih Tabel --</option>
                            <?php foreach ($tables as $t): ?>
                                <option value="<?= htmlspecialchars($t['code']) ?>"><?= htmlspecialchars($t['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex gap-2">
                        <button class="btn btn-primary btn-sm" id="btnAddTable"><i class="bi bi-plus-circle"></i> Tambah Tabel Baru</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="tableManageContainer">
            <p class="text-muted">Silakan pilih Tabel terlebih dahulu.</p>
        </div>

    </div>

    <!-- ===================== TAB: DAFTAR DOKUMEN ===================== -->
    <div class="manage-tab-pane" id="pane-daftarDokumen" style="display:none;">

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <label class="form-label">Pilih Kriteria</label>
                <select class="form-select" id="docCriteriaSelector">
                    <option value="">-- Pilih Kriteria --</option>
                    <?php foreach ($criteriaList as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div id="documentManageContainer">
            <p class="text-muted">Silakan pilih Kriteria terlebih dahulu.</p>
        </div>

    </div>

    <!-- ===================== TAB: TAHUN AKADEMIK ===================== -->
    <div class="manage-tab-pane" id="pane-tahunAkademik" style="display:none;">

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Daftar Tahun Akademik</strong>
                <button class="btn btn-primary btn-sm" id="btnAddAcademicYear"><i class="bi bi-plus-circle"></i> Tambah Tahun</button>
            </div>
            <div class="card-body">
                <ul class="list-group" id="academicYearList">
                    <li class="list-group-item text-muted">Memuat data...</li>
                </ul>
            </div>
        </div>

    </div>

</div>

<!-- Modal Tabel -->
<div class="modal fade" id="tableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="tableForm">
                <input type="hidden" id="table_id" name="id">
                <div class="modal-header">
                    <h5 class="modal-title" id="tableModalTitle">Tambah Tabel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3" id="tableCodeWrapper">
                        <label class="form-label">Kode Tabel (unik, tanpa spasi)</label>
                        <input type="text" class="form-control" id="table_code" name="code" placeholder="contoh: tabel_4_dosen_tetap">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Judul Tabel</label>
                        <input type="text" class="form-control" id="table_title" name="title" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Deskripsi (opsional)</label>
                        <textarea class="form-control" id="table_description" name="description" rows="2"></textarea>
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

<!-- Modal Kolom -->
<div class="modal fade" id="columnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="columnForm">
                <input type="hidden" id="column_id" name="id">
                <input type="hidden" id="column_table_id" name="table_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="columnModalTitle">Tambah Kolom</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Header Kelompok (opsional, kosongkan jika berdiri sendiri)</label>
                        <input type="text" class="form-control" id="column_group_label" name="group_label" placeholder="contoh: Jumlah Lulusan">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Kolom</label>
                        <input type="text" class="form-control" id="column_label" name="label" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipe Data</label>
                        <select class="form-select" id="column_data_type" name="data_type">
                            <option value="integer">Angka Bulat</option>
                            <option value="decimal">Angka Desimal</option>
                            <option value="text">Teks</option>
                            <option value="checkbox">Centang (√)</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Cara Hitung Baris "Jumlah"</label>
                        <select class="form-select" id="column_total_mode" name="total_mode">
                            <option value="sum">Jumlah (Total)</option>
                            <option value="average">Rata-rata</option>
                            <option value="min">Nilai Minimum</option>
                            <option value="max">Nilai Maksimum</option>
                            <option value="none">Tidak Dihitung (kosong)</option>
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

<!-- Modal Dokumen -->
<div class="modal fade" id="documentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="documentForm">
                <input type="hidden" id="document_id" name="id">
                <input type="hidden" id="document_criteria_id" name="criteria_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="documentModalTitle">Tambah Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Dokumen</label>
                        <input type="text" class="form-control" id="document_name" name="document_name" required>
                    </div>
                    <div class="mb-2">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="document_all_units" checked>
                            <label class="form-check-label" for="document_all_units">Berlaku untuk Semua Unit Kerja</label>
                        </div>
                        <div id="document_unit_checklist_wrapper" style="display:none;">
                            <label class="form-label small">Pilih Unit Kerja (boleh lebih dari satu)</label>
                            <div class="border rounded p-2" style="max-height:220px; overflow-y:auto;">
                                <?php foreach ($prodiUnits as $u): ?>
                                    <div class="form-check">
                                        <input class="form-check-input document-unit-checkbox" type="checkbox" value="<?= $u['id'] ?>" id="doc_unit_<?= $u['id'] ?>">
                                        <label class="form-check-label small" for="doc_unit_<?= $u['id'] ?>">
                                            <?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?><?= !empty($u['type']) ? ' (' . htmlspecialchars($u['type']) . ')' : '' ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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

<script src="<?= BASE_URL ?>assets/js/acc_manage.js"></script>