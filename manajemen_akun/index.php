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

$roles = $conn->query("SELECT id, role_name FROM roles WHERE status = 1 ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$units = $conn->query("SELECT id, code, name FROM units ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

<div class="container-fluid py-4" id="tambahPeranPage">

    <div style="font-size:19px; font-weight:800; color:#14112b;">Tambah Peran ke Akun</div>
    <div class="text-muted mb-4" style="font-size:12.5px;">Untuk akun yang punya lebih dari 1 peran (mis. Auditor sekaligus Dosen), tambahkan peran baru di sini — akun bisa berpindah peran lewat dropdown "Ganti Peran" di Header tanpa perlu logout.</div>

    <div class="card shadow-sm" style="max-width:600px;">
        <div class="card-body">

            <label class="form-label small">Cari Akun (username / nama)</label>
            <select class="form-select mb-3" id="userSelector" style="width:100%;"></select>

            <label class="form-label small">Peran Baru</label>
            <select class="form-select mb-3" id="roleSelector">
                <option value="">-- Pilih Peran --</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                <?php endforeach; ?>
            </select>

            <div id="contextUnitWrap" class="mb-3" style="display:none;">
                <label class="form-label small">Unit / Program Studi</label>
                <select class="form-select" id="contextUnit">
                    <option value="">-- Pilih Unit --</option>
                    <?php foreach ($units as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="contextDosenWrap" class="mb-3" style="display:none;">
                <label class="form-label small">Data Dosen</label>
                <select class="form-select" id="contextDosen">
                    <option value="">-- Ketik untuk cari Dosen --</option>
                </select>
            </div>

            <div id="contextMahasiswaWrap" class="mb-3" style="display:none;">
                <label class="form-label small">Data Mahasiswa</label>
                <select class="form-select" id="contextMahasiswa">
                    <option value="">-- Ketik untuk cari Mahasiswa --</option>
                </select>
            </div>

            <button class="btn btn-primary w-100" id="btnTambahPeran">
                <i class="bi bi-plus-circle"></i> Tambahkan Peran
            </button>
        </div>
    </div>

    <div class="card shadow-sm mt-3" style="max-width:600px; display:none;" id="daftarPeranCard">
        <div class="card-body">
            <div class="fw-semibold small mb-2">Peran yang Sudah Dimiliki Akun Ini</div>
            <div id="daftarPeranList"></div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
const TambahPeranApi = SIQUA.BASE_URL + "manajemen_akun/api.php";

$("#userSelector").select2({
  placeholder: "-- Cari akun --",
  ajax: {
    url: TambahPeranApi,
    dataType: "json",
    delay: 300,
    data: (params) => ({ action: "search_user", q: params.term || "" }),
    processResults: (res) => ({ results: res.data.map((u) => ({ id: u.id, text: u.username + " - " + u.full_name })) }),
  },
});

$("#userSelector").on("change", function () {
  loadDaftarPeran($(this).val());
});

$("#contextDosen").select2({
  placeholder: "-- Cari Dosen --",
  ajax: {
    url: TambahPeranApi,
    dataType: "json",
    delay: 300,
    data: (params) => ({ action: "search_dosen", q: params.term || "" }),
    processResults: (res) => ({ results: res.data.map((d) => ({ id: d.id, text: d.nidn + " - " + d.name })) }),
  },
});

$("#contextMahasiswa").select2({
  placeholder: "-- Cari Mahasiswa --",
  ajax: {
    url: TambahPeranApi,
    dataType: "json",
    delay: 300,
    data: (params) => ({ action: "search_mahasiswa", q: params.term || "" }),
    processResults: (res) => ({ results: res.data.map((m) => ({ id: m.id, text: m.nim + " - " + m.nama })) }),
  },
});

$("#roleSelector").on("change", function () {
  const roleId = parseInt($(this).val());
  $("#contextUnitWrap, #contextDosenWrap, #contextMahasiswaWrap").hide();

  if ([1, 2, 3, 4, 5].includes(roleId)) $("#contextUnitWrap").show();
  if (roleId === 6) $("#contextDosenWrap").show();
  if (roleId === 7) $("#contextMahasiswaWrap").show();
});

function loadDaftarPeran(userId) {
  if (!userId) {
    $("#daftarPeranCard").hide();
    return;
  }
  $.getJSON(TambahPeranApi, { action: "list_peran", user_id: userId }, function (res) {
    if (!res.success) return;
    let html = "";
    res.data.forEach(function (p) {
      let extra = p.dosen_name ? " — " + p.dosen_name : p.unit_name ? " — " + p.unit_name : "";
      html += `<div class="badge bg-light text-dark border me-1 mb-1 py-2 px-3">${escapeHtml(p.role_name + extra)}</div>`;
    });
    $("#daftarPeranList").html(html || '<span class="text-muted small">Belum ada peran.</span>');
    $("#daftarPeranCard").show();
  });
}

$("#btnTambahPeran").on("click", function () {
  const userId = $("#userSelector").val();
  const roleId = $("#roleSelector").val();

  if (!userId || !roleId) {
    Swal.fire("Belum Lengkap", "Pilih Akun dan Peran terlebih dahulu.", "warning");
    return;
  }

  const payload = {
    user_id: userId,
    role_id: roleId,
    unit_id: $("#contextUnit").val() || "",
    dosen_id: $("#contextDosen").val() || "",
    mahasiswa_id: $("#contextMahasiswa").val() || "",
  };

  $.post(TambahPeranApi + "?action=tambah_peran", payload, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }
    Swal.fire({ icon: "success", title: "Berhasil", text: res.message, timer: 1500, showConfirmButton: false });
    loadDaftarPeran(userId);
  }, "json");
});

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}
</script>