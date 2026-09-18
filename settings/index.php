<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';

if (!Auth::isAdmin()) {
    die('Halaman ini hanya untuk Administrator.');
}

require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$config = require __DIR__ . '/config.php';

$repository = new SettingsRepository($conn);
$service    = new SettingsService($repository);

$result = $service->getAll();
$settings = $result['data'] ?? [];

require_once __DIR__ . '/../layouts/app.php';

?>

<div class="container-fluid py-4">

    <h4 class="mb-3">Pengaturan Sistem</h4>

    <div class="row">

        <div class="col-lg-6 mb-3">

            <div class="card shadow-sm">
                <div class="card-header"><strong><i class="bi bi-gear"></i> Pengaturan Umum</strong></div>
                <div class="card-body">

                    <form id="generalForm">

                        <div class="mb-3">
                            <label class="form-label">Nama Aplikasi</label>
                            <input type="text" class="form-control" name="app_name" value="<?= htmlspecialchars($settings['app_name'] ?? 'SIQUA') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Item per Halaman (Tabel)</label>
                            <input type="number" class="form-control" name="items_per_page" min="5" max="100" value="<?= htmlspecialchars($settings['items_per_page'] ?? '10') ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Pengaturan Umum
                        </button>

                    </form>

                </div>
            </div>

        </div>

        <div class="col-lg-6 mb-3">

            <div class="card shadow-sm">
                <div class="card-header"><strong><i class="bi bi-envelope"></i> Pengaturan Email (SMTP)</strong></div>
                <div class="card-body">

                    <form id="emailForm">

                        <div class="mb-3">
                            <label class="form-label">SMTP Host</label>
                            <input type="text" class="form-control" name="smtp_host" placeholder="smtp.gmail.com" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SMTP Port</label>
                            <input type="text" class="form-control" name="smtp_port" placeholder="587" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Pengirim (Username)</label>
                            <input type="text" class="form-control" name="smtp_username" placeholder="nama@gmail.com" value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">App Password</label>
                            <input type="password" class="form-control" name="smtp_password" placeholder="Kosongkan jika tidak ingin mengubah">
                            <small class="text-muted">Kosongkan kalau tidak ingin mengubah password yang sudah tersimpan.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Pengirim</label>
                            <input type="text" class="form-control" name="smtp_from_name" placeholder="SIQUA" value="<?= htmlspecialchars($settings['smtp_from_name'] ?? 'SIQUA') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Pengirim (From)</label>
                            <input type="text" class="form-control" name="smtp_from_email" placeholder="Kosongkan untuk memakai Email Pengirim di atas" value="<?= htmlspecialchars($settings['smtp_from_email'] ?? '') ?>">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Pengaturan Email
                        </button>

                    </form>

                    <hr>

                    <label class="form-label">Uji Coba Kirim Email</label>
                    <div class="d-flex gap-2">
                        <input type="email" class="form-control" id="test_email_to" placeholder="email@tujuan.com">
                        <button type="button" class="btn btn-outline-secondary" id="btnTestEmail">
                            <i class="bi bi-send"></i> Kirim Tes
                        </button>
                    </div>

                </div>
            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script src="<?= BASE_URL ?>assets/js/settings.js"></script>