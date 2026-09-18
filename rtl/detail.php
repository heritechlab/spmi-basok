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

$repository = new RtmRepository($conn);
$service    = new RtmService($repository);

$result = $service->getById($id);

if (!$result['success']) {
    die('Data rapat RTM tidak ditemukan.');
}

$meeting = $result['data'];

require_once __DIR__ . '/../layouts/app.php';

?>

<style>
    #rtlInfoCard {
        background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 100%);
        border: none; border-radius: 16px;
        box-shadow: 0 8px 24px rgba(76,29,149,0.22);
    }
    #rtlInfoCard .card-body { padding: 22px 24px; }
    #rtlInfoCard small.text-muted {
        font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
        color: rgba(255,255,255,0.65) !important; margin-bottom: 4px;
    }
    #rtlInfoCard strong {
        font-size: 13.5px; font-weight: 700; color: #fff; display: block; line-height: 1.35;
    }
    #rtlInfoCard hr { border-color: rgba(255,255,255,0.18); margin: 16px 0 12px; }
    #rtlInfoCard .card-body > div:not(.row) { color: rgba(255,255,255,0.9); }

    #tableRtlDetail { font-size: 12.5px; border-collapse: separate; border-spacing: 0; }
    #tableRtlDetail thead th {
        background: #f6f3fc; color: #5b21b6; font-weight: 700; font-size: 10.5px;
        text-transform: uppercase; letter-spacing: .5px; padding: 11px 14px;
        border-bottom: 2px solid #ddd0f7; border-top: none; vertical-align: middle;
    }
    #tableRtlDetail tbody td {
        padding: 10px 14px; vertical-align: middle; color: #2d2a45; font-weight: 400;
        border-color: #f0eef7;
    }
    #tableRtlDetail tbody tr:nth-child(even) { background: #faf9fd; }
    #tableRtlDetail tbody tr:hover { background: #f1edfc; }
    #tableRtlDetail tbody td:first-child { border-left: 3px solid #ddd0f7; font-weight: 600; color: #14112b; }
    #tableRtlDetail tbody tr:hover td:first-child { border-left-color: #7c3aed; }
    #tableRtlDetail .badge { font-weight: 600; font-size: 10.5px; padding: 4px 10px; border-radius: 20px; }
    #tableRtlDetail .btn-sm { border-radius: 8px; padding: 5px 10px; font-size: 11px; }

    .card:has(#tableRtlDetail) {
        border: 1px solid #eceaf5; border-radius: 14px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(20,17,43,0.04), 0 8px 20px rgba(20,17,43,0.03);
    }
    .card:has(#tableRtlDetail) .card-body { padding: 20px; }
</style>

<div class="container-fluid py-4">

    <h4 class="mb-3">Kelola Rapat RTM</h4>

    <div class="card shadow-sm mb-3" id="rtlInfoCard">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <small class="text-muted d-block">No. BAP/Rapat</small>
                    <strong><?= htmlspecialchars($meeting['meeting_number']) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Periode</small>
                    <strong><?= htmlspecialchars($meeting['period_name'] ?? '-') ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Tanggal Rapat</small>
                    <strong><?= htmlspecialchars($meeting['meeting_date'] ?? '-') ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Status</small>
                    <strong><?= htmlspecialchars($meeting['status']) ?></strong>
                </div>
            </div>
            <?php if (!empty($meeting['agenda'])): ?>
                <hr>
                <small class="text-muted d-block">Agenda</small>
                <div><?= nl2br(htmlspecialchars($meeting['agenda'])) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (Auth::canManageRtm()): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Tanda Tangan Berita Acara</strong>
            <a href="<?= BASE_URL ?>laporan/signatures/manage.php?type=rtm&meeting_id=<?= $id ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pen"></i> Kelola TTD Halaman Pengesahan (Ka. LPM + Ketua Institusi)
            </a>
        </div>
        <div class="card-body">

            <form id="signatureForm" enctype="multipart/form-data">

                <input type="hidden" name="meeting_id" value="<?= $id ?>">

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <strong class="d-block mb-2">Notulis Rapat</strong>

                        <label class="form-label small">Nama</label>
                        <input type="text" class="form-control form-control-sm mb-2" name="notulis_nama" value="<?= htmlspecialchars($meeting['notulis_nama'] ?? '') ?>">

                        <label class="form-label small">Tanggal</label>
                        <input type="date" class="form-control form-control-sm mb-2" name="notulis_tanggal" value="<?= htmlspecialchars($meeting['notulis_tanggal'] ?? '') ?>">

                        <label class="form-label small">Tanda Tangan (Gambar)</label>
                        <div class="mb-1">
                            <?php if (!empty($meeting['notulis_ttd'])): ?>
                                <img src="<?= BASE_URL . htmlspecialchars($meeting['notulis_ttd']) ?>" style="max-height:45px; border:1px solid #ddd; border-radius:4px; padding:2px;">
                            <?php else: ?>
                                <span class="text-muted small">Belum ada TTD.</span>
                            <?php endif; ?>
                        </div>
                        <input type="file" class="form-control form-control-sm" name="notulis_ttd" accept="image/jpeg,image/png">
                    </div>

                    <div class="col-md-6 mb-3">
                        <strong class="d-block mb-2">Pimpinan Rapat</strong>

                        <label class="form-label small">Nama</label>
                        <input type="text" class="form-control form-control-sm mb-2" name="pimpinan_nama" value="<?= htmlspecialchars($meeting['pimpinan_nama'] ?? '') ?>">

                        <label class="form-label small">Tanggal</label>
                        <input type="date" class="form-control form-control-sm mb-2" name="pimpinan_tanggal" value="<?= htmlspecialchars($meeting['pimpinan_tanggal'] ?? '') ?>">

                        <label class="form-label small">Tanda Tangan (Gambar)</label>
                        <div class="mb-1">
                            <?php if (!empty($meeting['pimpinan_ttd'])): ?>
                                <img src="<?= BASE_URL . htmlspecialchars($meeting['pimpinan_ttd']) ?>" style="max-height:45px; border:1px solid #ddd; border-radius:4px; padding:2px;">
                            <?php else: ?>
                                <span class="text-muted small">Belum ada TTD.</span>
                            <?php endif; ?>
                        </div>
                        <input type="file" class="form-control form-control-sm" name="pimpinan_ttd" accept="image/jpeg,image/png">
                    </div>

                </div>

                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-save"></i> Simpan TTD Berita Acara
                </button>

            </form>

        </div>
    </div>
    <?php endif; ?>

    <!-- ===================================================== -->
    <!-- DOKUMEN PENDUKUNG -->
    <!-- ===================================================== -->

    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Dokumen Pendukung Rapat</strong>

            <?php if (Auth::canManageRtm()): ?>
                <div class="d-flex gap-2 align-items-center">
                    <select class="form-select form-select-sm" id="docType" style="width:auto;">
                        <option value="BAP">BAP</option>
                        <option value="Notulen">Notulen</option>
                        <option value="Foto Kegiatan">Foto Kegiatan</option>
                    </select>
                    <input type="file" class="form-control form-control-sm" id="docFile" style="width:auto;">
                    <button class="btn btn-primary btn-sm" id="btnUploadDoc">
                        <i class="bi bi-upload"></i> Upload
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div id="documentList">
                <p class="text-muted mb-0">Memuat dokumen...</p>
            </div>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- DAFTAR RTL -->
    <!-- ===================================================== -->

<?php $isSurveyMeeting = str_starts_with($meeting['meeting_number'] ?? '', 'RTM-SURVEY-'); ?>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Daftar Rencana Tindak Lanjut (RTL)</strong>

            <?php if ($isSurveyMeeting): ?>
                <span class="badge bg-info">
                    <i class="bi bi-magic"></i> Rapat Otomatis dari Survey Kepuasan — RTL sudah terisi otomatis
                </span>
            <?php else: ?>
                <button class="btn btn-primary <?= Auth::canManageRtm() ? '' : 'btn-locked' ?>" id="btnAddRtl"
                    <?= Auth::canManageRtm() ? '' : 'title="Hanya Auditee yang dapat menambah data"' ?>>
                    <i class="bi bi-plus-circle"></i>
                    Tambah RTL
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="tableRtlDetail">
                    <thead>
                        <tr>
                            <th>Temuan</th>
                            <th width="90">Kuadran</th>
                            <th>Kegiatan</th>
                            <th width="120">Waktu</th>
                            <th width="110">PIC</th>
                            <th width="110">Anggaran</th>
                            <th width="100">Status</th>
                            <th width="100">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="rtlTableBody">
                        <tr><td colspan="8" class="text-muted">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/views/action_modal.php'; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
    const RTM_MEETING_ID = <?= (int) $id ?>;
    const RTM_PERIOD_ID = <?= (int) $meeting['period_id'] ?>;
</script>
<script src="<?= BASE_URL ?>assets/js/rtm_detail.js"></script>