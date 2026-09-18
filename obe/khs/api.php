<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'mahasiswa_list':

            $kurikulumId = (int) ($_GET['kurikulum_id'] ?? 0);

            $stmt = $conn->prepare("
                SELECT id, nim, nama
                FROM obe_mahasiswa
                WHERE kurikulum_id = ? AND is_active = 1 AND status = 'Aktif'
                ORDER BY nama ASC
            ");
            $stmt->bind_param("i", $kurikulumId);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            echo json_encode(['success' => true, 'data' => $data]);

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}