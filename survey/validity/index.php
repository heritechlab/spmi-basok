<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../repository.php';

if (!Auth::canManage()) {
    die('Anda tidak memiliki akses ke halaman ini.');
}

$surveyRepo = new SurveyRepository($conn);
$types = $surveyRepo->getAllTypes()['data'] ?? $surveyRepo->getAllTypes();

require_once __DIR__ . '/../../layouts/app.php';

?>

<div class="container-fluid py-4">

    <h4 class="mb-3">Kelola Hasil Uji Validitas &amp; Reliabilitas Instrumen</h4>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <label class="form-label">Pilih Jenis Survey</label>
            <select class="form-select" id="typeSelector" style="max-width:400px;">
                <option value="">-- Pilih Jenis Survey --</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div id="validityContainer">
        <p class="text-muted">Silakan pilih Jenis Survey terlebih dahulu.</p>
    </div>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>assets/js/survey_validity.js"></script>