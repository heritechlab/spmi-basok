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

    <h4 class="mb-3">Master Mata Kuliah</h4>

    <?php if (!Auth::isAuditee()): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label small">Program Studi</label>
            <select class="form-select" id="mkUnitSelector" style="max-width:400px;">
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label small">Kurikulum</label>
            <select class="form-select" id="mkKurikulumSelector" style="max-width:400px;" disabled>
                <option value="">-- Pilih Program Studi dulu --</option>
            </select>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Daftar Mata Kuliah</strong>
            <div>
                <button class="btn btn-outline-secondary btn-sm" id="btnViewMatriksCplMk">
                    <i class="bi bi-grid-3x3"></i> Lihat Matriks Kegayutan CPL-MK
                </button>
                <button class="btn btn-primary btn-sm" id="btnAddMk">
                    <i class="bi bi-plus-circle"></i> Tambah Mata Kuliah
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="mkListArea">
                <p class="text-center text-muted">Pilih Program Studi dan Kurikulum terlebih dahulu.</p>
            </div>
        </div>
    </div>

</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="mkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="mkForm">
                <input type="hidden" id="mk_tahun_ajaran" name="tahun_ajaran">
                <input type="hidden" id="mk_tanggal_revisi_rps" name="tanggal_revisi_rps">
                <input type="hidden" id="mk_gkm_dosen_id" name="gkm_dosen_id">
                <input type="hidden" id="mk_deskripsi" name="deskripsi">
                <input type="hidden" id="mk_media_pembelajaran" name="media_pembelajaran">
                <input type="hidden" id="mk_prasyarat_mk" name="prasyarat_mk">
                <input type="hidden" id="mk_pustaka_utama" name="pustaka_utama">
                <input type="hidden" id="mk_pustaka_pendukung" name="pustaka_pendukung">
                <input type="hidden" id="mk_id" name="id">
                <div class="modal-header">
                    <h5 class="modal-title" id="mkModalTitle">Tambah Mata Kuliah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">Kode (Opsional)</label>
                            <input type="text" class="form-control form-control-sm" id="mk_code" name="code" placeholder="Boleh kosong">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Semester</label>
                            <select class="form-select form-select-sm" id="mk_semester" name="semester" required>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>">Semester <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Jenis MK</label>
                            <select class="form-select form-select-sm" id="mk_jenis" name="jenis_mk" required>
                                <option value="Wajib Nasional">Wajib Nasional</option>
                                <option value="Wajib Institusi">Wajib Institusi</option>
                                <option value="Wajib Prodi">Wajib Prodi</option>
                                <option value="Pilihan Prodi">Pilihan Prodi</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2 mt-2">
                        <label class="form-label small">Nama Mata Kuliah</label>
                        <input type="text" class="form-control form-control-sm" id="mk_name" name="name" required>
                    </div>

                    <input type="hidden" id="mk_kurikulum_id" name="kurikulum_id">

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Kelompok Mata Kuliah</label>
                            <input type="text" class="form-control form-control-sm" id="mk_kelompok" name="kelompok_mk" placeholder="Boleh kosong">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Konsentrasi</label>
                            <input type="text" class="form-control form-control-sm" id="mk_konsentrasi" name="konsentrasi" placeholder="Boleh kosong">
                        </div>
                    </div>

                    <label class="form-label small fw-semibold mt-2">Rincian SKS</label>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">Tatap Muka</label>
                            <input type="number" class="form-control form-control-sm" id="mk_sks_tatap_muka" name="sks_tatap_muka" value="0" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Praktikum</label>
                            <input type="number" class="form-control form-control-sm" id="mk_sks_praktikum" name="sks_praktikum" value="0" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Praktek Lapangan</label>
                            <input type="number" class="form-control form-control-sm" id="mk_sks_praktek_lapangan" name="sks_praktek_lapangan" value="0" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Simulasi</label>
                            <input type="number" class="form-control form-control-sm" id="mk_sks_simulasi" name="sks_simulasi" value="0" min="0">
                        </div>
                    </div>

                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <label class="form-label small">Minimal Nilai Lulus</label>
                            <select class="form-select form-select-sm" id="mk_minimal_nilai" name="minimal_nilai_lulus">
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C" selected>C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Rumpun MK</label>
                            <input type="text" class="form-control form-control-sm" id="mk_rumpun" name="rumpun_mk" placeholder="Boleh kosong">
                        </div>
                    </div>

                    <div class="mb-2 mt-2">
                        <label class="form-label small">Dosen Pengembang RPS</label>
                        <select class="form-select form-select-sm" id="mk_dosen_pengembang_rps" name="dosen_pengembang_rps_id">
                            <option value="">-- Belum Ditentukan --</option>
                        </select>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="mk_ada_diktat" name="ada_diktat" value="1">
                                <label class="form-check-label small" for="mk_ada_diktat">Ada Diktat</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="mk_ada_silabus" name="ada_silabus" value="1">
                                <label class="form-check-label small" for="mk_ada_silabus">Ada Silabus</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Validasi RPS</label>
                            <select class="form-select form-select-sm" id="mk_validasi_rps" name="validasi_rps">
                                <option value="Belum">Belum</option>
                                <option value="Sudah">Sudah</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2 mt-3">
                        <label class="form-label small fw-semibold">Pemetaan ke CPL</label>
                        <div id="mkCplChecklist" class="border rounded p-2" style="max-height:180px; overflow-y:auto;">
                            <p class="text-muted small mb-0">Memuat daftar CPL...</p>
                        </div>
                    </div>

                    <div class="mb-2 mt-2">
                        <label class="form-label small fw-semibold">Dosen Pengampu</label>
                        <small class="text-muted d-block mb-1">Centang Dosen yang mengampu, lalu tandai salah satu sebagai Koordinator.</small>
                        <div id="mkDosenChecklist" class="border rounded p-2" style="max-height:180px; overflow-y:auto;">
                            <p class="text-muted small mb-0">Memuat daftar Dosen...</p>
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

<!-- Modal Matriks CPL-MK -->
<div class="modal fade" id="matriksCplMkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-grid-3x3"></i> Matriks Kegayutan CPL &amp; Mata Kuliah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small">Tampilkan Semester</label>
                    <select class="form-select form-select-sm" id="matriksSemesterFilter" style="max-width:220px;">
                        <option value="">Semua Semester</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>">Semester <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center" id="matriksCplMkTable" style="font-size:12px;">
                        <thead class="table-light">
                            <tr><td>Memuat...</td></tr>
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
    MK_IS_AUDITEE = <?= Auth::isAuditee() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/obe_mata_kuliah.js"></script>