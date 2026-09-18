<?php

declare(strict_types=1);

?>

<div class="modal fade" id="periodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form id="periodForm">

                <input type="hidden" id="id" name="id">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Periode Audit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Siklus Audit</label>
                        <input type="text" class="form-control" id="period_name" name="period_name"
                               placeholder="Contoh : Siklus Audit Mutu Internal 2026/2027" required>
                    </div>

                    <div class="row">

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tahun</label>
                            <input type="number" class="form-control" id="year" name="year"
                                   min="2000" max="2100" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tahun Akademik</label>
                            <input type="text" class="form-control" id="academic_year" name="academic_year"
                                   placeholder="Contoh : 2026/2027" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Draft">Draft</option>
                                <option value="Aktif">Aktif</option>
                                <option value="Ditutup">Ditutup</option>
                            </select>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="start_date" name="start_date">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" class="form-control" id="end_date" name="end_date">
                        </div>

                    </div>

                    <div class="mb-2">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="alert alert-warning py-2 mb-0" id="activeWarning" style="display:none;">
                        <i class="bi bi-exclamation-triangle"></i>
                        Menyimpan dengan status <strong>Aktif</strong> akan otomatis menutup periode lain yang sedang aktif.
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSavePeriod">
                        <i class="bi bi-save"></i>
                        Simpan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>