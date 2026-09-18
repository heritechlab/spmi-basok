<?php

header('Content-Type: application/json');

require_once '../../../config/config.php';
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method Not Allowed'
    ]);

    exit;
}

$checklistId = isset($_POST['checklist_id'])
    ? (int) $_POST['checklist_id']
    : 0;

$status = trim($_POST['status'] ?? '');

$allowedStatus = [
    'Tidak Terpenuhi',
    'Memenuhi Sebagian',
    'Memenuhi',
    'Melampaui'
];

if ($checklistId <= 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Checklist tidak valid.'
    ]);

    exit;
}

if (!in_array($status, $allowedStatus, true)) {

    echo json_encode([
        'success' => false,
        'message' => 'Status audit tidak valid.'
    ]);

    exit;
}

try {

    $repo = new WorkspaceRepository($conn);

    $save = $repo->saveStatus(
        $checklistId,
        $status
    );

    if ($save) {

        echo json_encode([
            'success' => true,
            'message' => 'Status audit berhasil disimpan.',
            'status'  => $status
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyimpan status.'
        ]);

    }

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

}