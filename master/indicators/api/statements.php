<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';

$repository = new IndicatorRepository($conn);
$service = new IndicatorService($repository);

$standardId = (int) ($_GET['standard_id'] ?? 0);

echo json_encode($service->getStatementsByStandard($standardId));