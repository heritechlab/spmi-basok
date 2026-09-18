<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../layouts/app.php';

$namaUser = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Bapak/Ibu';
$role = $_SESSION['role'] ?? '-';
$jam = (int) date('H');
$sapaan = $jam < 11 ? 'Selamat Pagi' : ($jam < 15 ? 'Selamat Siang' : ($jam < 18 ? 'Selamat Sore' : 'Selamat Malam'));

$bulanIndo = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

$units = $conn->query("SELECT id, code, name FROM units WHERE type = 'Program Studi' ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

function kurCountPerUnit(mysqli $conn, string $sql, array $units): array
{
    $counts = [];
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
        $counts[(int) $row['unit_id']] = (int) $row['c'];
    }

    $result = [];
    foreach ($units as $u) {
        $result[] = [
            'code'  => $u['code'],
            'count' => $counts[(int) $u['id']] ?? 0,
        ];
    }

    return $result;
}

function kurCountGlobal(mysqli $conn, string $sql): int
{
    $row = $conn->query($sql)->fetch_assoc();
    return (int) ($row['c'] ?? 0);
}

$profilLulusanPerUnit = kurCountPerUnit($conn, "SELECT unit_id, COUNT(*) AS c FROM obe_profil_lulusan GROUP BY unit_id", $units);
$cplPerUnit = kurCountPerUnit($conn, "SELECT unit_id, COUNT(*) AS c FROM obe_cpl GROUP BY unit_id", $units);
$cpmkPerUnit = kurCountPerUnit($conn, "SELECT mk.unit_id AS unit_id, COUNT(*) AS c FROM obe_cpmk cp JOIN obe_mata_kuliah mk ON mk.id = cp.mata_kuliah_id GROUP BY mk.unit_id", $units);
$subCpmkPerUnit = kurCountPerUnit($conn, "
    SELECT mk.unit_id AS unit_id, COUNT(*) AS c
    FROM obe_sub_cpmk sc
    JOIN obe_cpmk cp ON cp.id = sc.cpmk_id
    JOIN obe_mata_kuliah mk ON mk.id = cp.mata_kuliah_id
    GROUP BY mk.unit_id
", $units);
$mataKuliahPerUnit = kurCountPerUnit($conn, "SELECT unit_id, COUNT(*) AS c FROM obe_mata_kuliah WHERE is_active = 1 GROUP BY unit_id", $units);
$bahanKajianPerUnit = kurCountPerUnit($conn, "
    SELECT mk.unit_id AS unit_id, COUNT(*) AS c
    FROM obe_bahan_kajian bk
    JOIN obe_sub_cpmk sc ON sc.id = bk.sub_cpmk_id
    JOIN obe_cpmk cp ON cp.id = sc.cpmk_id
    JOIN obe_mata_kuliah mk ON mk.id = cp.mata_kuliah_id
    GROUP BY mk.unit_id
", $units);

$totalBentukMetode = kurCountGlobal($conn, "SELECT COUNT(*) AS c FROM obe_master_bentuk_pembelajaran") + kurCountGlobal($conn, "SELECT COUNT(*) AS c FROM obe_master_metode_pembelajaran");
$totalIndikator = kurCountGlobal($conn, "SELECT COUNT(*) AS c FROM obe_indikator_penilaian");
$totalBentukPenilaian = kurCountGlobal($conn, "SELECT COUNT(*) AS c FROM obe_master_bentuk_penilaian");

$totalCpl = array_sum(array_column($cplPerUnit, 'count'));
$totalCpmk = array_sum(array_column($cpmkPerUnit, 'count'));
$totalSubCpmk = array_sum(array_column($subCpmkPerUnit, 'count'));
$totalMataKuliah = array_sum(array_column($mataKuliahPerUnit, 'count'));

$navCards = [
    [
        'label' => 'Profil Lulusan', 'desc' => 'Rumusan profil lulusan Prodi',
        'url' => BASE_URL . 'obe/profil_lulusan/', 'icon' => 'bi-person-badge-fill', 'iconBg' => '#eef4fe', 'iconColor' => '#2563eb', 'accent' => '#2563eb',
        'per_unit' => $profilLulusanPerUnit,
    ],
    [
        'label' => 'CPL', 'desc' => 'Capaian Pembelajaran Lulusan',
        'url' => BASE_URL . 'obe/cpl/', 'icon' => 'bi-award-fill', 'iconBg' => '#f1edfc', 'iconColor' => '#7c3aed', 'accent' => '#7c3aed',
        'per_unit' => $cplPerUnit,
    ],
    [
        'label' => 'CPMK', 'desc' => 'Capaian Pembelajaran Mata Kuliah',
        'url' => BASE_URL . 'obe/cpmk/', 'icon' => 'bi-diagram-3-fill', 'iconBg' => '#ecfdf9', 'iconColor' => '#0d9488', 'accent' => '#0d9488',
        'per_unit' => $cpmkPerUnit,
    ],
    [
        'label' => 'Sub-CPMK', 'desc' => 'Kemampuan Akhir Tiap Tahapan',
        'url' => BASE_URL . 'obe/sub_cpmk/', 'icon' => 'bi-diagram-2-fill', 'iconBg' => '#fef7e8', 'iconColor' => '#d97706', 'accent' => '#d97706',
        'per_unit' => $subCpmkPerUnit,
    ],
    [
        'label' => 'Struktur Mata Kuliah', 'desc' => 'Daftar MK per Semester',
        'url' => BASE_URL . 'obe/mata_kuliah/', 'icon' => 'bi-journal-bookmark-fill', 'iconBg' => '#fdecec', 'iconColor' => '#dc2626', 'accent' => '#dc2626',
        'per_unit' => $mataKuliahPerUnit,
    ],
    [
        'label' => 'Bahan Kajian', 'desc' => 'Materi/topik per Sub-CPMK',
        'url' => BASE_URL . 'obe/bahan_kajian/', 'icon' => 'bi-journal-text', 'iconBg' => '#eef2ff', 'iconColor' => '#4338ca', 'accent' => '#4338ca',
        'per_unit' => $bahanKajianPerUnit,
    ],
    [
        'label' => 'Bentuk & Metode Pembelajaran', 'desc' => 'Master Bentuk dan Metode (global)',
        'url' => BASE_URL . 'obe/bentuk_metode_pembelajaran/', 'icon' => 'bi-easel2-fill', 'iconBg' => '#ecfeff', 'iconColor' => '#0891b2', 'accent' => '#0891b2',
        'per_unit' => null, 'total' => $totalBentukMetode,
    ],
    [
        'label' => 'Indikator & Kriteria Penilaian', 'desc' => 'Master Indikator dan Rubrik (global)',
        'url' => BASE_URL . 'obe/indikator_penilaian/', 'icon' => 'bi-list-check', 'iconBg' => '#fdf2f8', 'iconColor' => '#be185d', 'accent' => '#be185d',
        'per_unit' => null, 'total' => $totalIndikator,
    ],
    [
        'label' => 'Bentuk Penilaian', 'desc' => 'Master Bentuk Penilaian (global)',
        'url' => BASE_URL . 'obe/bentuk_penilaian/', 'icon' => 'bi-clipboard-check-fill', 'iconBg' => '#f7fee7', 'iconColor' => '#65a30d', 'accent' => '#65a30d',
        'per_unit' => null, 'total' => $totalBentukPenilaian,
    ],
];

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    #kurDashPage { font-size: 13px; }
    #kurDashPage .section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #a39fb5; margin-bottom: 10px; }
    .kur-quick-actions { display: flex; gap: 10px; margin-top: 16px; }
    .kur-quick-btn {
        display: inline-flex; align-items: center; gap: 7px;
        background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.18);
        border-radius: 10px; padding: 8px 16px; font-size: 12px; font-weight: 600; color: #fff;
        text-decoration: none; transition: all .15s;
    }
    .kur-quick-btn:hover { background: rgba(255,255,255,0.18); border-color: rgba(255,255,255,0.3); color: #fff; text-decoration: none; transform: translateY(-1px); }
    .kur-quick-btn i { font-size: 13px; }
    #kurNavRow { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    #kurDashPage .nav-card {
        background: #fff; border: 1px solid #f0eef7; border-radius: 14px; padding: 14px 16px 12px;
        text-decoration: none; display: block; color: inherit;
        transition: all .2s cubic-bezier(.2,.8,.2,1);
        position: relative; overflow: hidden;
        box-shadow: 0 1px 2px rgba(20,17,43,0.04);
    }
    #kurDashPage .nav-card::before {
        content: ""; position: absolute; top: 0; left: 0; right: 0; height: 3px;
        background: var(--card-accent, #7c3aed);
    }
    #kurDashPage .nav-watermark {
        position: absolute; bottom: -14px; right: -10px; font-size: 78px; line-height: 1;
        color: var(--card-accent, #7c3aed); opacity: 0.05; transform: rotate(-8deg);
        pointer-events: none; transition: opacity .2s, transform .2s;
    }
    #kurDashPage .nav-card:hover .nav-watermark { opacity: 0.09; transform: rotate(-4deg) scale(1.05); }
    #kurDashPage .nav-footer {
        display: flex; align-items: center; gap: 4px; margin-top: 8px; padding-top: 8px;
        border-top: 1px solid #f3f1f9; font-size: 9.5px; font-weight: 700; color: var(--card-accent, #7c3aed);
        opacity: 0; transform: translateY(2px); transition: all .2s;
    }
    #kurDashPage .nav-card:hover .nav-footer { opacity: 1; transform: translateY(0); }
    #kurDashPage .nav-card:hover {
        box-shadow: 0 16px 32px -8px rgba(76,29,149,0.18), 0 4px 10px rgba(20,17,43,0.06);
        transform: translateY(-3px);
        border-color: #e4defa;
        text-decoration: none; color: inherit;
    }
    #kurDashPage .nav-arrow {
        position: absolute; top: 20px; right: 20px; font-size: 13px; color: #d8d4e8;
        opacity: 0; transform: translate(-4px, 4px); transition: all .2s;
    }
    #kurDashPage .nav-card:hover .nav-arrow { opacity: 1; transform: translate(0,0); color: #7c3aed; }

    #kurDashPage .nav-top { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
    #kurDashPage .nav-icon {
        width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 15px; flex-shrink: 0;
        box-shadow: inset 0 0 0 1px rgba(0,0,0,0.03);
    }
    #kurDashPage .nav-label { font-size: 12.5px; font-weight: 800; color: #14112b; line-height: 1.25; letter-spacing: -.1px; }
    #kurDashPage .nav-desc { font-size: 10px; color: #9490a8; margin-bottom: 8px; line-height: 1.35; }

    #kurDashPage .nav-total { font-size: 22px; font-weight: 800; color: #14112b; letter-spacing: -.5px; margin-bottom: 1px; }
    #kurDashPage .nav-total-label { font-size: 9px; color: #a39fb5; text-transform: uppercase; letter-spacing: .4px; font-weight: 700; }

    #kurDashPage .nav-chip-row { display: flex; flex-wrap: wrap; gap: 5px; padding-top: 8px; border-top: 1px solid #f3f1f9; }
    #kurDashPage .nav-chip {
        display: inline-flex; align-items: center; gap: 5px;
        background: #faf9fd; border: 1px solid #f0eef7; border-radius: 8px; padding: 4px 9px 4px 7px;
        font-size: 10px; color: #6b6785; font-weight: 600; white-space: nowrap;
    }
    #kurDashPage .nav-chip .chip-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
    #kurDashPage .nav-chip strong { color: #14112b; font-weight: 800; margin-left: 2px; }
    #kurDashPage .nav-unit-empty { font-size: 10px; color: #c4c0d4; font-style: italic; padding-top: 12px; border-top: 1px solid #f3f1f9; }

    @media (max-width: 1200px) {
        #kurNavRow { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        #kurNavRow { grid-template-columns: repeat(1, 1fr); }
    }
