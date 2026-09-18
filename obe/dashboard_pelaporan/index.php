<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../layouts/app.php';

$namaUser = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Bapak/Ibu';
$jam = (int) date('H');
$sapaan = $jam < 11 ? 'Selamat Pagi' : ($jam < 15 ? 'Selamat Siang' : ($jam < 18 ? 'Selamat Sore' : 'Selamat Malam'));

$bulanIndo = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$role = $_SESSION['role'] ?? '-';

// Statistik ringkas
$totalProdi = (int) ($conn->query("SELECT COUNT(*) AS c FROM units WHERE type = 'Program Studi'")->fetch_assoc()['c'] ?? 0);
$totalMahasiswa = (int) ($conn->query("SELECT COUNT(*) AS c FROM obe_mahasiswa WHERE is_active = 1 AND status = 'Aktif'")->fetch_assoc()['c'] ?? 0);
$totalMkDenganRps = (int) ($conn->query("SELECT COUNT(DISTINCT mata_kuliah_id) AS c FROM obe_rps")->fetch_assoc()['c'] ?? 0);
$totalNilaiTerinput = (int) ($conn->query("SELECT COUNT(*) AS c FROM obe_penilaian")->fetch_assoc()['c'] ?? 0);

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    #dbpPage { font-size: 13px; }
    .dbp-quick-actions { display: flex; gap: 10px; margin-top: 16px; }
    .dbp-quick-btn {
        display: inline-flex; align-items: center; gap: 7px;
        background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.18);
        border-radius: 10px; padding: 8px 16px; font-size: 12px; font-weight: 600; color: #fff;
        text-decoration: none; transition: all .15s;
    }
    .dbp-quick-btn:hover { background: rgba(255,255,255,0.18); border-color: rgba(255,255,255,0.3); color: #fff; text-decoration: none; transform: translateY(-1px); }
    .dbp-quick-btn i { font-size: 13px; }
    #dbpPage .section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #a39fb5; margin-bottom: 10px; }

    #dbpStatsRow { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 26px; }
    #dbpPage .stat-card {
        background: #fff; border: 1px solid #eceaf5; border-radius: 16px; padding: 18px 20px;
        display: flex; align-items: center; gap: 14px; transition: all .18s;
    }
    #dbpPage .stat-card:hover { box-shadow: 0 10px 26px rgba(20,17,43,0.08); transform: translateY(-2px); }
    #dbpPage .stat-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    #dbpPage .stat-value { font-size: 24px; font-weight: 800; color: #14112b; line-height: 1.1; }
    #dbpPage .stat-label { font-size: 11px; color: #8a8698; font-weight: 600; margin-top: 2px; }
</style>

<div class="container-fluid py-4" id="dbpPage">

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
                    Dashboard Pelaporan OBE &mdash; <?= htmlspecialchars($role) ?>
                </div>

                <div class="dbp-quick-actions">
            <a href="<?= BASE_URL ?>obe/panduan_penilaian/" class="dbp-quick-btn">
                <i class="bi bi-signpost-2-fill"></i> Panduan Alur Perhitungan
            </a>
                </div>
            </div>

            <div class="dashboard-iku-strip">
                <div class="dashboard-iku-item">
                    <div class="dashboard-iku-value"><?= $totalProdi ?></div>
                    <div class="dashboard-iku-label">Program Studi</div>
                </div>
                <div class="dashboard-iku-item">
                    <div class="dashboard-iku-value"><?= number_format($totalMahasiswa, 0, ',', '.') ?></div>
                    <div class="dashboard-iku-label">Mahasiswa Aktif</div>
                </div>
                <div class="dashboard-iku-item">
                    <div class="dashboard-iku-value"><?= $totalMkDenganRps ?></div>
                    <div class="dashboard-iku-label">MK dengan RPS</div>
                </div>
                <div class="dashboard-iku-item">
                    <div class="dashboard-iku-value"><?= number_format($totalNilaiTerinput, 0, ',', '.') ?></div>
                    <div class="dashboard-iku-label">Data Nilai</div>
                </div>
            </div>

        </div>

    </div>

    <div class="section-label">Ringkasan Sistem</div>
    <div id="dbpStatsRow">
        <div class="stat-card">
            <div class="stat-icon" style="background:#eef4fe; color:#2563eb;"><i class="bi bi-building"></i></div>
            <div>
                <div class="stat-value"><?= $totalProdi ?></div>
                <div class="stat-label">Program Studi</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#eef2ff; color:#4f46e5;"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-value"><?= number_format($totalMahasiswa, 0, ',', '.') ?></div>
                <div class="stat-label">Mahasiswa Aktif</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#ecfdf9; color:#0d9488;"><i class="bi bi-journal-check"></i></div>
            <div>
                <div class="stat-value"><?= $totalMkDenganRps ?></div>
                <div class="stat-label">Mata Kuliah dengan RPS</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef7e8; color:#d97706;"><i class="bi bi-clipboard-data-fill"></i></div>
            <div>
                <div class="stat-value"><?= number_format($totalNilaiTerinput, 0, ',', '.') ?></div>
                <div class="stat-label">Data Nilai Terinput</div>
            </div>
        </div>
    </div>

    <div class="section-label">Menu Laporan</div>
    <?php $obeActiveCard = ''; require_once __DIR__ . '/../../layouts/obe_dashboard_cards.php'; ?>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>