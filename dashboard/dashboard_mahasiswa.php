<?php

declare(strict_types=1);

$mahasiswaId = Auth::getMahasiswaId();

$stmtMhs = $conn->prepare("
    SELECT m.nim, m.nama, m.angkatan, k.unit_id, m.kurikulum_id, u.name AS unit_name
    FROM obe_mahasiswa m
    JOIN obe_kurikulum k ON k.id = m.kurikulum_id
    JOIN units u ON u.id = k.unit_id
    WHERE m.id = ? LIMIT 1
");
$stmtMhs->bind_param("i", $mahasiswaId);
$stmtMhs->execute();
$mhs = $stmtMhs->get_result()->fetch_assoc() ?: [];

$unitId = (int) ($mhs['unit_id'] ?? 0);
$kurikulumId = (int) ($mhs['kurikulum_id'] ?? 0);

$jam = (int) date('G');
$sapaan = $jam < 11 ? 'Selamat Pagi' : ($jam < 15 ? 'Selamat Siang' : ($jam < 18 ? 'Selamat Sore' : 'Selamat Malam'));

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    #mhsDashPage .greet-card {
        background: linear-gradient(135deg, #5b21b6 0%, #7c3aed 50%, #6d28d9 100%);
        border-radius: 20px; padding: 28px 30px; color: #fff; margin-bottom: 24px;
        box-shadow: 0 10px 30px rgba(124,58,237,0.25);
    }
    #mhsDashPage .greet-name { font-size: 20px; font-weight: 800; margin-bottom: 2px; }
    #mhsDashPage .greet-sub { font-size: 12.5px; opacity: .85; }

    #mhsDashPage .portfolio-card {
        background: #fff; border: 1px solid #eceaf5; border-radius: 16px; padding: 20px;
        text-align: center; text-decoration: none; display: block; height: 100%;
        transition: transform .15s, box-shadow .15s;
    }
    #mhsDashPage .portfolio-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(20,17,43,0.08); }
    #mhsDashPage .portfolio-icon {
        width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 22px; margin: 0 auto 12px; color: #fff;
    }
    #mhsDashPage .portfolio-title { font-size: 13px; font-weight: 700; color: #14112b; }

    #mhsDashPage .chat-card { background: #fff; border: 1px solid #eceaf5; border-radius: 16px; overflow: hidden; }
    #mhsDashPage .chat-header { background: #f6f3fc; padding: 14px 20px; border-bottom: 1px solid #eceaf5; font-weight: 700; font-size: 13.5px; color: #5b21b6; display: flex; align-items: center; gap: 8px; }
    #mhsDashPage .chat-body { padding: 16px 20px; max-height: 380px; overflow-y: auto; background: #f9f8fc; }
    #mhsDashPage .chat-item { display: flex; margin-bottom: 12px; }
    #mhsDashPage .chat-item.me { justify-content: flex-end; }
    #mhsDashPage .chat-bubble { max-width: 72%; padding: 9px 14px; border-radius: 16px; font-size: 12.5px; line-height: 1.4; }
    #mhsDashPage .chat-item.me .chat-bubble { background: #7c3aed; color: #fff; border-bottom-right-radius: 4px; }
    #mhsDashPage .chat-item.other .chat-bubble { background: #fff; color: #2d2a45; border: 1px solid #eceaf5; border-bottom-left-radius: 4px; }
    #mhsDashPage .chat-nama { font-size: 10.5px; font-weight: 700; margin-bottom: 2px; opacity: .8; }
    #mhsDashPage .chat-waktu { font-size: 9.5px; opacity: .65; margin-top: 3px; display: block; }
    #mhsDashPage .chat-footer { padding: 12px 16px; border-top: 1px solid #eceaf5; background: #fff; }
</style>

