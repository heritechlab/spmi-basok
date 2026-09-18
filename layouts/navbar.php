<?php

$roleLabels = [
    1 => 'Administrator',
    2 => 'Ketua LPM',
    3 => 'Auditor',
    4 => 'Auditee',
    5 => 'Pimpinan',
    6 => 'Dosen',
    7 => 'Mahasiswa',
];

$userName = $_SESSION['full_name'] ?? 'Pengguna';
$userRole = $roleLabels[(int)($_SESSION['role_id'] ?? 0)] ?? 'Pengguna';

$daftarPeran = [];
if (!empty($_SESSION['user_id'])) {
    $stmtPeran = $conn->prepare("
        SELECT ur.id, ur.role_id, ur.unit_id, ur.dosen_id, ur.mahasiswa_id,
               r.role_name,
               u.name AS unit_name,
               d.name AS dosen_name
        FROM user_roles ur
        JOIN roles r ON r.id = ur.role_id
        LEFT JOIN units u ON u.id = ur.unit_id
        LEFT JOIN obe_dosen d ON d.id = ur.dosen_id
        WHERE ur.user_id = ?
        ORDER BY r.id ASC
    ");
    $stmtPeran->bind_param("i", $_SESSION['user_id']);
    $stmtPeran->execute();
    $daftarPeran = $stmtPeran->get_result()->fetch_all(MYSQLI_ASSOC);
}

?>

<header class="topbar">

    <div class="topbar-left">

        <button
            class="btn btn-link p-0"
            id="sidebarToggle">

            <i class="bi bi-list fs-3"></i>

        </button>

        <?php if (!empty($sidebarLogo)): ?>
        <img src="<?= BASE_URL . htmlspecialchars($sidebarLogo) ?>" alt="Logo Institusi" class="topbar-inst-logo">
        <?php endif; ?>

    </div>

    <div class="topbar-right">

<div class="topbar-search position-relative">

            <input
                type="text"
                class="form-control"
                id="globalSearchInput"
                placeholder="Cari menu, indikator, audit..."
                autocomplete="off">

            <div id="globalSearchResults" class="global-search-results" style="display:none;"></div>

        </div>

        <div class="dropdown">

            <button class="btn btn-light position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside">

                <i class="bi bi-bell"></i>

                <span
                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                    id="notifBadge"
                    style="display:none;">

                    0

                </span>

            </button>

            <div class="dropdown-menu dropdown-menu-end p-0" style="width: 340px; max-height: 420px; overflow-y: auto;" id="notifDropdown">

                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <strong>Notifikasi</strong>
                    <a href="#" class="small" id="btnMarkAllRead">Tandai semua dibaca</a>
                </div>

                <div id="notifList">
                    <div class="text-center text-muted py-4">Memuat...</div>
                </div>

            </div>

        </div>

        <?php if (count($daftarPeran) > 1): ?>
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-arrow-repeat"></i> Ganti Peran
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header">Pilih Peran Aktif</h6></li>
                <?php foreach ($daftarPeran as $p): ?>
                    <?php
                    $labelTambahan = '';
                    if ($p['dosen_name']) $labelTambahan = ' — ' . $p['dosen_name'];
                    elseif ($p['unit_name']) $labelTambahan = ' — ' . $p['unit_name'];
                    $isActive = (int) ($_SESSION['active_user_role_id'] ?? 0) === (int) $p['id']
                        || ((int) $_SESSION['role_id'] === (int) $p['role_id'] && empty($_SESSION['active_user_role_id']));
                    ?>
                    <li>
                        <a class="dropdown-item d-flex justify-content-between align-items-center <?= $isActive ? 'active' : '' ?>"
                           href="<?= BASE_URL ?>auth/switch_role.php?user_role_id=<?= $p['id'] ?>">
                            <span><?= htmlspecialchars($p['role_name'] . $labelTambahan) ?></span>
                            <?php if ($isActive): ?><i class="bi bi-check-lg"></i><?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="dropdown">

            <button
                class="btn btn-light dropdown-toggle"
                data-bs-toggle="dropdown">

                <i class="bi bi-person-circle"></i>

                <?= htmlspecialchars($userName) ?>

            </button>

            <ul class="dropdown-menu dropdown-menu-end">

                <li>

                    <h6 class="dropdown-header">

                        <?= htmlspecialchars($userRole) ?>

                    </h6>

                </li>

<li>

                    <a
                        class="dropdown-item"
                        href="<?= BASE_URL ?>profile/">

                        <i class="bi bi-person"></i>

                        Profil

                    </a>

                </li>

                <li>

                    <a
                        class="dropdown-item"
                        href="<?= BASE_URL ?>profile/#change-password">

                        <i class="bi bi-key"></i>

                        Ubah Password

                    </a>

                </li>

                <li><hr class="dropdown-divider"></li>

                <li>

                    <a
                        class="dropdown-item text-danger"
                        href="<?= BASE_URL ?>auth/logout.php">

                        <i class="bi bi-box-arrow-right"></i>

                        Logout

                    </a>

                </li>

            </ul>

        </div>

    </div>

</header>