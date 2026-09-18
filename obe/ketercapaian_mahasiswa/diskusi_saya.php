<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../layouts/app.php';

if (!Auth::isMahasiswa()) {
    die('<div style="padding:40px; font-family:sans-serif;">Halaman ini khusus untuk akun Mahasiswa.</div>');
}

$mahasiswaId = Auth::getMahasiswaId();

$stmtMhs = $conn->prepare("SELECT nim, nama FROM obe_mahasiswa WHERE id = ? LIMIT 1");
$stmtMhs->bind_param("i", $mahasiswaId);
$stmtMhs->execute();
$mhs = $stmtMhs->get_result()->fetch_assoc();

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    #diskusiSayaThread { max-height: 420px; overflow-y: auto; padding-right: 4px; }
    #diskusiSayaThread .diskusi-item { display: flex; gap: 10px; margin-bottom: 14px; }
    #diskusiSayaThread .diskusi-avatar { width: 34px; height: 34px; border-radius: 50%; background: #f1edfc; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0; }
    #diskusiSayaThread .diskusi-bubble { background: #faf9fd; border-radius: 12px; padding: 8px 12px; flex: 1; }
    #diskusiSayaThread .diskusi-nama { font-size: 11.5px; font-weight: 700; color: #14112b; }
    #diskusiSayaThread .diskusi-waktu { font-size: 10px; color: #a39fb5; margin-left: 6px; font-weight: 400; }
    #diskusiSayaThread .diskusi-pesan { font-size: 12.5px; color: #2d2a45; margin-top: 2px; white-space: pre-line; }
</style>

<div class="container-fluid py-4">

    <div class="mb-1" style="font-size:19px; font-weight:800; color:#14112b;">Diskusi &amp; Umpan Balik Ketercapaian CPL</div>
    <div class="text-muted mb-4" style="font-size:12.5px;"><?= htmlspecialchars($mhs['nama'] ?? '-') ?> &mdash; NIM <?= htmlspecialchars($mhs['nim'] ?? '-') ?></div>

    <div class="card shadow-sm" style="max-width:700px;">
        <div class="card-body">
            <div id="diskusiSayaThread" class="mb-3"><p class="text-muted small">Memuat diskusi...</p></div>
            <form id="diskusiSayaForm" class="d-flex gap-2">
                <input type="text" class="form-control form-control-sm" id="diskusiSayaPesan" placeholder="Tulis catatan/tanggapan..." required>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send-fill"></i> Kirim</button>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script>
const mahasiswaId = <?= (int) $mahasiswaId ?>;
const diskusiApi = SIQUA.BASE_URL + "obe/diskusi_cpl/api.php";

function loadDiskusiSaya() {
  $.getJSON(diskusiApi, { action: "list", mahasiswa_id: mahasiswaId }, function (res) {
    if (!res.success || !res.data.length) {
      $("#diskusiSayaThread").html('<p class="text-muted small">Belum ada diskusi. Mulai percakapan di bawah.</p>');
      return;
    }
    let html = "";
    res.data.forEach(function (d) {
      const inisial = d.penulis_nama.trim().charAt(0).toUpperCase() || "?";
      const waktu = new Date(d.created_at.replace(" ", "T")).toLocaleString("id-ID", {
        day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit",
      });
      html += `
        <div class="diskusi-item">
          <div class="diskusi-avatar">${inisial}</div>
          <div class="diskusi-bubble">
            <span class="diskusi-nama">${escapeHtml(d.penulis_nama)}</span>
            <span class="diskusi-waktu">${waktu}</span>
            <div class="diskusi-pesan">${escapeHtml(d.pesan)}</div>
          </div>
        </div>
      `;
    });
    $("#diskusiSayaThread").html(html);
    $("#diskusiSayaThread").scrollTop($("#diskusiSayaThread")[0].scrollHeight);
  });
}

$("#diskusiSayaForm").on("submit", function (e) {
  e.preventDefault();
  const pesan = $("#diskusiSayaPesan").val().trim();
  if (!pesan) return;

  $.post(diskusiApi + "?action=create", { mahasiswa_id: mahasiswaId, pesan: pesan }, function (res) {
    if (res.success) {
      $("#diskusiSayaPesan").val("");
      loadDiskusiSaya();
    } else {
      Swal.fire("Gagal", res.message, "error");
    }
  }, "json");
});

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

loadDiskusiSaya();
</script>