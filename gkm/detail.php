<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$id = (int)($_GET['id'] ?? 0);

$repository = new GkmRepository($conn);
$service    = new GkmService($repository);

$result = $service->getDetail($id);

if (!$result['success']) {
    die('Data Monitoring GKM tidak ditemukan.');
}

$data = $result['data'];
$monitoring = $data['monitoring'];
$items = $data['items'];
$stats = $data['stats'];

require_once __DIR__ . '/../layouts/app.php';

$tahapIcons = ['Perencanaan' => 'bi-1-circle', 'Proses' => 'bi-2-circle', 'Pelaporan' => 'bi-3-circle'];
$tahapColors = ['Perencanaan' => 'purple', 'Proses' => 'blue', 'Pelaporan' => 'green'];

?>

<div class="container-fluid py-4">

<div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Checklist Monitoring GKM</h4>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>gkm/print_unit.php?id=<?= $id ?>" target="_blank" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf"></i> Cetak Laporan
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="history.back()">
                <i class="bi bi-arrow-left"></i> Kembali
            </button>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <small class="text-muted d-block">Unit Kerja</small>
                    <strong><?= htmlspecialchars($monitoring['unit_code'] . ' - ' . $monitoring['unit_name']) ?></strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Semester</small>
                    <strong><?= htmlspecialchars($monitoring['semester']) ?></strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Tahun Akademik</small>
                    <strong><?= htmlspecialchars($monitoring['academic_year']) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <?php foreach (['Perencanaan', 'Proses', 'Pelaporan'] as $tahap): ?>
            <?php $st = $stats[$tahap] ?? ['total' => 0, 'done' => 0, 'percent' => 0]; ?>
            <div class="col-md-4 mb-2">
                <div class="info-card info-card-compact">
                    <div class="info-card-header">
                        <i class="bi <?= $tahapIcons[$tahap] ?>"></i>
                        <?= $tahap ?>
                    </div>
                    <div class="info-card-body">
                        <div class="info-card-total">
                            <div class="info-card-total-label"><?= $st['done'] ?> dari <?= $st['total'] ?> Item</div>
                            <div class="info-card-total-value" style="font-size:28px;"><?= $st['percent'] ?>%</div>
                        </div>
                        <div class="info-card-progress-track">
                            <div class="info-card-progress-fill" style="width: <?= $st['percent'] ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (Auth::canManageRtm()): ?>
    <div class="card shadow-sm mb-3">

        <div class="card-header">
            <strong><i class="bi bi-patch-check"></i> Data Pengesahan</strong>
        </div>

        <div class="card-body">

            <form id="signatureForm">

                <input type="hidden" name="id" value="<?= $id ?>">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <strong class="d-block mb-2">Ketua Gugus Kendali Mutu (GKM)</strong>

                        <label class="form-label small">Nama</label>
                        <input type="text" class="form-control form-control-sm mb-2" name="gkm_nama" value="<?= htmlspecialchars($monitoring['gkm_nama'] ?? '') ?>">

                        <label class="form-label small">Jabatan</label>
                        <input type="text" class="form-control form-control-sm mb-2" name="gkm_jabatan" value="<?= htmlspecialchars($monitoring['gkm_jabatan'] ?? 'Ketua Gugus Kendali Mutu') ?>">

                        <label class="form-label small">Tanggal</label>
                        <input type="date" class="form-control form-control-sm mb-2" name="gkm_tanggal" value="<?= htmlspecialchars($monitoring['gkm_tanggal'] ?? '') ?>">

                        <label class="form-label small">Tanda Tangan (Gambar)</label>
                        <div class="mb-1">
                            <?php if (!empty($monitoring['gkm_ttd'])): ?>
                                <img src="<?= BASE_URL . htmlspecialchars($monitoring['gkm_ttd']) ?>" style="max-height:50px; border:1px solid #ddd; border-radius:4px; padding:2px;">
                            <?php else: ?>
                                <span class="text-muted small">Belum ada TTD.</span>
                            <?php endif; ?>
                        </div>
                        <input type="file" class="form-control form-control-sm" name="gkm_ttd" accept="image/jpeg,image/png">
                        <small class="text-muted">Kosongkan jika tidak ingin mengganti.</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <strong class="d-block mb-2">Ketua Lembaga Penjaminan Mutu</strong>

                        <label class="form-label small">Nama</label>
                        <input type="text" class="form-control form-control-sm mb-2" name="lpm_nama" value="<?= htmlspecialchars($monitoring['lpm_nama'] ?? '') ?>">

                        <label class="form-label small">Tanggal</label>
                        <input type="date" class="form-control form-control-sm mb-2" name="lpm_tanggal" value="<?= htmlspecialchars($monitoring['lpm_tanggal'] ?? '') ?>">

                        <label class="form-label small">Tanda Tangan (Gambar)</label>
                        <div class="mb-1">
                            <?php if (!empty($monitoring['lpm_ttd'])): ?>
                                <img src="<?= BASE_URL . htmlspecialchars($monitoring['lpm_ttd']) ?>" style="max-height:50px; border:1px solid #ddd; border-radius:4px; padding:2px;">
                            <?php else: ?>
                                <span class="text-muted small">Belum ada TTD.</span>
                            <?php endif; ?>
                        </div>
                        <input type="file" class="form-control form-control-sm" name="lpm_ttd" accept="image/jpeg,image/png">
                        <small class="text-muted">Kosongkan jika tidak ingin mengganti.</small>
                    </div>

                </div>

                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-save"></i> Simpan Data Pengesahan
                </button>

            </form>

        </div>

    </div>
    <?php endif; ?>

    <form id="checklistForm">

        <input type="hidden" name="monitoring_id" value="<?= $id ?>">

        <?php foreach (['Perencanaan', 'Proses', 'Pelaporan'] as $tahap): ?>

            <div class="card shadow-sm mb-3">

                <div class="card-header">
                    <strong><i class="bi <?= $tahapIcons[$tahap] ?>"></i> Tahap <?= $tahap ?></strong>
                </div>

                <div class="card-body">

                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th width="30">No</th>
                                <th>Item Penilaian</th>
                                <th width="160">Status</th>
                                <th width="260">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items[$tahap] as $i => $item): ?>
                                <tr>
                                    <td class="text-center"><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($item['item_text']) ?></td>
                                    <td>
                                        <select class="form-select form-select-sm item-status" name="responses[<?= $item['id'] ?>][status]" <?= Auth::canManageRtm() ? '' : 'disabled' ?>>
                                            <option value="Belum" <?= $item['status'] === 'Belum' ? 'selected' : '' ?>>Belum</option>
                                            <option value="Sudah" <?= $item['status'] === 'Sudah' ? 'selected' : '' ?>>Sudah</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="responses[<?= $item['id'] ?>][catatan]" value="<?= htmlspecialchars($item['catatan'] ?? '') ?>" <?= Auth::canManageRtm() ? '' : 'disabled' ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>

            </div>

        <?php endforeach; ?>

        <?php if (Auth::canManageRtm()): ?>
        <button type="submit" class="btn btn-primary" id="btnSaveChecklist">
            <i class="bi bi-save"></i> Simpan Checklist
        </button>
        <?php endif; ?>

    </form>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script src="<?= BASE_URL ?>assets/js/gkm_detail.js"></script>