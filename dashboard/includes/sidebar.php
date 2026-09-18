<?php
$current = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">

    <div class="sidebar-header">

        <div class="logo-box">
            <i class="bi bi-shield-check"></i>
        </div>

        <div class="logo-text">
            <h2>SIQUA</h2>
            <small>Smart Integration Quality Assurance</small>
        </div>

    </div>

    <nav class="sidebar-menu">

        <!-- Dashboard -->

        <a href="<?= BASE_URL ?>dashboard/" class="menu-link <?= ($current=="index.php")?"active":""; ?>">

            <i class="bi bi-speedometer2"></i>

            <span>Dashboard</span>

        </a>


        <div class="menu-title">MASTER DATA</div>

        <details class="menu-group">

            <summary>

                <span>

                    <i class="bi bi-journal-bookmark"></i>

                    Standar Mutu

                </span>

            </summary>

            <div class="submenu">

                <a href="#">Standar</a>

                <a href="#">Butir Standar</a>

                <a href="#">Instrumen</a>

                <a href="#">Dokumen Wajib</a>

            </div>

        </details>


        <a href="#" class="menu-link">

            <i class="bi bi-building"></i>

            <span>Unit Kerja</span>

        </a>

        <a href="#" class="menu-link">

            <i class="bi bi-people"></i>

            <span>Pengguna</span>

        </a>


        <div class="menu-title">

            AUDIT INTERNAL

        </div>

        <details class="menu-group">

            <summary>

                <span>

                    <i class="bi bi-clipboard-check"></i>

                    Audit

                </span>

            </summary>

            <div class="submenu">

                <a href="#">Periode Audit</a>

                <a href="#">Penugasan Auditor</a>

                <a href="#">Pelaksanaan Audit</a>

                <a href="#">Temuan Audit</a>

            </div>

        </details>


        <div class="menu-title">

            TINDAK LANJUT

        </div>

        <details class="menu-group">

            <summary>

                <span>

                    <i class="bi bi-check2-square"></i>

                    RTL

                </span>

            </summary>

            <div class="submenu">

                <a href="#">Rencana RTL</a>

                <a href="#">Verifikasi RTL</a>

            </div>

        </details>


       <div class="menu-title">

    PELAPORAN

</div>

<details class="menu-group">

    <summary>

        <span>

            <i class="bi bi-bar-chart"></i>

            Laporan Audit

        </span>

    </summary>

    <div class="submenu">

        <a href="#">Ringkasan Audit</a>

        <a href="#">Hasil Audit per Unit</a>

        <a href="#">Daftar Temuan</a>

        <a href="#">Status RTL</a>

        <a href="#">Dashboard Mutu</a>

        <a href="#">Export PDF</a>

        <a href="#">Export Excel</a>

    </div>

</details>


        <div class="menu-title">

    SYSTEM

</div>

<details class="menu-group">

    <summary>

        <span>

            <i class="bi bi-gear"></i>

            Pengaturan

        </span>

    </summary>

    <div class="submenu">

        <a href="#">Profil Institusi</a>

        <a href="#">Tahun Akademik</a>

        <a href="#">Backup Database</a>

        <a href="#">Log Aktivitas</a>

    </div>

</details>

<a href="<?= BASE_URL ?>auth/logout.php" class="menu-link">

    <i class="bi bi-box-arrow-right"></i>

    <span>Logout</span>

</a>

    </nav>

</aside>