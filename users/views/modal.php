<?php

declare(strict_types=1);

?>

<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form id="userForm">

                <input type="hidden" id="id" name="id">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Peran (Role)</label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="">Pilih Role</option>
                                <option value="1">Administrator</option>
                                <option value="2">Ketua LPM</option>
                                <option value="3">Auditor</option>
                                <option value="4">Auditee</option>
                                <option value="5">Pimpinan</option>
                            </select>
                        </div>

                    </div>

                    <div class="mb-3" id="unitFieldWrapper" style="display:none;">
                        <label class="form-label">Unit Kerja</label>
                        <select class="form-select" id="unit_id" name="unit_id">
                            <option value="">Pilih Unit Kerja</option>
                        </select>
                        <small class="text-muted">Wajib diisi untuk role Auditee.</small>
                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">No. HP</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>

                    </div>

                    <div class="mb-2">
                        <label class="form-label" id="passwordLabel">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter">
                        <small class="text-muted" id="passwordHint">Wajib diisi saat menambah user baru.</small>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveUser">
                        <i class="bi bi-save"></i>
                        Simpan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>