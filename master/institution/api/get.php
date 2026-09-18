<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';

try {

    $repository = new InstitutionRepository($conn);

    $service = new InstitutionService($repository);

    $profile = $service->getProfile();

    echo json_encode([

        'success' => true,

        'message' => 'Success',

        'data' => $profile

    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([

        'success' => false,

        'message' => $e->getMessage()

    ]);

}