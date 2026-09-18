<?php

declare(strict_types=1);

session_start();

require_once __DIR__.'/../../../config/config.php';
require_once __DIR__.'/../repository.php';
require_once __DIR__.'/../service.php';

$repository = new StandardRepository($conn);

$service = new StandardService($repository);

$filter = $service->normalizeFilter($_GET);

$rows = $service->getTable($filter);

require '../views/table_rows.php';