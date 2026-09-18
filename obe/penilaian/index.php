<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';

$embedMode = isset($_GET['embed']) && $_GET['embed'] == '1';
$presetUnitId = (int) ($_GET['unit_id'] ?? 0);
$presetKurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);
$presetMkId = (int) ($_GET['mata_kuliah_id'] ?? 0);

$units = [];

if (!Auth::isAuditee()) {
    $stmtUnits = $conn->prepare("SELECT id, code, name FROM units WHERE type = 'Program Studi' ORDER BY name ASC");
    $stmtUnits->execute();
    $units = $stmtUnits->get_result()->fetch_all(MYSQLI_ASSOC);
}

if ($embedMode) {
    require_once __DIR__ . '/../../layouts/header.php';
} else {
    require_once __DIR__ . '/../../layouts/app.php';
}

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

<style>
    #pnlPage { font-size: 13px; color: #1e1b3a; }
    #pnlPage .page-title { font-size: 19px; font-weight: 800; color: #14112b; letter-spacing: -0.3px; margin-bottom: 2px; }
    #pnlPage .page-subtitle { font-size: 12.5px; color: #8a8698; margin-bottom: 20px; }
    #pnlPage .nav-pills .nav-link { font-size: 12px; padding: 7px 16px; border-radius: 24px; color: #6b6785; font-weight: 600; transition: all .15s; }
    #pnlPage .nav-pills .nav-link:hover { background: #f1edfc; color: #7c3aed; }
    #pnlPage .nav-pills .nav-link.active { background: linear-gradient(135deg, #0d9488, #0f766e); color: #fff; box-shadow: 0 3px 10px rgba(13,148,136,0.25); }

    #pnlPage .selector-card { border: 1px solid #eceaf5; border-radius: 14px; box-shadow: 0 1px 2px rgba(20,17,43,0.03); }
    #pnlPage .select-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #a39fb5; margin-bottom: 6px; display: block; }

    #pnlPage .mk-selector-icon { width: 44px; height: 44px; flex-shrink: 0; background: #ecfdf9; color: #0d9488; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 19px; }

    #pnlPage table { font-size: 11.5px; }
    #pnlPage table thead th { background: #faf9fd; color: #6b6785; font-weight: 600; font-size: 10px; text-transform: uppercase; letter-spacing: .3px; padding: 8px 10px; border-bottom: 1px solid #eceaf5; text-align: center; white-space: nowrap; }
    #pnlPage table tbody td { padding: 6px 8px; vertical-align: middle; color: #2d2a45; border: 1px solid #eceaf5; }
    #pnlPage .nilai-input { width: 52px; text-align: center; border: 1px solid #e2dff2; border-radius: 4px; padding: 3px; font-size: 11.5px; }
    #pnlPage .nilai-input:focus { border-color: #0d9488; outline: none; box-shadow: 0 0 0 2px rgba(13,148,136,0.15); }
    #pnlPage .nilai-input.saved { background: #eef8f1; }
    #pnlPage .nilai-input.saving { background: #fef3e2; }
    #pnlPage .col-nim { position: sticky; left: 0; background: #fff; z-index: 2; min-width: 100px; }
    #pnlPage .col-nama { position: sticky; left: 100px; background: #fff; z-index: 2; min-width: 160px; text-align: left !important; }
    #pnlPage thead .col-nim, #pnlPage thead .col-nama { background: #faf9fd; z-index: 3; }
    #pnlPage .table-responsive { max-height: 70vh; }

    /* Select2 - gaya premium teal (identitas kartu Mata Kuliah) */
    #pnlPage .select2-container { width: 100% !important; }
    #pnlPage .select2-container--default .select2-selection--single {
        height: auto; border: 1px solid #e2dff2; border-radius: 10px; padding: 9px 12px;
        transition: border-color .15s, box-shadow .15s; background: #fff;
    }
    #pnlPage .select2-container--default .select2-selection--single:hover { border-color: #99e0d6; }
    #pnlPage .select2-container--default.select2-container--open .select2-selection--single,
    #pnlPage .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.12);
    }
    #pnlPage .select2-selection__rendered { padding: 0 !important; font-size: 13px; color: #14112b; font-weight: 500; line-height: 1.4 !important; }
    #pnlPage .select2-selection__placeholder { color: #a39fb5 !important; }
    #pnlPage .select2-selection__arrow { height: 100% !important; top: 0 !important; right: 10px !important; }
    #pnlPage .select2-selection__arrow b { border-color: #0d9488 transparent transparent transparent !important; }
    #pnlPage .select2-dropdown { border: 1px solid #cdece7 !important; border-radius: 12px !important; box-shadow: 0 12px 32px rgba(20,17,43,0.14); overflow: hidden; margin-top: 4px; }
    #pnlPage .select2-search--dropdown { padding: 10px !important; background: #f0fdfa; }
    #pnlPage .select2-search__field { border: 1px solid #cdece7 !important; border-radius: 8px !important; padding: 7px 10px !important; font-size: 12.5px !important; }
    #pnlPage .select2-search__field:focus { outline: none; border-color: #0d9488 !important; }
    #pnlPage .select2-results__option { font-size: 13px; padding: 9px 14px !important; }
    #pnlPage .select2-container--default .select2-results__option--highlighted[aria-selected] { background: #0d9488 !important; color: #fff !important; }
    #pnlPage .select2-container--default .select2-results__option[aria-selected=true] { background: #ecfdf9; color: #0d9488; font-weight: 600; }
