<?php

declare(strict_types=1);

?>

<div class="modal fade" id="rtmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form id="rtmForm">

                <input type="hidden" id="id" name="id">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Rapat RTM</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor BAP/Rapat</label>
                            <input type="text" class="form-control" id="meeting_number" name="meeting_number"
                                placeholder="Contoh: 001/RTM/SIQUA/2026" required>
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
                            <label class="form-label">Tanggal Rapat</label>
                            <input type="date" class="form-control" id="meeting_date" name="meeting_date">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Draft">Draft</option>
                                <option value="Selesai">Selesai</option>
                            </select>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">Agenda</label>
                        <textarea class="form-control" id="agenda" name="agenda" rows="2"
                            placeholder="Contoh: Tinjauan hasil AMI Siklus 001"></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Notulen Ringkas</label>
                        <textarea class="form-control" id="minutes" name="minutes" rows="4"
                            placeholder="Ringkasan jalannya rapat. Dokumen Notulen lengkap dapat diupload di halaman Kelola setelah data ini disimpan."></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveRtm">
                        <i class="bi bi-save"></i>
                        Simpan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>