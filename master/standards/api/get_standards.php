<?php

declare(strict_types=1);

session_start();

require_once __DIR__.'/../../../config/config.php';
require_once __DIR__.'/../repository.php';
require_once __DIR__.'/../service.php';

header('Content-Type: application/json');

$category = (int)($_GET['category'] ?? 0);

$repository = new StandardRepository($conn);
$service    = new StandardService($repository);

echo json_encode(
    $service->getStandards($category)
);