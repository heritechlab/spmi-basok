<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';

if (!Auth::canManage()) {
    die('Anda tidak memiliki akses ke halaman ini.');
}

$repository = new SurveyRepository($conn);
$service    = new SurveyService($repository);

$types = $service->getAllTypes()['data'];

require_once __DIR__ . '/../../layouts/app.php';

?>

<div class="container-fluid py-4">

    <h4 class="mb-3">Kelola Pertanyaan Survey</h4>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label">Pilih Jenis Survey</label>
            <select class="form-select" id="typeSelector" style="max-width: 400px;">
                <option value="">-- Pilih Jenis Survey --</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div id="treeContainer">
        <p class="text-muted">Silakan pilih jenis survey terlebih dahulu.</p>
    </div>

</div>

<!-- Modal Layanan -->
<div class="modal fade" id="layananModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="layananForm">
                <input type="hidden" id="layanan_id" name="id">
                <input type="hidden" id="layanan_type_id" name="type_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="layananModalTitle">Tambah Layanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Nama Layanan</label>
                    <input type="text" class="form-control" id="layanan_name" name="name" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Kategori / Aspek -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="categoryForm">
                <input type="hidden" id="category_id" name="id">
                <input type="hidden" id="category_type_id" name="type_id">
                <input type="hidden" id="category_layanan_id" name="layanan_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle">Tambah Kategori/Aspek</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Nama Kategori/Aspek</label>
                    <textarea class="form-control" id="category_name" name="name" rows="2" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Pertanyaan -->
<div class="modal fade" id="questionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="questionForm">
                <input type="hidden" id="question_id" name="id">
                <input type="hidden" id="question_category_id" name="category_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="questionModalTitle">Tambah Pertanyaan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Teks Pertanyaan</label>
                    <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<script src="<?= BASE_URL ?>assets/js/survey_manage.js"></script>