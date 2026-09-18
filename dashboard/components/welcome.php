<?php

$hour = (int) date('H');

if ($hour < 11) {
    $greeting = 'Selamat Pagi';
} elseif ($hour < 15) {
    $greeting = 'Selamat Siang';
} elseif ($hour < 18) {
    $greeting = 'Selamat Sore';
} else {
    $greeting = 'Selamat Malam';
}

$bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

$activePeriod = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT period_name FROM audit_periods WHERE status = 'Aktif' LIMIT 1")
);

$ikuStats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        SUM(CASE WHEN indicator_type = 'IKU Wajib' THEN 1 ELSE 0 END) AS iku_wajib,
        SUM(CASE WHEN indicator_type = 'IKU Pilihan' THEN 1 ELSE 0 END) AS iku_pilihan,
        SUM(CASE WHEN indicator_type = 'IKU PT' THEN 1 ELSE 0 END) AS iku_pt,
        SUM(CASE WHEN indicator_type = 'IKT' THEN 1 ELSE 0 END) AS ikt
    FROM audit_indicators
    WHERE status = 1
"));

?>

<div class="greeting-card dashboard-greeting-wide mb-3">

    <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Admin" class="dashboard-hero-img">

    <div class="d-flex justify-content-between align-items-start flex-wrap">

        <div>

            <div class="greeting-date">
                <i class="bi bi-calendar3"></i>
                <?= date('d') . ' ' . $bulan[(int) date('n')] . ' ' . date('Y') ?>
                <span class="dashboard-live-clock"><i class="bi bi-clock-fill"></i> <span id="liveClock"><?= date('H:i') ?></span></span>
            </div>

            <div class="indicator-summary-title">
                <?= $greeting ?>, <?= htmlspecialchars($nama) ?>!
            </div>

            <div class="indicator-summary-greeting">
                Selamat datang di SIQUA &mdash; <?= htmlspecialchars($role) ?>
                <?php if ($activePeriod): ?>
                    &middot; Periode Aktif: <strong><?= htmlspecialchars($activePeriod['period_name']) ?></strong>
                <?php endif; ?>
            </div>

        </div>

        <div class="dashboard-iku-strip">

            <div class="dashboard-iku-item">
                <div class="dashboard-iku-value"><?= (int) ($ikuStats['iku_wajib'] ?? 0) ?></div>
                <div class="dashboard-iku-label">IKU Wajib</div>
            </div>

            <div class="dashboard-iku-item">
                <div class="dashboard-iku-value"><?= (int) ($ikuStats['iku_pilihan'] ?? 0) ?></div>
                <div class="dashboard-iku-label">IKU Pilihan</div>
            </div>

            <div class="dashboard-iku-item">
                <div class="dashboard-iku-value"><?= (int) ($ikuStats['iku_pt'] ?? 0) ?></div>
                <div class="dashboard-iku-label">IKU PT</div>
            </div>

            <div class="dashboard-iku-item">
                <div class="dashboard-iku-value"><?= (int) ($ikuStats['ikt'] ?? 0) ?></div>
                <div class="dashboard-iku-label">IKT</div>
            </div>

        </div>

    </div>

</div>