<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/Auth.php';

?>

<div class="modal fade" id="formulirModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <form id="formulirForm">

                <input type="hidden" id="id" name="id">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Ajukan Formulir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

<div class="modal-body">

                    <div class="alert alert-warning py-2 px-3" id="rejectionNoticeBox" style="display:none; font-size:13px;"></div>

                    <h6 class="fw-bold text-muted mb-3">Informasi Dokumen</h6>

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label">No. Dokumen</label>
                            <input type="text" class="form-control" id="document_number" name="document_number" placeholder="FORM/SPMI/BAAK/001" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Berlaku Sejak</label>
                            <input type="month" class="form-control" id="effective_date" name="effective_date">
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label">Revisi</label>
                            <input type="number" class="form-control" id="revision" name="revision" value="0" min="0">
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label">Jml Halaman</label>
                            <input type="number" class="form-control" id="total_pages" name="total_pages" value="1" min="1">
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Formulir</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="Contoh: FORMULIR PENGAJUAN LEGALISIR IJAZAH" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi (Opsional)</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>

                    <hr>

<h6 class="fw-bold text-muted mb-3">Proses Persetujuan</h6>

                    <?php
                        $processLabels = [
                            'perumusan'    => 'Perumusan',
                            'pemeriksaan'  => 'Pemeriksaan',
                            'persetujuan'  => 'Persetujuan',
                            'penetapan'    => 'Penetapan',
                            'pengendalian' => 'Pengendalian',
                        ];
                    ?>

                    <?php foreach ($processLabels as $key => $label): ?>

                        <div class="border rounded p-3 mb-3">

                            <strong class="d-block mb-2"><?= $label ?></strong>

                            <div class="row">

                                <div class="col-md-6 mb-2">
                                    <label class="form-label small">Nama</label>
                                    <input type="text" class="form-control form-control-sm" id="<?= $key ?>_nama" name="<?= $key ?>_nama">
                                </div>

                                <div class="col-md-6 mb-2">
                                    <label class="form-label small">Jabatan/Unit</label>
                                    <input type="text" class="form-control form-control-sm" id="<?= $key ?>_jabatan" name="<?= $key ?>_jabatan">
                                </div>

                                <div class="col-md-6 mb-2">
                                    <label class="form-label small">Tanggal</label>
                                    <input type="date" class="form-control form-control-sm" id="<?= $key ?>_tanggal" name="<?= $key ?>_tanggal">
                                </div>

                                <div class="col-md-6 mb-2">
                                    <label class="form-label small">Tanda Tangan (Gambar)</label>
                                    <div id="<?= $key ?>_ttd_preview" class="mb-1"></div>
                                    <input type="file" class="form-control form-control-sm" id="<?= $key ?>_ttd" name="<?= $key ?>_ttd" accept="image/jpeg,image/png">
                                    <small class="text-muted">Kosongkan jika tidak ingin mengganti.</small>
                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                    <hr>

                    <div class="mb-2" id="fileWrapper">
                        <label class="form-label">File Isi Formulir (PDF)</label>
                        <div id="filePreview" class="mb-2"></div>
                        <div id="fileLockedNotice" class="alert alert-warning py-2 px-3 mb-0" style="font-size:13px;">
                            <i class="bi bi-info-circle"></i> Simpan dokumen terlebih dahulu (klik "Simpan"), baru kolom upload ini akan aktif.
                        </div>
                        <div class="d-flex gap-2" id="fileUploadRow" style="display:none;">
                            <input type="file" class="form-control" id="formulir_file_input" accept=".pdf">
                            <button type="button" class="btn btn-outline-primary" id="btnUploadFile">
                                <i class="bi bi-upload"></i> Upload
                            </button>
                        </div>
                        <small class="text-muted">Wajib PDF (bukan Word) — supaya bisa tergabung otomatis dengan Halaman Pengesahan saat dicetak. Maks 5 MB.</small>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>

                    <?php if (Auth::canVerifyRtl()): ?>
                    <button type="button" class="btn btn-warning" id="btnRejectFormulir" style="display:none;">
                        <i class="bi bi-x-circle"></i> Tolak
                    </button>
                    <button type="button" class="btn btn-success" id="btnApproveFormulir" style="display:none;">
                        <i class="bi bi-check-circle"></i> Sahkan
                    </button>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary" id="btnSaveFormulir">
                        <i class="bi bi-save"></i>
                        Simpan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>