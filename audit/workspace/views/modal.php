<?php

declare(strict_types=1);

?>

<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <form id="resultForm">

                <input type="hidden" id="checklist_id" name="checklist_id">

                <div class="modal-header">
                    <h5 class="modal-title">Lembar Kerja Audit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- ===================================================== -->
                    <!-- PEDOMAN STANDAR (READ ONLY) -->
                    <!-- ===================================================== -->

                    <div class="p-3 mb-4 rounded" style="background:#f7f5ff; border:1px solid #e6ddfb;">

                        <div class="mb-2">
                            <small class="text-muted d-block">Standar</small>
                            <strong id="dt_standard">-</strong>
                        </div>

                        <div class="mb-2">
                            <small class="text-muted d-block">Butir / Pernyataan Standar</small>
                            <div id="dt_statement">-</div>
                        </div>

                        <div class="row align-items-end">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Indikator</small>
                                <div id="dt_indicator">-</div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Target</small>
                                <div id="dt_target">-</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-0 fw-semibold">Capaian</label>
                                <input type="text" class="form-control" id="achievement" name="achievement" placeholder="Nilai capaian aktual">
                            </div>
                        </div>

                    </div>

                    <!-- ===================================================== -->
                    <!-- HASIL AUDIT -->
                    <!-- ===================================================== -->

                    <div   div class="mb-2 p-3 rounded" id="deskEvalReference" style="background:#eef7ee; border:1px solid #cfe8cf; display:none;">
                        <small class="text-success fw-semibold d-block mb-1">
                            <i class="bi bi-info-circle"></i> Referensi Desk Evaluation dari Auditee
                        </small>
                        <div id="deskEvalNotes" class="small mb-2"></div>
                        <div id="deskEvalDocs"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hasil Audit Dokumen</label>
                        <textarea class="form-control" id="document_audit_result" name="document_audit_result" rows="3"
                            placeholder="Hasil pemeriksaan dokumen (dapat mengacu pada hasil Desk Evaluation Auditee)"></textarea>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="btnCopyDeskEval" style="display:none;">
                            <i class="bi bi-clipboard-check"></i> Salin dari Desk Evaluation
                        </button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hasil Audit Lapangan / Visitasi</label>
                        <textarea class="form-control" id="field_audit_result" name="field_audit_result" rows="3"
                            placeholder="Hasil observasi/wawancara saat kunjungan lapangan"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hasil Temuan</label>
                        <select class="form-select" id="audit_status" name="audit_status" required>
                            <option value="">Pilih Status Capaian</option>
                            <option value="Menyimpang">Menyimpang</option>
                            <option value="Belum Mencapai">Belum Mencapai</option>
                            <option value="Mencapai">Mencapai</option>
                            <option value="Melampaui">Melampaui</option>
                        </select>
                    </div>

                    <!-- ===================================================== -->
                    <!-- RISK REGISTER TERKAIT (Pasal 23 ayat 2e - rujukan) -->
                    <!-- ===================================================== -->

                    <div class="mb-3 p-3 rounded" id="riskRegisterPanel" style="background:#fef2f2; border:1px solid #fca5a5; display:none;">
                        <small class="text-danger fw-semibold d-block mb-2">
                            <i class="bi bi-shield-exclamation"></i> Risk Register Terkait Standar/Indikator ini
                        </small>
                        <div id="riskRegisterList"></div>
                    </div>

                    <!-- ===================================================== -->
                    <!-- TEMUAN RISIKO TINGGI (Pasal 24 ayat 2d & 3) -->
                    <!-- ===================================================== -->

                    <div class="mb-3 p-3 rounded" style="background:#fff7ed; border:1px solid #fed7aa;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_temuan_risiko_tinggi" name="is_temuan_risiko_tinggi" value="1">
                            <label class="form-check-label fw-semibold text-danger" for="is_temuan_risiko_tinggi">
                                <i class="bi bi-exclamation-triangle-fill"></i> Tandai sebagai Temuan Risiko Tinggi
                            </label>
                        </div>
                        <div id="blockRekomendasiSegera" style="display:none;" class="mt-2">
                            <label class="form-label fw-semibold small">Rekomendasi Mitigasi Segera (urgent, Pasal 24 ayat 3)</label>
                            <textarea class="form-control" id="rekomendasi_mitigasi_segera" name="rekomendasi_mitigasi_segera" rows="2"
                                placeholder="Tindakan mitigasi yang harus SEGERA dilakukan"></textarea>
                        </div>
                    </div>

                    <!-- ===================================================== -->
                    <!-- BLOK KONDISIONAL: TIDAK TERPENUHI / MEMENUHI SEBAGIAN -->
                    <!-- ===================================================== -->

                    <div id="blockGap" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Akar Masalah</label>
                            <textarea class="form-control" id="root_cause" name="root_cause" rows="2"></textarea>
                        </div>
                    </div>

                    <!-- ===================================================== -->
                    <!-- BLOK KONDISIONAL: MEMENUHI / MELAMPAUI -->
                    <!-- ===================================================== -->

                    <div id="blockGood" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Faktor Pendukung</label>
                            <textarea class="form-control" id="supporting_factor" name="supporting_factor" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" id="labelRecommendation">Rekomendasi</label>
                        <textarea class="form-control" id="recommendation" name="recommendation" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Eviden</label>
                        <textarea class="form-control" id="evidence" name="evidence" rows="2"
                            placeholder="Sebutkan dokumen pendukung. (Akan otomatis terhubung ke dokumen yang diupload Auditee setelah fitur Desk Evaluation tersedia)"></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Catatan Tambahan</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveResult">
                        <i class="bi bi-save"></i>
                        Simpan Hasil
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>