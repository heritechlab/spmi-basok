<?php

declare(strict_types=1);

class Periode
{
    public static function getActiveId(mysqli $conn): int
    {
        $result = $conn->query("SELECT id FROM obe_periode_akademik WHERE is_active = 1 LIMIT 1");
        $row = $result ? $result->fetch_assoc() : null;

        return $row ? (int) $row['id'] : 0;
    }
}