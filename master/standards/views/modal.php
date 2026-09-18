<?php

declare(strict_types=1);

?>

<div
    class="modal fade"
    id="standardModal"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form
                id="standardForm"
                enctype="multipart/form-data">

                <input
                    type="hidden"
                    id="id"
                    name="id">

                <!-- ========================================= -->
                <!-- HEADER -->
                <!-- ========================================= -->

                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="modalTitle">

                        Tambah Standar

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"></button>

                </div>

                <!-- ========================================= -->
                <!-- BODY -->
                <!-- ========================================= -->

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kode Standar</label>
                            <input type="text" class="form-control" id="code" name="code" maxlength="30" required>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label">Revisi</label>
                            <input type="number" class="form-control" id="revision" name="revision" value="0" min="0">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="status_id" name="status_id" required>
                                <option value="">Pilih Status</option>
                            </select>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tanggal Terbit</label>
                            <input type="date" class="form-control" id="publish_date" name="publish_date">
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jenis Standar</label>
                            <select class="form-select" id="type_id" name="type_id" required>
                                <option value="">Pilih Jenis Standar</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Pilih Jenis Standar dahulu</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jenis Dokumen</label>
                            <select class="form-select" id="document_type_id" name="document_type_id" required>
                                <option value="">Pilih Jenis Dokumen</option>
                            </select>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Standar</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nomor Dokumen</label>
                        <input type="text" class="form-control" id="document_number" name="document_number" maxlength="100" required>
                    </div>

                <hr>

                    <h6 class="fw-bold text-muted mb-3">Isi Standar (untuk Cetak PDF)</h6>

                    <div class="mb-3">
                        <label class="form-label">1. Rasional</label>
                        <textarea class="form-control" id="rasional" name="rasional" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">2. Pihak yang Bertanggung Jawab</label>
                        <textarea class="form-control" id="pihak_bertanggung_jawab" name="pihak_bertanggung_jawab" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">3. Definisi Istilah</label>
                        <textarea class="form-control" id="definisi_istilah" name="definisi_istilah" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">4. Pernyataan Standar</label>
                        <small class="text-muted d-block mb-2">
                            Bisa lebih dari satu — nantinya dipilih dari dropdown saat mengisi Master Indikator.
                        </small>

                        <div id="statementList"></div>

                        <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="btnAddStatement">
                            <i class="bi bi-plus-circle"></i> Tambah Pernyataan
                        </button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">5. Dokumen Terkait Pelaksanaan Standar</label>
                        <textarea class="form-control" id="dokumen_terkait" name="dokumen_terkait" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">6. Referensi</label>
                        <textarea class="form-control" id="referensi" name="referensi" rows="2"></textarea>
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

                </div>

                <!-- ========================================= -->
                <!-- FOOTER -->
                <!-- ========================================= -->

                <div class="modal-footer">

                    <button
                        type="reset"
                        class="btn btn-secondary">

                        Reset

                    </button>

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">

                        Batal

                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary">

                        Simpan

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- ========================================================= -->
<!-- MODAL DETAIL (READ ONLY) -->
<!-- ========================================================= -->

<div class="modal fade" id="standardDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Detail Standar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">Kode Standar</label>
                        <div class="fw-semibold" id="dt_code">-</div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label text-muted">Revisi</label>
                        <div class="fw-semibold" id="dt_revision">-</div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label text-muted">Status</label>
                        <div class="fw-semibold" id="dt_status">-</div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label text-muted">Tanggal Terbit</label>
                        <div class="fw-semibold" id="dt_publish_date">-</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">Jenis Standar</label>
                        <div class="fw-semibold" id="dt_type">-</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">Kategori</label>
                        <div class="fw-semibold" id="dt_category">-</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">Jenis Dokumen</label>
                        <div class="fw-semibold" id="dt_doctype">-</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Nama Standar</label>
                    <div class="fw-semibold" id="dt_name">-</div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Nomor Dokumen</label>
                    <div class="fw-semibold" id="dt_document_number">-</div>
                </div>

            <div class="mb-3">
                    <a href="#" id="dt_print_link" target="_blank" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> Cetak PDF Standar
                    </a>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>

        </div>
    </div>
</div>