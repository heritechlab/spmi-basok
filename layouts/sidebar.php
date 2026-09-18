<?php

$current = $_SERVER['REQUEST_URI'] ?? '';

function menuActive(string $keyword): string
{
    global $current;

    return str_contains($current, $keyword)
        ? 'active'
        : '';
}

function groupActive(array $keywords): bool
{
    global $current;

    foreach ($keywords as $kw) {
        if (str_contains($current, $kw)) {
            return true;
        }
    }

    return false;
}
?>
<?php require_once __DIR__ . '/../core/Auth.php'; ?>
<aside class="sidebar">

    <!-- ==========================================
         HEADER
    =========================================== -->

<?php
        $sidebarLogo = null;

        if (isset($conn) && $conn instanceof mysqli) {
            $qLogo = mysqli_query($conn, "SELECT logo FROM institution_profile LIMIT 1");
            if ($qLogo && $rowLogo = mysqli_fetch_assoc($qLogo)) {
                $sidebarLogo = $rowLogo['logo'] ?? null;
            }
        }
    ?>

    <div class="sidebar-header">

        <div class="sidebar-header-top">

            <img src="<?= BASE_URL ?>assets/img/brand/app-name.png" alt="eSPMI BASOK" class="sidebar-brand-logo">

        </div>

    </div>

    <!-- ==========================================
         MENU
    =========================================== -->

    <nav class="sidebar-menu">

        <?php if (Auth::isDosen() || Auth::isMahasiswa()): ?>

        <a href="<?= BASE_URL ?>dashboard/"
           class="menu-single <?= str_contains($current, '/dashboard') ? 'active' : '' ?>">
            <span class="menu-icon-badge"><i class="bi bi-speedometer2"></i></span>
            Dashboard
        </a>

        <?php if (Auth::isDosen()): ?>
        <div class="menu-divider"><span>Menu Dosen</span></div>
        <a href="<?= BASE_URL ?>obe/rps/" class="menu-single <?= str_contains($current, '/obe/rps') ? 'active' : '' ?>">
            <span class="menu-icon-badge"><i class="bi bi-journal-text"></i></span>
            RPS Mata Kuliah Saya
        </a>
        <a href="<?= BASE_URL ?>obe/penilaian/" class="menu-single <?= str_contains($current, '/obe/penilaian') ? 'active' : '' ?>">
            <span class="menu-icon-badge"><i class="bi bi-pencil-square"></i></span>
            Input Nilai
        </a>
        <a href="<?= BASE_URL ?>obe/ketercapaian_mahasiswa/" class="menu-single <?= str_contains($current, '/obe/ketercapaian_mahasiswa') ? 'active' : '' ?>">
            <span class="menu-icon-badge"><i class="bi bi-people-fill"></i></span>
            Mahasiswa Bimbingan (PA)
        </a>
        <?php endif; ?>

        <?php if (Auth::isMahasiswa()):
            $mhsIdSidebar = (int) Auth::getMahasiswaId();
            $stmtMhsSidebar = $conn->prepare("
                SELECT k.unit_id, m.kurikulum_id
                FROM obe_mahasiswa m
                JOIN obe_kurikulum k ON k.id = m.kurikulum_id
                WHERE m.id = ? LIMIT 1
            ");
            $stmtMhsSidebar->bind_param("i", $mhsIdSidebar);
            $stmtMhsSidebar->execute();
            $mhsInfoSidebar = $stmtMhsSidebar->get_result()->fetch_assoc() ?: ['unit_id' => 0, 'kurikulum_id' => 0];
        ?>
        <div class="menu-divider"><span>Menu Mahasiswa</span></div>
        <a href="<?= BASE_URL ?>obe/ketercapaian_mahasiswa/profil_capaian.php" class="menu-single <?= str_contains($current, 'profil_capaian') ? 'active' : '' ?>">
            <span class="menu-icon-badge"><i class="bi bi-person-badge-fill"></i></span>
            Profil &amp; Capaian Detail
        </a>
        <a href="<?= BASE_URL ?>obe/khs/cetak.php?mahasiswa_id=<?= $mhsIdSidebar ?>" target="_blank" class="menu-single">
            <span class="menu-icon-badge"><i class="bi bi-file-earmark-text"></i></span>
            KHS Saya
        </a>
        <a href="<?= BASE_URL ?>obe/khs/transkrip.php?mahasiswa_id=<?= $mhsIdSidebar ?>" target="_blank" class="menu-single">
            <span class="menu-icon-badge"><i class="bi bi-journal-text"></i></span>
            Transkrip Saya
        </a>
        <a href="<?= BASE_URL ?>obe/ketercapaian_mahasiswa/cetak_cpl.php?mahasiswa_id=<?= $mhsIdSidebar ?>&unit_id=<?= (int) $mhsInfoSidebar['unit_id'] ?>&kurikulum_id=<?= (int) $mhsInfoSidebar['kurikulum_id'] ?>" target="_blank" class="menu-single">
            <span class="menu-icon-badge"><i class="bi bi-bar-chart-fill"></i></span>
            Ketercapaian 5 CPL Saya
        </a>
        <a href="<?= BASE_URL ?>obe/ketercapaian_mahasiswa/cetak_cpl_per_mk.php?mahasiswa_id=<?= $mhsIdSidebar ?>&unit_id=<?= (int) $mhsInfoSidebar['unit_id'] ?>&kurikulum_id=<?= (int) $mhsInfoSidebar['kurikulum_id'] ?>" target="_blank" class="menu-single">
            <span class="menu-icon-badge"><i class="bi bi-table"></i></span>
            Ketercapaian CPL per MK Saya
        </a>
        <a href="<?= BASE_URL ?>obe/ketercapaian_mahasiswa/diskusi_saya.php" class="menu-single <?= str_contains($current, 'diskusi_saya') ? 'active' : '' ?>">
            <span class="menu-icon-badge"><i class="bi bi-chat-dots-fill"></i></span>
            Diskusi &amp; Umpan Balik CPL
        </a>
        <?php endif; ?>

        <?php else: ?>

        <a href="<?= BASE_URL ?>dashboard/"
           class="menu-single <?= str_contains($current, '/dashboard') ? 'active' : '' ?>">
            <span class="menu-icon-badge"><i class="bi bi-speedometer2"></i></span>
            Dashboard
        </a>

        <div class="menu-divider"><span>Siklus PPEPP</span></div>

        <details class="menu-group" <?= groupActive(['/master/standards', '/master/indicators']) ? 'open' : '' ?>>
            <summary>
                <span>
                    <span class="menu-icon-badge"><i class="bi bi-journal-check"></i></span>
                    Penetapan
                </span>
                <i class="bi bi-chevron-down summary-arrow"></i>
            </summary>
            <div class="submenu">
                <a href="<?= BASE_URL ?>kebijakan/" class="<?= menuActive('/kebijakan') ?>">Komitmen Mutu</a>
                <a href="<?= BASE_URL ?>manual_mutu/" class="<?= menuActive('/manual_mutu') ?>">Manual Mutu</a>
                <a href="<?= BASE_URL ?>master/standards/" class="<?= menuActive('/master/standards') ?>">Standar Mutu</a>
                <a href="<?= BASE_URL ?>master/indicators/" class="<?= menuActive('/master/indicators') ?>">Indikator Standar</a>
                <a href="<?= BASE_URL ?>sop/" class="<?= menuActive('/sop') ?>">SOP Mutu</a>
                <a href="<?= BASE_URL ?>formulir/" class="<?= menuActive('/formulir') ?>">Formulir Mutu</a>
            </div>
        </details>

        <a href="<?= BASE_URL ?>pelaksanaan/" class="menu-single <?= menuActive('/pelaksanaan') ?>">
            <span class="menu-icon-badge"><i class="bi bi-play-circle"></i></span>
            Pelaksanaan
        </a>

        <?php
            $amiKeywords = ['/master/auditor', '/master/periods', '/audit/assignments', '/audit/workspace', '/audit/findings'];
            $amiActive = groupActive($amiKeywords);

            $amiKeywordsAuditee = ['/auditee/desk_evaluation', '/audit/findings'];
            $amiActiveAuditee = groupActive($amiKeywordsAuditee);
        ?>

        <details class="menu-group" <?= ($amiActive || $amiActiveAuditee) ? 'open' : '' ?>>
            <summary>
                <span>
                    <span class="menu-icon-badge"><i class="bi bi-clipboard-check"></i></span>
                    Evaluasi
                </span>
                <i class="bi bi-chevron-down summary-arrow"></i>
            </summary>
            <div class="submenu">

                <?php if (Auth::canManage()): ?>
                    <a href="<?= BASE_URL ?>master/auditor/" class="<?= $amiActive ? 'active' : '' ?>">Audit Mutu Internal</a>
                <?php endif; ?>

                <?php if (Auth::isAuditee()): ?>
                    <a href="<?= BASE_URL ?>led_prodi/" class="<?= $amiActiveAuditee ? 'active' : '' ?>">Audit Mutu Internal (AMI)</a>
                <?php endif; ?>

               <a href="<?= BASE_URL ?>gkm/" class="<?= menuActive('/gkm') ?>">Monitoring Program Studi</a>
                <?php
                    $accPendingTotal = 0;
                    if (Auth::isAuditee()) {
                        require_once __DIR__ . '/../acc/repository.php';
                        require_once __DIR__ . '/../acc/service.php';
                        $accSidebarRepo = new AccRepository($conn);
                        $accSidebarService = new AccService($accSidebarRepo);
                        $accPendingData = $accSidebarService->getPendingCount((int) ($_SESSION['unit_id'] ?? 0))['data'];
                        $accPendingTotal = $accPendingData['total'] ?? 0;
                    }
                ?>
                <a href="<?= BASE_URL ?>acc/" class="<?= menuActive('/acc/index') ?> d-flex justify-content-between align-items-center">
                    <span class="text-truncate">Monitoring Data Akreditasi</span>
                    <?php if ($accPendingTotal > 0): ?>
                        <span class="badge bg-danger rounded-pill" style="font-size:10px !important; display:inline-block !important; width:auto !important; max-width:32px !important; flex:0 0 auto !important; margin-left:6px !important; padding:2px 7px !important;"><?= $accPendingTotal ?></span>
                    <?php endif; ?>
                </a>
                <?php
                    $ikuPendingTotal = 0;
                    if (Auth::isAuditee()) {
                        require_once __DIR__ . '/../iku/repository.php';
                        require_once __DIR__ . '/../iku/service.php';
                        $ikuSidebarRepo = new IkuRepository($conn);
                        $ikuSidebarService = new IkuService($ikuSidebarRepo);
                        $ikuTahunNow = (int) date('Y');
                        $ikuBulanNow = (int) date('n');
                        $ikuTriwulanNow = $ikuBulanNow <= 3 ? 'TW1' : ($ikuBulanNow <= 6 ? 'TW2' : ($ikuBulanNow <= 9 ? 'TW3' : 'TW4'));
                        $ikuPendingData = $ikuSidebarService->getAssignedIndicatorCount((int) ($_SESSION['unit_id'] ?? 0), $ikuTahunNow, $ikuTriwulanNow)['data'];
                        $ikuPendingTotal = $ikuPendingData['pending'] ?? 0;
                    }
                ?>
                <a href="<?= BASE_URL ?>iku/" class="<?= menuActive('/iku') ?> d-flex justify-content-between align-items-center">
                    <span class="text-truncate">IKU Diktisaintek Berdampak</span>
                    <?php if ($ikuPendingTotal > 0): ?>
                        <span class="badge bg-danger rounded-pill flex-shrink-0 ms-1" style="font-size:10px;"><?= $ikuPendingTotal ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= BASE_URL ?>capaian_kriteria/" class="<?= menuActive('/capaian_kriteria') ?>">Dashboard Capaian Kriteria</a>

            </div>
        </details>

        <?php
            $rtlKeywords = ['/rtl/plans', '/rtl/implementation', '/rtl/monitoring'];
            $rtlActive = groupActive($rtlKeywords);
            $rtmActive = groupActive(['/rtl/index']) || str_ends_with(rtrim($current, '/'), '/rtl');
        ?>

        <details class="menu-group" <?= ($rtmActive || $rtlActive) ? 'open' : '' ?>>
            <summary>
                <span>
                    <span class="menu-icon-badge"><i class="bi bi-arrow-repeat"></i></span>
                    Pengendalian
                </span>
                <i class="bi bi-chevron-down summary-arrow"></i>
            </summary>
            <div class="submenu">
                <a href="<?= BASE_URL ?>rtl/" class="<?= $rtmActive ? 'active' : '' ?>">RTM Pengendalian</a>
                <a href="<?= BASE_URL ?>rtl/plans/" class="<?= $rtlActive ? 'active' : '' ?>">Rencana Tindak Lanjut</a>
            </div>
        </details>

        <details class="menu-group" <?= groupActive(['/ptp']) ? 'open' : '' ?>>
            <summary>
                <span>
                    <span class="menu-icon-badge"><i class="bi bi-graph-up-arrow"></i></span>
                    Peningkatan
                </span>
                <i class="bi bi-chevron-down summary-arrow"></i>
            </summary>
            <div class="submenu">
                <a href="<?= BASE_URL ?>ptp/" class="<?= menuActive('/ptp') ?>">RTM Peningkatan</a>
            </div>
        </details>

    <div class="menu-divider"><span>SPMI Berbasis Risiko</span></div>
<a href="<?= BASE_URL ?>risiko/identifikasi/" class="menu-single <?= str_contains($current, 'risiko/identifikasi') ? 'active' : '' ?>">
    <span class="menu-icon-badge"><i class="bi bi-shield-exclamation"></i></span>
    Identifikasi Risiko
</a>
<a href="<?= BASE_URL ?>risiko/analisis/" class="menu-single <?= str_contains($current, 'risiko/analisis') ? 'active' : '' ?>">
    <span class="menu-icon-badge"><i class="bi bi-graph-up-arrow"></i></span>
    Analisis Risiko
</a>
<a href="<?= BASE_URL ?>risiko/mitigasi/" class="menu-single <?= str_contains($current, 'risiko/mitigasi') ? 'active' : '' ?>">
    <span class="menu-icon-badge"><i class="bi bi-clipboard2-pulse"></i></span>
    Mitigasi Risiko
</a>
<a href="<?= BASE_URL ?>risiko/prioritas/" class="menu-single <?= str_contains($current, 'risiko/prioritas') ? 'active' : '' ?>">
    <span class="menu-icon-badge"><i class="bi bi-list-ol"></i></span>
    Prioritas Audit
</a>
<a href="<?= BASE_URL ?>risiko/pascaaudit/" class="menu-single <?= str_contains($current, 'risiko/pascaaudit') ? 'active' : '' ?>">
    <span class="menu-icon-badge"><i class="bi bi-arrow-repeat"></i></span>
    Analisis Risiko Pascaaudit
</a>
<a href="<?= BASE_URL ?>risiko/laporan_audit/" class="menu-single <?= str_contains($current, 'risiko/laporan_audit') ? 'active' : '' ?>">
    <span class="menu-icon-badge"><i class="bi bi-file-earmark-text-fill"></i></span>
    Laporan Audit Berbasis Risiko
</a>

       <div class="menu-divider"><span>Siklus Kurikulum OBE</span></div>

        <a href="<?= BASE_URL ?>obe/kurikulum_dashboard/"
           class="menu-single <?= menuActive('/obe/kurikulum_dashboard') ?>">
            <span class="menu-icon-badge"><i class="bi bi-diagram-3"></i></span>
            Peta Kurikulum (OBC)
        </a>

        <a href="<?= BASE_URL ?>obe/rps/"
           class="menu-single <?= menuActive('/obe/rps') ?>">
            <span class="menu-icon-badge"><i class="bi bi-journal-text"></i></span>
            RPS (OBLT)
        </a>

        <a href="<?= BASE_URL ?>obe/dashboard_pelaporan/"
           class="menu-single <?= menuActive('/obe/dashboard_pelaporan') ?>">
            <span class="menu-icon-badge"><i class="bi bi-clipboard-data"></i></span>
            Peta Penilaian (OBAE)
        </a>
        

<div class="menu-divider"><span>Lainnya</span></div>

        <details class="menu-group" <?= groupActive(['/survey/']) ? 'open' : '' ?>>
            <summary>
                <span>
                    <span class="menu-icon-badge"><i class="bi bi-emoji-smile"></i></span>
                    Survey Kepuasan
                </span>
                <i class="bi bi-chevron-down summary-arrow"></i>
            </summary>
            <div class="submenu">
                <a href="<?= BASE_URL ?>survey/results.php?type=mahasiswa" class="<?= (str_contains($current, 'type=mahasiswa')) ? 'active' : '' ?>">Kepuasan Mahasiswa</a>
                <a href="<?= BASE_URL ?>survey/results.php?type=dosen" class="<?= (str_contains($current, 'type=dosen')) ? 'active' : '' ?>">Kepuasan Dosen</a>
                <a href="<?= BASE_URL ?>survey/results.php?type=tendik" class="<?= (str_contains($current, 'type=tendik')) ? 'active' : '' ?>">Kepuasan Tendik</a>
                <a href="<?= BASE_URL ?>survey/results.php?type=mitra" class="<?= (str_contains($current, 'type=mitra')) ? 'active' : '' ?>">Kepuasan Mitra</a>
                <a href="<?= BASE_URL ?>survey/results.php?type=pengguna" class="<?= (str_contains($current, 'type=pengguna')) ? 'active' : '' ?>">Kepuasan Pengguna Lulusan</a>
                <a href="<?= BASE_URL ?>survey/results.php?type=alumni" class="<?= (str_contains($current, 'type=alumni')) ? 'active' : '' ?>">Kepuasan Alumni</a>
                <?php if (Auth::canManage()): ?>
                    <a href="<?= BASE_URL ?>survey/manage/" class="<?= menuActive('/survey/manage') ?>">Kelola Survey Kepuasan</a>
                <?php endif; ?>
            </div>
        </details>

        <details class="menu-group" <?= groupActive(['/laporan']) ? 'open' : '' ?>>
            <summary>
                <span>
                    <span class="menu-icon-badge"><i class="bi bi-bar-chart-line"></i></span>
                    Laporan
                </span>
                <i class="bi bi-chevron-down summary-arrow"></i>
            </summary>
            <div class="submenu">
                <a href="<?= BASE_URL ?>laporan/rtm/" class="<?= menuActive('/laporan/rtm') ?>">Laporan RTM Pengendalian</a>
                <a href="<?= BASE_URL ?>laporan/ptp/" class="<?= menuActive('/laporan/ptp') ?>">Laporan RTM Peningkatan</a>
                <a href="<?= BASE_URL ?>laporan/rtl/" class="<?= menuActive('/laporan/rtl') ?>">Laporan RTL</a>
                <a href="<?= BASE_URL ?>laporan/ami/" class="<?= menuActive('/laporan/ami') ?>">Laporan AMI per Unit</a>
                <a href="<?= BASE_URL ?>laporan/ami_institusi/" class="<?= menuActive('/laporan/ami_institusi') ?>">Laporan AMI Institusi</a>
                <?php if (Auth::canManage() || Auth::isAuditee()): ?>
                    <a href="<?= BASE_URL ?>laporan/led/" class="<?= menuActive('/laporan/led') ?>">Laporan LED</a>
                <?php endif; ?>
            </div>
        </details>

        <details class="menu-group" <?= groupActive(['/master/unit', '/master/auditor', '/master/institution', '/obe/profil_prodi', '/obe/dosen', '/obe/mahasiswa']) ? 'open' : '' ?>>
            <summary>
                <span>
                    <span class="menu-icon-badge"><i class="bi bi-folder2-open"></i></span>
                    Data Pendukung
                </span>
                <i class="bi bi-chevron-down summary-arrow"></i>
            </summary>
            <div class="submenu">
                <a href="<?= BASE_URL ?>master/unit/" class="<?= menuActive('/master/unit') ?>">Daftar Unit Kerja</a>
                <a href="<?= BASE_URL ?>master/auditor/" class="<?= menuActive('/master/auditor') ?>">Daftar Auditor</a>
                <a href="<?= BASE_URL ?>master/institution/" class="<?= menuActive('/master/institution') ?>">Profil Institusi</a>
                <a href="<?= BASE_URL ?>obe/profil_prodi/" class="<?= menuActive('/obe/profil_prodi') ?>">Profil Program Studi</a>
                <a href="<?= BASE_URL ?>obe/dosen/" class="<?= menuActive('/obe/dosen') ?>">Master Dosen Pengampu</a>
                <a href="<?= BASE_URL ?>obe/mahasiswa/" class="<?= menuActive('/obe/mahasiswa') ?>">Master Mahasiswa</a>
                <a href="<?= BASE_URL ?>obe/generate_akun/" class="<?= menuActive('/obe/generate_akun') ?>">Generate Akun Dosen &amp; Mahasiswa</a>
                <a href="<?= BASE_URL ?>manajemen_akun/" class="<?= menuActive('/manajemen_akun') ?>">Tambah Peran ke Akun</a>
                <a href="<?= BASE_URL ?>pengaturan_survey/" class="<?= menuActive('/pengaturan_survey') ?>">Pengaturan Survey Gate</a>
            </div>
        </details>

        <details class="menu-group" <?= groupActive(['/users', '/access', '/settings']) ? 'open' : '' ?>>
            <summary>
                <span>
                    <span class="menu-icon-badge"><i class="bi bi-gear"></i></span>
                    Pengaturan
                </span>
                <i class="bi bi-chevron-down summary-arrow"></i>
            </summary>
            <div class="submenu">

                <?php if (Auth::canManage()): ?>
                    <a href="<?= BASE_URL ?>users/" class="<?= menuActive('/users') ?>">Manajemen User</a>
                    <a href="<?= BASE_URL ?>access/" class="<?= menuActive('/access') ?>">Hak Akses</a>
                    <a href="<?= BASE_URL ?>settings/" class="<?= menuActive('/settings') ?>">Pengaturan Sistem</a>
                <?php endif; ?>

                <a href="<?= BASE_URL ?>auth/logout.php" class="menu-logout">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>

            </div>
        </details>

        <?php endif; ?>

</nav>

    <div class="sidebar-footer">
        <div class="sidebar-version"><?= APP_NAME ?> v<?= defined('APP_VERSION') ? APP_VERSION : '1.0' ?></div>
        <div class="sidebar-copyright">&copy; <?= date('Y') ?> Basok Buhari</div>
    </div>

</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const groups = document.querySelectorAll('.sidebar-menu .menu-group');

    groups.forEach(function (group) {
        group.addEventListener('toggle', function () {
            if (group.open) {
                groups.forEach(function (other) {
                    if (other !== group) {
                        other.open = false;
                    }
                });
            }
        });
    });

    /* ==========================================
       TOGGLE SIDEBAR (HP/TABLET)
    ========================================== */

    const sidebarEl = document.querySelector('.sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');

    if (sidebarEl && toggleBtn) {

        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);

        function openSidebar() {
            sidebarEl.classList.add('show');
            overlay.classList.add('show');
        }

        function closeSidebar() {
            sidebarEl.classList.remove('show');
            overlay.classList.remove('show');
        }

        toggleBtn.addEventListener('click', function () {
            if (sidebarEl.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        overlay.addEventListener('click', closeSidebar);

        sidebarEl.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 992) {
                    closeSidebar();
                }
            });
        });

    }
});
</script>