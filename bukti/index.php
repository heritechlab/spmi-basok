<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$repository = new BuktiRepository($conn);
$service    = new BuktiService($repository);

$categories = $service->getCategories();

require_once __DIR__ . '/../layouts/app.php';

?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Upload Bukti Pelaksanaan Standar Mutu</h4>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="history.back()">
            <i class="bi bi-arrow-left"></i> Kembali
        </button>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header"><strong>Upload Dokumen</strong></div>
        <div class="card-body">

            <form id="buktiForm">

<div class="row">

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nama Dokumen</label>
                        <input type="text" class="form-control" id="document_name" name="document_name" placeholder="Contoh: RPS Keperawatan Dasar Semester 3" required>
                    </div>

                    <?php if (!Auth::isAuditee()): ?>
                    <div class="col-md-5 mb-3">
                        <label class="form-label">Unit Kerja</label>
                        <select class="form-select" id="unit_id" name="unit_id" required>
                            <option value="">Pilih Unit Kerja</option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Semester</label>
                        <select class="form-select" id="semester" name="semester" required>
                            <option value="">Pilih Semester</option>
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tahun Akademik</label>
                        <input type="text" class="form-control" id="academic_year" name="academic_year" placeholder="Contoh: 2025/2026" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Keterangan (Opsional)</label>
                        <input type="text" class="form-control" id="description" name="description">
                    </div>

                <div class="col-md-2 mb-3">
                        <label class="form-label">File</label>
                        <input type="file" class="form-control" id="bukti_file_input" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" required>
                    </div>

                </div>

                <button type="submit" class="btn btn-primary" id="btnUploadBukti">
                    <i class="bi bi-upload"></i> Upload
                </button>

            </form>

        </div>
    </div>

    <div class="card shadow-sm">

        <div class="card-body">

<div class="row mb-3">

                <div class="col-md-3">
                    <label class="form-label">Filter Kategori</label>
                    <select id="filterCategory" class="form-select">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (!Auth::isAuditee()): ?>
                <div class="col-md-3">
                    <label class="form-label">Filter Unit Kerja</label>
                    <select id="filterUnit" class="form-select">
                        <option value="">Semua Unit</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-md-2">
                    <label class="form-label">Filter Semester</label>
                    <select id="filterSemester" class="form-select">
                        <option value="">Semua</option>
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Filter TA</label>
                    <input type="text" class="form-control" id="filterAcademicYear" placeholder="2025/2026">
                </div>

</div>

            <table class="table table-bordered table-hover" id="tableBukti">
                <thead>
                    <tr>
                        <th width="120">Kategori</th>
                        <th>Nama Dokumen</th>
                        <th width="170">Unit Kerja</th>
                        <th width="130">Semester/TA</th>
                        <th width="150">Diunggah Oleh</th>
                        <th width="130">Tanggal</th>
                        <th width="110">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script src="<?= BASE_URL ?>assets/js/bukti.js"></script>