</style>

<div class="container-fluid py-4" id="kurDashPage">

    <div class="greeting-card dashboard-greeting-wide mb-3">

        <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Admin" class="dashboard-hero-img">

        <div class="d-flex justify-content-between align-items-start flex-wrap">

            <div>

                <div class="greeting-date">
                    <i class="bi bi-calendar3"></i>
                    <?= date('d') . ' ' . $bulanIndo[(int) date('n')] . ' ' . date('Y') ?>
                    <span class="dashboard-live-clock"><i class="bi bi-clock-fill"></i> <span id="liveClock"><?= date('H:i') ?></span></span>
                </div>

                <div class="indicator-summary-title">
                    <?= $sapaan ?>, <?= htmlspecialchars($namaUser) ?>!
                </div>

                <div class="indicator-summary-greeting">
                    Peta Kurikulum &mdash; <?= htmlspecialchars($role) ?>
                </div>

                <div class="kur-quick-actions">
                    <a href="<?= BASE_URL ?>obe/kurikulum/" class="kur-quick-btn">
                        <i class="bi bi-calendar-range"></i> Kelola Kurikulum
                    </a>
                    <a href="<?= BASE_URL ?>obe/periode_akademik/" class="kur-quick-btn">
                        <i class="bi bi-clock-history"></i> Periode Akademik
                    </a>
                </div>

            </div>

            <div class="dashboard-iku-strip">

                <div class="dashboard-iku-item">
                    <div class="dashboard-iku-value"><?= $totalCpl ?></div>
                    <div class="dashboard-iku-label">CPL</div>
                </div>

                <div class="dashboard-iku-item">
                    <div class="dashboard-iku-value"><?= $totalCpmk ?></div>
                    <div class="dashboard-iku-label">CPMK</div>
                </div>

                <div class="dashboard-iku-item">
                    <div class="dashboard-iku-value"><?= $totalSubCpmk ?></div>
                    <div class="dashboard-iku-label">Sub-CPMK</div>
                </div>

                <div class="dashboard-iku-item">
                    <div class="dashboard-iku-value"><?= $totalMataKuliah ?></div>
                    <div class="dashboard-iku-label">Mata Kuliah</div>
                </div>

            </div>

        </div>

    </div>

    <div class="section-label">Komponen Kurikulum</div>
    <div id="kurNavRow">
        <?php foreach ($navCards as $card): ?>
        <a href="<?= $card['url'] ?>" class="nav-card" style="--card-accent: <?= $card['accent'] ?>;">
            <i class="bi <?= $card['icon'] ?> nav-watermark"></i>
            <i class="bi bi-arrow-up-right nav-arrow"></i>
            <div class="nav-top">
                <div class="nav-icon" style="background:<?= $card['iconBg'] ?>; color:<?= $card['iconColor'] ?>;">
                    <i class="bi <?= $card['icon'] ?>"></i>
                </div>
                <div>
                    <div class="nav-label"><?= htmlspecialchars($card['label']) ?></div>
                </div>
            </div>
            <div class="nav-desc"><?= htmlspecialchars($card['desc']) ?></div>

            <?php if ($card['per_unit'] === null): ?>
                <div class="nav-total"><?= $card['total'] ?></div>
                <div class="nav-total-label">Total Data</div>
            <?php elseif (empty($card['per_unit'])): ?>
                <div class="nav-unit-empty">Belum ada Program Studi.</div>
            <?php else: ?>
                <div class="nav-chip-row">
                    <?php foreach ($card['per_unit'] as $u): ?>
                        <span class="nav-chip"><span class="chip-dot" style="background:<?= $card['iconColor'] ?>;"></span><?= htmlspecialchars($u['code']) ?><strong><?= $u['count'] ?></strong></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="nav-footer">Lihat Detail <i class="bi bi-chevron-right" style="font-size:8px;"></i></div>
        </a>
        <?php endforeach; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>