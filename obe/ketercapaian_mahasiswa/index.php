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
    #kmPage { font-size: 13px; color: #1e1b3a; }
    #kmPage .page-title { font-size: 19px; font-weight: 800; color: #14112b; letter-spacing: -0.3px; margin-bottom: 2px; }
    #kmPage .page-subtitle { font-size: 12.5px; color: #8a8698; margin-bottom: 20px; }
    #kmPage .nav-pills .nav-link { font-size: 12px; padding: 7px 16px; border-radius: 24px; color: #6b6785; font-weight: 600; transition: all .15s; }
    #kmPage .nav-pills .nav-link:hover { background: #f1edfc; color: #7c3aed; }
    #kmPage .nav-pills .nav-link.active { background: linear-gradient(135deg, #7c3aed, #6d28d9); color: #fff; box-shadow: 0 3px 10px rgba(124,58,237,0.25); }

    #kmPage .selector-card { border: 1px solid #eceaf5; border-radius: 14px; box-shadow: 0 1px 2px rgba(20,17,43,0.03); }

    #kmPage .mhs-profile-bar { display: flex; align-items: center; gap: 14px; background: #fff; border: 1px solid #eceaf5; border-radius: 14px; padding: 16px 20px; margin-bottom: 20px; }
    #kmPage .mhs-avatar { width: 46px; height: 46px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #6d28d9); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; flex-shrink: 0; }
    #kmPage .mhs-name { font-size: 15px; font-weight: 700; color: #14112b; }
    #kmPage .mhs-nim { font-size: 11.5px; color: #8a8698; }

    #kmPage .section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #a39fb5; margin-bottom: 10px; }

    #kmPage .cpl-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 14px; margin-bottom: 24px; }
    #kmPage .cpl-card { background: #fff; border: 1px solid #eceaf5; border-radius: 16px; padding: 18px; transition: all .18s ease; position: relative; overflow: hidden; }
    #kmPage .cpl-card:hover { box-shadow: 0 10px 28px rgba(20,17,43,0.08); transform: translateY(-2px); border-color: #ddd6f5; }
    #kmPage .cpl-card.is-warning { border-color: #f6c2c2; }
    #kmPage .cpl-card-top { display: flex; align-items: center; gap: 14px; margin-bottom: 12px; }
    #kmPage .ring-wrap { position: relative; width: 68px; height: 68px; flex-shrink: 0; }
    #kmPage .ring-wrap svg { transform: rotate(-90deg); }
    #kmPage .ring-track { fill: none; stroke: #f1edfc; stroke-width: 6; }
    #kmPage .ring-fill { fill: none; stroke-width: 6; stroke-linecap: round; transition: stroke-dashoffset 1s cubic-bezier(.4,0,.2,1); }
    #kmPage .ring-value { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 13.5px; font-weight: 800; color: #14112b; }
    #kmPage .cpl-code { font-size: 13.5px; font-weight: 700; color: #14112b; }
    #kmPage .cpl-status-line { display: flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; margin-top: 3px; }
    #kmPage .dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
    #kmPage .warning-strip { display: flex; align-items: center; gap: 6px; background: #fdf2f2; color: #b42318; font-size: 10.5px; font-weight: 600; padding: 7px 10px; border-radius: 8px; margin-bottom: 10px; }
    #kmPage .cpl-detail-toggle { font-size: 11px; font-weight: 600; cursor: pointer; color: #7c3aed; display: inline-flex; align-items: center; gap: 4px; user-select: none; }
    #kmPage .cpl-detail-toggle:hover { color: #5b21b6; }
    #kmPage .cpl-detail-toggle .chev { transition: transform .2s; font-size: 9px; }
    #kmPage .cpl-detail-toggle.open .chev { transform: rotate(180deg); }
    #kmPage .cpl-detail-table { display: none; font-size: 11.5px; margin-top: 10px; }
    #kmPage .cpl-detail-table th { background: #faf9fd; font-size: 10px; text-transform: uppercase; letter-spacing: .3px; color: #a39fb5; padding: 6px 8px; border-color: #f1edfc; }
    #kmPage .cpl-detail-table td { padding: 6px 8px; border-color: #f1edfc; }

    #kmPage .mk-panel { background: #fff; border: 1px solid #eceaf5; border-radius: 14px; overflow: hidden; }
    #kmPage .mk-panel-header { padding: 14px 18px; border-bottom: 1px solid #eceaf5; font-size: 12.5px; font-weight: 700; color: #14112b; }
    #kmPage .mk-row { display: flex; align-items: center; justify-content: space-between; padding: 11px 18px; border-bottom: 1px solid #f5f4fa; transition: background .12s; }
    #kmPage .mk-row:last-child { border-bottom: none; }
    #kmPage .mk-row:hover { background: #faf9fd; }
    #kmPage .mk-row-name { font-size: 12px; color: #2d2a45; font-weight: 500; }
    #kmPage .mk-row-sem { font-size: 10px; color: #a39fb5; }
    #kmPage .mk-row-score { font-size: 13px; font-weight: 700; color: #14112b; margin-right: 10px; }
    #kmPage .btn-ghost-eye { width: 28px; height: 28px; border-radius: 8px; border: 1px solid #eceaf5; background: #fff; color: #7c3aed; display: inline-flex; align-items: center; justify-content: center; transition: all .12s; }
    #kmPage .btn-ghost-eye:hover { background: #7c3aed; color: #fff; border-color: #7c3aed; }

    #kmDetailTableBody tr td { font-size: 12px; }

    /* Select2 - gaya premium ungu */
    #kmPage .select2-container { width: 100% !important; }
    #kmPage .select2-container--default .select2-selection--single {
        height: auto; border: 1px solid #e2dff2; border-radius: 10px; padding: 9px 12px;
        transition: border-color .15s, box-shadow .15s; background: #fff;
    }
    #kmPage .select2-container--default .select2-selection--single:hover { border-color: #c9bff5; }
    #kmPage .select2-container--default.select2-container--open .select2-selection--single,
    #kmPage .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.12);
    }
    #kmPage .select2-selection__rendered { padding: 0 !important; font-size: 13px; color: #14112b; font-weight: 500; line-height: 1.4 !important; }
    #kmPage .select2-selection__placeholder { color: #a39fb5 !important; }
    #kmPage .select2-selection__arrow { height: 100% !important; top: 0 !important; right: 10px !important; }
    #kmPage .select2-selection__arrow b { border-color: #7c3aed transparent transparent transparent !important; }
    #kmPage .select2-dropdown { border: 1px solid #e2dff2 !important; border-radius: 12px !important; box-shadow: 0 12px 32px rgba(20,17,43,0.14); overflow: hidden; margin-top: 4px; }
    #kmPage .select2-search--dropdown { padding: 10px !important; background: #faf9fd; }
    #kmPage .select2-search__field { border: 1px solid #e2dff2 !important; border-radius: 8px !important; padding: 7px 10px !important; font-size: 12.5px !important; }
    #kmPage .select2-search__field:focus { outline: none; border-color: #7c3aed !important; }
    #kmPage .select2-results__option { font-size: 13px; padding: 9px 14px !important; }
    #kmPage .select2-container--default .select2-results__option--highlighted[aria-selected] { background: #7c3aed !important; color: #fff !important; }
    #kmPage .select2-container--default .select2-results__option[aria-selected=true] { background: #f1edfc; color: #7c3aed; font-weight: 600; }
    #kmPage .select-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #a39fb5; margin-bottom: 6px; display: block; }

        #kmProfileBar .dropdown-toggle {
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
        border: none; color: #fff; font-weight: 600; font-size: 12.5px;
        padding: 9px 18px; border-radius: 10px;
        box-shadow: 0 4px 14px rgba(124,58,237,0.28);
        transition: all .18s;
    }
    #kmProfileBar .dropdown-toggle:hover,
    #kmProfileBar .dropdown-toggle:focus {
        background: linear-gradient(135deg, #6d28d9, #5b21b6);
        box-shadow: 0 6px 18px rgba(124,58,237,0.38);
        transform: translateY(-1px);
        color: #fff;
    }
    #kmProfileBar .dropdown-toggle::after { margin-left: 8px; vertical-align: 2px; }

    #kmProfileBar .dropdown-menu {
        border: none; border-radius: 14px; padding: 8px;
        box-shadow: 0 16px 40px rgba(20,17,43,0.18), 0 2px 8px rgba(20,17,43,0.08);
        min-width: 220px; margin-top: 8px !important;
    }
    #kmProfileBar .dropdown-item {
        border-radius: 9px; padding: 10px 12px; font-size: 12.5px; font-weight: 500;
        color: #2d2a45; display: flex; align-items: center; gap: 10px;
        transition: background .12s;
    }
    #kmProfileBar .dropdown-item:hover,
    #kmProfileBar .dropdown-item:focus {
        background: #f1edfc; color: #5b21b6;
    }
    #kmProfileBar .dropdown-item i { font-size: 15px; width: 18px; text-align: center; }
    #kmProfileBar .dropdown-item:nth-child(1) i { color: #2563eb; }
    #kmProfileBar .dropdown-item:nth-child(2) i { color: #0d9488; }
    #kmProfileBar .dropdown-item:nth-child(3) i { color: #d97706; }

    #kmDiskusiThread { max-height: 320px; overflow-y: auto; padding-right: 4px; }
    #kmDiskusiThread .diskusi-item { display: flex; gap: 10px; margin-bottom: 14px; }
    #kmDiskusiThread .diskusi-avatar {
        width: 34px; height: 34px; border-radius: 50%; background: #f1edfc; color: #7c3aed;
        display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0;
    }
    #kmDiskusiThread .diskusi-bubble { background: #faf9fd; border-radius: 12px; padding: 8px 12px; flex: 1; }
    #kmDiskusiThread .diskusi-nama { font-size: 11.5px; font-weight: 700; color: #14112b; }
    #kmDiskusiThread .diskusi-waktu { font-size: 10px; color: #a39fb5; margin-left: 6px; font-weight: 400; }
    #kmDiskusiThread .diskusi-pesan { font-size: 12.5px; color: #2d2a45; margin-top: 2px; white-space: pre-line; }
