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

<style>
    #reoPage { font-size: 13px; }
    #reoPage .page-title { font-size: 17px; font-weight: 700; color: #1e1b3a; margin-bottom: 2px; }
    #reoPage .page-subtitle { font-size: 12px; color: #8a8698; margin-bottom: 16px; }
    #reoPage .card { border: 1px solid #eceaf5; border-radius: 10px; box-shadow: 0 1px 3px rgba(30,27,58,0.04); }
    #reoPage .card-header { background: #faf9fd; border-bottom: 1px solid #eceaf5; font-size: 13px; font-weight: 600; color: #3f3a5c; padding: 10px 14px; }
    #reoPage .card-body { padding: 14px; }
    #reoPage .form-label.small { font-size: 11.5px; font-weight: 600; color: #6b6785; text-transform: uppercase; letter-spacing: .3px; margin-bottom: 4px; }
    #reoPage .form-select-sm, #reoPage .form-control-sm { font-size: 12.5px; }
    #reoPage .nav-pills .nav-link { font-size: 12px; padding: 6px 14px; border-radius: 20px; color: #6b6785; font-weight: 500; }
    #reoPage .nav-pills .nav-link.active { background: #7c3aed; color: #fff; }
    #reoPage .mk-selector-icon { width: 42px; height: 42px; flex-shrink: 0; background: #f1edfc; color: #7c3aed; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    #reoPage #reoMkSelector { font-size: 13.5px; font-weight: 600; color: #2d2a45; border: 1px solid #e2dff2; padding: 8px 12px; border-radius: 8px; }
    #reoPage #reoMkSelector:focus { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.1); }
    #reoPage #reoMkInfoCard { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); border: none; }
    #reoPage #reoMkInfoCard .card-body { padding: 16px 18px; }
    #reoPage #reoMkInfoCard .info-label { font-size: 10.5px; text-transform: uppercase; letter-spacing: .4px; color: rgba(255,255,255,.7); font-weight: 600; margin-bottom: 2px; }
    #reoPage #reoMkInfoCard .info-value { font-size: 13.5px; font-weight: 700; color: #fff; }
    #reoPage table { font-size: 12.5px; }
    #reoPage table thead th { background: #faf9fd; color: #6b6785; font-weight: 600; font-size: 11.5px; text-transform: uppercase; letter-spacing: .3px; padding: 8px 10px; border-bottom: 1px solid #eceaf5; }
    #reoPage table tbody td { padding: 8px 10px; vertical-align: middle; color: #2d2a45; }
    #reoPage table tbody tr:hover { background: #faf9fd; }
    #reoPage .btn-sm { font-size: 11.5px; padding: 3px 9px; }
</style>

<?php if ($embedMode): ?>
<style>
    #reoPage .page-title, #reoPage .page-subtitle { display: none !important; }
    #reoUnitSelectorWrap, #reoKurikulumTabsWrap, #reoMkSelectorCard, #reoMkInfoCard { display: none !important; }
    #reoPage { padding-top: 0 !important; }
</style>
<?php endif; ?>

