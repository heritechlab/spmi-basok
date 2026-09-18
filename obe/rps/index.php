<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';

$units = [];

if (!Auth::isAuditee()) {
    $stmtUnits = $conn->prepare("SELECT id, code, name FROM units WHERE type = 'Program Studi' ORDER BY name ASC");
    $stmtUnits->execute();
    $units = $stmtUnits->get_result()->fetch_all(MYSQLI_ASSOC);
}

$activePeriode = $conn->query("SELECT tahun_ajaran, jenis_semester FROM obe_periode_akademik WHERE is_active = 1 LIMIT 1")->fetch_assoc();

$namaUserRps = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Bapak/Ibu';
$roleRps = $_SESSION['role'] ?? '-';
$jamRps = (int) date('H');
$sapaanRps = $jamRps < 11 ? 'Selamat Pagi' : ($jamRps < 15 ? 'Selamat Siang' : ($jamRps < 18 ? 'Selamat Sore' : 'Selamat Malam'));
$bulanIndoRps = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

require_once __DIR__ . '/../../layouts/app.php';

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<style>
    #rpsModal .select2-container { width: 100% !important; }
    #rpsModal .select2-container--default .select2-selection--multiple {
        border: 1px solid #e2dff2; border-radius: 8px; min-height: 38px; padding: 4px 6px;
    }
    #rpsModal .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.10);
    }
    #rpsModal .select2-selection__choice {
        background: linear-gradient(135deg, #7c3aed, #6d28d9) !important; color: #fff !important;
        border: none !important; border-radius: 6px !important; padding: 2px 8px !important; font-size: 11.5px !important;
    }
    #rpsModal .select2-selection__choice__remove { color: rgba(255,255,255,0.75) !important; margin-right: 5px !important; }
    #rpsModal .select2-selection__choice__remove:hover { color: #fff !important; }
    #rpsModal .select2-dropdown { border: 1px solid #e2dff2 !important; border-radius: 10px !important; box-shadow: 0 12px 28px rgba(20,17,43,0.14); }
    #rpsModal .select2-container--default .select2-results__option--highlighted[aria-selected] { background: #7c3aed !important; }
</style>

