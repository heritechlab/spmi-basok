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
    #ipPage { font-size: 13px; }
    #ipPage .page-title { font-size: 17px; font-weight: 700; color: #1e1b3a; margin-bottom: 2px; }
    #ipPage .page-subtitle { font-size: 12px; color: #8a8698; margin-bottom: 16px; }
    #ipPage .indikator-item { border: 1px solid #eceaf5; border-radius: 10px; box-shadow: 0 1px 3px rgba(30,27,58,0.04); padding: 12px 14px; margin-bottom: 12px; }
    #ipPage .indikator-head { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; }
    #ipPage .indikator-text { font-size: 13px; color: #2d2a45; flex-grow:1; }
    #ipPage .teknik-badge { font-size: 10px; background:#f1edfc; color:#7c3aed; padding:2px 8px; border-radius:20px; font-weight:600; white-space:nowrap; margin-right:6px; }
    #ipPage table { font-size: 12px; margin-bottom: 0; margin-top:8px; }
    #ipPage table thead th { background: #faf9fd; color: #6b6785; font-weight: 600; font-size: 10.5px; text-transform: uppercase; letter-spacing: .3px; padding: 5px 10px; border-bottom: 1px solid #eceaf5; }
    #ipPage table tbody td { padding: 6px 10px; vertical-align: middle; color: #2d2a45; }
    #ipPage .btn-sm { font-size: 11px; padding: 3px 8px; }
</style>

<div class="container-fluid py-4" id="ipPage">

    <div class="page-title">Master Indikator &amp; Kriteria Penilaian</div>
    <div class="page-subtitle">Template Indikator dan Rubrik penilaian, berlaku global - tinggal dicentang saat menyusun RPS</div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label small">Filter Bentuk Penilaian</label>
                    <select class="form-select form-select-sm" id="ipFilterBentuk">
                        <option value="">Semua Bentuk Penilaian</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Filter Ranah</label>
                    <select class="form-select form-select-sm" id="ipFilterRanah">
                        <option value="">Semua Ranah</option>
                        <option value="Kognitif">Kognitif</option>
                        <option value="Afektif">Afektif</option>
                        <option value="Psikomotorik">Psikomotorik</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Filter Jenjang</label>
                    <select class="form-select form-select-sm" id="ipFilterJenjang">
                        <option value="">Semua Jenjang</option>
                        <option value="Sarjana">Sarjana</option>
                        <option value="Profesi">Profesi</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <?php if (Auth::canManage()): ?>
    <div class="mb-3">
        <button class="btn btn-primary btn-sm" id="btnAddIndikator">
            <i class="bi bi-plus-circle"></i> Tambah Indikator Penilaian
        </button>
    </div>
    <?php endif; ?>

    <div id="ipListArea">
        <p class="text-center text-muted">Memuat data...</p>
    </div>

</div>

<!-- Modal Tambah/Edit Indikator -->
<div class="modal fade" id="ipModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="ipForm">
                <input type="hidden" id="ip_id" name="id">
                <div class="modal-header">
                    <h5 class="modal-title" id="ipModalTitle">Tambah Indikator Penilaian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Bentuk Penilaian</label>
                            <select class="form-select form-select-sm" id="ip_teknik" name="teknik_penilaian" required>
                                <option value="">-- Pilih Bentuk Penilaian --</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Ranah</label>
                            <select class="form-select form-select-sm" id="ip_ranah" name="taksonomi_ranah" required>
                                <option value="Kognitif">Kognitif</option>
                                <option value="Afektif">Afektif</option>
                                <option value="Psikomotorik">Psikomotorik</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Jenjang</label>
                            <select class="form-select form-select-sm" id="ip_jenjang" name="taksonomi_jenjang" required>
                                <option value="Sarjana">Sarjana</option>
                                <option value="Profesi">Profesi</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2" id="ipDraftRubrikWrap">
                        <label class="form-label small fw-semibold">Kriteria Rubrik</label>
                        <small class="text-muted d-block mb-1">Isi Kriteria dulu, lalu klik "Buat Indikator dari Rubrik" untuk menyusun kalimat Indikator otomatis.</small>
                        <table class="table table-bordered table-sm mb-1">
                            <thead>
                                <tr>
                                    <th>Nama Kriteria</th>
                                    <th width="40"></th>
                                </tr>
                            </thead>
                            <tbody id="ipDraftRubrikBody"></tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnAddDraftRubrikRow">
                            <i class="bi bi-plus-circle"></i> Tambah Baris Kriteria
                        </button>
                    </div>

                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label small mb-0">Indikator</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnBuildIndikator">
                                <i class="bi bi-magic"></i> Buat Indikator dari Rubrik
                            </button>
                        </div>
                        <textarea class="form-control form-control-sm mt-1" id="ip_indikator" name="indikator" rows="2" required></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Urutan Tampil</label>
                        <input type="number" class="form-control form-control-sm" id="ip_sort_order" name="sort_order" value="0" style="max-width:140px;">
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

<!-- Modal Tambah/Edit Rubrik -->
<div class="modal fade" id="rubrikModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rubrikForm">
                <input type="hidden" id="rb_id" name="id">
                <input type="hidden" id="rb_indikator_penilaian_id" name="indikator_penilaian_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="rubrikModalTitle">Tambah Kriteria Rubrik</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small">Nama Kriteria</label>
                        <input type="text" class="form-control form-control-sm" id="rb_nama" name="nama_kriteria" placeholder="Contoh: Ketajaman Analisis" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Deskripsi</label>
                        <textarea class="form-control form-control-sm" id="rb_deskripsi" name="deskripsi" rows="2"></textarea>
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
    IP_CAN_MANAGE = <?= Auth::canManage() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/obe_indikator_penilaian.js"></script>