<?php

declare(strict_types=1);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=template_master_standar.csv');

$out = fopen('php://output', 'w');

fputcsv($out, [
    'Kode', 'Nama Standar', 'Nomor Dokumen', 'Kode Jenis Standar',
    'Kode Kategori', 'Kode Jenis Dokumen', 'Kode Status', 'Tanggal Terbit (YYYY-MM-DD)', 'Revisi'
]);

fputcsv($out, [
    'SND-01', 'Contoh Nama Standar', 'DOC-001', 'SNDIKTI',
    'VMTS', 'PDF', 'DRAFT', '2026-01-01', '0'
]);

fclose($out);
exit;