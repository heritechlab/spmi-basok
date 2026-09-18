<?php

declare(strict_types=1);

?>

<div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <form id="itemForm">

                <input type="hidden" name="ptp_meeting_id" value="<?= (int) $id ?>">
                <input type="hidden" id="audit_indicator_id" name="audit_indicator_id">
                <input type="hidden" id="checklist_result_id" name="checklist_result_id">
                <input type="hidden" id="old_indicator" name="old_indicator">
                <input type="hidden" id="old_statement" name="old_statement">
                <input type="hidden" id="old_target" name="old_target">

                <div class="modal-header">
                    <h5 class="modal-title">Usulkan Peningkatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="p-3 mb-3 rounded" style="background:#f7f5ff; border:1px solid #e6ddfb;">
                        <small class="text-muted d-block mb-2">Kondisi Saat Ini (Master Indikator)</small>
                        <div id="item_current_info"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pernyataan Standar (Baru)</label>
                        <textarea class="form-control" id="new_statement" name="new_statement" rows="3"
                            placeholder="Kosongkan jika tidak ingin mengubah pernyataan standar."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Indikator (Baru)</label>
                        <textarea class="form-control" id="new_indicator" name="new_indicator" rows="2"
                            placeholder="Kosongkan jika tidak ingin mengubah kalimat indikator."></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Target (Baru)</label>
                        <input type="text" class="form-control" id="new_target" name="new_target"
                            placeholder="Contoh: 95% (kosongkan jika tidak ingin mengubah target)">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveItem">
                        <i class="bi bi-save"></i>
                        Simpan Usulan
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>