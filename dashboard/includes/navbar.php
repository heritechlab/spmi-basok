<?php
/*
|--------------------------------------------------------------------------
| SIQUA Dashboard v2.1
| File : dashboard/includes/navbar.php
|--------------------------------------------------------------------------
*/

$userName = $_SESSION['full_name'] ?? 'Administrator';
$userRole = $_SESSION['role_name'] ?? 'Administrator';

$hour = date('H');

$greeting = "TEST";

if ($hour < 11) {
    $greeting = "Selamat Pagi";
} elseif ($hour < 15) {
    $greeting = "Selamat Siang";
} elseif ($hour < 18) {
    $greeting = "Selamat Sore";
} else {
    $greeting = "Selamat Malam";
}
?>

<header class="topbar">

    <!-- KIRI -->
    <div class="topbar-left">

       <h4 class="page-title">
    <?= $pageTitle ?? 'Dashboard'; ?>
        </h4>

        <small class="page-subtitle">
            <?= $greeting ?>,
            <?= htmlspecialchars($userName) ?>
        </small>

    </div>

    <!-- TENGAH -->
    <div class="topbar-center">

        <div class="search-box">

            <i class="bi bi-search"></i>

            <input
                type="text"
                placeholder="Cari menu, standar, audit..."
            >

        </div>

    </div>

    <!-- KANAN -->
    <div class="topbar-right">

        <!-- Tanggal -->

        <div class="today-box">

            <i class="bi bi-calendar-event"></i>

            <?= date('d M Y') ?>

        </div>

        <!-- Notifikasi -->

        <button class="notification-btn">

            <i class="bi bi-bell-fill"></i>

            <span class="badge-notif">

                3

            </span>

        </button>

        <!-- Profil -->

        <details class="profile-menu">

            <summary>

                <div class="avatar">

                    <?= strtoupper(substr($userName,0,1)); ?>

                </div>

                <div class="profile-info">

                    <strong>

                        <?= htmlspecialchars($userName) ?>

                    </strong>

                    <small>

                        <?= htmlspecialchars($userRole) ?>

                    </small>

                </div>

            </summary>

            <div class="profile-dropdown">

                <a href="#">

                    <i class="bi bi-person-circle"></i>

                    Profil Saya

                </a>

                <a href="#">

                    <i class="bi bi-gear"></i>

                    Pengaturan

                </a>

                <hr>

                <a href="<?= BASE_URL ?>auth/logout.php">

                    <i class="bi bi-box-arrow-right"></i>

                    Logout

                </a>

            </div>

        </details>

    </div>

</header>