<style>
    #rpsPage { font-size: 13px; }
    #rpsPage .page-title { font-size: 17px; font-weight: 700; color: #1e1b3a; margin-bottom: 2px; }
    #rpsPage .page-subtitle { font-size: 12px; color: #8a8698; margin-bottom: 16px; }
    #rpsPage .nav-pills .nav-link { font-size: 12px; padding: 6px 14px; border-radius: 20px; color: #6b6785; font-weight: 500; }
    #rpsPage .nav-pills .nav-link.active { background: #7c3aed; color: #fff; }
    #rpsPage .mk-selector-icon { width: 42px; height: 42px; flex-shrink: 0; background: #f1edfc; color: #7c3aed; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    #rpsPage #rpsMkSelector { font-size: 13.5px; font-weight: 600; color: #2d2a45; border: 1px solid #e2dff2; padding: 8px 12px; border-radius: 8px; }
    #rpsPage #rpsMkInfoCard { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); border: none; }
    #rpsPage #rpsMkInfoCard .card-body { padding: 16px 18px; }
    #rpsPage #rpsMkInfoCard .info-label { font-size: 10.5px; text-transform: uppercase; letter-spacing: .4px; color: rgba(255,255,255,.7); font-weight: 600; margin-bottom: 2px; }
    #rpsPage #rpsMkInfoCard .info-value { font-size: 13.5px; font-weight: 700; color: #fff; }
    #rpsPage table { font-size: 12px; }
    #rpsPage table thead th { background: #faf9fd; color: #6b6785; font-weight: 600; font-size: 10.5px; text-transform: uppercase; letter-spacing: .3px; padding: 7px 9px; border-bottom: 1px solid #eceaf5; text-align: center; vertical-align: middle; }
    #rpsPage table tbody td { padding: 7px 9px; vertical-align: top; color: #2d2a45; }
    #rpsPage .btn-sm { font-size: 11px; padding: 3px 8px; }
    #rpsPage .badge-pertemuan { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; background:#f1edfc; color:#7c3aed; font-weight:700; border-radius:50%; font-size:11.5px; }
    #rpsPage .mini-badge { font-size: 9.5px; background:#f1f0f8; color:#4b4670; padding:2px 6px; border-radius:10px; display:inline-block; margin: 1px 2px 1px 0; }
    #rpsPage .form-section-title { font-size: 12px; font-weight: 700; color: #7c3aed; margin-top: 14px; margin-bottom: 6px; border-bottom: 1px solid #eceaf5; padding-bottom: 4px; }
    #rpsPage .checklist-box { border: 1px solid #e2dff2; border-radius: 8px; padding: 8px; max-height: 160px; overflow-y: auto; }

    /* Modal Premium */
    #rpsModal .modal-content { border: none; border-radius: 18px; overflow: hidden; }
    #rpsModal .modal-header { background: linear-gradient(135deg, #7c3aed, #6d28d9); border: none; padding: 18px 24px; }
    #rpsModal .modal-header .modal-title { color: #fff; font-weight: 700; font-size: 15px; }
    #rpsModal .modal-header .btn-close { filter: invert(1) brightness(2); }
    #rpsModal .modal-body { padding: 22px 26px; background: #fafafd; }
    #rpsModal .rps-section {
        background: #fff; border: 1px solid #eceaf5; border-radius: 14px; padding: 18px; margin-bottom: 16px;
    }
    #rpsModal .rps-section-title {
        font-size: 12.5px; font-weight: 700; color: #7c3aed; text-transform: uppercase; letter-spacing: .4px;
        margin-bottom: 4px; display: flex; align-items: center; gap: 7px;
    }
    #rpsModal .rps-section-hint { font-size: 11px; color: #a39fb5; margin-bottom: 12px; }
    #rpsModal .form-label.small { font-size: 11px; font-weight: 700; color: #6b6785; text-transform: uppercase; letter-spacing: .3px; margin-bottom: 5px; }
    #rpsModal .form-control-sm, #rpsModal .form-select-sm { border: 1px solid #e2dff2; border-radius: 8px; font-size: 12.5px; }
    #rpsModal .form-control-sm:focus, #rpsModal .form-select-sm:focus { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.10); }
    #rpsModal .checklist-box { border: 1px solid #e2dff2; border-radius: 10px; padding: 10px; max-height: 150px; overflow-y: auto; background: #fdfdff; }
    #rpsModal .modal-footer { border: none; padding: 16px 26px; background: #fafafd; }
</style>

