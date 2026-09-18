<?php

declare(strict_types=1);

?>

<div class="modal fade" id="rtlModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <form id="rtlForm">

                <input type="hidden" id="rtl_id" name="id">
                <input type="hidden" name="rtm_meeting_id" value="<?= (int) $id ?>">

                <div class="modal-header">
                    <h5 class="modal-title" id="rtlModalTitle">Tambah RTL</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3" id="findingSelectWrapper">
                        <label class="form-label">Pilih Temuan</label>
                        <select class="form-select" id="checklist_result_id" name="checklist_result_id" required>
                            <option value="">Pilih Temuan</option>
                        </select>
                    </div>

                    <div id="findingDetail" class="p-2 mb-3 rounded small" style="background:#f7f5ff; border:1px solid #e6ddfb; display:none;"></div>

                    <div class="row mb-3">

                        <div class="col-md-6">
                            <label class="form-label d-block">Important / Not Important</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="importance" value="Important" checked>
                                <label class="form-check-label">Important</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="importance" value="Not Important">
                                <label class="form-check-label">Not Important</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label d-block">Urgent / Not Urgent</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="urgency" value="Urgent" checked>
                                <label class="form-check-label">Urgent</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="urgency" value="Not Urgent">
                                <label class="form-check-label">Not Urgent</label>
                            </div>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kegiatan</label>
                        <textarea class="form-control" id="activity" name="activity" rows="3" required></textarea>
                    </div>

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Waktu Pelaksanaan</label>
                            <input type="text" class="form-control" id="implementation_time" name="implementation_time" placeholder="Contoh: Semester Ganjil 2026/2027">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">PIC</label>
                            <input type="text" class="form-control" id="pic" name="pic">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Anggaran</label>
                            <input type="text" class="form-control" id="budget" name="budget" placeholder="Contoh: Rp 5.000.000">
                        </div>

                    </div>

                    <div class="mb-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="rtl_status" name="status">
                            <option value="Belum">Belum</option>
                            <option value="Proses">Proses</option>
                            <option value="Selesai">Selesai</option>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveRtl">
                        <i class="bi bi-save"></i>
                        Simpan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>