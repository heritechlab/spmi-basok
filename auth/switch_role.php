<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['login']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userRoleId = (int) ($_GET['user_role_id'] ?? $_POST['user_role_id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM user_roles WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param("ii", $userRoleId, $_SESSION['user_id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    $_SESSION['error'] = "Peran tidak ditemukan atau bukan milik akun Anda.";
    header("Location: ../dashboard/");
    exit;
}

$_SESSION['role_id'] = (int) $row['role_id'];
$_SESSION['unit_id'] = $row['unit_id'] !== null ? (int) $row['unit_id'] : null;
$_SESSION['dosen_id'] = $row['dosen_id'] !== null ? (int) $row['dosen_id'] : null;
$_SESSION['mahasiswa_id'] = $row['mahasiswa_id'] !== null ? (int) $row['mahasiswa_id'] : null;
$_SESSION['auditee_id'] = $row['auditee_id'] !== null ? (int) $row['auditee_id'] : null;
$_SESSION['active_user_role_id'] = (int) $row['id'];

$update = $conn->prepare("UPDATE users SET role_id = ?, unit_id = ?, dosen_id = ?, mahasiswa_id = ?, auditee_id = ? WHERE id = ?");
$update->bind_param(
    "iiiiii",
    $row['role_id'],
    $row['unit_id'],
    $row['dosen_id'],
    $row['mahasiswa_id'],
    $row['auditee_id'],
    $_SESSION['user_id']
);
$update->execute();

if ((int) $row['role_id'] === 4) {
    header("Location: ../auditee/");
    exit;
}

header("Location: ../dashboard/");
exit;