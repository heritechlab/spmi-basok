<?php

declare(strict_types=1);

?>

<div class="modal fade" id="deModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="deModalTitle">Desk Evaluation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

            <input type="hidden" id="de_assignment_id">
                <input type="hidden" id="de_standard_id">

<div class="mb-3">
                    <label class="form-label fw-semibold d-block">Evaluasi Diri per Indikator</label>
                    <div class="text-muted small mb-3">
                        Narasikan dengan jelas hasil capaian target tiap indikator berikut
                        (sertakan capaian Anda dan uraikan proses pencapaiannya).
                    </div>

                    <div id="de_indicator_forms">
                        <p class="text-muted">Memuat data indikator...</p>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="btnSaveDe">
                    <i class="bi bi-save"></i> Simpan
                </button>
            </div>

        </div>
    </div>
</div>