<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';

function jsonResponse(bool $success, string $message, $data = null): void {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

if (empty($_FILES['file']['tmp_name'])) {
    jsonResponse(false, 'File belum dipilih.');
}

$repository = new StandardRepository($conn);

$handle = fopen($_FILES['file']['tmp_name'], 'r');

if (!$handle) {
    jsonResponse(false, 'File tidak bisa dibaca.');
}

fgetcsv($handle); // lewati baris header

$imported = 0;
$skipped = 0;
$errors = [];
$rowNum = 1;

while (($row = fgetcsv($handle)) !== false) {
    $rowNum++;

    $code = trim($row[0] ?? '');
    $name = trim($row[1] ?? '');
    $documentNumber = trim($row[2] ?? '');
    $typeCode = trim($row[3] ?? '');
    $categoryCode = trim($row[4] ?? '');
    $docTypeCode = trim($row[5] ?? '');
    $statusCode = trim($row[6] ?? '');
    $publishDate = trim($row[7] ?? '');
    $revision = (int)($row[8] ?? 0);

    if ($code === '' || $name === '') {
        $errors[] = "Baris {$rowNum}: kode/nama kosong, dilewati.";
        $skipped++;
        continue;
    }

    if ($repository->existsCode($code)) {
        $errors[] = "Baris {$rowNum}: kode '{$code}' sudah ada, dilewati.";
        $skipped++;
        continue;
    }

    $typeId = $conn->query("SELECT id FROM standard_types WHERE code='" . $conn->real_escape_string($typeCode) . "' LIMIT 1")->fetch_assoc()['id'] ?? null;
    $categoryId = $conn->query("SELECT id FROM standard_categories WHERE code='" . $conn->real_escape_string($categoryCode) . "' LIMIT 1")->fetch_assoc()['id'] ?? null;
    $docTypeId = $conn->query("SELECT id FROM standard_document_types WHERE code='" . $conn->real_escape_string($docTypeCode) . "' LIMIT 1")->fetch_assoc()['id'] ?? null;
    $statusId = $conn->query("SELECT id FROM standard_statuses WHERE code='" . $conn->real_escape_string($statusCode) . "' LIMIT 1")->fetch_assoc()['id'] ?? null;

    $data = [
        'code' => $code,
        'name' => $name,
        'reference' => '',
        'document_number' => $documentNumber,
        'document_type_id' => $docTypeId,
        'publish_date' => $publishDate !== '' ? $publishDate : null,
        'type_id' => $typeId,
        'category_id' => $categoryId,
        'status_id' => $statusId,
        'category' => '',
        'version' => '',
        'revision' => $revision,
        'year' => $publishDate !== '' ? (int) substr($publishDate, 0, 4) : (int) date('Y'),
        'weight' => 1,
        'sort_order' => 1,
        'description' => '',
        'status' => 1,
        'is_active' => 1,
        'document_file' => null,
        'document_original_name' => null,
        'document_size' => null,
    ];

    try {
        $repository->create($data);
        $imported++;
    } catch (Throwable $e) {
        $errors[] = "Baris {$rowNum}: gagal disimpan ({$e->getMessage()}).";
        $skipped++;
    }
}

fclose($handle);

jsonResponse(true, "Import selesai: {$imported} data berhasil, {$skipped} dilewati.", ['errors' => $errors]);