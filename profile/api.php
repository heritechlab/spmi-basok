<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['login'])) {
    echo json_encode(['success' => false, 'message' => 'Sesi tidak valid, silakan login ulang.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'update_profile':

            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if ($fullName === '') {
                echo json_encode(['success' => false, 'message' => 'Nama lengkap wajib diisi.']);
                break;
            }

            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("sssi", $fullName, $email, $phone, $_SESSION['user_id']);
            $stmt->execute();

            $_SESSION['full_name'] = $fullName;

            echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui.']);

            break;

        case 'change_password':

            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (strlen($newPassword) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password baru minimal 6 karakter.']);
                break;
            }

            if ($newPassword !== $confirmPassword) {
                echo json_encode(['success' => false, 'message' => 'Konfirmasi password baru tidak cocok.']);
                break;
            }

            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user || !password_verify($currentPassword, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'Password saat ini salah.']);
                break;
            }

            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);

            $update = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $update->bind_param("si", $hashed, $_SESSION['user_id']);
            $update->execute();

            echo json_encode(['success' => true, 'message' => 'Password berhasil diubah.']);

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}