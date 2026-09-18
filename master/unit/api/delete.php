<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__.'/../../../config/config.php';
require_once __DIR__.'/../../../core/Auth.php';
require_once __DIR__.'/../repository.php';
require_once __DIR__.'/../service.php';

try {

    if (!Auth::canManage()) {
        echo json_encode([
            'success' => false,
            'message' => 'Anda tidak memiliki akses untuk menghapus data.'
        ]);
        exit;
    }

    $repository = new UnitRepository($conn);
    $service    = new UnitService($repository);

    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {

        throw new RuntimeException(
            'ID Unit tidak valid.'
        );

    }

    $service->delete($id);

    echo json_encode([

        'success' => true,

        'message' => 'Unit kerja berhasil dihapus.',

        'data' => null,

        'meta' => null

    ]);

} catch (Throwable $e) {

    echo json_encode([

        'success' => false,

        'message' => $e->getMessage(),

        'errors'  => []

    ]);

}