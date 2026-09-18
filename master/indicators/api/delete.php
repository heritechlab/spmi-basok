<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';

if (!Auth::canManage()) {

    echo json_encode([
        'success' => false,
        'message' => 'Anda tidak memiliki akses untuk menghapus data.'
    ]);

    exit;
}

$repository = new IndicatorRepository($conn);
$service    = new IndicatorService($repository);

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {

    echo json_encode([
        'success' => false,
        'message' => 'ID indikator tidak valid.'
    ]);

    exit;
}

echo json_encode(

    $service->delete($id)

);