</style>

<div class="container-fluid py-4" id="kmPage">

    <?php $obeActiveCard = 'mahasiswa'; require_once __DIR__ . '/../../layouts/obe_dashboard_cards.php'; ?>

    <div class="page-title">Ketercapaian CPL Mahasiswa</div>
    <div class="page-subtitle">Rekap Ketercapaian CPL 1 orang Mahasiswa dari semua Mata Kuliah yang sudah dinilai</div>

    <?php if (!Auth::isAuditee()): ?>
    <div class="selector-card mb-3">
        <div class="p-3" style="max-width:420px;">
            <label class="select-label">Program Studi</label>
            <select class="form-select" id="kmUnitSelector">
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div id="kmKurikulumTabsWrap" class="mb-3" style="display:none;">
        <ul class="nav nav-pills" id="kmKurikulumTabs"></ul>
    </div>

    <div class="selector-card mb-3" id="kmMhsSelectorCard" style="display:none;">
        <div class="p-3" style="max-width:420px;">
            <label class="select-label">Cari Mahasiswa (Nama / NIM)</label>
            <select class="form-select" id="kmMhsSelector">
                <option value="">-- Pilih Mahasiswa --</option>
            </select>
        </div>
    </div>

    <div id="kmContent" style="display:none;">

        <div class="mhs-profile-bar" id="kmProfileBar"></div>

        <div class="section-label">Ketercapaian per CPL</div>
        <div class="cpl-grid" id="kmCplList"></div>

        <div class="section-label">Nilai Akhir per Mata Kuliah</div>
        <div class="mk-panel" id="kmMkPanel">
            <div id="kmMkTableBody"></div>
        </div>

        <div class="section-label" style="margin-top:28px;">Diskusi &amp; Umpan Balik Ketercapaian CPL</div>
        <div class="card shadow-sm" id="kmDiskusiCard">
            <div class="card-body">
                <div id="kmDiskusiThread" class="mb-3">
                    <p class="text-muted small">Memuat diskusi...</p>
                </div>
                <form id="kmDiskusiForm" class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm" id="kmDiskusiPesan" placeholder="Tulis catatan/umpan balik..." required>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-send-fill"></i> Kirim
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>

<!-- Modal Detail per Pertemuan -->
<div class="modal fade" id="kmDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kmDetailModalTitle">Detail Nilai per Pertemuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" style="font-size:12px;">
                        <thead>
                            <tr>
                                <th width="40">TM</th>
                                <th width="120">Komponen</th>
                                <th>Sub-CPMK</th>
                                <th width="80">CPL</th>
                                <th width="70" class="text-center">Bobot</th>
                                <th width="70" class="text-center">Nilai</th>
                            </tr>
                        </thead>
                        <tbody id="kmDetailTableBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/obe_ketercapaian_mahasiswa.js"></script>