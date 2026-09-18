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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

<style>
    #sikPage { font-size: 13px; color: #1e1b3a; }
    #sikPage .page-title { font-size: 19px; font-weight: 800; color: #14112b; letter-spacing: -0.3px; margin-bottom: 2px; }
    #sikPage .page-subtitle { font-size: 12.5px; color: #8a8698; margin-bottom: 20px; }
    #sikPage .nav-pills .nav-link { font-size: 12px; padding: 7px 16px; border-radius: 24px; color: #6b6785; font-weight: 600; }
    #sikPage .nav-pills .nav-link.active { background: linear-gradient(135deg, #d97706, #b45309); color: #fff; }
    #sikPage .selector-card { border: 1px solid #eceaf5; border-radius: 14px; }
    #sikPage .select-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #a39fb5; margin-bottom: 6px; display: block; }
    #sikPage .mk-selector-icon { width: 44px; height: 44px; flex-shrink: 0; background: #fef3e2; color: #d97706; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 19px; }

    #sikPage table { font-size: 12px; }
    #sikPage table thead th { background: #fdf8ef; color: #92722c; font-weight: 700; font-size: 10.5px; text-transform: uppercase; letter-spacing: .3px; padding: 10px; border-bottom: 1px solid #f5e6c8; text-align: center; }
    #sikPage table tbody td { padding: 8px 10px; vertical-align: middle; text-align: center; border-color: #f5efe0; }
    #sikPage table tbody tr:hover { background: #fffaf0; }
    #sikPage .col-nim { text-align: left !important; }
    #sikPage .col-nama { text-align: left !important; font-weight: 500; }
    #sikPage .nilai-cell { font-weight: 700; }
    #sikPage .nilai-total { font-weight: 800; font-size: 13px; background: #fef3e2; }

    /* Select2 - gaya premium amber */
    #sikPage .select2-container { width: 100% !important; }
    #sikPage .select2-container--default .select2-selection--single { height: auto; border: 1px solid #f0dfc0; border-radius: 10px; padding: 9px 12px; }
    #sikPage .select2-container--default.select2-container--open .select2-selection--single,
    #sikPage .select2-container--default.select2-container--focus .select2-selection--single { border-color: #d97706; box-shadow: 0 0 0 3px rgba(217,119,6,0.12); }
    #sikPage .select2-selection__rendered { padding: 0 !important; font-size: 13px; font-weight: 500; line-height: 1.4 !important; }
    #sikPage .select2-dropdown { border: 1px solid #f0dfc0 !important; border-radius: 12px !important; box-shadow: 0 12px 32px rgba(20,17,43,0.14); }
    #sikPage .select2-container--default .select2-results__option--highlighted[aria-selected] { background: #d97706 !important; }
    #sikPage .select2-container--default .select2-results__option[aria-selected=true] { background: #fef3e2; color: #d97706; font-weight: 600; }
</style>

<div class="container-fluid py-4" id="sikPage">

    <?php $obeActiveCard = 'siakad'; require_once __DIR__ . '/../../layouts/obe_dashboard_cards.php'; ?>

    <div class="page-title">Pelaporan Nilai SIAKAD</div>
    <div class="page-subtitle">Nilai akhir per Mahasiswa dipecah menurut Aspek: Sikap, Pengetahuan, Keterampilan Umum &amp; Khusus</div>

    <?php if (!Auth::isAuditee()): ?>
    <div class="selector-card mb-3">
        <div class="p-3" style="max-width:420px;">
            <label class="select-label">Program Studi</label>
            <select class="form-select" id="sikUnitSelector">
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div id="sikKurikulumTabsWrap" class="mb-3" style="display:none;">
        <ul class="nav nav-pills" id="sikKurikulumTabs"></ul>
    </div>

    <div class="selector-card mb-3" id="sikMkSelectorCard" style="display:none;">
        <div class="p-3 d-flex align-items-center gap-3">
            <div class="mk-selector-icon"><i class="bi bi-journal-bookmark-fill"></i></div>
            <div class="flex-grow-1" style="max-width:420px;">
                <label class="select-label">Cari Mata Kuliah</label>
                <select class="form-select" id="sikMkSelector">
                    <option value="">-- Pilih Mata Kuliah --</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card shadow-sm" id="sikTableCard" style="display:none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Rekap Nilai SIAKAD</span>
            <button type="button" class="btn btn-sm btn-outline-warning" id="btnExportSiakad">
                <i class="bi bi-download"></i> Export Excel
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" id="sikTable">
                    <thead>
                        <tr>
                            <th width="40">No.</th>
                            <th>NIM</th>
                            <th>Nama</th>
                            <th>Sikap</th>
                            <th>Pengetahuan</th>
                            <th>Ket. Umum</th>
                            <th>Ket. Khusus</th>
                            <th>Nilai Akhir<br><span style="font-weight:400; font-size:9px; text-transform:none;">(20-40-20-20)</span></th>
                        </tr>
                    </thead>
                    <tbody id="sikTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/obe_pelaporan_siakad.js"></script>