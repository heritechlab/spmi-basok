<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../layouts/app.php';

$units = $conn->query("SELECT id, code, name FROM units WHERE type = 'Program Studi' ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="container-fluid py-4">

    <div class="mb-1" style="font-size:19px; font-weight:800; color:#14112b;">Kartu Hasil Studi (KHS)</div>
    <div class="mb-4 text-muted" style="font-size:12.5px;">Pilih Mahasiswa untuk melihat rekap Nilai seluruh Mata Kuliah pada Periode Akademik aktif</div>

    <div class="card shadow-sm" style="border-radius:14px; max-width:600px;">
        <div class="card-body">
            <label class="form-label small">Program Studi</label>
            <select class="form-select mb-3" id="khsUnitSelector">
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label class="form-label small">Kurikulum</label>
            <select class="form-select mb-3" id="khsKurikulumSelector" disabled>
                <option value="">-- Pilih Program Studi dulu --</option>
            </select>

            <label class="form-label small">Mahasiswa</label>
            <select class="form-select mb-3" id="khsMahasiswaSelector" disabled>
                <option value="">-- Pilih Kurikulum dulu --</option>
            </select>

            <button class="btn btn-primary w-100 mb-2" id="btnCetakKhs" disabled>
                <i class="bi bi-file-earmark-text"></i> Buka KHS (Periode Aktif)
            </button>
            <button class="btn btn-outline-primary w-100" id="btnCetakTranskrip" disabled>
                <i class="bi bi-journal-text"></i> Buka Transkrip Nilai (Semua Semester)
            </button>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script>
const KhsApi = SIQUA.BASE_URL + "obe/rps/api.php";

function loadKurikulum(unitId) {
    $("#khsKurikulumSelector").prop("disabled", true).html('<option value="">-- Memuat... --</option>');
    $("#khsMahasiswaSelector").prop("disabled", true).html('<option value="">-- Pilih Kurikulum dulu --</option>');
    $("#btnCetakKhs").prop("disabled", true);

    $.getJSON(KhsApi, { action: "kurikulum_list", unit_id: unitId }, function (res) {
        if (!res.success || !res.data.length) {
            $("#khsKurikulumSelector").html('<option value="">-- Belum ada Kurikulum --</option>');
            return;
        }
        let html = '<option value="">-- Pilih Kurikulum --</option>';
        res.data.forEach(function (k) {
            html += `<option value="${k.id}">${k.tahun} - ${k.nama}</option>`;
        });
        $("#khsKurikulumSelector").html(html).prop("disabled", false);
    });
}

$("#khsUnitSelector").on("change", function () {
    const unitId = $(this).val();
    if (unitId) loadKurikulum(unitId);
});

$("#khsKurikulumSelector").on("change", function () {
    const kurikulumId = $(this).val();
    $("#btnCetakKhs").prop("disabled", true);
    if (!kurikulumId) return;

    $("#khsMahasiswaSelector").prop("disabled", true).html('<option value="">-- Memuat... --</option>');

    $.getJSON(SIQUA.BASE_URL + "obe/khs/api.php", { action: "mahasiswa_list", kurikulum_id: kurikulumId }, function (res) {
        if (!res.success || !res.data.length) {
            $("#khsMahasiswaSelector").html('<option value="">-- Belum ada Mahasiswa --</option>');
            return;
        }
        let html = '<option value="">-- Pilih Mahasiswa --</option>';
        res.data.forEach(function (m) {
            html += `<option value="${m.id}">${m.nim} - ${m.nama}</option>`;
        });
        $("#khsMahasiswaSelector").html(html).prop("disabled", false);
    });
});

$("#khsMahasiswaSelector").on("change", function () {
    $("#btnCetakKhs").prop("disabled", !$(this).val());
});

$("#btnCetakKhs").on("click", function () {
    const mahasiswaId = $("#khsMahasiswaSelector").val();
    if (!mahasiswaId) return;
    window.open(SIQUA.BASE_URL + "obe/khs/cetak.php?mahasiswa_id=" + mahasiswaId, "_blank");
});

$("#khsMahasiswaSelector").on("change", function () {
    $("#btnCetakTranskrip").prop("disabled", !$(this).val());
});

$("#btnCetakTranskrip").on("click", function () {
    const mahasiswaId = $("#khsMahasiswaSelector").val();
    if (!mahasiswaId) return;
    window.open(SIQUA.BASE_URL + "obe/khs/transkrip.php?mahasiswa_id=" + mahasiswaId, "_blank");
});
</script>