<div class="container-fluid py-4" id="rpsPage">

    <div class="greeting-card dashboard-greeting-wide mb-3">

        <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Admin" class="dashboard-hero-img">

        <div class="d-flex justify-content-between align-items-start flex-wrap">

            <div>
                <div class="greeting-date">
                    <i class="bi bi-calendar3"></i>
                    <?= date('d') . ' ' . $bulanIndoRps[(int) date('n')] . ' ' . date('Y') ?>
                    <span class="dashboard-live-clock"><i class="bi bi-clock-fill"></i> <span id="liveClock"><?= date('H:i') ?></span></span>
                </div>

                <div class="indicator-summary-title">
                    <?= $sapaanRps ?>, <?= htmlspecialchars($namaUserRps) ?>!
                </div>

                <div class="indicator-summary-greeting">
                    Rencana Pembelajaran Semester (RPS) &mdash; <?= htmlspecialchars($roleRps) ?>
                </div>
            </div>

            <div class="dashboard-iku-strip">
                <div class="dashboard-iku-item">
                    <div class="dashboard-iku-value" style="font-size:16px;"><?= $activePeriode ? htmlspecialchars($activePeriode['jenis_semester']) : '-' ?></div>
                    <div class="dashboard-iku-label">Semester Aktif</div>
                </div>
                <div class="dashboard-iku-item">
                    <div class="dashboard-iku-value" style="font-size:16px;"><?= $activePeriode ? htmlspecialchars($activePeriode['tahun_ajaran']) : '-' ?></div>
                    <div class="dashboard-iku-label">Tahun Ajaran</div>
                </div>
            </div>

        </div>

    </div>

    <?php if (!Auth::isAuditee()): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label small">Program Studi</label>
            <select class="form-select" id="rpsUnitSelector" style="max-width:400px;">
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div id="rpsKurikulumTabsWrap" class="mb-3" style="display:none;">
        <ul class="nav nav-pills" id="rpsKurikulumTabs"></ul>
    </div>

    <div class="card shadow-sm mb-3" id="rpsMkSelectorCard" style="display:none;">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="mk-selector-icon"><i class="bi bi-journal-bookmark-fill"></i></div>
            <div class="flex-grow-1">
                <label class="form-label small mb-1">Pilih Mata Kuliah</label>
                <select class="form-select" id="rpsMkSelector">
                    <option value="">-- Pilih Mata Kuliah --</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3" id="rpsMkInfoCard" style="display:none;">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><div class="info-label">Tahun Kurikulum</div><div class="info-value" id="rpsInfoTahun">-</div></div>
                <div class="col-md-3"><div class="info-label">Kode Mata Kuliah</div><div class="info-value" id="rpsInfoKode">-</div></div>
                <div class="col-md-3"><div class="info-label">Nama Mata Kuliah</div><div class="info-value" id="rpsInfoNama">-</div></div>
                <div class="col-md-3"><div class="info-label">SKS Total</div><div class="info-value" id="rpsInfoSks">-</div></div>
            </div>
        </div>
    </div>

    <ul class="nav nav-pills mb-3" id="rpsWorkspaceTabs" style="display:none;">
        <li class="nav-item"><button type="button" class="nav-link rps-tab-btn active" data-tab="detail">Detail Mata Kuliah</button></li>
        <li class="nav-item"><button type="button" class="nav-link rps-tab-btn" data-tab="isi-rps">Isi RPS</button></li>
        <li class="nav-item"><button type="button" class="nav-link rps-tab-btn" data-tab="rencana-evaluasi">Rencana Evaluasi</button></li>
        <li class="nav-item"><button type="button" class="nav-link rps-tab-btn" data-tab="jadwal-dosen">Jadwal Dosen</button></li>
        <li class="nav-item"><button type="button" class="nav-link rps-tab-btn" data-tab="rubrik-penilaian">Rubrik Penilaian</button></li>
        <li class="nav-item"><button type="button" class="nav-link rps-tab-btn" data-tab="rencana-tugas">Lampiran Rencana Tugas</button></li>
        <li class="nav-item"><button type="button" class="nav-link rps-tab-btn" data-tab="cetak-rps">Cetak RPS</button></li>
    </ul>

    <div class="rps-tab-panel" id="rpsTabPanelDetail" style="display:none;">
        <div class="card shadow-sm"><div class="card-body" id="rpsDetailMkContent">
            <p class="text-muted">Memuat detail Mata Kuliah...</p>
        </div></div>
    </div>

    <div class="rps-tab-panel" id="rpsTabPanelIsiRps" style="display:none;">
        <div class="card shadow-sm" id="rpsListCard" style="display:none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-calendar-week-fill me-1"></i> RPS per Pertemuan <span class="badge ms-1" id="rpsTotalBobotBadge" style="font-size:10.5px;">Total Bobot: 0%</span></span>
            <button class="btn btn-primary btn-sm" id="btnAddRps">
                <i class="bi bi-plus-circle"></i> Tambah Baris RPS
            </button>
        </div>
        <div class="card-body">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th width="50" rowspan="2">Mg</th>
                        <th width="150" rowspan="2">Sub-CPMK</th>
                        <th colspan="2">Penilaian</th>
                        <th colspan="2">Bentuk Pembelajaran, Metode Pembelajaran</th>
                        <th width="90" rowspan="2">Alokasi Waktu</th>
                        <th width="150" rowspan="2">Materi Pembelajaran</th>
                        <th width="150" rowspan="2">Pengalaman Belajar</th>
                        <th width="80" class="text-center" rowspan="2">Bobot (%)</th>
                        <th width="90" rowspan="2">Aksi</th>
                    </tr>
                    <tr>
                        <th width="130">Indikator</th>
                        <th width="150">Kriteria &amp; Bentuk</th>
                        <th width="130">Luring (offline)</th>
                        <th width="130">Daring (online)</th>
                    </tr>
                </thead>
                <tbody id="rpsTableBody">
                    <tr><td colspan="9" class="text-center text-muted">Belum ada data.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <div class="rps-tab-panel" id="rpsTabPanelRencanaEvaluasi" style="display:none;">
        <div class="card shadow-sm">
            <iframe id="rpsRencanaEvaluasiFrame" src="about:blank" style="width:100%; min-height:640px; border:0; display:block;"></iframe>
        </div>
    </div>

    <div class="rps-tab-panel" id="rpsTabPanelJadwalDosen" style="display:none;">
        <div class="card shadow-sm"><div class="card-body" id="rpsJadwalDosenContent">
            <p class="text-muted">Memuat Jadwal Dosen...</p>
        </div></div>
    </div>

    <div class="rps-tab-panel" id="rpsTabPanelRubrikPenilaian" style="display:none;">
        <div class="card shadow-sm">
            <iframe id="rpsRubrikPenilaianFrame" src="about:blank" style="width:100%; min-height:640px; border:0; display:block;"></iframe>
        </div>
    </div>
    <div class="rps-tab-panel" id="rpsTabPanelRencanaTugas" style="display:none;">
        <div id="rpsRencanaTugasContent">
            <p class="text-muted">Memuat Rencana Tugas...</p>
        </div>
    </div>

    <div class="rps-tab-panel" id="rpsTabPanelCetakRps" style="display:none;">
        <div class="card shadow-sm"><div class="card-body text-center py-5" id="rpsCetakContent">
            <i class="bi bi-printer-fill" style="font-size:40px; color:#7c3aed;"></i>
            <p class="text-muted mt-2 mb-3">Buka pratinjau RPS siap cetak (Cover, Visi Misi, Bagian Awal RPS) di tab baru.</p>
            <button type="button" class="btn btn-primary" id="btnBukaCetakRps">
                <i class="bi bi-box-arrow-up-right"></i> Buka Pratinjau Cetak RPS
            </button>
        </div></div>
    </div>

