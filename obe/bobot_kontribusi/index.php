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

require_once __DIR__ . '/../../layouts/app.php';

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    #bkPage { font-size: 13px; }
    #bkPage .page-title { font-size: 17px; font-weight: 700; color: #1e1b3a; margin-bottom: 2px; }
    #bkPage .page-subtitle { font-size: 12px; color: #8a8698; margin-bottom: 16px; }
    #bkPage .nav-pills .nav-link { font-size: 12px; padding: 6px 14px; border-radius: 20px; color: #6b6785; font-weight: 500; }
    #bkPage .nav-pills .nav-link.active { background: #7c3aed; color: #fff; }
    #bkPage table { font-size: 12px; }
    #bkPage table thead th { background: #faf9fd; color: #6b6785; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; padding: 8px 10px; border-bottom: 1px solid #eceaf5; text-align: center; }
    #bkPage table tbody td { padding: 6px 10px; vertical-align: middle; color: #2d2a45; border: 1px solid #eceaf5; }
    #bkPage .col-mk { text-align: left !important; min-width: 220px; }
    #bkPage .bobot-input { width: 60px; text-align: center; border: 1px solid #e2dff2; border-radius: 4px; padding: 3px; font-size: 12px; }
    #bkPage .bobot-input:focus { border-color: #7c3aed; outline: none; box-shadow: 0 0 0 2px rgba(124,58,237,0.15); }
    #bkPage .row-total, #bkPage .col-total-row { font-weight: 700; background: #faf9fd; }
    #bkPage #bkSaveStatus { font-size: 11.5px; }
</style>

<div class="container-fluid py-4" id="bkPage">

    <div class="page-title">Bobot Kontribusi Mata Kuliah terhadap CPL Prodi</div>
    <div class="page-subtitle">Ditentukan Ka.Prodi — seberapa besar tiap Mata Kuliah "menyumbang" ke pencapaian CPL Prodi (tiap kolom CPL idealnya total 100%)</div>

    <?php if (!Auth::isAuditee()): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label small">Program Studi</label>
            <select class="form-select" id="bkUnitSelector" style="max-width:400px;">
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div id="bkKurikulumTabsWrap" class="mb-3" style="display:none;">
        <ul class="nav nav-pills" id="bkKurikulumTabs"></ul>
    </div>

    <div class="card shadow-sm" id="bkMatriksCard" style="display:none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-grid-3x3-gap-fill me-1"></i> Matriks Bobot</span>
            <div>
                <button type="button" class="btn btn-sm btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#bkPanduanModal">
                    <i class="bi bi-book-fill"></i> Panduan
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="btnLihatDasarBK">
                    <i class="bi bi-list-ul"></i> Lihat Dasar Perhitungan
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary me-2" id="btnHitungOtomatisBK">
                    <i class="bi bi-calculator"></i> Hitung Otomatis
                </button>
                <small class="text-muted" id="bkSaveStatus"></small>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" id="bkMatriksTable">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<div class="modal fade" id="bkDasarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border-radius:16px; border:none;">
            <div class="modal-header" style="padding:18px 24px; border-bottom:1px solid #eceaf5;">
                <h5 class="modal-title mb-0" style="font-weight:700;">Dasar Perhitungan Otomatis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bkDasarModalBody" style="padding:20px 24px; max-height:75vh; overflow-y:auto;">
                <p class="text-center text-muted">Memuat...</p>
            </div>
        </div>
    </div>
</div>
<!-- Modal Panduan Bobot Kontribusi -->
<div class="modal fade" id="bkPanduanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:16px; border:none;">
            <div class="modal-header" style="background:linear-gradient(135deg,#4c1d95,#7c3aed); border:none; padding:20px 24px;">
                <h5 class="modal-title text-white mb-0" style="font-weight:700;"><i class="bi bi-book-fill"></i> Panduan Menentukan Bobot Kontribusi MK-CPL</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:22px 24px;">

                <p class="small">Tombol <strong>"Hitung Otomatis"</strong> mengisi nilai awal berdasar total bobot penilaian RPS yang sudah dipetakan ke tiap CPL, dinormalisasi supaya total per kolom (per CPL) = 100%. Nilai ini <strong>boleh diedit manual</strong> — keputusan akhir tetap di Ka. Prodi/GKM berdasar pertimbangan kurikulum.</p>

                <div class="fw-bold small mb-2 mt-3">Dasar pertimbangan menentukan bobot secara manual:</div>
                <ol class="small">
                    <li class="mb-2"><strong>Kedalaman keterlibatan MK terhadap CPL</strong> — MK yang secara eksplisit merancang banyak Sub-CPMK/CPMK untuk 1 CPL tertentu (dilihat dari Matriks Kegayutan CPL-CPMK di RPS) sewajarnya diberi bobot lebih besar dibanding MK yang cuma menyentuh CPL itu sekilas.</li>
                    <li class="mb-2"><strong>Posisi MK dalam struktur kurikulum</strong> — MK di semester akhir/lanjutan yang menguji penerapan CPL secara komprehensif (mis. Skripsi, PKL, Praktik Klinik) umumnya diberi bobot lebih tinggi dibanding MK dasar/pengantar di semester awal.</li>
                    <li class="mb-2"><strong>Jumlah SKS MK</strong> — MK dengan SKS lebih besar wajar diberi kontribusi lebih besar, karena porsi waktu belajarnya juga lebih banyak.</li>
                    <li class="mb-2"><strong>Total per kolom (CPL) wajib 100%</strong> — ini aturan baku yang tidak bisa dilanggar; kalau Bapak/Ibu naikkan bobot 1 MK, turunkan proporsional di MK lain untuk CPL yang sama supaya totalnya tetap 100%.</li>
                    <li class="mb-2"><strong>Total per baris (MK) boleh &gt;100%</strong> — ini normal, karena 1 MK bisa berkontribusi penuh ke beberapa CPL sekaligus (bukan dibagi-bagi dari 100%).</li>
                </ol>

                <div class="alert alert-light border small mb-0">
                    <i class="bi bi-info-circle-fill text-primary"></i>
                    Rekomendasi: gunakan "Hitung Otomatis" dulu sebagai titik awal (berbasis data RPS riil), baru sesuaikan manual kalau ada pertimbangan kurikulum khusus yang tidak tertangkap dari data RPS semata.
                </div>

            </div>
            <div class="modal-footer" style="border:none; padding:14px 24px;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script>
    BK_IS_AUDITEE = <?= Auth::isAuditee() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/obe_bobot_kontribusi.js"></script>