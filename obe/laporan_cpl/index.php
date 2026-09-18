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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    #lcplPage { font-size: 13px; color: #1e1b3a; }
    #lcplPage .page-title { font-size: 19px; font-weight: 800; color: #14112b; letter-spacing: -0.3px; margin-bottom: 2px; }
    #lcplPage .page-subtitle { font-size: 12.5px; color: #8a8698; margin-bottom: 20px; }
    #lcplPage .nav-pills .nav-link { font-size: 12px; padding: 7px 16px; border-radius: 24px; color: #6b6785; font-weight: 600; }
    #lcplPage .nav-pills .nav-link.active { background: linear-gradient(135deg, #7c3aed, #6d28d9); color: #fff; }
    #lcplPage .selector-card { border: 1px solid #eceaf5; border-radius: 14px; }
    #lcplPage .select-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #a39fb5; margin-bottom: 6px; display: block; }

    /* HERO GELAP - panel radar chart */
    #lcplHero {
        background: radial-gradient(ellipse at top right, #2e1065 0%, #14112b 55%, #0c0a1f 100%);
        border-radius: 22px; padding: 32px 30px; margin-bottom: 24px; position: relative; overflow: hidden;
    }
    #lcplHero::before {
        content: ""; position: absolute; inset: 0; opacity: .06; pointer-events: none;
        background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 22px 22px;
    }
    #lcplHero .hero-eyebrow { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; color: #a78bfa; margin-bottom: 6px; }
    #lcplHero .hero-title { font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 22px; }
    #lcplHero .radar-wrap { position: relative; max-width: 380px; margin: 0 auto; }
    #lcplHero .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-top: 26px; position: relative; z-index: 1; }
    #lcplHero .kpi-box { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.10); border-radius: 14px; padding: 14px 16px; backdrop-filter: blur(6px); }
    #lcplHero .kpi-label { font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: rgba(255,255,255,0.55); font-weight: 600; margin-bottom: 4px; }
    #lcplHero .kpi-value { font-size: 22px; font-weight: 800; color: #fff; }
    #lcplHero .kpi-value.accent-green { color: #4ade80; }
    #lcplHero .kpi-value.accent-red { color: #f87171; }
    #lcplHero .kpi-sub { font-size: 10.5px; color: rgba(255,255,255,0.45); }

    /* Daftar detail CPL */
    #lcplPage .section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #a39fb5; margin-bottom: 10px; }
    #lcplPage .cpl-row-card {
        background: #fff; border: 1px solid #eceaf5; border-left: 4px solid #ddd;
        border-radius: 14px; margin-bottom: 12px; overflow: hidden;
        box-shadow: 0 2px 8px rgba(20,17,43,0.04); transition: box-shadow .18s, transform .18s;
    }
    #lcplPage .cpl-row-card:hover { box-shadow: 0 10px 26px rgba(20,17,43,0.09); transform: translateY(-1px); }
    #lcplPage .cpl-row-main { display: grid; grid-template-columns: 76px 1fr 90px 160px; align-items: center; gap: 18px; padding: 16px 20px; }
    #lcplPage .cpl-row-code {
        font-size: 14px; font-weight: 800; color: #14112b; width: 76px; flex-shrink: 0;
        background: #f6f3fc; border-radius: 10px; padding: 8px 4px; text-align: center;
    }
    #lcplPage .cpl-row-bar-wrap { flex: 1; }
    #lcplPage .cpl-row-bar-track { height: 10px; background: #f1edfc; border-radius: 10px; overflow: hidden; }
    #lcplPage .cpl-row-bar-fill { height: 100%; border-radius: 10px; transition: width 1.1s cubic-bezier(.4,0,.2,1); width: 0%; }
    #lcplPage .cpl-row-score-wrap {
        display: flex; flex-direction: column; align-items: flex-end; justify-content: center;
    }
    #lcplPage .cpl-row-score { font-size: 20px; font-weight: 800; line-height: 1.2; }
    #lcplPage .cpl-row-score-label { font-size: 9px; color: #a39fb5; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; margin-top: 3px; line-height: 1; }
    #lcplPage .cpl-row-status {
        font-size: 11px; font-weight: 700; padding: 6px 14px;
        border-radius: 20px; white-space: nowrap; text-align: center; justify-self: end;
    }
    #lcplPage .warning-strip { display: flex; align-items: center; gap: 6px; background: #fdf2f2; color: #b42318; font-size: 10.5px; font-weight: 600; padding: 8px 20px; }
    #lcplPage .cpl-detail-toggle { font-size: 11px; font-weight: 600; cursor: pointer; color: #7c3aed; padding: 0 18px 12px; display: inline-flex; align-items: center; gap: 4px; user-select: none; }
    #lcplPage .cpl-detail-toggle .chev { transition: transform .2s; font-size: 9px; }
    #lcplPage .cpl-detail-toggle.open .chev { transform: rotate(180deg); }
    #lcplPage .cpl-detail-table { display: none; font-size: 12px; margin: 0 18px 14px; }
    #lcplPage .cpl-detail-table th { background: #faf9fd; font-size: 10px; text-transform: uppercase; color: #a39fb5; padding: 6px 10px; }
    #lcplPage .cpl-detail-table td { padding: 6px 10px; }

    /* Select2 ungu */
    #lcplPage .select2-container { width: 100% !important; }
    #lcplPage .select2-container--default .select2-selection--single { height: auto; border: 1px solid #e2dff2; border-radius: 10px; padding: 9px 12px; }
    #lcplPage .select2-container--default.select2-container--open .select2-selection--single,
    #lcplPage .select2-container--default.select2-container--focus .select2-selection--single { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.12); }
    #lcplPage .select2-selection__rendered { padding: 0 !important; font-size: 13px; font-weight: 500; line-height: 1.4 !important; }
    #lcplPage .select2-dropdown { border: 1px solid #e2dff2 !important; border-radius: 12px !important; box-shadow: 0 12px 32px rgba(20,17,43,0.14); }
    #lcplPage .select2-container--default .select2-results__option--highlighted[aria-selected] { background: #7c3aed !important; }
