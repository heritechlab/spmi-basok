<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../layouts/app.php';

if (!Auth::canManage() && !Auth::isAuditee()) {
    die('<div style="padding:40px; font-family:sans-serif;">Anda tidak memiliki akses ke halaman ini.</div>');
}

$units = $conn->query("SELECT id, code, name FROM units ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

$namaUser = $_SESSION['full_name'] ?? 'Pengguna';
$jam = (int) date('G');
$sapaan = $jam < 11 ? 'Selamat Pagi' : ($jam < 15 ? 'Selamat Siang' : ($jam < 18 ? 'Selamat Sore' : 'Selamat Malam'));

$ringkasan = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM standard_statements) +
        (SELECT COUNT(*) FROM audit_indicators WHERE status = 1) AS total_item,
        (SELECT COUNT(DISTINCT sumber_id) FROM risk_register WHERE sumber_jenis = 'standar') +
        (SELECT COUNT(DISTINCT sumber_id) FROM risk_register WHERE sumber_jenis = 'indikator') AS sudah_teridentifikasi
")->fetch_assoc();

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<style>
    #risikoPage .greeting-card {
        background: linear-gradient(135deg, #3a0ca3 0%, #6a11cb 60%, #2575fc 100%);
        border-radius: 20px; padding: 32px 36px; color: #fff; position: relative;
        overflow: visible; margin-bottom: 20px;
        box-shadow: 0 12px 32px rgba(58, 12, 163, 0.25);
    }
    #risikoPage .greeting-card::before {
        content: ""; position: absolute; top: -60px; left: -60px; width: 220px; height: 220px;
        background: rgba(255,255,255,0.06); border-radius: 50%;
    }
    #risikoPage .greeting-card::after {
        content: ""; position: absolute; bottom: -80px; left: 30%; width: 260px; height: 260px;
        background: rgba(255,255,255,0.05); border-radius: 50%;
    }
    #risikoPage .greeting-date {
        display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.15);
        width: fit-content; padding: 6px 14px; border-radius: 999px; font-size: 13px; font-weight: 600;
        margin-bottom: 14px; position: relative; z-index: 2;
    }
    #risikoPage .indicator-summary-title { font-size: 26px; font-weight: 800; letter-spacing: .3px; margin-bottom: 8px; position: relative; z-index: 2; }
    #risikoPage .indicator-summary-greeting { font-size: 18px; font-weight: 600; opacity: .95; max-width: 85%; position: relative; z-index: 2; }
    #risikoPage .standard-summary-hero {
        position: absolute; right: 20px; bottom: 0; height: 115%; width: auto; max-width: 240px;
        object-fit: contain; object-position: bottom right; pointer-events: none;
    }

    #risikoPage .risk-header { background: linear-gradient(135deg, #b91c1c 0%, #dc2626 50%, #ea580c 100%); border-radius: 18px; padding: 22px 26px; color: #fff; margin-bottom: 22px; }
    #risikoPage .risk-header h1 { font-size: 20px; font-weight: 800; margin: 0 0 4px; }
    #risikoPage .risk-header p { font-size: 12.5px; opacity: .9; margin: 0; }
    #risikoPage .nav-pills .nav-link.active { background: #dc2626; }

    #risikoPage .menu-grid-wrap { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; max-width: 700px; }
    #risikoPage .menu-card {
        background: #fff; border: 1px solid #f1d4d4; border-radius: 18px; padding: 24px 14px;
        text-align: center; cursor: pointer; transition: transform .15s, box-shadow .15s;
        position: relative;
    }
    #risikoPage .menu-card:hover { transform: translateY(-4px); box-shadow: 0 10px 24px rgba(220,38,38,0.15); }
    #risikoPage .menu-card-icon {
        width: 58px; height: 58px; border-radius: 16px; margin: 0 auto 12px;
        display: flex; align-items: center; justify-content: center; font-size: 26px; color: #fff;
    }
    #risikoPage .menu-card-title { font-size: 12.5px; font-weight: 700; color: #14112b; }
    #risikoPage .menu-card-count {
        position: absolute; top: 10px; right: 10px; background: #dc2626; color: #fff;
        font-size: 10px; font-weight: 800; border-radius: 20px; padding: 2px 8px; min-width: 20px;
    }
    #risikoPage .btn-kembali {
        border: none; background: #f3f4f6; color: #4b5563; font-size: 12px; font-weight: 700;
        padding: 8px 16px; border-radius: 10px; margin-bottom: 16px;
    }
    #risikoPage .btn-kembali:hover { background: #e5e7eb; }
    #risikoPage .detail-title { font-size: 15px; font-weight: 800; color: #14112b; margin-bottom: 14px; }

    #risikoPage .section-card { background: #fff; border: 1px solid #eceef1; border-radius: 14px; padding: 14px; box-shadow: 0 1px 2px rgba(16,24,40,0.04); }
    #risikoPage table.identifikasi-table { width: 100%; font-size: 11.5px; }
    #risikoPage table.identifikasi-table th { background: #4c1d95; color: #fff; white-space: nowrap; font-weight: 600; font-size: 10.5px; text-transform: uppercase; letter-spacing: .3px; }
    #risikoPage table.identifikasi-table td { vertical-align: middle; }
    #risikoPage .desc-cell { max-width: 320px; white-space: normal; }
    #risikoPage .risk-count-badge { font-size: 10px; font-weight: 800; padding: 3px 10px; border-radius: 20px; }
    #risikoPage .risk-count-badge.has-risk { background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; }
    #risikoPage .risk-count-badge.no-risk { background: #f9fafb; color: #9ca3af; border: 1px solid #e5e7eb; }
    #risikoPage .btn-icon-aksi {
        width: 30px; height: 30px; border-radius: 9px; border: none;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 14px; cursor: pointer; transition: transform .12s, box-shadow .12s;
        background: linear-gradient(135deg, #dc2626, #ea580c); color: #fff;
    }
    #risikoPage .btn-icon-aksi:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