</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="rpsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="rpsForm">
                <input type="hidden" id="rps_id" name="id">
                <input type="hidden" id="rps_mata_kuliah_id" name="mata_kuliah_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="rpsModalTitle">Tambah Baris RPS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">Pertemuan Ke-</label>
                            <select class="form-select form-select-sm" id="rps_pertemuan" name="pertemuan" required>
                                <?php for ($i = 1; $i <= 16; $i++): ?>
                                    <option value="<?= $i ?>">Pertemuan <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label small">Sub-CPMK</label>
                            <select class="form-select form-select-sm" id="rps_sub_cpmk" name="sub_cpmk_id" required>
                                <option value="">-- Pilih Sub-CPMK --</option>
                            </select>
                        </div>
                    </div>

              <div class="form-section-title"><i class="bi bi-book"></i> Bahan Kajian &amp; Materi Pembelajaran</div>
                    <div class="rps-section-hint">Wajib diisi lebih dulu — Topik untuk Indikator diambil dari sini.</div>
                    <label class="form-label small">Bahan Kajian (tersaring sesuai Sub-CPMK)</label>
                    <div class="checklist-box mb-2" id="rpsBahanKajianChecklist">
                        <p class="text-muted small mb-0">Pilih Sub-CPMK terlebih dahulu.</p>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label small mb-0">Materi Pembelajaran</label>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnBuildMateri"><i class="bi bi-magic"></i> Susun dari Bahan Kajian</button>
                            </div>
                            <textarea class="form-control form-control-sm mt-1" id="rps_materi" name="materi_pembelajaran" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Pustaka</label>
                            <textarea class="form-control form-control-sm" id="rps_pustaka" name="pustaka" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="form-section-title"><i class="bi bi-easel2"></i> Bentuk &amp; Metode Pembelajaran</div>
                    <div class="rps-section-hint">Wajib dicentang/pilih lebih dulu — Aktivitas Pembuka untuk Indikator diambil dari sini.</div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Bentuk Pembelajaran (Luring)</label>
                            <div class="checklist-box mb-2" id="rpsBentukLuringChecklist"></div>
                            <label class="form-label small">Metode Pembelajaran (Luring)</label>
                            <div class="checklist-box" id="rpsMetodeLuringChecklist"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Metode Pembelajaran (Daring)</label>
                            <div class="checklist-box" id="rpsMetodeDaringChecklist"></div>
                        </div>
                    </div>

                    <div class="form-section-title"><i class="bi bi-pencil-square"></i> Indikator Umum &amp; Khusus</div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small">Aktivitas Pembuka (dari Metode dicentang)</label>
                            <select class="form-select form-select-sm" id="rps_builder_aktivitas"></select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Persen (%)</label>
                            <input type="number" class="form-control form-control-sm" id="rps_builder_persen" value="90" min="0" max="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Topik (dari Bahan Kajian)</label>
                            <select class="form-select form-select-sm" id="rps_builder_topik"></select>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label small">KKO Pengetahuan (opsional)</label>
                            <select class="form-select form-select-sm" id="rps_builder_kko_pengetahuan"><option value="">-- Tidak dipakai --</option></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">KKO Sikap (opsional)</label>
                            <select class="form-select form-select-sm" id="rps_builder_kko_sikap"><option value="">-- Tidak dipakai --</option></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">KKO Keterampilan (opsional)</label>
                            <select class="form-select form-select-sm" id="rps_builder_kko_keterampilan"><option value="">-- Tidak dipakai --</option></select>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-2" id="btnSusunIndikatorUmum">
                        <i class="bi bi-magic"></i> Susun ke Indikator Umum
                    </button>

                    <div class="mb-2">
                        <label class="form-label small">Indikator Umum</label>
                        <textarea class="form-control form-control-sm" id="rps_indikator_umum" name="indikator_umum" rows="2"></textarea>
                    </div>

                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label small mb-0">Alokasi Waktu (menit)</label>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnHitungWaktu"><i class="bi bi-calculator"></i> Hitung Otomatis</button>
                            </div>
                            <input type="number" class="form-control form-control-sm mt-1" id="rps_alokasi_waktu" name="alokasi_waktu_menit" value="0" min="0">
                            <input type="hidden" id="rps_alokasi_waktu_rincian_input" name="alokasi_waktu_rincian">
                            <small class="text-muted d-block mt-1" id="rps_alokasi_waktu_rincian" style="font-size:10.5px;"></small>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label small mb-0">Pengalaman Belajar</label>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnBuildPengalaman"><i class="bi bi-magic"></i> Susun dari Metode</button>
                            </div>
                            <textarea class="form-control form-control-sm mt-1" id="rps_pengalaman" name="pengalaman_belajar" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="form-section-title"><i class="bi bi-clipboard-check"></i> Penilaian</div>
                    <div class="rps-section-hint">Otomatis mengikuti Rencana Evaluasi yang sudah ditetapkan untuk Pertemuan ini. Centang salah satu atau lebih kalau ada beberapa Basis Evaluasi.</div>
                    <div id="rpsRencanaEvaluasiChecklist" class="border rounded p-2" style="max-height:220px; overflow-y:auto;"></div>
                    <div id="rpsRencanaEvaluasiKosong" class="border rounded p-2 mt-2" style="background:#fdecec; display:none;">
                        <div class="small text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Belum ada Rencana Evaluasi untuk Pertemuan ini. Silakan tambahkan dulu di menu <strong>Rencana Evaluasi</strong>.</div>
                    </div>
                    <div class="small mt-2"><strong>Total Bobot baris ini:</strong> <span id="rpsDetailBobotTotal" class="fw-bold text-primary">0%</span></div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script>
    RPS_IS_AUDITEE = <?= Auth::isAuditee() ? 'true' : 'false' ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/obe_rps.js"></script>