<div class="container-fluid py-4" id="mhsDashPage">

    <div class="greet-card">
        <div class="greet-name"><?= htmlspecialchars($sapaan) ?>, <?= htmlspecialchars($mhs['nama'] ?? '-') ?>!</div>
        <div class="greet-sub">NIM <?= htmlspecialchars($mhs['nim'] ?? '-') ?> &middot; <?= htmlspecialchars($mhs['unit_name'] ?? '-') ?> &middot; Angkatan <?= htmlspecialchars((string) ($mhs['angkatan'] ?? '-')) ?></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="<?= BASE_URL ?>obe/khs/cetak.php?mahasiswa_id=<?= $mahasiswaId ?>" target="_blank" class="portfolio-card">
                <div class="portfolio-icon" style="background:#2563eb;"><i class="bi bi-file-earmark-text"></i></div>
                <div class="portfolio-title">KHS Saya</div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="<?= BASE_URL ?>obe/khs/transkrip.php?mahasiswa_id=<?= $mahasiswaId ?>" target="_blank" class="portfolio-card">
                <div class="portfolio-icon" style="background:#0d9488;"><i class="bi bi-journal-text"></i></div>
                <div class="portfolio-title">Transkrip Saya</div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="<?= BASE_URL ?>obe/ketercapaian_mahasiswa/cetak_cpl_per_mk.php?mahasiswa_id=<?= $mahasiswaId ?>&unit_id=<?= $unitId ?>&kurikulum_id=<?= $kurikulumId ?>" target="_blank" class="portfolio-card">
                <div class="portfolio-icon" style="background:#d97706;"><i class="bi bi-table"></i></div>
                <div class="portfolio-title">Ketercapaian CPL per MK</div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="<?= BASE_URL ?>obe/ketercapaian_mahasiswa/cetak_cpl.php?mahasiswa_id=<?= $mahasiswaId ?>&unit_id=<?= $unitId ?>&kurikulum_id=<?= $kurikulumId ?>" target="_blank" class="portfolio-card">
                <div class="portfolio-icon" style="background:#7c3aed;"><i class="bi bi-bar-chart-fill"></i></div>
                <div class="portfolio-title">Ketercapaian 5 CPL</div>
            </a>
        </div>
    </div>

    <div class="chat-card">
        <div class="chat-header"><i class="bi bi-chat-dots-fill"></i> Diskusi &amp; Umpan Balik Ketercapaian CPL</div>
        <div class="chat-body" id="mhsChatBody"><p class="text-muted small text-center mt-3">Memuat diskusi...</p></div>
        <div class="chat-footer">
            <form id="mhsChatForm" class="d-flex gap-2">
                <input type="text" class="form-control form-control-sm" id="mhsChatPesan" placeholder="Tulis pesan..." required>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send-fill"></i></button>
            </form>
        </div>
    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

const mhsDashMahasiswaId = <?= (int) $mahasiswaId ?>;
const mhsDashNamaSaya = <?= json_encode($mhs['nama'] ?? '') ?>;
const mhsDashDiskusiApi = SIQUA.BASE_URL + "obe/diskusi_cpl/api.php";

function loadMhsDashChat() {
  $.getJSON(mhsDashDiskusiApi, { action: "list", mahasiswa_id: mhsDashMahasiswaId }, function (res) {
    if (!res.success || !res.data.length) {
      $("#mhsChatBody").html('<p class="text-muted small text-center mt-3">Belum ada diskusi. Mulai percakapan di bawah.</p>');
      return;
    }
    let html = "";
    res.data.forEach(function (d) {
      const isMe = d.penulis_nama === mhsDashNamaSaya;
      const waktu = new Date(d.created_at.replace(" ", "T")).toLocaleString("id-ID", {
        day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit",
      });
      html += `
        <div class="chat-item ${isMe ? "me" : "other"}">
          <div class="chat-bubble">
            ${!isMe ? `<div class="chat-nama">${escapeHtmlMhsDash(d.penulis_nama)}</div>` : ""}
            ${escapeHtmlMhsDash(d.pesan)}
            <span class="chat-waktu">${waktu}</span>
          </div>
        </div>
      `;
    });
    $("#mhsChatBody").html(html);
    $("#mhsChatBody").scrollTop($("#mhsChatBody")[0].scrollHeight);
  });
}

$("#mhsChatForm").on("submit", function (e) {
  e.preventDefault();
  const pesan = $("#mhsChatPesan").val().trim();
  if (!pesan) return;

  $.post(mhsDashDiskusiApi + "?action=create", { mahasiswa_id: mhsDashMahasiswaId, pesan: pesan }, function (res) {
    if (res.success) {
      $("#mhsChatPesan").val("");
      loadMhsDashChat();
    } else {
      Swal.fire("Gagal", res.message, "error");
    }
  }, "json");
});

function escapeHtmlMhsDash(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

loadMhsDashChat();

});
</script>