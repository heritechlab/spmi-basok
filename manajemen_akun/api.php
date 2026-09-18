<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';

if (!Auth::canManage()) {
    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'search_user':

            $q = '%' . ($_GET['q'] ?? '') . '%';
            $stmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE username LIKE ? OR full_name LIKE ? ORDER BY full_name ASC LIMIT 20");
            $stmt->bind_param("ss", $q, $q);
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);

            break;

        case 'search_dosen':

            $q = '%' . ($_GET['q'] ?? '') . '%';
            $stmt = $conn->prepare("SELECT id, nidn, name FROM obe_dosen WHERE (name LIKE ? OR nidn LIKE ?) AND is_active = 1 ORDER BY name ASC LIMIT 20");
            $stmt->bind_param("ss", $q, $q);
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);

            break;

        case 'search_mahasiswa':

            $q = '%' . ($_GET['q'] ?? '') . '%';
            $stmt = $conn->prepare("SELECT id, nim, nama FROM obe_mahasiswa WHERE (nama LIKE ? OR nim LIKE ?) AND is_active = 1 ORDER BY nama ASC LIMIT 20");
            $stmt->bind_param("ss", $q, $q);
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);

            break;

        case 'list_peran':

            $userId = (int) ($_GET['user_id'] ?? 0);
            $stmt = $conn->prepare("
                SELECT ur.id, r.role_name, u.name AS unit_name, d.name AS dosen_name
                FROM user_roles ur
                JOIN roles r ON r.id = ur.role_id
                LEFT JOIN units u ON u.id = ur.unit_id
                LEFT JOIN obe_dosen d ON d.id = ur.dosen_id
                WHERE ur.user_id = ?
                ORDER BY r.id ASC
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);

            break;

        case 'tambah_peran':

            $userId = (int) ($_POST['user_id'] ?? 0);
            $roleId = (int) ($_POST['role_id'] ?? 0);
            $unitId = !empty($_POST['unit_id']) ? (int) $_POST['unit_id'] : null;
            $dosenId = !empty($_POST['dosen_id']) ? (int) $_POST['dosen_id'] : null;
            $mahasiswaId = !empty($_POST['mahasiswa_id']) ? (int) $_POST['mahasiswa_id'] : null;

            if ($userId <= 0 || $roleId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Akun dan Peran wajib dipilih.']);
                break;
            }

            $check = $conn->prepare("
                SELECT id FROM user_roles
                WHERE user_id = ? AND role_id = ?
                  AND unit_id <=> ? AND dosen_id <=> ? AND mahasiswa_id <=> ?
                LIMIT 1
            ");
            $check->bind_param("iiiii", $userId, $roleId, $unitId, $dosenId, $mahasiswaId);
            $check->execute();

            if ($check->get_result()->fetch_assoc()) {
                echo json_encode(['success' => false, 'message' => 'Akun ini sudah punya peran yang sama persis.']);
                break;
            }

            $insert = $conn->prepare("
                INSERT INTO user_roles (user_id, role_id, unit_id, dosen_id, mahasiswa_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert->bind_param("iiiii", $userId, $roleId, $unitId, $dosenId, $mahasiswaId);
            $insert->execute();

            echo json_encode(['success' => true, 'message' => 'Peran baru berhasil ditambahkan ke akun.']);

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}