</style>

<div class="container-fluid py-4" id="risikoPage">

    <div class="greeting-card">
        <div class="greeting-date">
            <i class="bi bi-calendar3"></i>
            <?php
                setlocale(LC_TIME, 'id_ID');
                $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                echo date('d') . ' ' . $bulan[(int) date('n')] . ' ' . date('Y');
            ?>
        </div>
        <div class="indicator-summary-title"><?= htmlspecialchars($sapaan) ?>, <?= htmlspecialchars($namaUser) ?>!</div>
        <div class="indicator-summary-greeting">
            Total Standar &amp; Indikator: <strong><?= (int) ($ringkasan['total_item'] ?? 0) ?></strong> &middot;
            Sudah Teridentifikasi: <strong><?= (int) ($ringkasan['sudah_teridentifikasi'] ?? 0) ?></strong>
        </div>
        <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Admin" class="standard-summary-hero">
    </div>

    <div class="risk-header">
        <h1><i class="bi bi-shield-exclamation"></i> Identifikasi Risiko</h1>
        <p>Gambarkan risiko dari seluruh unsur Penetapan — Kebijakan Mutu, Manual Mutu, Standar, dan Indikator Mutu</p>
    </div>

    <div id="menuGrid" class="menu-grid-wrap"></div>

    <div id="detailView" style="display:none;">
        <button type="button" class="btn-kembali" id="btnKembaliGrid">
            <i class="bi bi-arrow-left"></i> Kembali ke Menu
        </button>
        <div id="detailTitle" class="detail-title"></div>

        <div class="mb-3" style="max-width:320px;" id="unitFilterWrap">
            <label class="form-label small fw-semibold">Program Studi / Unit</label>
            <select class="form-select" id="risikoUnitSelector">
                <option value="0">-- Semua Unit --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="section-card table-responsive">
            <table class="table table-bordered table-hover identifikasi-table" id="tableDetail">
                <thead><tr><th>Nama / Uraian</th><th>Konteks</th><th width="140">Jumlah Risiko</th><th width="60">Aksi</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Tambah Risiko -->
