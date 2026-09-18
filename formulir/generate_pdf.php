<?php

declare(strict_types=1);

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../master/institution/repository.php';
require_once __DIR__ . '/repository.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use setasign\Fpdi\Fpdi;

$id = (int)($_GET['id'] ?? 0);

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

$formulirRepo = new FormulirRepository($conn);
$formulir = $formulirRepo->findById($id);

if (!$formulir) {
    die('Dokumen Formulir tidak ditemukan.');
}

$bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

$berlakuSejak = '-';
if (!empty($formulir['effective_date'])) {
    $ts = strtotime($formulir['effective_date']);
    $berlakuSejak = $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

$institusiName = $profile['institution_name'] ?? '';

$jabatanMap = [
    'perumusan'    => $formulir['perumusan_jabatan'] ?: '-',
    'pemeriksaan'  => $formulir['pemeriksaan_jabatan'] ?: '-',
    'persetujuan'  => $formulir['persetujuan_jabatan'] ?: '-',
    'penetapan'    => $formulir['penetapan_jabatan'] ?: '-',
    'pengendalian' => $formulir['pengendalian_jabatan'] ?: '-',
];

/*
|--------------------------------------------------------------------------
| 1. Bangun HTML Halaman Pengesahan (khusus Dompdf, CSS sederhana)
|--------------------------------------------------------------------------
*/

$logoImgTag = '';
if (!empty($profile['logo'])) {
    $logoFile = dirname(__DIR__) . '/' . ltrim($profile['logo'], '/');
    if (is_file($logoFile)) {
        $logoImgTag = '<img src="file://' . $logoFile . '">';
    }
}

$html = '<html><head><meta charset="UTF-8"><style>
@page { margin: 15mm; }
    body { font-family: Arial, sans-serif; font-size: 12px; color:#000; width: 100%; }
    table { width:100%; border-collapse: collapse; table-layout: fixed; }
    .kop-table td { border:1px solid #000; padding:2px 8px; vertical-align:middle; line-height: 1.2; }
    .kop-logo-cell { width:90px; text-align:center; }
    .kop-logo-cell img { max-width:80px; max-height:80px; }
    .kop-title-cell { font-weight:bold; font-size:13px; text-align:center; line-height: 1.3; }
    .kop-label-cell { width:110px; font-weight:bold; }
    .kop-value-cell { width:120px; }
    .doc-title { text-align:center; margin:16px 0 4px; }
    .doc-title h1 { font-size:15px; font-weight:bold; text-transform:uppercase; margin:0; }
    .doc-title h2 { font-size:13px; font-weight:bold; text-transform:uppercase; margin:2px 0 0; }
    .approval-table { margin-top:16px; }
.approval-table td, .approval-table th { border:1px solid #000; padding:6px 8px; font-size:11px; vertical-align:middle; text-align:left; }
    .approval-table th { background:#f0f0f0; font-weight:bold; text-align:center; }
    .approval-table td.text-left { text-align:left; }
    .footer-note { margin-top:16px; font-size:11px; }
</style></head><body>';

$html .= '<table class="kop-table">
    <tr>
        <td class="kop-logo-cell" rowspan="4">' . $logoImgTag . '</td>
        <td class="kop-title-cell" rowspan="2">Lembaga Penjaminan Mutu<br>' . htmlspecialchars($institusiName) . '</td>
        <td class="kop-label-cell">No. Dokumen</td>
        <td class="kop-value-cell">' . htmlspecialchars($formulir['document_number']) . '</td>
    </tr>
    <tr>
        <td class="kop-label-cell">Berlaku sejak</td>
        <td class="kop-value-cell">' . htmlspecialchars($berlakuSejak) . '</td>
    </tr>
    <tr>
        <td class="kop-title-cell" rowspan="2">' . htmlspecialchars($formulir['title']) . '</td>
        <td class="kop-label-cell">Revisi</td>
        <td class="kop-value-cell">' . (int) $formulir['revision'] . '</td>
    </tr>
    <tr>
        <td class="kop-label-cell">Halaman</td>
        <td class="kop-value-cell">1 dari ' . (int) $formulir['total_pages'] . '</td>
    </tr>
</table>';

$html .= '<div class="doc-title"><h1>' . htmlspecialchars($formulir['title']) . '</h1><h2>' . htmlspecialchars($institusiName) . '</h2></div>';

if (!empty($formulir['description'])) {
    $html .= '<p>' . nl2br(htmlspecialchars($formulir['description'])) . '</p>';
}

$html .= '<table class="approval-table">
    <thead>
        <tr><th rowspan="2" width="18%">Proses</th><th colspan="3">Penanggung Jawab</th><th rowspan="2" width="10%">Tanggal</th></tr>
        <tr><th width="30%">Nama</th><th width="30%">Jabatan</th><th width="12%">Tanda Tangan</th></tr>
    </thead>
    <tbody>';

$processes = [
    '1. Perumusan'    => 'perumusan',
    '2. Pemeriksaan'  => 'pemeriksaan',
    '3. Persetujuan'  => 'persetujuan',
    '4. Penetapan'    => 'penetapan',
    '5. Pengendalian' => 'pengendalian',
];

foreach ($processes as $label => $key) {

    $ttdCell = '';

    if (!empty($formulir[$key . '_ttd'])) {
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode(BASE_URL . 'verify.php?type=formulir&id=' . $formulir['id'] . '&role=' . $key);
        $ttdCell = '<img src="' . $qrUrl . '" style="width:35px;height:35px;">';
    }

    $tanggalCell = !empty($formulir[$key . '_tanggal']) ? date('d/m/Y', strtotime($formulir[$key . '_tanggal'])) : '';

    $html .= '<tr>
        <td class="text-left">' . $label . '</td>
        <td class="text-left">' . htmlspecialchars($formulir[$key . '_nama'] ?: '') . '</td>
        <td class="text-left">' . htmlspecialchars($jabatanMap[$key]) . '</td>
        <td style="text-align:center;">' . $ttdCell . '</td>
        <td style="text-align:center;">' . $tanggalCell . '</td>
    </tr>';
}

$html .= '</tbody></table>';
$html .= '<div class="footer-note">1 &nbsp;&nbsp; ' . htmlspecialchars(strtoupper($institusiName)) . '</div>';
$html .= '</body></html>';

/*
|--------------------------------------------------------------------------
| 2. Render Halaman Pengesahan jadi PDF (via Dompdf)
|--------------------------------------------------------------------------
*/

$tempWorkDir = dirname(__DIR__) . '/uploads/tmp';

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');
$options->setChroot(dirname(__DIR__));
$options->setTempDir($tempWorkDir);
$options->setFontCache($tempWorkDir);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$tempDir = dirname(__DIR__) . '/uploads/tmp';
$pengesahanPdfPath = $tempDir . '/formulir_pengesahan_' . uniqid() . '.pdf';
file_put_contents($pengesahanPdfPath, $dompdf->output());

/*
|--------------------------------------------------------------------------
| 3. Gabungkan Halaman Pengesahan + Isi PDF (via FPDI)
|--------------------------------------------------------------------------
*/

$pdf = new Fpdi();

$pageCount = $pdf->setSourceFile($pengesahanPdfPath);
for ($i = 1; $i <= $pageCount; $i++) {
    $tplId = $pdf->importPage($i);
    $size = $pdf->getTemplateSize($tplId);
    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
    $pdf->useTemplate($tplId);
}

if (!empty($formulir['file_path'])) {

    $uploadedPdfPath = dirname(__DIR__) . '/' . ltrim($formulir['file_path'], '/');

    if (is_file($uploadedPdfPath)) {

        $pageCount2 = $pdf->setSourceFile($uploadedPdfPath);

        for ($i = 1; $i <= $pageCount2; $i++) {
            $tplId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tplId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);
        }
    }
}

@unlink($pengesahanPdfPath);

$filename = 'Formulir_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $formulir['document_number']) . '.pdf';

$pdf->Output('I', $filename);