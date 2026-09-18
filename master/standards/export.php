<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';

$repository = new StandardRepository($conn);
$rows = $repository->getStandards();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=master_standar_' . date('Ymd_His') . '.csv');

$out = fopen('php://output', 'w');

fputcsv($out, [
    'Kode', 'Nama Standar', 'Nomor Dokumen', 'Jenis Standar',
    'Kategori', 'Jenis Dokumen', 'Status', 'Tanggal Terbit', 'Revisi'
]);

foreach ($rows as $row) {
    fputcsv($out, [
        $row['code'],
        $row['name'],
        $row['document_number'],
        $row['type_name'] ?? '',
        $row['category_name'] ?? '',
        $row['document_type_name'] ?? '',
        $row['status_name'] ?? '',
        $row['publish_date'],
        $row['revision'],
    ]);
}

fclose($out);
exit;