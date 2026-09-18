<?php

declare(strict_types=1);

?>

<div class="modal fade" id="implModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Pelaksanaan RTL</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <input type="hidden" id="impl_id">

                <div class="p-3 mb-3 rounded" style="background:#f7f5ff; border:1px solid #e6ddfb;">
                    <div id="impl_finding_info">Memuat data...</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Kegiatan</label>
                    <div id="impl_activity" class="border rounded p-2 bg-light"></div>
                </div>

                <!-- ===================================================== -->
                <!-- BAGIAN AUDITEE: PROGRES & BUKTI -->
                <!-- ===================================================== -->

                <div id="implAuditeeSection" style="display:none;">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan Progres Pelaksanaan</label>
                        <textarea class="form-control" id="progress_note" rows="4"
                            placeholder="Ceritakan progres/hasil pelaksanaan kegiatan ini."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status Pelaksanaan</label>
                        <select class="form-select" id="impl_status">
                            <option value="Belum">Belum</option>
                            <option value="Proses">Proses</option>
                            <option value="Selesai">Selesai</option>
                        </select>
                    </div>

                    <button type="button" class="btn btn-primary btn-sm mb-3" id="btnSaveImplementation">
                        <i class="bi bi-save"></i> Simpan Progres
                    </button>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload Bukti Pelaksanaan</label>
                        <input type="file" class="form-control" id="evidenceFile">
                        <small class="text-muted">Bisa upload lebih dari satu file, satu per satu.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Atau Tambahkan Link</label>
                        <div class="input-group">
                            <input type="url" class="form-control" id="evidenceLink" placeholder="https://drive.google.com/...">
                            <button type="button" class="btn btn-outline-primary" id="btnAddEvidenceLink">
                                <i class="bi bi-plus-circle"></i> Tambah
                            </button>
                        </div>
                        <small class="text-muted">Bisa tambah lebih dari satu link, satu per satu.</small>
                    </div>

                </div>

                <!-- ===================================================== -->
                <!-- DAFTAR BUKTI (dilihat semua role) -->
                <!-- ===================================================== -->

                <div class="mb-3">
                    <label class="form-label fw-semibold d-block">Bukti Pelaksanaan Terupload</label>
                    <div id="evidenceList"></div>
                </div>

<!-- ===================================================== -->
                <!-- HASIL VERIFIKASI (tampil untuk semua role) -->
                <!-- ===================================================== -->

                <div id="implVerificationInfo" class="p-3 rounded mb-3" style="background:#fff7e6; border:1px solid #ffe2a8;">

                    <strong class="d-block mb-2"><i class="bi bi-shield-check"></i> Hasil Verifikasi</strong>

                    <div id="impl_current_verification"></div>

                </div>

                <!-- ===================================================== -->
                <!-- KONTROL VERIFIKASI: ADMIN / KA. LPM -->
                <!-- ===================================================== -->

                <div id="implVerifierSection" class="p-3 rounded" style="background:#fff7e6; border:1px solid #ffe2a8; display:none;">

                    <strong class="d-block mb-2"><i class="bi bi-pencil-square"></i> Verifikasi Ulang</strong>

                    <div class="mb-3">
                        <label class="form-label">Hasil Verifikasi</label>
                        <select class="form-select" id="verification_status">
                            <option value="">Pilih Hasil</option>
                            <option value="Sesuai">Sesuai</option>
                            <option value="Perlu Revisi">Perlu Revisi</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan Verifikasi</label>
                        <textarea class="form-control" id="verification_note" rows="3"></textarea>
                    </div>

                    <button type="button" class="btn btn-warning btn-sm" id="btnVerify">
                        <i class="bi bi-check2-square"></i> Simpan Verifikasi
                    </button>

                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>

        </div>
    </div>
</div>