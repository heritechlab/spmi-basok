<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/guard.php';

/*
|--------------------------------------------------------------------------
| Ambil data unit milik Auditee yang login
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT u.id, u.code, u.name, u.head_name
    FROM users us
    LEFT JOIN units u ON u.id = us.unit_id
    WHERE us.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$unit = $stmt->get_result()->fetch_assoc();

$unitId = (int)($unit['id'] ?? 0);

/*
|--------------------------------------------------------------------------
| Ambil daftar penugasan untuk unit ini
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        a.id, a.assignment_number, a.audit_type, a.audit_date, a.status,
        a.approved, a.approved_at,
        p.period_name,
        us.full_name AS lead_auditor_name
    FROM audit_assignments a
    LEFT JOIN audit_periods p ON p.id = a.period_id
    LEFT JOIN users us ON us.id = a.lead_auditor
    WHERE a.auditee_id = ?
    ORDER BY a.audit_date DESC
");
$stmt->bind_param("i", $unitId);
$stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../layouts/app.php';

?>

<div class="container-fluid py-4">

    <h4 class="mb-3">Beranda Auditee</h4>

<div class="dual-card-row mb-3">

        <div class="greeting-card">

            <?php
                $hour = (int) date('H');
                if ($hour < 11) { $greeting = 'Selamat Pagi'; }
                elseif ($hour < 15) { $greeting = 'Selamat Siang'; }
                elseif ($hour < 18) { $greeting = 'Selamat Sore'; }
                else { $greeting = 'Selamat Malam'; }

                $displayName = $unit['head_name'] ?? $unit['name'] ?? 'Auditee';
            ?>

            <div class="greeting-date">
                <i class="bi bi-calendar3"></i>
                <?php
                    $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                    echo date('d') . ' ' . $bulan[(int) date('n')] . ' ' . date('Y');
                ?>
            </div>

            <div class="indicator-summary-title">
                <?= $greeting ?>, <?= htmlspecialchars($displayName) ?>!
            </div>

            <div class="indicator-summary-greeting">
                Berikut daftar penugasan audit untuk <?= htmlspecialchars($unit['name'] ?? 'unit Anda') ?>.
            </div>

            <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Auditee" class="standard-summary-hero">

        </div>

        <div class="info-card profile-info-card">

            <div class="info-card-header">
                <i class="bi bi-person-badge"></i>
                Profil Saya
            </div>

            <div class="info-card-body">

                <div class="profile-card-top">

                    <div class="profile-card-avatar-circle">
                        <i class="bi bi-person-fill"></i>
                    </div>

                    <div class="profile-card-name-block">
                        <div class="profile-card-name"><?= htmlspecialchars($displayName) ?></div>
                        <div class="profile-card-sub"><?= htmlspecialchars($unit['name'] ?? '-') ?></div>
                        <div class="profile-card-sub"><?= htmlspecialchars($institutionName ?? 'STIKES Harapan Ibu Jambi') ?></div>
                        <span class="status-pill-green mt-1"><i class="bi bi-check-circle-fill"></i> Auditee Aktif</span>
                    </div>

                </div>

                <div class="profile-stat-row">

                    <div class="profile-stat-item">
                        <div class="profile-stat-label">Total<br>Penugasan</div>
                        <div class="profile-stat-value"><?= count($assignments) ?></div>
                    </div>

                    <div class="profile-stat-item">
                        <div class="profile-stat-label">Sudah<br>Disetujui</div>
                        <div class="profile-stat-value"><?= count(array_filter($assignments, fn($a) => (int)$a['approved'] === 1)) ?></div>
                    </div>

                    <div class="profile-stat-item">
                        <div class="profile-stat-label">Menunggu<br>Persetujuan</div>
                        <div class="profile-stat-value"><?= count(array_filter($assignments, fn($a) => (int)$a['approved'] === 0)) ?></div>
                    </div>

                </div>

            </div>

</div>

    </div>

    <div class="card shadow-sm">
        <div class="card-header"><strong>Daftar Penugasan Audit</strong></div>
        <div class="card-body">

            <?php if (empty($assignments)): ?>

                <p class="text-muted mb-0">Belum ada penugasan audit untuk unit Anda.</p>

            <?php else: ?>

                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>No. Penugasan</th>
                            <th>Periode</th>
                            <th>Ketua Tim Audit</th>
                            <th>Tanggal</th>
                            <th>Persetujuan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['assignment_number']) ?></td>
                                <td><?= htmlspecialchars($a['period_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($a['lead_auditor_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($a['audit_date'] ?? '-') ?></td>
                                <td>
                                    <?php if ((int)$a['approved'] === 1): ?>
                                        <span class="badge bg-success">Disetujui</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Menunggu Persetujuan</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($a['status']) ?></td>
                                <td>
                                    <?php if ((int)$a['approved'] === 0): ?>
                                        <button class="btn btn-success btn-sm btn-approve" data-id="<?= $a['id'] ?>">
                                            <i class="bi bi-check-circle"></i> Setujui
                                        </button>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>auditee/desk_evaluation/?assignment_id=<?= $a['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-file-earmark-text"></i> Desk Evaluation
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<script>
    $(document).on("click", ".btn-approve", function () {
        const id = $(this).data("id");

        Swal.fire({
            icon: "question",
            title: "Setujui penugasan audit ini?",
            showCancelButton: true,
            confirmButtonText: "Ya, Setujui",
            cancelButtonText: "Batal",
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "<?= BASE_URL ?>auditee/api.php?action=approve",
                type: "POST",
                dataType: "json",
                data: { id: id },
                success: function (response) {
                    if (response.success) {
                        Swal.fire("Berhasil", response.message, "success").then(function () {
                            location.reload();
                        });
                    } else {
                        Swal.fire("Gagal", response.message, "error");
                    }
                },
                error: function () {
                    Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
                },
            });
        });
    });
</script>