<div class="modal fade" id="modalTambahRisiko" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Identifikasi Risiko Baru</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2 small text-muted" id="modalSumberLabel"></div>
        <label class="form-label small">Deskripsi Risiko</label>
        <textarea class="form-control mb-3" id="inputDeskripsiRisiko" rows="3" placeholder="Apa yang bisa terjadi kalau ini tidak tercapai?"></textarea>
        <label class="form-label small">Kategori Risiko (Kriteria Akreditasi)</label>
        <select class="form-select" id="inputKategoriRisiko">
            <option value="">-- Pilih Kategori --</option>
        </select>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-danger btn-sm" id="btnSimpanRisiko">Simpan Risiko</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {

const api = SIQUA.BASE_URL + "risiko/api.php";
let dataGlobal = {};
let modalCtx = {};
let dtDetail = null;

const menuIcons = {
  kebijakan_mutu: { icon: "bi-bank2", color: "#7c3aed", label: "Kebijakan Mutu" },
  manual_ppepp: { icon: "bi-journal-bookmark-fill", color: "#0d9488", label: "Manual Mutu" },
  manual_lainnya: { icon: "bi-journal-text", color: "#0d9488", label: "Manual Mutu Lainnya" },
  standar: { icon: "bi-clipboard-check-fill", color: "#2563eb", label: "Standar" },
  indikator: { icon: "bi-bar-chart-fill", color: "#d97706", label: "Indikator Mutu" },
};

function loadData() {
  const unitId = $("#risikoUnitSelector").val() || 0;
  $.getJSON(api, { action: "identifikasi_data", unit_id: unitId }, function (res) {
    if (!res.success) return;
    dataGlobal = res.data;

    if (currentDetailKey) {
      $(".menu-card[data-key='" + currentDetailKey + "']").trigger("click");
    } else {
      renderGrid();
    }
  });
}

$(document).on("change", "#risikoUnitSelector", function () {
  loadData();
});

function loadKriteria() {
  $.getJSON(api, { action: "kriteria_list" }, function (res) {
    if (!res.success) return;
    let html = '<option value="">-- Pilih Kategori --</option>';
    res.data.forEach(function (k) {
      html += `<option value="${escapeHtml(k.name)}">${escapeHtml(k.name)}</option>`;
    });
    $("#inputKategoriRisiko").html(html);
  });
}

function renderGrid() {
  const cats = Object.keys(dataGlobal.manuals_grouped || {});
  let html = "";

  cats.forEach(function (c) {
    const info = menuIcons[c] || { icon: "bi-folder-fill", color: "#6b7280", label: c };
    const count = (dataGlobal.manuals_grouped[c] || []).length;
    html += menuCardHtml(info, count, "manual-" + c);
  });

  html += menuCardHtml(menuIcons.standar, (dataGlobal.statements || []).length, "standar");
  html += menuCardHtml(menuIcons.indikator, (dataGlobal.indicators || []).length, "indikator");

  $("#menuGrid").html(html);
}

function menuCardHtml(info, count, key) {
  return `
    <div class="menu-card" data-key="${key}">
      ${count > 0 ? `<span class="menu-card-count">${count}</span>` : ""}
      <div class="menu-card-icon" style="background:${info.color};"><i class="bi ${info.icon}"></i></div>
      <div class="menu-card-title">${escapeHtml(info.label)}</div>
    </div>
  `;
}

function buildRowsFor(key) {
  const rows = [];

  if (key === "standar") {
    (dataGlobal.statements || []).forEach(function (s) {
      rows.push({
        jenis: "standar",
        nama: s.statement_text,
        konteks: s.standard_name,
        sumberId: s.id,
        count: dataGlobal.risk_counts["standar_" + s.id] || 0,
      });
    });
  } else if (key === "indikator") {
    (dataGlobal.indicators || []).forEach(function (ind) {
      rows.push({
        jenis: "indikator",
        nama: ind.item_code + " - " + (ind.indicator || ind.statement),
        konteks: ind.standard_name,
        sumberId: ind.id,
        count: dataGlobal.risk_counts["indikator_" + ind.id] || 0,
      });
    });
  } else {
    const cat = key.replace("manual-", "");
    (dataGlobal.manuals_grouped[cat] || []).forEach(function (m) {
      rows.push({
        jenis: m.sumber_tabel,
        nama: m.name,
        konteks: m.category || "-",
        sumberId: m.id,
        count: dataGlobal.risk_counts[m.sumber_tabel + "_" + m.id] || 0,
      });
    });
  }

  return rows;
}

