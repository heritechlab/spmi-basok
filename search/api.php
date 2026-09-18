<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';

if (empty($_SESSION['login'])) {
    echo json_encode(['success' => false, 'message' => 'Sesi tidak valid.']);
    exit;
}

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

$like = '%' . $q . '%';
$results = [];

/*
|--------------------------------------------------------------------------
| Menu Statis
|--------------------------------------------------------------------------
*/

$menus = [
    ['label' => 'Standar', 'url' => 'master/standards/', 'admin_only' => false],
    ['label' => 'Indikator', 'url' => 'master/indicators/', 'admin_only' => false],
    ['label' => 'Unit Kerja', 'url' => 'master/unit/', 'admin_only' => false],
    ['label' => 'Data Auditor', 'url' => 'master/auditor/', 'admin_only' => false],
    ['label' => 'Periode Audit', 'url' => 'master/periods/', 'admin_only' => false],
    ['label' => 'Institusi', 'url' => 'master/institution/', 'admin_only' => false],
    ['label' => 'Penugasan Audit', 'url' => 'audit/assignments/', 'admin_only' => true],
    ['label' => 'Workspace Audit (LKA)', 'url' => 'audit/workspace/', 'admin_only' => true],
    ['label' => 'Temuan Audit', 'url' => 'audit/findings/', 'admin_only' => false],
    ['label' => 'Desk Evaluation', 'url' => 'auditee/desk_evaluation/', 'admin_only' => false],
    ['label' => 'RTM Pengendalian', 'url' => 'rtl/', 'admin_only' => false],
    ['label' => 'Penetapan RTL', 'url' => 'rtl/plans/', 'admin_only' => false],
    ['label' => 'Pelaksanaan RTL', 'url' => 'rtl/implementation/', 'admin_only' => false],
    ['label' => 'Monitoring RTL', 'url' => 'rtl/monitoring/', 'admin_only' => false],
    ['label' => 'PTP (Peningkatan)', 'url' => 'ptp/', 'admin_only' => false],
    ['label' => 'Laporan AMI per Unit', 'url' => 'laporan/ami/', 'admin_only' => false],
    ['label' => 'Laporan AMI Institusi', 'url' => 'laporan/ami_institusi/', 'admin_only' => false],
    ['label' => 'Laporan RTM per Unit', 'url' => 'laporan/rtm/', 'admin_only' => false],
    ['label' => 'Laporan RTL per Unit', 'url' => 'laporan/rtl/', 'admin_only' => false],
    ['label' => 'Laporan PTP per Unit', 'url' => 'laporan/ptp/', 'admin_only' => false],
    ['label' => 'Profil Saya', 'url' => 'profile/', 'admin_only' => false],
];

foreach ($menus as $menu) {
    if ($menu['admin_only'] && !Auth::canManage()) {
        continue;
    }
    if (stripos($menu['label'], $q) !== false) {
        $results[] = ['category' => 'Menu', 'icon' => 'bi-list', 'label' => $menu['label'], 'sub' => 'Menu Navigasi', 'url' => BASE_URL . $menu['url']];
    }
}

/*
|--------------------------------------------------------------------------
| Standar
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("SELECT id, code, name FROM standards WHERE is_active = 1 AND (code LIKE ? OR name LIKE ?) LIMIT 5");
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $row) {
    $results[] = ['category' => 'Standar', 'icon' => 'bi-journal-check', 'label' => $row['code'] . ' - ' . $row['name'], 'sub' => 'Master Standar', 'url' => BASE_URL . 'master/standards/'];
}

/*
|--------------------------------------------------------------------------
| Indikator
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("SELECT id, item_code, indicator FROM audit_indicators WHERE status = 1 AND (item_code LIKE ? OR indicator LIKE ?) LIMIT 5");
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $row) {
    $results[] = ['category' => 'Indikator', 'icon' => 'bi-bar-chart', 'label' => $row['item_code'] . ' - ' . $row['indicator'], 'sub' => 'Master Indikator', 'url' => BASE_URL . 'master/indicators/'];
}

/*
|--------------------------------------------------------------------------
| Unit Kerja
|--------------------------------------------------------------------------
*/

if (Auth::canManage()) {

    $stmt = $conn->prepare("SELECT id, code, name FROM units WHERE status = 1 AND (code LIKE ? OR name LIKE ?) LIMIT 5");
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $row) {
        $results[] = ['category' => 'Unit Kerja', 'icon' => 'bi-building', 'label' => $row['code'] . ' - ' . $row['name'], 'sub' => 'Master Unit Kerja', 'url' => BASE_URL . 'master/unit/'];
    }

    /*
    |--------------------------------------------------------------------------
    | Auditor
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE role_id = 3 AND status = 1 AND full_name LIKE ? LIMIT 5");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $row) {
        $results[] = ['category' => 'Auditor', 'icon' => 'bi-person-badge', 'label' => $row['full_name'], 'sub' => 'Data Auditor', 'url' => BASE_URL . 'master/auditor/'];
    }

    /*
    |--------------------------------------------------------------------------
    | Penugasan Audit
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("SELECT id, assignment_number FROM audit_assignments WHERE assignment_number LIKE ? LIMIT 5");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $row) {
        $results[] = ['category' => 'Penugasan Audit', 'icon' => 'bi-clipboard-check', 'label' => $row['assignment_number'], 'sub' => 'Penugasan Audit', 'url' => BASE_URL . 'audit/workspace/?assignment_id=' . $row['id']];
    }

} elseif (Auth::isAuditee()) {

    $myUnitId = (int) ($_SESSION['unit_id'] ?? 0);

    $stmt = $conn->prepare("SELECT id, assignment_number FROM audit_assignments WHERE auditee_id = ? AND assignment_number LIKE ? LIMIT 5");
    $stmt->bind_param("is", $myUnitId, $like);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $row) {
        $results[] = ['category' => 'Penugasan Audit', 'icon' => 'bi-clipboard-check', 'label' => $row['assignment_number'], 'sub' => 'Penugasan Audit', 'url' => BASE_URL . 'auditee/desk_evaluation/?assignment_id=' . $row['id']];
    }
}

echo json_encode(['success' => true, 'data' => $results]);