</style>

<?php if ($embedMode): ?>
<style>
    #pnlPage .page-title, #pnlPage .page-subtitle { display: none !important; }
    #pnlUnitSelectorWrap, #pnlKurikulumTabsWrap, #pnlMkSelectorCard { display: none !important; }
    #pnlPage { padding-top: 0 !important; }
</style>
<?php endif; ?>

<div class="container-fluid py-4" id="pnlPage">

    <?php if (!$embedMode): $obeActiveCard = 'mk'; require_once __DIR__ . '/../../layouts/obe_dashboard_cards.php'; endif; ?>

    <div class="page-title">Penilaian</div>
    <div class="page-subtitle">Input nilai mentah per Mahasiswa per Pertemuan — tersimpan otomatis</div>

<?php if (!Auth::isAuditee() && !$embedMode): ?>
    <div class="selector-card mb-3" id="pnlUnitSelectorWrap">
        <div class="p-3" style="max-width:420px;">
            <label class="select-label">Program Studi</label>
            <select class="form-select" id="pnlUnitSelector">
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div id="pnlKurikulumTabsWrap" class="mb-3" style="display:none;">
        <ul class="nav nav-pills" id="pnlKurikulumTabs"></ul>
    </div>

    <div class="selector-card mb-3" id="pnlMkSelectorCard" style="display:none;">
        <div class="p-3 d-flex align-items-center gap-3">
            <div class="mk-selector-icon"><i class="bi bi-journal-bookmark-fill"></i></div>
            <div class="flex-grow-1" style="max-width:420px;">
                <label class="select-label">Cari Mata Kuliah</label>
                <select class="form-select" id="pnlMkSelector">
                    <option value="">-- Pilih Mata Kuliah --</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card shadow-sm" id="pnlGridCard" style="display:none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-grid-3x3-gap-fill me-1"></i> Grid Penilaian</span>
            <div>
                <small class="text-muted me-2" id="pnlSaveStatus"></small>
                <button type="button" class="btn btn-sm btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#pnlPanduanModal">
                    <i class="bi bi-book-fill"></i> Lihat Panduan Rubrik Penilaian
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnLihatLaporan">
                    <i class="bi bi-bar-chart-fill"></i> Lihat Laporan
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLaporanDetail">
                    <i class="bi bi-table"></i> Laporan Detail
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" id="pnlGridTable">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Modal Laporan Capaian -->
<div class="modal fade" id="pnlLaporanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border-radius:18px; border:none; overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#0d9488,#0f766e); border:none; padding:20px 24px;">
                <div>
                    <h5 class="modal-title text-white mb-0" style="font-weight:700;"><i class="bi bi-bar-chart-fill"></i> Laporan Nilai Akhir &amp; Ketercapaian CPL</h5>
                    <div style="font-size:11.5px; color:rgba(255,255,255,0.8);" id="pnlLaporanSubtitle">Rata-rata kelas per CPL</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:22px 24px;">

                <div id="pnlCplSummary" class="d-flex flex-wrap gap-2 mb-3"></div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center" id="pnlLaporanTable" style="font-size:12px;">
                        <thead class="table-light">
                            <tr><td>Memuat...</td></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer" style="border:none; padding:14px 24px;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Panduan Rubrik Penilaian -->
