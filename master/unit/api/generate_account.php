<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/Mailer.php';

function jsonResponse(bool $success, string $message, $data = null): void {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

if (!Auth::canManage()) {
    jsonResponse(false, 'Anda tidak memiliki akses untuk membuat/reset akun.');
}

$unitId = (int)($_POST['unit_id'] ?? 0);

if ($unitId <= 0) {
    jsonResponse(false, 'Unit tidak valid.');
}

/*
|--------------------------------------------------------------------------
| Ambil data unit
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("SELECT id, code, name, head_name, email FROM units WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $unitId);
$stmt->execute();
$unit = $stmt->get_result()->fetch_assoc();

if (!$unit) {
    jsonResponse(false, 'Data unit tidak ditemukan.');
}

if (empty($unit['email'])) {
    jsonResponse(false, 'Unit ini belum memiliki email. Lengkapi email di Master Unit Kerja terlebih dahulu.');
}

/*
|--------------------------------------------------------------------------
| Generate password acak
|--------------------------------------------------------------------------
*/

$plainPassword = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
$hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

$username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $unit['code']));
$fullName = $unit['head_name'] ?: $unit['name'];

/*
|--------------------------------------------------------------------------
| Cek apakah sudah ada akun untuk unit ini
|--------------------------------------------------------------------------
*/

$check = $conn->prepare("SELECT id, username FROM users WHERE unit_id = ? AND role_id = 4 LIMIT 1");
$check->bind_param("i", $unitId);
$check->execute();
$existing = $check->get_result()->fetch_assoc();

if ($existing) {

    $update = $conn->prepare("
        UPDATE users
        SET password = ?, full_name = ?, email = ?, status = 1, updated_at = NOW()
        WHERE id = ?
    ");

    $update->bind_param(
        "sssi",
        $hashedPassword,
        $fullName,
        $unit['email'],
        $existing['id']
    );

    $update->execute();

    $username = $existing['username'];
    $actionMessage = 'Password akun berhasil di-reset.';

} else {

    $insert = $conn->prepare("
        INSERT INTO users
            (role_id, unit_id, full_name, username, password, email, status, created_at)
        VALUES
            (4, ?, ?, ?, ?, ?, 1, NOW())
    ");

    $insert->bind_param(
        "issss",
        $unitId,
        $fullName,
        $username,
        $hashedPassword,
        $unit['email']
    );

    $insert->execute();

    $actionMessage = 'Akun login berhasil dibuat.';
}

/*
|--------------------------------------------------------------------------
| Kirim email kredensial
|--------------------------------------------------------------------------
*/

$loginUrl = BASE_URL . 'auth/login.php';

$emailBody = "
    <p>Yth. {$fullName},</p>
    <p>Berikut akun login Anda untuk Sistem Informasi Audit Mutu Internal (SIQUA) sebagai Auditee:</p>
    <table>
        <tr><td><strong>Username</strong></td><td>: {$username}</td></tr>
        <tr><td><strong>Password</strong></td><td>: {$plainPassword}</td></tr>
    </table>
    <p>Silakan login melalui: <a href='{$loginUrl}'>{$loginUrl}</a></p>
    <p>Mohon segera login dan simpan informasi ini dengan aman.</p>
    <p>Terima kasih.</p>
";

$mailer = new Mailer();
$emailSent = $mailer->send($unit['email'], $fullName, 'Akun Login SIQUA - ' . $unit['name'], $emailBody);

jsonResponse(true, $actionMessage . ($emailSent ? ' Email kredensial telah dikirim.' : ' Namun gagal mengirim email, silakan sampaikan manual.'), [
    'username' => $username,
    'password' => $plainPassword,
    'email_sent' => $emailSent,
]);