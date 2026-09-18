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

<style>
    #panduanPage { font-size: 13px; }
    #panduanPage .page-title { font-size: 19px; font-weight: 800; color: #14112b; margin-bottom: 2px; }
    #panduanPage .page-subtitle { font-size: 12.5px; color: #8a8698; margin-bottom: 24px; }

    .alur-step {
        background: #fff; border: 1px solid #eceaf5; border-radius: 16px; padding: 20px 22px; margin-bottom: 16px;
        box-shadow: 0 1px 3px rgba(20,17,43,0.04); position: relative;
    }
    .alur-step-num {
        position: absolute; top: -14px; left: 20px; width: 32px; height: 32px; border-radius: 50%;
        background: #7c3aed; color: #fff; display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 14px; box-shadow: 0 4px 10px rgba(124,58,237,0.3);
    }
    .alur-step-title { font-size: 14px; font-weight: 800; color: #14112b; margin: 6px 0 6px 34px; }
    .alur-step-desc { font-size: 12px; color: #6b6785; margin-left: 34px; margin-bottom: 10px; line-height: 1.6; }
    .alur-formula {
        background: #f6f3fc; border-left: 3px solid #7c3aed; border-radius: 8px; padding: 10px 14px;
        margin: 10px 0 10px 34px; font-family: 'Courier New', monospace; font-size: 12px; color: #4c1d95;
    }
    .alur-arrow { text-align: center; font-size: 20px; color: #c4b5fd; margin: -6px 0 4px; }
    .alur-badge { display: inline-block; background: #ecfdf9; color: #0d9488; font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 20px; margin-left: 34px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: .3px; }
    .alur-badge.tingkat-prodi { background: #f1edfc; color: #5b21b6; }

    .selector-card { background: #fff; border: 1px solid #eceaf5; border-radius: 14px; padding: 20px; margin-bottom: 24px; }
</style>

<div class="container-fluid py-4" id="panduanPage">

    <div class="page-title">Panduan Alur Perhitungan OBE Lengkap</div>
    <div class="page-subtitle">Dari nilai mentah yang diisi dosen per Pertemuan, hingga Ketercapaian CPL Program Studi &mdash; setiap tahap dijelaskan dengan rumusnya</div>

    <!-- ===================== TINGKAT MATA KULIAH ===================== -->

    <div class="alur-step">
        <div class="alur-step-num">1</div>
        <div class="alur-step-title">Dosen Mengisi Nilai Mentah</div>
        <div class="alur-badge">Tingkat Mata Kuliah</div>
        <div class="alur-step-desc">
            Di menu <strong>Penilaian</strong>, dosen mengisi angka 0&ndash;100 untuk tiap Mahasiswa, per Pertemuan (TM), per Komponen (Basis Evaluasi seperti Tugas/UTS/UAS kalau 1 TM punya lebih dari 1 komponen). Ini satu-satunya tempat dosen kerja &mdash; semua tabel di bawah ini otomatis terhitung dari sini.
        </div>
    </div>

    <div class="alur-arrow"><i class="bi bi-arrow-down-circle-fill"></i></div>

    <div class="alur-step">
        <div class="alur-step-num">2</div>
        <div class="alur-step-title">Nilai Akhir Mata Kuliah per Mahasiswa</div>
        <div class="alur-badge">Tingkat Mata Kuliah</div>
        <div class="alur-step-desc">Tiap Komponen (TM) punya Bobot (%) dari Rencana Evaluasi. Nilai Akhir MK = rata-rata tertimbang seluruh Komponen.</div>
        <div class="alur-formula">Nilai Akhir MK = &Sigma;(Nilai Komponen &times; Bobot Komponen) &divide; &Sigma;(Bobot Komponen)</div>
        <div class="alur-step-desc" style="margin-top:-4px;">Lihat di: <strong>Laporan Detail Penilaian &rarr; Tabel 2</strong> (rekap per Mahasiswa &times; TM) dan <strong>Tabel 1</strong> (detail 1 halaman per Mahasiswa, sudah termasuk huruf mutu A/AB/B/BC/C/D/E).</div>
    </div>

    <div class="alur-arrow"><i class="bi bi-arrow-down-circle-fill"></i></div>

    <div class="alur-step">
        <div class="alur-step-num">3</div>
        <div class="alur-step-title">Ketercapaian CPL per Mahasiswa (dalam 1 MK)</div>
        <div class="alur-badge">Tingkat Mata Kuliah</div>
        <div class="alur-step-desc">Setiap Komponen (TM) sudah terhubung ke 1 CPL (lewat Sub-CPMK&rarr;CPMK&rarr;CPL). Komponen-komponen yang mengarah ke CPL yang sama dikelompokkan, lalu dirata-rata tertimbang bobotnya &mdash; menghasilkan skor Ketercapaian CPL <em>khusus untuk 1 mahasiswa itu</em>.</div>
        <div class="alur-formula">Ketercapaian CPL (1 mhs) = &Sigma;(Nilai Komponen milik CPL X &times; Bobotnya) &divide; &Sigma;(Bobot Komponen milik CPL X)</div>
        <div class="alur-step-desc" style="margin-top:-4px;">Lihat di: <strong>Laporan Detail Penilaian &rarr; Tabel 3</strong> (kolom dikelompokkan ulang per CPL, bukan urutan Minggu) dan <strong>Tabel 1</strong> (baris "Nilai Ketercapaian CPL" di tiap halaman mahasiswa).</div>
    </div>

    <div class="alur-arrow"><i class="bi bi-arrow-down-circle-fill"></i></div>

    <div class="alur-step">
        <div class="alur-step-num">4</div>
        <div class="alur-step-title">Ketercapaian CPL rata-rata Kelas (per Mata Kuliah)</div>
        <div class="alur-badge">Tingkat Mata Kuliah</div>
        <div class="alur-step-desc">Skor Ketercapaian CPL semua mahasiswa (hasil Langkah 3) dirata-ratakan sederhana &mdash; menghasilkan 1 angka per CPL yang mewakili performa <em>seluruh kelas</em> di MK tersebut.</div>
        <div class="alur-formula">Ketercapaian CPL (1 MK) = &Sigma;(Ketercapaian CPL tiap Mahasiswa) &divide; Jumlah Mahasiswa</div>
        <div class="alur-step-desc" style="margin-top:-4px;">Lihat di: <strong>Laporan Detail Penilaian &rarr; Tabel 4</strong> (Rekap Ketercapaian Mata Kuliah, 1 baris per mahasiswa + baris "Rata-Rata OBE" di bawah = angka inilah).</div>
    </div>

    <div class="alur-arrow"><i class="bi bi-arrow-down-circle-fill" style="color:#7c3aed;"></i></div>

    <!-- ===================== TINGKAT PRODI ===================== -->

    <div class="alur-step" style="border-color:#ddd0f7;">
        <div class="alur-step-num" style="background:#5b21b6;">5</div>
        <div class="alur-step-title">Bobot Kontribusi tiap Mata Kuliah terhadap CPL Prodi</div>
        <div class="alur-badge tingkat-prodi">Tingkat Program Studi</div>
        <div class="alur-step-desc">Ini ditentukan Ka. Prodi/GKM (menu <strong>Bobot Kontribusi MK-CPL</strong>) &mdash; bisa "Hitung Otomatis" dari total Bobot RPS tiap MK per CPL (dinormalisasi supaya tiap kolom CPL = 100%), lalu boleh disesuaikan manual berdasar pertimbangan kurikulum (kedalaman keterlibatan MK, posisi di semester, SKS, dst &mdash; lihat tombol "Panduan" di halaman itu).</div>
        <div class="alur-step-desc" style="margin-top:-4px;">Lihat di: <strong>Matriks Detail &rarr; Tabel 1</strong> (Bobot Kontribusi, baris=MK, kolom=CPL, total per kolom=100%).</div>
    </div>

    <div class="alur-arrow"><i class="bi bi-arrow-down-circle-fill" style="color:#7c3aed;"></i></div>

    <div class="alur-step" style="border-color:#ddd0f7;">
        <div class="alur-step-num" style="background:#5b21b6;">6</div>
        <div class="alur-step-title">Ketercapaian CPL Program Studi (Angka Final)</div>
        <div class="alur-badge tingkat-prodi">Tingkat Program Studi</div>
        <div class="alur-step-desc">Ketercapaian CPL tiap MK (hasil Langkah 4) dikalikan Bobot Kontribusi MK itu (hasil Langkah 5), dijumlahkan dari <em>semua</em> MK yang punya Bobot Kontribusi terisi untuk CPL tersebut. Inilah angka akhir yang mewakili performa Program Studi.</div>
        <div class="alur-formula">Ketercapaian CPL Prodi = &Sigma;(Ketercapaian CPL tiap MK &times; Bobot Kontribusi MK &divide; 100)</div>
        <div class="alur-step-desc" style="margin-top:-4px;">Kategori: <strong>&gt;80 Sangat Memuaskan</strong> &middot; <strong>70&ndash;79 Memuaskan</strong> &middot; <strong>60&ndash;69 Cukup Memuaskan</strong> &middot; <strong>&lt;60 Tidak Memuaskan</strong>.</div>
        <div class="alur-step-desc" style="margin-top:-4px;">Lihat di: <strong>Laporan Ketercapaian CPL Prodi</strong> (Diagram Radar) dan <strong>Matriks Detail &rarr; Tabel 2</strong> (rincian per-MK, baris terakhir = angka final ini).</div>
    </div>

    <!-- ===================== SHORTCUT KE LAPORAN ===================== -->

    <div class="selector-card mt-4">
        <div class="fw-bold mb-3"><i class="bi bi-lightning-charge-fill text-primary"></i> Buka Laporan Langsung</div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="fw-semibold small mb-2">Laporan Tingkat Mata Kuliah (Langkah 1&ndash;4)</div>
                <label class="form-label small text-muted">Program Studi</label>
                <select class="form-select form-select-sm mb-2" id="panduanUnitSelectorMk">
                    <option value="">-- Pilih Program Studi --</option>
                    <?php foreach ($units as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label small text-muted">Kurikulum</label>
                <select class="form-select form-select-sm mb-2" id="panduanKurikulumSelectorMk" disabled>
                    <option value="">-- Pilih Program Studi dulu --</option>
                </select>
                <label class="form-label small text-muted">Mata Kuliah</label>
                <select class="form-select form-select-sm mb-3" id="panduanMkSelector" disabled>
                    <option value="">-- Pilih Kurikulum dulu --</option>
                </select>
                <button class="btn btn-primary btn-sm w-100" id="btnBukaLaporanMk" disabled>
                    <i class="bi bi-box-arrow-up-right"></i> Buka Laporan Detail Penilaian
                </button>
            </div>

            <div class="col-md-6">
                <div class="fw-semibold small mb-2">Laporan Tingkat Program Studi (Langkah 5&ndash;6)</div>
                <label class="form-label small text-muted">Program Studi</label>
                <select class="form-select form-select-sm mb-2" id="panduanUnitSelectorProdi">
                    <option value="">-- Pilih Program Studi --</option>
                    <?php foreach ($units as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label small text-muted">Kurikulum</label>
                <select class="form-select form-select-sm mb-3" id="panduanKurikulumSelectorProdi" disabled>
                    <option value="">-- Pilih Program Studi dulu --</option>
                </select>
                <button class="btn btn-primary btn-sm w-100" id="btnBukaMatriksProdi" disabled>
                    <i class="bi bi-box-arrow-up-right"></i> Buka Matriks Ketercapaian Prodi
                </button>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script>
const Panduan = { api: SIQUA.BASE_URL + "obe/rps/api.php" };

function loadKurikulumInto(unitId, $select) {
    $select.prop("disabled", true).html('<option value="">-- Memuat... --</option>');
    $.getJSON(Panduan.api, { action: "kurikulum_list", unit_id: unitId }, function (res) {
        if (!res.success || !res.data.length) {
            $select.html('<option value="">-- Belum ada Kurikulum --</option>');
            return;
        }
        let html = '<option value="">-- Pilih Kurikulum --</option>';
        res.data.forEach(function (k) {
            html += `<option value="${k.id}">${k.tahun} - ${k.nama}</option>`;
        });
        $select.html(html).prop("disabled", false);
    });
}

$("#panduanUnitSelectorMk").on("change", function () {
    const unitId = $(this).val();
    $("#panduanMkSelector").prop("disabled", true).html('<option value="">-- Pilih Kurikulum dulu --</option>');
    $("#btnBukaLaporanMk").prop("disabled", true);
    if (unitId) loadKurikulumInto(unitId, $("#panduanKurikulumSelectorMk"));
});

$("#panduanKurikulumSelectorMk").on("change", function () {
    const kurikulumId = $(this).val();
    $("#btnBukaLaporanMk").prop("disabled", true);
    if (!kurikulumId) return;

    $("#panduanMkSelector").prop("disabled", true).html('<option value="">-- Memuat... --</option>');
    $.getJSON(Panduan.api, { action: "mata_kuliah_list", kurikulum_id: kurikulumId }, function (res) {
        if (!res.success || !res.data.length) {
            $("#panduanMkSelector").html('<option value="">-- Belum ada Mata Kuliah --</option>');
            return;
        }
        let html = '<option value="">-- Pilih Mata Kuliah --</option>';
        res.data.forEach(function (mk) {
            html += `<option value="${mk.id}">${mk.code ? mk.code + " - " : ""}${mk.name}</option>`;
        });
        $("#panduanMkSelector").html(html).prop("disabled", false);
    });
});

$("#panduanMkSelector").on("change", function () {
    $("#btnBukaLaporanMk").prop("disabled", !$(this).val());
});

$("#btnBukaLaporanMk").on("click", function () {
    const mkId = $("#panduanMkSelector").val();
    const kurikulumId = $("#panduanKurikulumSelectorMk").val();
    if (!mkId) return;
    const url = SIQUA.BASE_URL + "obe/penilaian/laporan_detail.php?mata_kuliah_id=" + mkId + "&kurikulum_id=" + kurikulumId;
    window.open(url, "_blank");
});

$("#panduanUnitSelectorProdi").on("change", function () {
    const unitId = $(this).val();
    $("#btnBukaMatriksProdi").prop("disabled", true);
    if (unitId) loadKurikulumInto(unitId, $("#panduanKurikulumSelectorProdi"));
});

$("#panduanKurikulumSelectorProdi").on("change", function () {
    $("#btnBukaMatriksProdi").prop("disabled", !$(this).val());
});

$("#btnBukaMatriksProdi").on("click", function () {
    const unitId = $("#panduanUnitSelectorProdi").val();
    const kurikulumId = $("#panduanKurikulumSelectorProdi").val();
    if (!unitId || !kurikulumId) return;
    const url = SIQUA.BASE_URL + "obe/laporan_cpl/matriks.php?unit_id=" + unitId + "&kurikulum_id=" + kurikulumId;
    window.open(url, "_blank");
});
</script>