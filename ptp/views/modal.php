<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/Auth.php';

?>

<div class="modal fade" id="ptpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <form id="ptpForm">

                <input type="hidden" id="id" name="id">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Rapat PTP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor Rapat</label>
                            <input type="text" class="form-control" id="meeting_number" name="meeting_number"
                                placeholder="Contoh: 001/PTP/SIQUA/2026" required>
                        </div>

                    <div class="col-md-6 mb-3">
                            <label class="form-label">Periode Audit</label>
                            <select class="form-select" id="period_id" name="period_id" required>
                                <option value="">Pilih Periode</option>
                            </select>
                        </div>

                    </div>

                    <?php if (!Auth::isAuditee()): ?>
                    <div class="mb-3">
                        <label class="form-label">Unit Kerja</label>
                        <select class="form-select" id="unit_id" name="unit_id" required>
                            <option value="">Pilih Unit Kerja</option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tanggal Rapat</label>
                            <input type="date" class="form-control" id="meeting_date" name="meeting_date">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Waktu Mulai</label>
                            <input type="time" class="form-control" id="start_time" name="start_time">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Waktu Selesai</label>
                            <input type="time" class="form-control" id="end_time" name="end_time">
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tempat Pelaksanaan</label>
                        <input type="text" class="form-control" id="venue" name="venue" placeholder="Contoh: Ruang Rapat LPM">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Agenda</label>
                        <textarea class="form-control" id="agenda" name="agenda" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notulen Ringkas</label>
                        <textarea class="form-control" id="minutes" name="minutes" rows="3"></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Draft">Draft</option>
                            <option value="Selesai">Selesai</option>
                        </select>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Peserta Rapat</label>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddParticipant">
                            <i class="bi bi-plus"></i> Tambah Peserta
                        </button>
                    </div>

                    <div id="participantList"></div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSavePtp">
                        <i class="bi bi-save"></i>
                        Simpan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>