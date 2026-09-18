<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';

$myUnitId = 0;

if (Auth::isAuditee()) {

    $stmtMe = $conn->prepare("SELECT unit_id FROM users WHERE id = ? LIMIT 1");
    $stmtMe->bind_param("i", $_SESSION['user_id']);
    $stmtMe->execute();
    $myUnitId = (int) ($stmtMe->get_result()->fetch_assoc()['unit_id'] ?? 0);

    $units = [];

} elseif (Auth::canManage()) {

    $stmtUnits = $conn->prepare("SELECT id, code, name FROM units WHERE type = 'Program Studi' ORDER BY name ASC");
    $stmtUnits->execute();
    $units = $stmtUnits->get_result()->fetch_all(MYSQLI_ASSOC);

} else {

    die('Akses ditolak.');
}

$stmtPeriods = $conn->prepare("SELECT id, period_name FROM audit_periods ORDER BY id DESC");
$stmtPeriods->execute();
$periods = $stmtPeriods->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../layouts/app.php';

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="container-fluid py-4">

    <h4 class="mb-3">Kelola Laporan LED — Program Studi</h4>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">Program Studi</label>
                    <?php if (Auth::isAuditee()): ?>
                        <input type="hidden" id="ledUnitSelector" value="<?= $myUnitId ?>">
                        <input type="text" class="form-control" value="Program Studi Anda" disabled>
                    <?php else: ?>
                        <select class="form-select" id="ledUnitSelector">
                            <option value="">-- Pilih Program Studi --</option>
                            <?php foreach ($units as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="col-md-5">
                    <label class="form-label small">Periode</label>
                    <select class="form-select" id="ledPeriodSelector">
                        <option value="">-- Pilih Periode --</option>
                        <?php foreach ($periods as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['period_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="<?= BASE_URL ?>laporan/led/print.php" target="_blank" id="btnPrintLed" class="btn btn-outline-dark w-100" style="pointer-events:none; opacity:0.5;">
                        <i class="bi bi-printer"></i> Cetak
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div id="ledManageArea" style="display:none;">

        <div class="card shadow-sm mb-3">
            <div class="card-header"><strong><i class="bi bi-bar-chart-fill"></i> Kelengkapan Isian LED</strong></div>
            <div class="card-body">
                <div class="progress" style="height:22px;">
                    <div class="progress-bar bg-success" id="ledProgressBar" style="width:0%;">0%</div>
                </div>
                <small class="text-muted" id="ledProgressLabel">0 dari 0 Indikator terisi</small>
            </div>
        </div>

        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabNarasi">Narasi Pendahuluan</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAnalisis">Analisis per Kriteria</button></li>
            <?php if (Auth::canManage()): ?>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabTtd">Tanda Tangan Pengesahan</button></li>
            <?php endif; ?>
        </ul>

        <div class="tab-content">

            <!-- TAB NARASI -->
            <div class="tab-pane fade show active" id="tabNarasi">
                <div class="card shadow-sm">
                    <div class="card-body">

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Kata Pengantar</label>
                            <textarea class="form-control led-narrative-input" data-section="kata_pengantar" rows="4"></textarea>
                        </div>

                        <hr>
                        <h6 class="fw-bold text-muted mb-3">BAB I Pendahuluan</h6>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">A. Dasar Penyusunan</label>
                            <textarea class="form-control led-narrative-input" data-section="dasar_penyusunan" rows="4"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">B. Tim Penyusun</label>
                            <textarea class="form-control led-narrative-input" data-section="tim_penyusun" rows="4" placeholder="Sebutkan susunan Tim Penyusun LED (Ketua, Anggota, dst)"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">C. Mekanisme Kerja Penyusunan</label>
                            <textarea class="form-control led-narrative-input" data-section="mekanisme_kerja" rows="4"></textarea>
                        </div>

                        <hr>
                        <h6 class="fw-bold text-muted mb-3">BAB III Penutup</h6>

                        <div class="mb-2">
                            <label class="form-label fw-semibold">Kesimpulan</label>
                            <textarea class="form-control led-narrative-input" data-section="kesimpulan" rows="5"></textarea>
                        </div>

                        <button type="button" class="btn btn-primary" id="btnSaveNarratives">
                            <i class="bi bi-save"></i> Simpan Narasi
                        </button>

                    </div>
                </div>
            </div>

            <!-- TAB ANALISIS PER KRITERIA -->
            <div class="tab-pane fade" id="tabAnalisis">
                <div id="ledAnalysisList"></div>
            </div>

            <!-- TAB TTD -->
            <?php if (Auth::canManage()): ?>
            <div class="tab-pane fade" id="tabTtd">
                <div class="card shadow-sm">
                    <div class="card-body">

                        <form id="ledSignatureForm" enctype="multipart/form-data">
                            <input type="hidden" name="report_type" value="led">
                            <input type="hidden" name="unit_id" id="sig_unit_id">
                            <input type="hidden" name="period_id" id="sig_period_id">

                            <div class="row">
                                <div class="col-md-6 border-end">
                                    <h6 class="fw-bold mb-3">Ketua Program Studi</h6>
                                    <div class="mb-2">
                                        <label class="form-label small">Nama</label>
                                        <input type="text" class="form-control form-control-sm" name="ketua_tim_nama">
                                    </div>
                                    <input type="hidden" name="ketua_tim_jabatan" value="Ketua Program Studi">
                                    <div class="mb-2">
                                        <label class="form-label small">Tanggal</label>
                                        <input type="date" class="form-control form-control-sm" name="ketua_tim_tanggal">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">Tanda Tangan (Gambar)</label>
                                        <div id="preview_ketua_tim_ttd" class="mb-1"></div>
                                        <input type="file" class="form-control form-control-sm" name="ketua_tim_ttd" accept="image/jpeg,image/png">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3">Ketua LPM / Pimpinan Institusi</h6>
                                    <div class="mb-2">
                                        <label class="form-label small">Nama</label>
                                        <input type="text" class="form-control form-control-sm" name="ketua_lpm_nama">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">Tanggal</label>
                                        <input type="date" class="form-control form-control-sm" name="ketua_lpm_tanggal">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">Tanda Tangan (Gambar)</label>
                                        <div id="preview_ketua_lpm_ttd" class="mb-1"></div>
                                        <input type="file" class="form-control form-control-sm" name="ketua_lpm_ttd" accept="image/jpeg,image/png">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary mt-3">
                                <i class="bi bi-save"></i> Simpan Tanda Tangan
                            </button>

                        </form>

                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>assets/js/laporan_led_manage.js"></script>