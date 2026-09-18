<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__.'/../../../config/config.php';

$total = $conn->query("
SELECT COUNT(*) total
FROM audit_indicators
WHERE status=1
")->fetch_assoc()['total'];

$active = $conn->query("
SELECT COUNT(*) total
FROM audit_indicators
WHERE status=1
")->fetch_assoc()['total'];

$inactive = $conn->query("
SELECT COUNT(*) total
FROM audit_indicators
WHERE status=0
")->fetch_assoc()['total'];

$standard = $conn->query("
SELECT COUNT(DISTINCT standard_id) total
FROM audit_indicators
WHERE status=1
")->fetch_assoc()['total'];

echo json_encode([
    "success"=>true,
    "total"=>$total,
    "active"=>$active,
    "inactive"=>$inactive,
    "standard"=>$standard
]);