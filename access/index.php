<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';

if (!Auth::isAdmin()) {
    die('Halaman ini hanya untuk Administrator.');
}

$matrix = [
    [
        'group' => 'PENETAPAN',
        'items' => [
            ['module' => 'Master Standar', 'admin' => 'Kelola', 'kalpm' => 'Kelola', 'auditor' => 'Kelola', 'auditee' => 'Lihat'],
            ['module' => 'Master Indikator', 'admin' => 'Kelola', 'kalpm' => 'Kelola', 'auditor' => 'Kelola', 'auditee' => 'Lihat'],
        ],
    ],
    [
        'group' => 'EVALUASI (AMI)',
        'items' => [
            ['module' => 'Data Auditor', 'admin' => 'Kelola', 'kalpm' => 'Kelola', 'auditor' => 'Kelola', 'auditee' => 'Lihat'],
            ['module' => 'Periode Audit', 'admin' => 'Kelola', 'kalpm' => 'Kelola', 'auditor' => 'Kelola', 'auditee' => 'Lihat'],
            ['module' => 'Penugasan Audit', 'admin' => 'Kelola', 'kalpm' => 'Kelola', 'auditor' => 'Kelola', 'auditee' => 'Lihat'],
            ['module' => 'Workspace Audit (LKA)', 'admin' => 'Kelola', 'kalpm' => 'Kelola', 'auditor' => 'Kelola (miliknya)', 'auditee' => 'Tidak Ada'],
            ['module' => 'Desk Evaluation', 'admin' => 'Lihat', 'kalpm' => 'Lihat', 'auditor' => 'Lihat', 'auditee' => 'Kelola (unitnya)'],
            ['module' => 'Temuan Audit', 'admin' => 'Lihat', 'kalpm' => 'Lihat', 'auditor' => 'Lihat', 'auditee' => 'Lihat (unitnya)'],
        ],
    ],
    [
        'group' => 'PENGENDALIAN',
        'items' => [
            ['module' => 'RTM Pengendalian', 'admin' => 'Kelola', 'kalpm' => 'Kelola', 'auditor' => 'Lihat', 'auditee' => 'Kelola (unitnya)'],
            ['module' => 'Penetapan RTL', 'admin' => 'Kelola', 'kalpm' => 'Kelola', 'auditor' => 'Lihat', 'auditee' => 'Kelola (unitnya)'],
            ['module' => 'Pelaksanaan RTL', 'admin' => 'Kelola', 'kalpm' => 'Kelola', 'auditor' => 'Lihat', 'auditee' => 'Isi Progres'],
            ['module' => 'Verifikasi Pelaksanaan RTL', 'admin' => 'Kelola', 'kalpm' => 'Kelola', 'auditor' => 'Tidak Ada', 'auditee' => 'Tidak Ada'],
            ['module' => 'Monitoring RTL', 'admin' => 'Lihat', 'kalpm' => 'Lihat', 'auditor' => 'Lihat', 'auditee' => 'Lihat'],
        ],
    ],
    [
        'group' => 'PENINGKATAN',
        'items' => [
            ['module' => 'PTP (Usulan Peningkatan)', 'admin' => 'Kelola', 'kalpm' => 'Kelola', 'auditor' => 'Tidak Ada', 'auditee' => 'Kelola (unitnya)'],
            ['module' => 'Terapkan ke Master Indikator', 'admin' => 'Kelola', 'kalpm' => 'Kelola', 'auditor' => 'Tidak Ada', 'auditee' => 'Tidak Ada'],
        ],
    ],
    [
        'group' => 'LAPORAN',
        'items' => [
            ['module' => 'Laporan AMI (Unit/Institusi)', 'admin' => 'Lihat/Cetak', 'kalpm' => 'Lihat/Cetak', 'auditor' => 'Lihat/Cetak', 'auditee' => 'Lihat/Cetak'],
            ['module' => 'Laporan RTM, RTL, PTP', 'admin' => 'Lihat/Cetak', 'kalpm' => 'Lihat/Cetak', 'auditor' => 'Lihat/Cetak', 'auditee' => 'Lihat/Cetak'],
        ],
    ],
    [
        'group' => 'SYSTEM',
        'items' => [
            ['module' => 'Manajemen User', 'admin' => 'Kelola', 'kalpm' => 'Tidak Ada', 'auditor' => 'Tidak Ada', 'auditee' => 'Tidak Ada'],
            ['module' => 'Hak Akses', 'admin' => 'Kelola', 'kalpm' => 'Tidak Ada', 'auditor' => 'Tidak Ada', 'auditee' => 'Tidak Ada'],
        ],
    ],
];

require_once __DIR__ . '/../layouts/app.php';

?>

<div class="container-fluid py-4">

    <h4 class="mb-3">Hak Akses</h4>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <p class="mb-0 text-muted">
                <i class="bi bi-info-circle"></i>
                Halaman ini menampilkan ringkasan hak akses tiap peran (role) di SIQUA. Pengaturan ini
                melekat pada logika sistem — untuk mengubahnya diperlukan penyesuaian oleh pengembang.
            </p>
        </div>
    </div>

    <?php foreach ($matrix as $section): ?>

        <div class="card shadow-sm mb-3">

            <div class="card-header">
                <strong><?= htmlspecialchars($section['group']) ?></strong>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Modul</th>
                                <th width="140" class="text-center">Administrator</th>
                                <th width="140" class="text-center">Ketua LPM</th>
                                <th width="140" class="text-center">Auditor</th>
                                <th width="140" class="text-center">Auditee</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($section['items'] as $item): ?>
                                <?php
                                    $renderBadge = function (string $val): string {
                                        if ($val === 'Tidak Ada') {
                                            return '<span class="badge bg-secondary">Tidak Ada</span>';
                                        }
                                        if (str_starts_with($val, 'Kelola')) {
                                            return '<span class="badge bg-success">' . htmlspecialchars($val) . '</span>';
                                        }
                                        return '<span class="badge bg-info text-dark">' . htmlspecialchars($val) . '</span>';
                                    };
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['module']) ?></td>
                                    <td class="text-center"><?= $renderBadge($item['admin']) ?></td>
                                    <td class="text-center"><?= $renderBadge($item['kalpm']) ?></td>
                                    <td class="text-center"><?= $renderBadge($item['auditor']) ?></td>
                                    <td class="text-center"><?= $renderBadge($item['auditee']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    <?php endforeach; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <small class="text-muted">
                <strong>Keterangan:</strong>
                <span class="badge bg-success">Kelola</span> = bisa tambah/ubah/hapus data &nbsp;|&nbsp;
                <span class="badge bg-info text-dark">Lihat</span> = hanya bisa melihat, tombol ubah terkunci &nbsp;|&nbsp;
                <span class="badge bg-secondary">Tidak Ada</span> = menu tidak tersedia untuk peran ini
            </small>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>