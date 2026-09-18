<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['login']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $passwordBaru = trim($_POST['password_baru'] ?? '');
    $konfirmasi = trim($_POST['konfirmasi_password'] ?? '');

    if (strlen($passwordBaru) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($passwordBaru !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $hash = password_hash($passwordBaru, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?");
        $stmt->bind_param("si", $hash, $_SESSION['user_id']);
        $stmt->execute();

        $roleId = (int) ($_SESSION['role_id'] ?? 0);

        if ($roleId === 4) {
            header("Location: ../auditee/");
        } else {
            header("Location: ../dashboard/");
        }
        exit;
    }
}

?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Wajib Ganti Password</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
    body { background: linear-gradient(135deg, #5b21b6 0%, #6a11cb 50%, #581c87 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: Arial, sans-serif; }
    .card-wrap { background: #fff; border-radius: 18px; padding: 36px 32px; width: 100%; max-width: 420px; box-shadow: 0 20px 50px rgba(0,0,0,0.25); }
    .icon-badge { width: 56px; height: 56px; border-radius: 50%; background: #f1edfc; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 16px; }
</style>
</head>
<body>

<div class="card-wrap">
    <div class="icon-badge"><i class="bi bi-shield-lock-fill"></i></div>
    <h5 class="text-center fw-bold mb-1">Wajib Ganti Password</h5>
    <p class="text-center text-muted small mb-4">Ini adalah login pertama Anda. Demi keamanan, silakan ganti password default sebelum melanjutkan.</p>

    <?php if ($error): ?>
    <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label small">Password Baru</label>
            <input type="password" class="form-control" name="password_baru" minlength="6" required>
        </div>
        <div class="mb-4">
            <label class="form-label small">Konfirmasi Password Baru</label>
            <input type="password" class="form-control" name="konfirmasi_password" minlength="6" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Simpan &amp; Lanjutkan</button>
    </form>
</div>

</body>
</html>