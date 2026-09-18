<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';

try {

    if (!Auth::canManage()) {
        echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk mengubah data.']);
        exit;
    }

    $repository = new InstitutionRepository($conn);

    $service = new InstitutionService($repository);

    $result = $service->update($_POST, $_FILES);

    echo json_encode($result);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([

        'success' => false,

        'message' => $e->getMessage()

    ]);

}