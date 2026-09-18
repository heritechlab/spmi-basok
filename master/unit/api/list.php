<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';

try {

    $repository = new UnitRepository($conn);

    $service = new UnitService($repository);

    $keyword = trim($_GET['keyword'] ?? '');

    $type = trim($_GET['type'] ?? '');

    $status = $_GET['status'] ?? '';

    $page = max(
        1,
        (int)($_GET['page'] ?? 1)
    );

    $limit = max(
        1,
        (int)($_GET['limit'] ?? 20)
    );

    $status = ($status === '')
        ? null
        : (int)$status;

    $type = ($type === '')
        ? null
        : $type;

    $rows = $service->paginate(

        $keyword,

        $type,

        $status,

        $page,

        $limit

    );

    echo json_encode([

    'success' => true,

    'message' => 'OK',

    'data' => $rows,

    'meta' => [

    'total' => $service->countFiltered(

        $keyword,

        $type,

        $status

    ),

    'page' => $page,

    'limit' => $limit

]

]);

} catch (Throwable $e) {

    echo json_encode([

        'success' => false,

        'message' => $e->getMessage()

    ]);

}