</style>

<div class="container-fluid py-4" id="lcplPage">

    <?php $obeActiveCard = 'prodi'; require_once __DIR__ . '/../../layouts/obe_dashboard_cards.php'; ?>

    <div class="page-title">Laporan Ketercapaian CPL Program Studi</div>
        <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLihatMatriks">
            <i class="bi bi-table"></i> Lihat Matriks Detail (Bobot &amp; Ketercapaian per MK)
        </button>
    </div>
    <div class="page-subtitle">Ketercapaian CPL Prodi = &Sigma; (Nilai Capaian CPL tiap Mata Kuliah &times; Bobot Kontribusi Mata Kuliah)</div>

    <?php if (!Auth::isAuditee()): ?>
    <div class="selector-card mb-3">
        <div class="p-3" style="max-width:420px;">
            <label class="select-label">Program Studi</label>
            <select class="form-select" id="lcplUnitSelector">
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div id="lcplKurikulumTabsWrap" class="mb-3" style="display:none;">
        <ul class="nav nav-pills" id="lcplKurikulumTabs"></ul>
    </div>

    <div id="lcplContent" style="display:none;">

        <div id="lcplHero">
            <div class="hero-eyebrow">Peta Ketercapaian CPL</div>
            <div class="hero-title" id="lcplHeroTitle">Program Studi</div>
            <div class="radar-wrap">
                <canvas id="lcplRadarChart" height="320"></canvas>
            </div>
            <div class="kpi-row">
                <div class="kpi-box">
                    <div class="kpi-label">Rata-rata CPL</div>
                    <div class="kpi-value" id="kpiRataRata">-</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">CPL Tertinggi</div>
                    <div class="kpi-value accent-green" id="kpiTertinggi">-</div>
                    <div class="kpi-sub" id="kpiTertinggiCode"></div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">CPL Terendah</div>
                    <div class="kpi-value accent-red" id="kpiTerendah">-</div>
                    <div class="kpi-sub" id="kpiTerendahCode"></div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Belum Memenuhi</div>
                    <div class="kpi-value" id="kpiBelumMemenuhi">-</div>
                    <div class="kpi-sub">dari <span id="kpiTotalCpl">0</span> CPL</div>
                </div>
            </div>
        </div>

        <div class="section-label">Rincian per CPL</div>
        <div id="lcplCplList"></div>

    </div>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/obe_laporan_cpl.js"></script>