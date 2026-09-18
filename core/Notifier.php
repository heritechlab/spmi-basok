<?php

declare(strict_types=1);

class Notifier
{
    public static function send(mysqli $conn, int $userId, string $title, string $message = '', string $link = ''): void
    {
        if ($userId <= 0) {
            return;
        }

        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, link, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param("isss", $userId, $title, $message, $link);
        $stmt->execute();
    }

    public static function sendToUnit(mysqli $conn, int $unitId, string $title, string $message = '', string $link = ''): void
    {
        $stmt = $conn->prepare("SELECT id FROM users WHERE unit_id = ? AND status = 1");
        $stmt->bind_param("i", $unitId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            self::send($conn, (int) $row['id'], $title, $message, $link);
        }
    }
}