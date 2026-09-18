<?php

declare(strict_types=1);

ob_start();

session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/Auth.php';

require_once __DIR__ . '/../repository.php';
require_once __DIR__ . '/../service.php';

/*
|--------------------------------------------------------------------------
| Repository
|--------------------------------------------------------------------------
*/

$repository = new IndicatorRepository($conn);

$service = new IndicatorService($repository);

/*
|--------------------------------------------------------------------------
| Request
|--------------------------------------------------------------------------
*/

$draw   = (int)($_GET['draw'] ?? 1);
$start  = (int)($_GET['start'] ?? 0);
$length = (int)($_GET['length'] ?? 10);

$search = '';

if (isset($_GET['search']['value'])) {
    $search = trim($_GET['search']['value']);
}

/*
|--------------------------------------------------------------------------
| Filter Standar
|--------------------------------------------------------------------------
*/

$standardId = 0;

if (
    isset($_GET['standard_id']) &&
    $_GET['standard_id'] !== ''
) {
    $standardId = (int) $_GET['standard_id'];
}

/*
|--------------------------------------------------------------------------
| Filter Indikator
|--------------------------------------------------------------------------
*/

$typeFilter = '';

if (
    isset($_GET['indicator_type']) &&
    $_GET['indicator_type'] !== ''
) {
    $typeFilter = trim($_GET['indicator_type']);
}

/*
|--------------------------------------------------------------------------
| Data
|--------------------------------------------------------------------------
*/

$unitId = Auth::isAuditee() ? (int) ($_SESSION['unit_id'] ?? 0) : 0;

$data = $service->getAll(
    $search,
    $standardId,
    $typeFilter,
    $length,
    $start,
    $unitId
);

$total = $service->count(
    $search,
    $standardId,
    $typeFilter,
    $unitId
);

/*
|--------------------------------------------------------------------------
| Rows
|--------------------------------------------------------------------------
*/

$indicatorIds = array_map(fn($r) => (int) $r['id'], $data['data']);
$ptpStatusMap = $service->getPtpStatusMap($indicatorIds);

$rows = [];

foreach ($data['data'] as $row) {

    $statusBadge = (int)$row['status'] === 1
    ? '<span class="badge bg-success">Aktif</span>'
    : '<span class="badge bg-secondary">Nonaktif</span>';

    if (Auth::canManage()) {

        $aksi = '
            <button
                class="btn btn-warning btn-sm btn-edit"
                data-id="'.$row['id'].'">
                <i class="bi bi-pencil"></i>
            </button>

            <button
                class="btn btn-danger btn-sm btn-delete"
                data-id="'.$row['id'].'">
                <i class="bi bi-trash"></i>
            </button>
        ';

    } else {

        $aksi = '
            <button
                class="btn btn-warning btn-sm btn-locked"
                title="Anda tidak memiliki akses untuk mengubah data">
                <i class="bi bi-pencil"></i>
            </button>

            <button
                class="btn btn-danger btn-sm btn-locked"
                title="Anda tidak memiliki akses untuk menghapus data">
                <i class="bi bi-trash"></i>
            </button>
        ';

    }

            $indicatorType = match($row['indicator_type']){

    'IKU Wajib' =>
        '<span class="badge rounded-pill bg-success px-3 py-2 indicator-badge">IKU Wajib</span>',

    'IKU Pilihan' =>
        '<span class="badge rounded-pill bg-primary px-3 py-2 indicator-badge">IKU Pilihan</span>',

    'IKU PT' =>
        '<span class="badge rounded-pill bg-warning text-dark px-3 py-2 indicator-badge">IKU PT</span>',

    'IKT' =>
        '<span class="badge rounded-pill bg-dark px-3 py-2 indicator-badge">IKT</span>',

    default =>
        '<span class="badge bg-secondary indicator-badge">-</span>'

};

$lastApplied = $ptpStatusMap[(int) $row['id']] ?? null;

    if ($lastApplied) {
        $tanggal = date('d/m/Y', strtotime($lastApplied));
        $ptpBadge = '<span class="badge bg-info text-dark" title="Terakhir ditingkatkan: ' . htmlspecialchars($tanggal) . '"><i class="bi bi-arrow-up-circle-fill"></i> Ditingkatkan</span>';
    } else {
        $ptpBadge = '<span class="badge bg-light text-secondary border">Tetap</span>';
    }

$colorMap = ['Ekstrem' => '#dc2626', 'Tinggi' => '#f97316', 'Sedang' => '#eab308', 'Rendah' => '#22c55e'];

if ((int) ($row['jumlah_risiko'] ?? 0) === 0) {
    $riskBadge = '<span class="text-muted" style="font-size:10.5px;">Belum ada Risiko</span>';
} else {
    $riskColor = $colorMap[$row['risk_level_tertinggi']] ?? '#94a3b8';
    $riskLabel = $row['risk_level_tertinggi'] ?? 'Belum Dianalisis';
    $riskBadge = '<span class="badge" style="background:' . $riskColor . '; font-size:10px;">' . htmlspecialchars($riskLabel) . '</span> <span class="text-muted" style="font-size:9.5px;">(' . (int) $row['jumlah_risiko'] . ')</span>';
}

$rows[] = [

    $row['item_code'],

    $row['statement'],

    $row['indicator'],

    $row['target'],

    $indicatorType,

    $ptpBadge,

    $riskBadge,

    $aksi

];

}

/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/
ob_clean();
echo json_encode([
    'draw' => $draw,
    'recordsTotal' => (int)$total['data'],
    'recordsFiltered' => (int)$total['data'],
    'data' => $rows
]);