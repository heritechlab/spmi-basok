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

    $id = isset($_GET['id'])
        ? (int) $_GET['id']
        : 0;

    if ($id <= 0) {

        throw new RuntimeException(
            'ID Unit tidak valid.'
        );

    }

    $data = $service->getById($id);

    if (!$data) {

        throw new RuntimeException(
            'Data Unit tidak ditemukan.'
        );

    }

    echo json_encode([

    'success' => true,

    'message' => 'OK',

    'data' => $data,

    'meta' => null

]);

} catch (Throwable $e) {

    echo json_encode([

        'success' => false,

        'message' => $e->getMessage()

    ]);

}