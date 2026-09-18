<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';

$response = [
    'success' => false,
    'message' => '',
    'data'    => null
];

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method tidak diizinkan.');
    }

    $repository = new StandardRepository($conn);
    $service    = new StandardService($repository);

    $service->create($_POST);

    $response['success'] = true;
    $response['message'] = 'Standar berhasil disimpan.';

} catch (Throwable $e) {

    http_response_code(400);

    $response['message'] = $e->getMessage();

}

echo json_encode($response);

exit;