function renderRowsHtml(rows) {
  let html = "";
  rows.forEach(function (r) {
    html += `
      <tr>
        <td class="desc-cell">${escapeHtml(r.nama)}</td>
        <td>${escapeHtml(r.konteks)}</td>
        <td class="text-center">
          <span class="risk-count-badge ${r.count > 0 ? "has-risk" : "no-risk"}">${r.count > 0 ? r.count + " Risiko" : "Belum Ada"}</span>
        </td>
        <td class="text-center">
          <button type="button" class="btn-icon-aksi btn-tambah-risiko" data-jenis="${r.jenis}" data-id="${r.sumberId}" data-label="${escapeHtml(r.nama)}" title="Identifikasi Risiko">
            <i class="bi bi-plus-lg"></i>
          </button>
        </td>
      </tr>
    `;
  });
  return html;
}

let currentDetailKey = null;

$(document).on("click", ".menu-card", function () {
  const key = $(this).data("key");
  currentDetailKey = key;
  let title = "";

  if (key === "standar") title = "Standar (Pernyataan Standar)";
  else if (key === "indikator") title = "Indikator Mutu";
  else title = (menuIcons[key.replace("manual-", "")] || {}).label || key;

  $("#unitFilterWrap").toggle(key === "standar" || key === "indikator");

  const rows = buildRowsFor(key);

  $("#detailTitle").text(title);

  if (dtDetail) {
    dtDetail.destroy();
    $("#tableDetail tbody").empty();
  }
  $("#tableDetail tbody").html(renderRowsHtml(rows));
  dtDetail = $("#tableDetail").DataTable({
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    order: [[2, "asc"]],
    language: {
      search: "Cari:",
      lengthMenu: "Tampilkan _MENU_ data",
      info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
      infoEmpty: "Tidak ada data",
      zeroRecords: "Data tidak ditemukan",
      paginate: { first: "Awal", last: "Akhir", next: "›", previous: "‹" },
    },
  });

  $("#menuGrid").hide();
  $("#detailView").show();
});

$("#btnKembaliGrid").on("click", function () {
  $("#detailView").hide();
  $("#menuGrid").show();
});

$(document).on("click", ".btn-tambah-risiko", function () {
  modalCtx = { jenis: $(this).data("jenis"), id: $(this).data("id") };
  $("#modalSumberLabel").text($(this).data("label"));
  $("#inputDeskripsiRisiko").val("");
  $("#inputKategoriRisiko").val("");
  new bootstrap.Modal(document.getElementById("modalTambahRisiko")).show();
});

$("#btnSimpanRisiko").on("click", function () {
  const deskripsi = $("#inputDeskripsiRisiko").val().trim();
  if (!deskripsi) {
    Swal.fire("Belum Lengkap", "Deskripsi Risiko wajib diisi.", "warning");
    return;
  }

  $.post(api + "?action=tambah_risiko", {
    unit_id: $("#risikoUnitSelector").val() || 0,
    sumber_jenis: modalCtx.jenis,
    sumber_id: modalCtx.id,
    deskripsi_risiko: deskripsi,
    kategori_risiko: $("#inputKategoriRisiko").val(),
  }, function (res) {
    if (res.success) {
      bootstrap.Modal.getInstance(document.getElementById("modalTambahRisiko")).hide();
      Swal.fire({ icon: "success", title: "Tersimpan", text: res.message, timer: 1500, showConfirmButton: false });
      loadData();
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

loadData();
loadKriteria();

});
</script>