<?php

declare(strict_types=1);

?>

<div class="modal fade" id="assignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form id="assignmentForm" enctype="multipart/form-data">

                <input type="hidden" id="id" name="id">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Penugasan Audit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor Penugasan</label>
                            <input type="text" class="form-control" id="assignment_number" name="assignment_number"
                                   placeholder="Contoh : PA-001/SIQUA/2026" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Periode Audit</label>
                            <select class="form-select" id="period_id" name="period_id" required>
                                <option value="">Pilih Periode</option>
                            </select>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unit Kerja (Auditee)</label>
                            <select class="form-select" id="auditee_id" name="auditee_id" required>
                                <option value="">Pilih Unit Kerja</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ketua Tim Audit</label>
                            <select class="form-select" id="lead_auditor" name="lead_auditor">
                                <option value="">Pilih Auditor</option>
                            </select>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jenis Audit</label>
                            <select class="form-select" id="audit_type" name="audit_type" required>
                                <option value="AMI">AMI</option>
                                <option value="Audit Internal">Audit Internal</option>
                                <option value="Audit Eksternal">Audit Eksternal</option>
                                <option value="Surveilans">Surveilans</option>
                                <option value="Monitoring">Monitoring</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tanggal Audit</label>
                            <input type="date" class="form-control" id="audit_date" name="audit_date">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Draft">Draft</option>
                                <option value="Dijadwalkan">Dijadwalkan</option>
                                <option value="Berlangsung">Berlangsung</option>
                                <option value="Selesai">Selesai</option>
                            </select>
                        </div>

                    </div>
                    <div class="mb-3">
                        <label class="form-label">Surat Tugas</label>
                        <input type="file" class="form-control" id="document" name="document" accept=".pdf,.doc,.docx">
                        <small class="text-muted">Kosongkan apabila tidak ingin mengganti surat tugas.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Standar yang Berlaku untuk Penugasan Ini</label>
                        <div id="standardCheckboxList" class="border rounded p-2" style="max-height: 220px; overflow-y: auto;">
                            <small class="text-muted">Memuat daftar standar...</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Anggota Tim Auditor (selain Ketua Tim)</label>
                        <div id="teamMemberCheckboxList" class="border rounded p-2" style="max-height: 180px; overflow-y: auto;">
                            <small class="text-muted">Memuat daftar auditor...</small>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveAssignment">
                        <i class="bi bi-save"></i>
                        Simpan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>