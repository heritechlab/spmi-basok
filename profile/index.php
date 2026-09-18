<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['login'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$roleLabels = [
    1 => 'Administrator',
    2 => 'Ketua LPM',
    3 => 'Auditor',
    4 => 'Auditee',
];

$stmt = $conn->prepare("
    SELECT us.*, u.name AS unit_name
    FROM users us
    LEFT JOIN units u ON u.id = us.unit_id
    WHERE us.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

require_once __DIR__ . '/../layouts/app.php';

?>

<div class="container-fluid py-4">

    <h4 class="mb-3">Profil Saya</h4>

    <div class="row">

        <div class="col-lg-5 mb-3">

            <div class="card shadow-sm">
                <div class="card-header"><strong>Informasi Akun</strong></div>
                <div class="card-body text-center">

                    <div class="profile-card-avatar-circle mx-auto mb-3" style="width:90px; height:90px; font-size:40px;">
                        <i class="bi bi-person-fill"></i>
                    </div>

                    <h5 class="mb-1"><?= htmlspecialchars($user['full_name'] ?? '-') ?></h5>
                    <span class="badge bg-primary mb-3"><?= htmlspecialchars($roleLabels[(int)($user['role_id'] ?? 0)] ?? '-') ?></span>

                    <table class="table table-borderless table-sm text-start">
                        <tr>
                            <td class="text-muted" width="140">Username</td>
                            <td>: <?= htmlspecialchars($user['username'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email</td>
                            <td>: <?= htmlspecialchars($user['email'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">No. HP</td>
                            <td>: <?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                        </tr>
                        <?php if (!empty($user['unit_name'])): ?>
                        <tr>
                            <td class="text-muted">Unit Kerja</td>
                            <td>: <?= htmlspecialchars($user['unit_name']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($user['nidn_nip'])): ?>
                        <tr>
                            <td class="text-muted">NIDN/NIP</td>
                            <td>: <?= htmlspecialchars($user['nidn_nip']) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>

                </div>
            </div>

        </div>

        <div class="col-lg-7 mb-3">

            <div class="card shadow-sm">
                <div class="card-header"><strong>Edit Informasi Akun</strong></div>
                <div class="card-body">

                    <form id="profileForm">

                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">No. HP</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>

                        <button type="submit" class="btn btn-primary" id="btnSaveProfile">
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </button>

                        <button type="button" class="btn btn-light" onclick="history.back()">
                            <i class="bi bi-x-circle"></i> Batal
                        </button>

                    </form>

                </div>
            </div>

            <div class="card shadow-sm mt-3" id="change-password">
                <div class="card-header"><strong>Ubah Password</strong></div>
                <div class="card-body">

                    <form id="passwordForm">

                        <div class="mb-3">
                            <label class="form-label">Password Saat Ini</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                            <small class="text-muted">Minimal 6 karakter.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>

                        <button type="submit" class="btn btn-warning" id="btnSavePassword">
                            <i class="bi bi-key"></i> Ubah Password
                        </button>

                        <button type="button" class="btn btn-light" onclick="document.getElementById('passwordForm').reset();">
                            <i class="bi bi-x-circle"></i> Batal
                        </button>

                    </form>

                </div>
            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script src="<?= BASE_URL ?>assets/js/profile.js"></script>