<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../layouts/app.php';

if (!Auth::canManage()) {
    die('<div style="padding:40px; font-family:sans-serif;">Anda tidak memiliki akses ke halaman ini.</div>');
}

$units = $conn->query("SELECT id, code, name FROM units WHERE type = 'Program Studi' ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="container-fluid py-4" id="genAkunPage">

    <div style="font-size:19px; font-weight:800; color:#14112b;">Generate Akun Dosen &amp; Mahasiswa</div>
    <div class="text-muted mb-4" style="font-size:12.5px;">Buat akun login massal — username otomatis dari NIDN (Dosen) atau NIM (Mahasiswa), password awal sama dengan username, wajib diganti saat login pertama.</div>

    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabDosen" type="button">Akun Dosen</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabMahasiswa" type="button">Akun Mahasiswa</button></li>
    </ul>

    <div class="tab-content">

        <!-- TAB DOSEN -->
        <div class="tab-pane fade show active" id="tabDosen">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <label class="form-label small">Program Studi</label>
                    <select class="form-select mb-3" id="genUnitSelectorDosen" style="max-width:400px;">
                        <option value="">-- Pilih Program Studi --</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <div id="dosenPreviewWrap" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="checkAllDosen">
                                <label class="form-check-label small fw-semibold" for="checkAllDosen">Pilih Semua</label>
                            </div>
                            <button class="btn btn-primary btn-sm" id="btnGenerateDosen">
                                <i class="bi bi-magic"></i> Generate Akun Terpilih
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead><tr><th width="30"></th><th>NIDN</th><th>Nama Dosen</th></tr></thead>
                                <tbody id="dosenPreviewBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB MAHASISWA -->
        <div class="tab-pane fade" id="tabMahasiswa">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <label class="form-label small">Program Studi</label>
                    <select class="form-select mb-3" id="genUnitSelectorMhs" style="max-width:400px;">
                        <option value="">-- Pilih Program Studi --</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label small">Kurikulum</label>
                    <select class="form-select mb-3" id="genKurikulumSelectorMhs" style="max-width:400px;" disabled>
                        <option value="">-- Pilih Program Studi dulu --</option>
                    </select>

                    <div id="mhsPreviewWrap" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="checkAllMhs">
                                <label class="form-check-label small fw-semibold" for="checkAllMhs">Pilih Semua</label>
                            </div>
                            <button class="btn btn-primary btn-sm" id="btnGenerateMhs">
                                <i class="bi bi-magic"></i> Generate Akun Terpilih
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead><tr><th width="30"></th><th>NIM</th><th>Nama Mahasiswa</th></tr></thead>
                                <tbody id="mhsPreviewBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script>
const GenAkun = { api: SIQUA.BASE_URL + "obe/generate_akun/api.php" };

$("#genUnitSelectorDosen").on("change", function () {
  const unitId = $(this).val();
  $("#dosenPreviewWrap").hide();
  if (!unitId) return;

  $.getJSON(GenAkun.api, { action: "preview_dosen", unit_id: unitId }, function (res) {
    if (!res.success || !res.data.length) {
      $("#dosenPreviewBody").html('<tr><td colspan="3" class="text-muted text-center">Semua Dosen di Prodi ini sudah punya akun.</td></tr>');
      $("#dosenPreviewWrap").show();
      return;
    }

    let html = "";
    res.data.forEach(function (d) {
      html += `<tr><td><input type="checkbox" class="dosen-check" value="${d.id}" checked></td><td>${escapeHtml(d.nidn)}</td><td>${escapeHtml(d.name)}</td></tr>`;
    });
    $("#dosenPreviewBody").html(html);
    $("#dosenPreviewWrap").show();
    $("#checkAllDosen").prop("checked", true);
  });
});

$("#checkAllDosen").on("change", function () {
  $(".dosen-check").prop("checked", $(this).is(":checked"));
});

$("#btnGenerateDosen").on("click", function () {
  const unitId = $("#genUnitSelectorDosen").val();
  const ids = $(".dosen-check:checked").map(function () { return $(this).val(); }).get();

  if (!ids.length) {
    Swal.fire("Belum Ada Dipilih", "Centang minimal 1 Dosen.", "warning");
    return;
  }

  Swal.fire({
    icon: "question",
    title: `Generate ${ids.length} Akun Dosen?`,
    text: "Username = NIDN, Password awal = NIDN (wajib diganti saat login pertama).",
    showCancelButton: true,
    confirmButtonText: "Ya, Generate",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    let formData = "unit_id=" + encodeURIComponent(unitId);
    ids.forEach((id) => (formData += "&dosen_ids[]=" + id));

    $.post(GenAkun.api + "?action=generate_dosen", formData, function (res) {
      if (!res.success) {
        Swal.fire("Gagal", res.message, "error");
        return;
      }
      let msg = res.message;
      if (res.data.gagal.length) {
        msg += "<br><br><strong>Gagal:</strong><br>" + res.data.gagal.join("<br>");
      }
      Swal.fire({ icon: "success", title: "Selesai", html: msg });
      $("#genUnitSelectorDosen").trigger("change");
    }, "json");
  });
});

$("#genUnitSelectorMhs").on("change", function () {
  const unitId = $(this).val();
  $("#mhsPreviewWrap").hide();
  $("#genKurikulumSelectorMhs").prop("disabled", true).html('<option value="">-- Pilih Program Studi dulu --</option>');
  if (!unitId) return;

  $.getJSON(SIQUA.BASE_URL + "obe/rps/api.php", { action: "kurikulum_list", unit_id: unitId }, function (res) {
    if (!res.success || !res.data.length) {
      $("#genKurikulumSelectorMhs").html('<option value="">-- Belum ada Kurikulum --</option>');
      return;
    }
    let html = '<option value="">-- Pilih Kurikulum --</option>';
    res.data.forEach(function (k) {
      html += `<option value="${k.id}">${k.tahun} - ${escapeHtml(k.nama)}</option>`;
    });
    $("#genKurikulumSelectorMhs").html(html).prop("disabled", false);
  });
});

$("#genKurikulumSelectorMhs").on("change", function () {
  const kurikulumId = $(this).val();
  $("#mhsPreviewWrap").hide();
  if (!kurikulumId) return;

  $.getJSON(GenAkun.api, { action: "preview_mahasiswa", kurikulum_id: kurikulumId }, function (res) {
    if (!res.success || !res.data.length) {
      $("#mhsPreviewBody").html('<tr><td colspan="3" class="text-muted text-center">Semua Mahasiswa di Kurikulum ini sudah punya akun.</td></tr>');
      $("#mhsPreviewWrap").show();
      return;
    }

    let html = "";
    res.data.forEach(function (m) {
      html += `<tr><td><input type="checkbox" class="mhs-check" value="${m.id}" checked></td><td>${escapeHtml(m.nim)}</td><td>${escapeHtml(m.nama)}</td></tr>`;
    });
    $("#mhsPreviewBody").html(html);
    $("#mhsPreviewWrap").show();
    $("#checkAllMhs").prop("checked", true);
  });
});

$("#checkAllMhs").on("change", function () {
  $(".mhs-check").prop("checked", $(this).is(":checked"));
});

$("#btnGenerateMhs").on("click", function () {
  const unitId = $("#genUnitSelectorMhs").val();
  const kurikulumId = $("#genKurikulumSelectorMhs").val();
  const ids = $(".mhs-check:checked").map(function () { return $(this).val(); }).get();

  if (!ids.length) {
    Swal.fire("Belum Ada Dipilih", "Centang minimal 1 Mahasiswa.", "warning");
    return;
  }

  Swal.fire({
    icon: "question",
    title: `Generate ${ids.length} Akun Mahasiswa?`,
    text: "Username = NIM, Password awal = NIM (wajib diganti saat login pertama).",
    showCancelButton: true,
    confirmButtonText: "Ya, Generate",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    let formData = "unit_id=" + encodeURIComponent(unitId) + "&kurikulum_id=" + encodeURIComponent(kurikulumId);
    ids.forEach((id) => (formData += "&mahasiswa_ids[]=" + id));

    $.post(GenAkun.api + "?action=generate_mahasiswa", formData, function (res) {
      if (!res.success) {
        Swal.fire("Gagal", res.message, "error");
        return;
      }
      let msg = res.message;
      if (res.data.gagal.length) {
        msg += "<br><br><strong>Gagal:</strong><br>" + res.data.gagal.join("<br>");
      }
      Swal.fire({ icon: "success", title: "Selesai", html: msg });
      $("#genKurikulumSelectorMhs").trigger("change");
    }, "json");
  });
});

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}
</script>