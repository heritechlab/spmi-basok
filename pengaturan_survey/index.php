<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../layouts/app.php';

if (!Auth::canManage()) {
    die('<div style="padding:40px; font-family:sans-serif;">Anda tidak memiliki akses ke halaman ini.</div>');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['tambah'])) {
        $label = trim($_POST['label'] ?? '');
        $slug = trim($_POST['survey_slug'] ?? '');
        $url = trim($_POST['survey_url'] ?? '');

        if ($label === '' || $slug === '' || $url === '') {
            $message = '<div class="alert alert-danger small">Semua field wajib diisi.</div>';
        } else {
            $maxSort = $conn->query("SELECT COALESCE(MAX(sort_order),0)+1 AS s FROM survey_gate_settings")->fetch_assoc()['s'];
            $stmt = $conn->prepare("INSERT INTO survey_gate_settings (label, survey_slug, survey_url, enabled, sort_order) VALUES (?, ?, ?, 1, ?)");
            $stmt->bind_param("sssi", $label, $slug, $url, $maxSort);
            $stmt->execute();
            $message = '<div class="alert alert-success small">Survey baru berhasil ditambahkan.</div>';
        }
    }

    if (isset($_POST['update_id'])) {
        $id = (int) $_POST['update_id'];
        $label = trim($_POST['label'] ?? '');
        $slug = trim($_POST['survey_slug'] ?? '');
        $url = trim($_POST['survey_url'] ?? '');
        $enabled = isset($_POST['enabled']) ? 1 : 0;

        $stmt = $conn->prepare("UPDATE survey_gate_settings SET label=?, survey_slug=?, survey_url=?, enabled=? WHERE id=?");
        $stmt->bind_param("sssii", $label, $slug, $url, $enabled, $id);
        $stmt->execute();
        $message = '<div class="alert alert-success small">Survey berhasil diperbarui.</div>';
    }

    if (isset($_POST['hapus_id'])) {
        $id = (int) $_POST['hapus_id'];
        $stmt = $conn->prepare("DELETE FROM survey_gate_settings WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $message = '<div class="alert alert-success small">Survey berhasil dihapus.</div>';
    }
}

$surveyTypes = $conn->query("SELECT slug, name FROM survey_types ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$daftarSurvey = $conn->query("SELECT * FROM survey_gate_settings ORDER BY sort_order ASC")->fetch_all(MYSQLI_ASSOC);

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="container-fluid py-4">

    <div style="font-size:19px; font-weight:800; color:#14112b;">Pengaturan Survey Gate (Wajib Isi Survey Mahasiswa)</div>
    <div class="text-muted mb-4" style="font-size:12.5px;">Kelola daftar Survey yang wajib diisi mahasiswa sebelum meninjau nilai. Status "sudah isi" diverifikasi otomatis dari NIM, bukan pengakuan sendiri.</div>

    <?= $message ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="fw-semibold small mb-3">Daftar Survey Wajib</div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead><tr><th>Label</th><th>Jenis Survey (slug)</th><th>Link</th><th width="90">Aktif</th><th width="140">Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($daftarSurvey as $sv): ?>
                        <form method="POST">
                        <tr>
                            <td><input type="text" name="label" class="form-control form-control-sm" value="<?= htmlspecialchars($sv['label']) ?>"></td>
                            <td>
                                <select name="survey_slug" class="form-select form-select-sm">
                                    <?php foreach ($surveyTypes as $t): ?>
                                        <option value="<?= htmlspecialchars($t['slug']) ?>" <?= $t['slug'] === $sv['survey_slug'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="url" name="survey_url" class="form-control form-control-sm" value="<?= htmlspecialchars($sv['survey_url']) ?>"></td>
                            <td class="text-center"><input type="checkbox" name="enabled" class="form-check-input" <?= $sv['enabled'] ? 'checked' : '' ?>></td>
                            <td>
                                <input type="hidden" name="update_id" value="<?= $sv['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                                <button type="submit" form="hapusForm<?= $sv['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus Survey ini dari daftar wajib?')">Hapus</button>
                            </td>
                        </tr>
                        </form>
                        <form method="POST" id="hapusForm<?= $sv['id'] ?>"><input type="hidden" name="hapus_id" value="<?= $sv['id'] ?>"></form>
                        <?php endforeach; ?>
                        <?php if (empty($daftarSurvey)): ?>
                        <tr><td colspan="5" class="text-center text-muted">Belum ada Survey terdaftar.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm" style="max-width:600px;">
        <div class="card-body">
            <div class="fw-semibold small mb-3">Tambah Survey Wajib Baru</div>
            <form method="POST">
                <label class="form-label small">Label</label>
                <input type="text" name="label" class="form-control mb-2" placeholder="mis. Survey Kepuasan Layanan Akademik" required>

                <label class="form-label small">Jenis Survey</label>
                <select name="survey_slug" class="form-select mb-2" required>
                    <option value="">-- Pilih Jenis Survey --</option>
                    <?php foreach ($surveyTypes as $t): ?>
                        <option value="<?= htmlspecialchars($t['slug']) ?>"><?= htmlspecialchars($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="form-label small">Link Survey</label>
                <input type="url" name="survey_url" class="form-control mb-3" placeholder="http://localhost/siqua-legacy/survey/?type=..." required>

                <button type="submit" name="tambah" value="1" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Tambah Survey
                </button>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>