<div class="container-fluid py-4" id="reoPage">

    <div class="page-title">Rencana Evaluasi</div>
    <div class="page-subtitle">Komposisi Basis Evaluasi dan Bobot Penilaian Mata Kuliah</div>

    <?php if (!Auth::isAuditee() && !$embedMode): ?>
    <div class="card shadow-sm mb-3" id="reoUnitSelectorWrap">
        <div class="card-body">
            <label class="form-label small">Program Studi</label>
            <select class="form-select" id="reoUnitSelector" style="max-width:400px;">
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div id="reoKurikulumTabsWrap" class="mb-3" style="display:none;">
        <ul class="nav nav-pills" id="reoKurikulumTabs"></ul>
    </div>

    <div class="card shadow-sm mb-3" id="reoMkSelectorCard" style="display:none;">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="mk-selector-icon"><i class="bi bi-journal-bookmark-fill"></i></div>
            <div class="flex-grow-1">
                <label class="form-label small mb-1">Pilih Mata Kuliah</label>
                <select class="form-select" id="reoMkSelector">
                    <option value="">-- Pilih Mata Kuliah --</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3" id="reoMkInfoCard" style="display:none;">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><div class="info-label">Tahun Kurikulum</div><div class="info-value" id="reoInfoTahun">-</div></div>
                <div class="col-md-3"><div class="info-label">Kode Mata Kuliah</div><div class="info-value" id="reoInfoKode">-</div></div>
                <div class="col-md-3"><div class="info-label">Nama Mata Kuliah</div><div class="info-value" id="reoInfoNama">-</div></div>
                <div class="col-md-3"><div class="info-label">SKS Total</div><div class="info-value" id="reoInfoSks">-</div></div>
            </div>
        </div>
    </div>

    <div class="alert alert-light border d-none d-md-block mb-3" id="reoBatasInfo" style="display:none; font-size:11.5px;">
        <strong><i class="bi bi-info-circle-fill text-primary"></i> Batas Bobot per Basis Evaluasi (dijumlah dari semua Pertemuan):</strong>
        Aktivitas Partisipatif 25% &middot; Hasil Proyek 25% &middot; Tugas 10% &middot; UTS 20% &middot; UAS 20%
    </div>

    <div class="card shadow-sm" id="reoListCard" style="display:none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-pie-chart-fill me-1"></i> Komposisi Rencana Evaluasi <span class="badge ms-1" id="reoTotalBobotBadge" style="font-size:10.5px;">Total: 0%</span></span>
            <button class="btn btn-primary btn-sm" id="btnAddReo">
                <i class="bi bi-plus-circle"></i> Tambah Basis Evaluasi
            </button>
        </div>
        <div class="card-body">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th width="40">No.</th>
                        <th width="70">Pertemuan</th>
                        <th width="140">Basis Evaluasi</th>
                        <th width="130">Komponen</th>
                        <th width="150">Sub-CPMK</th>
                        <th width="110" class="text-center">Bobot (%)</th>
                        <th width="90">Aksi</th>
                    </tr>
                </thead>
                <tbody id="reoTableBody">
                    <tr><td colspan="7" class="text-center text-muted">Belum ada data.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="reoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="reoForm">
                <input type="hidden" id="reo_id" name="id">
                <input type="hidden" id="reo_mata_kuliah_id" name="mata_kuliah_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="reoModalTitle">Tambah Basis Evaluasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">Pertemuan Ke-</label>
                            <select class="form-select form-select-sm" id="reo_pertemuan" name="pertemuan" required>
                                <option value="">-- Pilih --</option>
                                <?php for ($i = 1; $i <= 16; $i++): ?>
                                    <option value="<?= $i ?>">Pertemuan <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small">Kaitan dengan Sub-CPMK</label>
                            <select class="form-select form-select-sm" id="reo_sub_cpmk">
                                <option value="">-- Pilih Sub-CPMK --</option>
                            </select>
                        </div>
                    </div>
                    <div id="reoSubCpmkDeskripsi" class="border rounded p-2 mt-2" style="background:#faf9fd; font-size:12.5px; display:none;"></div>

                    <div class="row g-2 mt-1">
                        <div class="col-md-12">
                            <label class="form-label small">Basis Evaluasi</label>
                            <select class="form-select form-select-sm" id="reo_basis" name="basis_evaluasi" required>
                                <option value="">-- Pilih Basis Evaluasi --</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2 mt-2">
                        <label class="form-label small">Bobot (%)</label>
                        <input type="number" class="form-control form-control-sm" id="reo_bobot" name="bobot_persen" value="0" min="0" max="100" step="0.01" style="max-width:220px;" required>
                        <small class="text-muted">Terisi otomatis sesuai Basis Evaluasi, boleh diubah manual — inilah Bobot inti yang dipakai untuk perhitungan Nilai/CPL</small>
                    </div>

                    <div class="row g-2 mt-1">
                        <div class="col-md-12">
                            <label class="form-label small">Rincian Komponen pada SIAKAD</label>
                            <small class="text-muted d-block mb-1">Centang Komponen yang relevan, lalu isi Bobot masing-masing. Total rincian harus sama persis dengan Bobot inti di atas.</small>
                            <div class="border rounded p-2">
                                <?php
                                $komponenOptions = [
                                    'SIKAP' => 'Sikap',
                                    'PENGETAHUAN' => 'Pengetahuan',
                                    'KETERAMPILAN UMUM' => 'Keterampilan Umum',
                                    'KETERAMPILAN KHUSUS' => 'Keterampilan Khusus',
                                ];
                                foreach ($komponenOptions as $val => $label):
                                ?>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="form-check mb-0" style="width:180px;">
                                        <input class="form-check-input reo-komponen-checkbox" type="checkbox" value="<?= htmlspecialchars($val) ?>" id="reo_komponen_<?= md5($val) ?>">
                                        <label class="form-check-label" for="reo_komponen_<?= md5($val) ?>" style="font-size:12px;"><?= htmlspecialchars($label) ?></label>
                                    </div>
                                    <input type="number" class="form-control form-control-sm reo-komponen-bobot" data-komponen="<?= htmlspecialchars($val) ?>" placeholder="0" min="0" max="100" step="0.01" style="max-width:100px;" disabled>
                                    <span class="text-muted small">%</span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-1 small" id="reoSiakadTotalInfo"></div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label small fw-semibold mb-0">Kaitan dengan Indikator Penilaian</label>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-bs-toggle="collapse" data-bs-target="#reoPanduanPanel">
                                <i class="bi bi-book-fill"></i> Panduan
                            </button>
                        </div>
                        <div class="collapse mb-2" id="reoPanduanPanel">
                            <div class="border rounded p-2 mt-1" style="background:#faf9fd; max-height:340px; overflow-y:auto;">
                                <?php require __DIR__ . '/panduan_indikator_content.php'; ?>
                            </div>
                        </div>
                        <small class="text-muted d-block mb-2">Isi indikator penilaian untuk masing-masing ranah (opsional, isi yang relevan saja).</small>

                        <div class="mb-2">
                            <label class="form-label small">Kognitif</label>
                            <textarea class="form-control form-control-sm" id="reo_indikator_kognitif" name="indikator_kognitif" rows="2" placeholder="mis. Ketepatan menjawab dan penguasaan terhadap materi yang diberikan dosen"></textarea>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small">Afektif</label>
                            <textarea class="form-control form-control-sm" id="reo_indikator_afektif" name="indikator_afektif" rows="2" placeholder="mis. Kedisiplinan mahasiswa dalam mengikuti diskusi kelompok"></textarea>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small">Psikomotorik</label>
                            <textarea class="form-control form-control-sm" id="reo_indikator_psikomotorik" name="indikator_psikomotorik" rows="2" placeholder="mis. Ketepatan teknik dalam mendemonstrasikan prosedur"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
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

<!-- Modal Panduan Kaitan dengan Indikator Penilaian -->


<?php else: ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<?php endif; ?>
<script>
    REO_IS_AUDITEE = <?= Auth::isAuditee() ? 'true' : 'false' ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/obe_rencana_evaluasi.js"></script>

<script>
  const REO_EMBED = <?= $embedMode ? 'true' : 'false' ?>;
  const REO_PRESET_UNIT_ID = <?= $presetUnitId ?>;
  const REO_PRESET_KURIKULUM_ID = <?= $presetKurikulumId ?>;
  const REO_PRESET_MK_ID = <?= $presetMkId ?>;
</script>