<div class="modal fade" id="pnlPanduanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border-radius:18px; border:none; overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#0d9488,#0f766e); border:none; padding:20px 24px;">
                <div>
                    <h5 class="modal-title text-white mb-0" style="font-weight:700;"><i class="bi bi-book-fill"></i> Panduan Rubrik Penilaian</h5>
                    <div style="font-size:11.5px; color:rgba(255,255,255,0.8);">Referensi kriteria penilaian &amp; komposisi Rencana Evaluasi (Case Method &amp; Project Based Learning)</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:22px 24px; max-height:75vh; overflow-y:auto;">

                <ul class="nav nav-pills mb-3" id="pnlPanduanTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#panduanDiskusi" type="button">Rubrik Diskusi / Aktivitas Partisipatif</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#panduanProyek" type="button">Rubrik Tugas Proyek</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#panduanRencana" type="button">Komposisi Rencana Evaluasi</button>
                    </li>
                </ul>

                <div class="tab-content">

                    <!-- Tab 1: Rubrik Diskusi -->
                    <div class="tab-pane fade show active" id="panduanDiskusi">
                        <p class="small text-muted mb-3">Contoh kriteria penilaian untuk komponen <strong>Aktivitas Partisipatif</strong> (mis. komentar/tanggapan diskusi, studi kasus).</p>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" style="font-size:11.5px;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Aspek Penilaian</th>
                                        <th class="text-center">4 (Sangat Baik)</th>
                                        <th class="text-center">3 (Baik)</th>
                                        <th class="text-center">2 (Cukup)</th>
                                        <th class="text-center">1 (Kurang)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Relevansi</strong><br><span class="text-muted">Bobot 30%</span></td>
                                        <td>Sangat relevan dengan topik, menunjukkan pemahaman mendalam</td>
                                        <td>Relevan dengan topik yang dibahas</td>
                                        <td>Cukup relevan dengan topik</td>
                                        <td>Tidak relevan dengan topik</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kedalaman Analisis</strong><br><span class="text-muted">Bobot 25%</span></td>
                                        <td>Sangat mendalam &amp; kritis, memberi wawasan baru</td>
                                        <td>Mendalam dan kritis</td>
                                        <td>Cukup mendalam dan kritis</td>
                                        <td>Kurang mendalam dan kritis</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kejelasan</strong><br><span class="text-muted">Bobot 20%</span></td>
                                        <td>Sangat jelas, terstruktur, mudah dipahami</td>
                                        <td>Jelas dan mudah dipahami</td>
                                        <td>Cukup jelas dan cukup mudah dipahami</td>
                                        <td>Tidak jelas dan sulit dipahami</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Sikap &amp; Etika</strong><br><span class="text-muted">Bobot 15%</span></td>
                                        <td>Sangat menghormati pendapat lain, sopan, mendukung diskusi konstruktif</td>
                                        <td>Menghormati pendapat lain dan sopan</td>
                                        <td>Cukup menghormati dan cukup sopan</td>
                                        <td>Tidak menghormati dan tidak sopan</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kreativitas</strong><br><span class="text-muted">Bobot 10%</span></td>
                                        <td>Sangat kreatif &amp; orisinal, perspektif baru</td>
                                        <td>Kreatif dan orisinal</td>
                                        <td>Cukup kreatif dan orisinal</td>
                                        <td>Tidak menunjukkan ide kreatif/orisinal</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Rubrik Proyek -->
                    <div class="tab-pane fade" id="panduanProyek">
                        <p class="small text-muted mb-3">Contoh kriteria penilaian untuk komponen <strong>Hasil Proyek</strong> (mis. tugas proyek berbentuk produk/video).</p>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" style="font-size:11.5px;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kelompok Aspek</th>
                                        <th>Sub-Aspek</th>
                                        <th class="text-center">4 (Sangat Baik)</th>
                                        <th class="text-center">3 (Baik)</th>
                                        <th class="text-center">2 (Cukup)</th>
                                        <th class="text-center">1 (Kurang)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td rowspan="3"><strong>Konten</strong><br><span class="text-muted">Bobot 30%</span></td>
                                        <td>Relevansi &amp; Keakuratan</td>
                                        <td>Sangat relevan &amp; akurat</td>
                                        <td>Relevan dan akurat</td>
                                        <td>Cukup relevan dan akurat</td>
                                        <td>Tidak relevan/tidak akurat</td>
                                    </tr>
                                    <tr>
                                        <td>Kedalaman Materi</td>
                                        <td>Sangat mendalam, pemahaman sangat baik</td>
                                        <td>Mendalam, pemahaman baik</td>
                                        <td>Cukup mendalam</td>
                                        <td>Tidak mendalam, pemahaman kurang</td>
                                    </tr>
                                    <tr>
                                        <td>Kreativitas</td>
                                        <td>Sangat kreatif</td>
                                        <td>Kreatif</td>
                                        <td>Cukup kreatif</td>
                                        <td>Tidak kreatif</td>
                                    </tr>
                                    <tr>
                                        <td rowspan="2"><strong>Struktur &amp; Organisasi</strong><br><span class="text-muted">Bobot 20%</span></td>
                                        <td>Alur Cerita</td>
                                        <td>Alur sangat jelas &amp; mudah diikuti</td>
                                        <td>Alur jelas dan mudah diikuti</td>
                                        <td>Alur cukup jelas</td>
                                        <td>Alur tidak jelas</td>
                                    </tr>
                                    <tr>
                                        <td>Penggunaan Visual</td>
                                        <td>Visual sangat mendukung narasi</td>
                                        <td>Visual mendukung narasi</td>
                                        <td>Visual cukup mendukung</td>
                                        <td>Visual tidak mendukung</td>
                                    </tr>
                                    <tr>
                                        <td rowspan="3"><strong>Kualitas Teknis</strong><br><span class="text-muted">Bobot 20%</span></td>
                                        <td>Audio</td>
                                        <td>Sangat jernih &amp; mudah didengar</td>
                                        <td>Jernih dan mudah didengar</td>
                                        <td>Cukup jernih</td>
                                        <td>Tidak jernih, sulit didengar</td>
                                    </tr>
                                    <tr>
                                        <td>Video</td>
                                        <td>Kualitas sangat baik</td>
                                        <td>Kualitas baik</td>
                                        <td>Kualitas cukup baik</td>
                                        <td>Kualitas tidak baik</td>
                                    </tr>
                                    <tr>
                                        <td>Pengeditan</td>
                                        <td>Sangat rapi</td>
                                        <td>Rapi</td>
                                        <td>Cukup rapi</td>
                                        <td>Tidak rapi</td>
                                    </tr>
                                    <tr>
                                        <td rowspan="3"><strong>Penyampaian</strong><br><span class="text-muted">Bobot 15%</span></td>
                                        <td>Kejelasan Narasi</td>
                                        <td>Sangat jelas &amp; mudah dipahami</td>
                                        <td>Jelas dan mudah dipahami</td>
                                        <td>Cukup jelas</td>
                                        <td>Tidak jelas</td>
                                    </tr>
                                    <tr>
                                        <td>Penguasaan Materi</td>
                                        <td>Sangat baik</td>
                                        <td>Baik</td>
                                        <td>Cukup</td>
                                        <td>Kurang</td>
                                    </tr>
                                    <tr>
                                        <td>Penggunaan Bahasa</td>
                                        <td>Sangat tepat &amp; sesuai konteks</td>
                                        <td>Tepat dan sesuai konteks</td>
                                        <td>Cukup tepat</td>
                                        <td>Tidak tepat/tidak sesuai</td>
                                    </tr>
                                    <tr>
                                        <td rowspan="2"><strong>Kreativitas &amp; Inovasi</strong><br><span class="text-muted">Bobot 15%</span></td>
                                        <td>Ide Kreatif</td>
                                        <td>Sangat kreatif, membedakan dari yang lain</td>
                                        <td>Kreatif</td>
                                        <td>Cukup kreatif</td>
                                        <td>Tidak kreatif</td>
                                    </tr>
                                    <tr>
                                        <td>Inovasi Teknologi</td>
                                        <td>Sangat mendukung pesan</td>
                                        <td>Mendukung pesan</td>
                                        <td>Cukup mendukung</td>
                                        <td>Tidak mendukung pesan</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 3: Komposisi Rencana Evaluasi -->
                    <div class="tab-pane fade" id="panduanRencana">
                        <p class="small text-muted mb-3">Acuan komposisi Basis Evaluasi untuk pembelajaran berbasis <em>case method</em> dan <em>team based project</em>.</p>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle text-center" style="font-size:11.5px;">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Basis Evaluasi</th>
                                        <th style="text-align:left;">Contoh Bentuk (boleh lebih dari satu)</th>
                                        <th>Bobot</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>Aktivitas Partisipatif</td>
                                        <td style="text-align:left;">Mengerjakan tugas analisis kasus / studi kasus</td>
                                        <td>50%</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>Hasil Proyek</td>
                                        <td style="text-align:left;">Membuat video pendukung penugasan; link pengumpulan bukti proyek di LMS</td>
                                        <td>30%</td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>Pengetahuan (Quiz)</td>
                                        <td style="text-align:left;">-</td>
                                        <td>-</td>
                                    </tr>
                                    <tr>
                                        <td>4</td>
                                        <td>Ujian Tengah Semester</td>
                                        <td style="text-align:left;">Tes tulis esai; portofolio</td>
                                        <td>10%</td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td>Ujian Akhir Semester</td>
                                        <td style="text-align:left;">Tes tulis esai; portofolio</td>
                                        <td>10%</td>
                                    </tr>
                                    <tr class="table-light">
                                        <td colspan="3" class="text-end"><strong>Jumlah</strong></td>
                                        <td><strong>100%</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-light border small mb-0">
                            <i class="bi bi-info-circle-fill text-primary"></i>
                            Untuk Basis Evaluasi no. 1 dan 2, salah satu minimal 50%. Jika salah satu sudah diisi 50%, maka yang lain boleh mulai dari 5%-50%.
                        </div>
                    </div>

                </div>

                <div class="text-end mt-3">
                    <a href="https://lmsspada.kemdiktisaintek.go.id/pluginfile.php/786271/mod_resource/content/2/Pedoman%20Penilaian.pdf" target="_blank" class="small text-muted">
                        <i class="bi bi-file-earmark-pdf"></i> Sumber: Pedoman Penilaian - LMS SPADA Kemdiktisaintek
                    </a>
                </div>

            </div>
            <div class="modal-footer" style="border:none; padding:14px 24px;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php if ($embedMode): ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const SIQUA = {
        BASE_URL : "<?= BASE_URL ?>",
        APP_NAME : "<?= APP_NAME ?>",
        VERSION : "<?= APP_VERSION ?>"
    };
</script>
<?php else: ?>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<?php endif; ?>
<script>
    PNL_IS_AUDITEE = <?= Auth::isAuditee() ? 'true' : 'false' ?>;
    PNL_EMBED = <?= $embedMode ? 'true' : 'false' ?>;
    PNL_PRESET_UNIT_ID = <?= $presetUnitId ?>;
    PNL_PRESET_KURIKULUM_ID = <?= $presetKurikulumId ?>;
    PNL_PRESET_MK_ID = <?= $presetMkId ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/obe_penilaian.js"></script>