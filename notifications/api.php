<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['login'])) {
    echo json_encode(['success' => false, 'message' => 'Sesi tidak valid.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

try {

    switch ($action) {

        case 'list':

            $stmt = $conn->prepare("
                SELECT id, title, message, link, is_read, created_at
                FROM notifications
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 20
            ");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
            $countStmt->bind_param("i", $_SESSION['user_id']);
            $countStmt->execute();
            $unread = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);

            echo json_encode(['success' => true, 'message' => '', 'data' => $rows, 'unread' => $unread]);

            break;

        case 'mark_read':

            $id = (int)($_POST['id'] ?? 0);

            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $_SESSION['user_id']);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'OK']);

            break;

        case 'mark_all_read':

            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Semua notifikasi ditandai sudah dibaca.']);

            break;

        default:

            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }

} catch (Throwable $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}