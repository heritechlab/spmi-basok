<?php

declare(strict_types=1);

?>

<div class="modal fade" id="auditorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form id="auditorForm" enctype="multipart/form-data">

                <input type="hidden" id="id" name="id">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Auditor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-8 mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">NIDN/NIP</label>
                            <input type="text" class="form-control" id="nidn_nip" name="nidn_nip">
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Kosongkan apabila tidak ingin mengganti password">
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telepon</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor Sertifikat Auditor</label>
                            <input type="text" class="form-control" id="certificate_number" name="certificate_number">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Upload Sertifikat</label>
                            <input type="file" class="form-control" id="certificate" name="certificate" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Kosongkan apabila tidak ingin mengganti sertifikat.</small>
                        </div>

                    </div>

                    <div class="mb-2">
                        <label class="form-label d-block">Status</label>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" value="1" checked>
                            <label class="form-check-label">Aktif</label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" value="0">
                            <label class="form-check-label">Nonaktif</label>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveAuditor">
                        <i class="bi bi-save"></i>
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

<div class="modal fade" id="auditorDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Detail Auditor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label text-muted">Nama Lengkap</label>
                        <div class="fw-semibold" id="dt_full_name">-</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">NIDN/NIP</label>
                        <div class="fw-semibold" id="dt_nidn_nip">-</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">Username</label>
                        <div class="fw-semibold" id="dt_username">-</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">Email</label>
                        <div class="fw-semibold" id="dt_email">-</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">Telepon</label>
                        <div class="fw-semibold" id="dt_phone">-</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">Status</label>
                        <div id="dt_status">-</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">Nomor Sertifikat</label>
                        <div class="fw-semibold" id="dt_certificate_number">-</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">Sertifikat</label>
                        <div id="dt_certificate">-</div>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>

        </div>
    </div>
</div>