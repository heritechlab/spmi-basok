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
    #ppdPage { font-size: 13px; color: #1e1b3a; }
    #ppdPage .page-title { font-size: 19px; font-weight: 800; color: #14112b; letter-spacing: -0.3px; margin-bottom: 2px; }
    #ppdPage .page-subtitle { font-size: 12.5px; color: #8a8698; margin-bottom: 20px; }
    #ppdPage .selector-card { border: 1px solid #eceaf5; border-radius: 14px; box-shadow: 0 1px 2px rgba(20,17,43,0.03); }
    #ppdPage .select-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #a39fb5; margin-bottom: 6px; display: block; }
    #ppdPage .form-label.small { font-size: 11.5px; font-weight: 600; color: #6b6785; text-transform: uppercase; letter-spacing: .3px; margin-bottom: 4px; }
    #ppdPage textarea.form-control { font-size: 13px; }
</style>

<div class="container-fluid py-4" id="ppdPage">

    <div class="page-title">Profil Program Studi</div>
    <div class="page-subtitle">Visi, Misi, dan Unggulan Program Studi &mdash; digunakan pada dokumen Cetak RPS</div>

    <?php if (!Auth::isAuditee()): ?>
    <div class="selector-card mb-3">
        <div class="p-3" style="max-width:420px;">
            <label class="select-label">Program Studi</label>
            <select class="form-select" id="ppdUnitSelector">
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm" id="ppdFormCard" style="display:none;">
        <div class="card-body">
            <form id="ppdForm">
                <input type="hidden" id="ppd_unit_id" name="unit_id">

                <div class="mb-3">
                    <label class="form-label small">Visi Program Studi</label>
                    <textarea class="form-control" id="ppd_visi" name="visi" rows="3" placeholder="Tuliskan visi program studi..."></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Misi Program Studi</label>
                    <textarea class="form-control" id="ppd_misi" name="misi" rows="6" placeholder="Tuliskan misi program studi, satu baris per poin misi..."></textarea>
                    <div class="form-text">Satu baris = satu poin misi (akan dinomori otomatis saat dicetak).</div>
                </div>

                <div class="mb-3">
                    <label class="form-label small">Unggulan / Nilai-Nilai Dasar Program Studi</label>
                    <textarea class="form-control" id="ppd_unggulan" name="unggulan" rows="5" placeholder="Tuliskan nilai-nilai dasar, satu baris per poin..."></textarea>
                    <div class="form-text">Satu baris = satu poin nilai dasar (akan dinomori otomatis saat dicetak).</div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm" id="btnSaveProfilProdi">
                    <i class="bi bi-save"></i> Simpan Profil Program Studi
                </button>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script>
    PPD_IS_AUDITEE = <?= Auth::isAuditee() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/obe_profil_prodi.js"></script>