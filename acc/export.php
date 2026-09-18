<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$repository = new AccRepository($conn);
$service    = new AccService($repository);

$code = trim($_GET['code'] ?? '');
$unitId = (int) ($_GET['unit_id'] ?? 0);

if (Auth::isAuditee()) {
    $unitId = (int) ($_SESSION['unit_id'] ?? 0);
}

$result = $service->getTableWithData($code, $unitId);

if (!$result['success']) {
    die($result['message']);
}

$data = $result['data'];
$table = $data['table'];
$columns = $data['columns'];
$rows = $data['rows'];
$total = $data['total'];

$hasFootnoteCode = false;
foreach ($columns as $col) {
    if (!empty($col['footnote_code'])) {
        $hasFootnoteCode = true;
        break;
    }
}

$groups = [];
$lastGroup = null;
foreach ($columns as $idx => $col) {
    if ($idx === 0 || $col['group_label'] !== $lastGroup) {
        $groups[] = ['label' => $col['group_label'], 'count' => 1];
        $lastGroup = $col['group_label'];
    } else {
        $groups[count($groups) - 1]['count']++;
    }
}

$unitName = '';
if ($unitId > 0) {
    foreach ($service->getProdiUnits()['data'] as $u) {
        if ((int) $u['id'] === $unitId) {
            $unitName = $u['name'];
            break;
        }
    }
}

$filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $table['code']) . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

echo "\xEF\xBB\xBF"; // BOM supaya karakter Indonesia tampil benar di Excel

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
    table { border-collapse: collapse; }
    th, td { border: 1px solid #000; padding: 4px 8px; font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
    th { background: #D9D9D9; font-weight: bold; text-align: center; }
    .title { font-weight: bold; font-size: 12pt; }
    .desc { font-size: 9pt; color: #444; }
</style>
</head>
<body>

<table>
    <tr><td colspan="<?= count($columns) + 1 ?>" class="title"><?= htmlspecialchars($table['title']) ?></td></tr>
    <?php if ($unitName): ?>
        <tr><td colspan="<?= count($columns) + 1 ?>">Program Studi: <?= htmlspecialchars($unitName) ?></td></tr>
    <?php endif; ?>
    <tr><td colspan="<?= count($columns) + 1 ?>">&nbsp;</td></tr>
</table>

<table>
    <thead>
        <tr>
            <th rowspan="2">Tahun Akademik</th>
            <?php foreach ($groups as $g): ?>
                <?php if ($g['label']): ?>
                    <th colspan="<?= $g['count'] ?>"><?= htmlspecialchars($g['label']) ?></th>
                <?php else: ?>
                    <?php for ($i = 0; $i < $g['count']; $i++): ?>
                        <th rowspan="2"></th>
                    <?php endfor; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </tr>
        <tr>
            <?php foreach ($columns as $col): ?>
                <?php if ($col['group_label']): ?>
                    <th><?= htmlspecialchars($col['label']) ?></th>
                <?php endif; ?>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['label']) ?></td>
                <?php foreach ($columns as $col): ?>
                    <td align="center"><?= $row['cells'][$col['id']] !== null ? htmlspecialchars((string) $row['cells'][$col['id']]) : '' ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>

        <tr>
            <th>Jumlah</th>
            <?php foreach ($columns as $col): ?>
                <td align="center"><b><?= $total[$col['id']] !== null ? htmlspecialchars((string) $total[$col['id']]) : '-' ?></b></td>
            <?php endforeach; ?>
        </tr>

        <?php if ($hasFootnoteCode): ?>
        <tr>
            <td>Kode</td>
            <?php foreach ($columns as $col): ?>
                <td align="center"><?= !empty($col['footnote_code']) ? htmlspecialchars($col['footnote_code']) : '-' ?></td>
            <?php endforeach; ?>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if (!empty($table['description'])): ?>
<table style="margin-top:10px;">
    <tr><td class="desc"><b>Keterangan:</b> <?= htmlspecialchars($table['description']) ?></td></tr>
</table>
<?php endif; ?>

</body>
</html>