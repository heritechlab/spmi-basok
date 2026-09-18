<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../layouts/app.php';

if (!Auth::canManage()) {
    die('<div style="padding:40px; font-family:sans-serif;">Anda tidak memiliki akses ke halaman ini.</div>');
}

$assignments = $conn->query("
    SELECT a.id, a.assignment_number, a.completion_date, u.name AS unit_name, p.period_name
    FROM audit_assignments a
    JOIN units u ON u.id = a.auditee_id
    LEFT JOIN audit_periods p ON p.id = a.period_id
    WHERE a.status = 'Selesai'
    ORDER BY a.completion_date DESC
")->fetch_all(MYSQLI_ASSOC);

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    #risikoPage {
        font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Arial, sans-serif;
        background: #f7f7fa; min-height: 100vh;
        margin: -1.5rem -1.5rem -1.5rem -1.5rem; padding: 1.75rem 2rem;
    }
    #risikoPage .page-head { display: flex; align-items: center; gap: 16px; margin-bottom: 22px; }
    #risikoPage .icon-chip { width: 46px; height: 46px; border-radius: 12px; background: linear-gradient(135deg, #dc2626, #ea580c); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 20px; box-shadow: 0 6px 16px rgba(220,38,38,0.25); flex-shrink: 0; }
    #risikoPage .eyebrow { font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: #b91c1c; margin-bottom: 4px; }
    #risikoPage .page-head h1 { font-size: 22px; font-weight: 700; color: #111827; margin: 0; }
    #risikoPage .page-head p { font-size: 12.5px; color: #6b7280; margin: 4px 0 0; }
    #risikoPage .item-row {
        background: #fff; border: 1px solid #eceef1; border-radius: 12px; padding: 14px 18px;
        margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;
    }
    #risikoPage .item-title { font-size: 13px; font-weight: 700; color: #111827; }
    #risikoPage .item-sub { font-size: 10.5px; color: #6b7280; margin-top: 2px; }
</style>

<div id="risikoPage">

    <div class="page-head">
        <div class="icon-chip"><i class="bi bi-file-earmark-text-fill"></i></div>
        <div>
            <div class="eyebrow">SPMI Berbasis Risiko</div>
            <h1>Laporan Audit Mutu Internal Berbasis Risiko</h1>
            <p>Pasal 29 — pilih Penugasan Audit yang sudah Selesai untuk mencetak laporan resmi</p>
        </div>
    </div>

    <?php if (empty($assignments)): ?>
    <p class="text-muted">Belum ada Penugasan Audit berstatus Selesai.</p>
    <?php endif; ?>

    <?php foreach ($assignments as $a): ?>
    <div class="item-row">
        <div>
            <div class="item-title"><?= htmlspecialchars($a['assignment_number']) ?> — <?= htmlspecialchars($a['unit_name']) ?></div>
            <div class="item-sub"><?= htmlspecialchars($a['period_name'] ?? '-') ?> &middot; Selesai: <?= $a['completion_date'] ? date('d M Y', strtotime($a['completion_date'])) : '-' ?></div>
        </div>
        <a href="cetak.php?assignment_id=<?= $a['id'] ?>" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-printer-fill"></i> Cetak Laporan
        </a>
    </div>
    <?php endforeach; ?>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>