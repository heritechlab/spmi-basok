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

$namaUser = $_SESSION['full_name'] ?? 'Pengguna';
$jam = (int) date('G');
$sapaan = $jam < 11 ? 'Selamat Pagi' : ($jam < 15 ? 'Selamat Siang' : ($jam < 18 ? 'Selamat Sore' : 'Selamat Malam'));

$ringkasan = $conn->query("
    SELECT
        SUM(CASE WHEN status = 'Teranalisis' THEN 1 ELSE 0 END) AS butuh_mitigasi,
        SUM(CASE WHEN status = 'Termitigasi' THEN 1 ELSE 0 END) AS sudah_termitigasi,
        SUM(CASE WHEN level_risiko IN ('Ekstrem','Tinggi') AND status != 'Termitigasi' THEN 1 ELSE 0 END) AS prioritas_tinggi
    FROM risk_register
    WHERE status IN ('Teranalisis','Termitigasi')
")->fetch_assoc();

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<style>
    #risikoPage {
        font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Arial, sans-serif;
        background: #f7f7fa;
        min-height: 100vh;
        margin: -1.5rem -1.5rem -1.5rem -1.5rem;
        padding: 1.75rem 2rem;
    }
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

    #risikoPage .page-head { display: flex; align-items: center; gap: 16px; margin-bottom: 22px; }
    #risikoPage .icon-chip {
        width: 46px; height: 46px; border-radius: 12px;
        background: linear-gradient(135deg, #dc2626, #ea580c);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 20px; box-shadow: 0 6px 16px rgba(220,38,38,0.25); flex-shrink: 0;
    }
    #risikoPage .eyebrow { font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: #b91c1c; margin-bottom: 4px; }
    #risikoPage .page-head h1 { font-size: 22px; font-weight: 700; color: #111827; margin: 0; }
    #risikoPage .page-head p { font-size: 12.5px; color: #6b7280; margin: 4px 0 0; }

    #risikoPage .section-card { background: #fff; border: 1px solid #eceef1; border-radius: 14px; padding: 14px; box-shadow: 0 1px 2px rgba(16,24,40,0.04); }
    #risikoPage table.mitigasi-table { width: 100%; font-size: 11.5px; }
    #risikoPage table.mitigasi-table th { background: #4c1d95; color: #fff; white-space: nowrap; font-weight: 600; font-size: 10.5px; text-transform: uppercase; letter-spacing: .3px; }
    #risikoPage table.mitigasi-table td { vertical-align: middle; }
    #risikoPage .level-badge { font-size: 10px; font-weight: 800; padding: 3px 10px; border-radius: 20px; color: #fff; white-space: nowrap; display: inline-block; }
    #risikoPage table.mitigasi-table td:nth-child(3),
    #risikoPage table.mitigasi-table td:nth-child(4) { text-align: center; }
    #risikoPage .desc-cell { max-width: 220px; white-space: normal; }
    #risikoPage .sel-inline {
        font-size: 11px; padding: 4px 26px 4px 8px; border-radius: 6px; border: 1.5px solid #e5e7eb;
        min-width: 110px; background-position: right 8px center; background-size: 12px;
    }

    #risikoPage .aksi-group { display: flex; gap: 6px; }
    #risikoPage .btn-icon-aksi {
        width: 30px; height: 30px; border-radius: 9px; border: none;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 14px; cursor: pointer; transition: transform .12s, box-shadow .12s;
        background: #f3f4f6; color: #6b7280;
    }
    #risikoPage .btn-icon-aksi:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.12); }
    #risikoPage .btn-icon-aksi.btn-tambah-mitigasi { background: linear-gradient(135deg, #dc2626, #ea580c); color: #fff; }
    #risikoPage .btn-icon-aksi.aksi-success { background: linear-gradient(135deg, #16a34a, #22c55e); color: #fff; }
    #risikoPage .btn-icon-aksi.aksi-done { background: #dcfce7; color: #16a34a; cursor: default; }
    #risikoPage .btn-icon-aksi.aksi-done:hover { transform: none; box-shadow: none; }
    #risikoPage .btn-icon-aksi.aksi-link { background: linear-gradient(135deg, #2563eb, #4c1d95); color: #fff; }
    #risikoPage .link-badge { display: inline-block; font-size: 9.5px; background: #f3f4f6; color: #4b5563; border-radius: 6px; padding: 2px 7px; margin: 2px 3px 0 0; }
    #risikoPage .linked-item {
        background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534;
        border-radius: 8px; padding: 8px 12px; margin-bottom: 6px; font-size: 12px; font-weight: 600;
    }
    #risikoPage .linked-item i { color: #16a34a; margin-right: 6px; }
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
            Prioritas Tinggi: <strong><?= (int) ($ringkasan['prioritas_tinggi'] ?? 0) ?></strong> &middot;
            Butuh Mitigasi: <strong><?= (int) ($ringkasan['butuh_mitigasi'] ?? 0) ?></strong> &middot;
            Termitigasi: <strong><?= (int) ($ringkasan['sudah_termitigasi'] ?? 0) ?></strong>
        </div>
        <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Admin" class="standard-summary-hero">
    </div>

    <div class="page-head">
        <div class="icon-chip"><i class="bi bi-clipboard2-pulse"></i></div>
        <div>
            <div class="eyebrow">SPMI Berbasis Risiko</div>
            <h1>Mitigasi Risiko</h1>
            <p>Rencana Perbaikan (Corrective Action Plan) untuk risiko yang sudah dianalisis</p>
        </div>
    </div>

    <div class="section-card table-responsive">
        <table class="table table-bordered table-hover mitigasi-table" id="tableMitigasi">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Deskripsi Risiko</th>
                    <th>Level Risiko</th>
                    <th>Tingkat Dampak</th>
                    <th>Narasi Dampak</th>
                    <th>Tindakan Mitigasi</th>
                    <th>Akar Masalah</th>
                    <th>PIC</th>
                    <th>Target</th>
                    <th>Status</th>
                    <th>Efektivitas</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>

<div class="modal fade" id="modalTautRtlPtp" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Taut Risiko ke RTL &amp; PTP</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-4">
          <div class="fw-semibold small mb-2"><i class="bi bi-diagram-3"></i> RTL (Rencana Tindak Lanjut) Tertaut</div>
          <div id="linkedRtlList" class="mb-2"></div>
          <label class="form-label small">Taut ke RTL Baru</label>
          <div class="d-flex gap-2">
            <select class="form-select form-select-sm" id="selectRtlOption"><option value="">-- Pilih RTL --</option></select>
            <button class="btn btn-sm btn-primary" id="btnTautRtl">Taut</button>
          </div>
        </div>
        <div id="ptpSection">
          <div class="fw-semibold small mb-2"><i class="bi bi-arrow-up-circle"></i> PTP (Peningkatan) Tertaut</div>
          <div id="ptpBelumTermitigasi" class="alert alert-warning small py-2 px-3" style="display:none;">
            <i class="bi bi-exclamation-triangle"></i> PTP hanya bisa ditaut kalau Risiko ini sudah berstatus <strong>Termitigasi</strong>. Untuk risiko yang masih aktif, gunakan RTL di atas.
          </div>
          <div id="ptpAktifWrap">
            <div id="linkedPtpList" class="mb-2"></div>
            <label class="form-label small">Taut ke PTP Baru</label>
            <div class="d-flex gap-2">
              <select class="form-select form-select-sm" id="selectPtpOption"><option value="">-- Pilih PTP --</option></select>
              <button class="btn btn-sm btn-primary" id="btnTautPtp">Taut</button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalTambahMitigasi" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Tambah Rencana Perbaikan (Corrective Action Plan)</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label small">Akar Masalah (Root Cause)</label>
        <textarea class="form-control mb-3" id="inputAkarMasalah" rows="2" placeholder="Penyebab utama risiko ini muncul"></textarea>
        <label class="form-label small">Tindakan Mitigasi</label>
        <textarea class="form-control mb-3" id="inputTindakanMitigasi" rows="3"></textarea>
        <label class="form-label small">Indikator Keberhasilan</label>
        <textarea class="form-control mb-3" id="inputIndikatorKeberhasilan" rows="2" placeholder="Bagaimana kita tahu tindakan ini berhasil?"></textarea>
        <label class="form-label small">PIC</label>
        <select class="form-select mb-3" id="inputPicMitigasi"><option value="">-- Pilih PIC --</option></select>
        <label class="form-label small">Target Tanggal <span class="text-muted">(kosongkan untuk otomatis sesuai tenggat Level Risiko)</span></label>
        <input type="date" class="form-control" id="inputTargetTanggal">
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-danger btn-sm" id="btnSimpanMitigasi">Simpan</button>
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
let mitigasiRiskId = null;
let dtTable = null;
const levelColor = { Rendah: "#22c55e", Sedang: "#eab308", Tinggi: "#f97316", Ekstrem: "#dc2626" };

function loadUsers() {
  $.getJSON(api, { action: "user_list" }, function (res) {
    if (!res.success) return;
    let html = '<option value="">-- Pilih PIC --</option>';
    res.data.forEach(function (u) { html += `<option value="${u.id}">${escapeHtml(u.full_name)}</option>`; });
    $("#inputPicMitigasi").html(html);
  });
}

function loadData() {
  $.getJSON(api, { action: "mitigasi_data" }, function (res) {
    if (!res.success) return;
    renderTable(flattenRows(res.data.risks));
  });
}

function flattenRows(risks) {
  const rows = [];
  risks.forEach(function (r) {
    if (!r.mitigasi || !r.mitigasi.length) {
      rows.push({ risk: r, plan: null });
    } else {
      r.mitigasi.forEach(function (m) {
        rows.push({ risk: r, plan: m });
      });
    }
  });
  return rows;
}

function renderTable(rows) {
  if (dtTable) {
    dtTable.destroy();
    $("#tableMitigasi tbody").empty();
  }

  const tbody = $("#tableMitigasi tbody");
  let html = "";

  rows.forEach(function (row) {
    const r = row.risk, m = row.plan;
    const color = levelColor[r.level_risiko] || "#94a3b8";

    const impactLabel = { 1: "Rendah", 2: "Sedang", 3: "Tinggi", 4: "Sangat Tinggi" };
    const impactColor = { 1: "#22c55e", 2: "#eab308", 3: "#f97316", 4: "#dc2626" };

    html += `<tr data-risk-id="${r.id}" data-risk-status="${r.status}" ${m ? `data-plan-id="${m.id}"` : ""}>
      <td>${escapeHtml(r.kategori_risiko || "-")}</td>
      <td class="desc-cell">${escapeHtml(r.deskripsi_risiko)}</td>
      <td><span class="level-badge" style="background:${color};">${escapeHtml(r.level_risiko || "-")}</span></td>
      <td>${r.impact ? `<span class="level-badge" style="background:${impactColor[r.impact]};">${impactLabel[r.impact]}</span>` : "-"}</td>
      <td class="desc-cell">${r.deskripsi_dampak ? escapeHtml(r.deskripsi_dampak) : '<span class="text-muted">-</span>'}</td>
      <td class="desc-cell">${m ? escapeHtml(m.tindakan_mitigasi) : '<span class="text-muted">Belum ada</span>'}</td>
      <td class="desc-cell">${m && m.akar_masalah ? escapeHtml(m.akar_masalah) : "-"}</td>
      <td>${m ? escapeHtml(m.pic_name || "-") : "-"}</td>
      <td>${m ? escapeHtml(m.target_tanggal || "-") : "-"}</td>
      <td>${m ? statusSelect(m) : "-"}</td>
      <td>${m ? efektivitasSelect(m) : "-"}</td>
      <td>
        <div class="aksi-group">
          <button class="btn-icon-aksi btn-tambah-mitigasi" data-id="${r.id}" title="${m ? "Tambah Rencana Lagi" : "Tambah Rencana Mitigasi"}">
            <i class="bi bi-plus-lg"></i>
          </button>
          ${r.status !== "Termitigasi"
            ? `<button class="btn-icon-aksi aksi-success btn-tandai-termitigasi" data-id="${r.id}" title="Tandai Termitigasi"><i class="bi bi-shield-check"></i></button>`
            : `<span class="btn-icon-aksi aksi-done" title="Sudah Termitigasi"><i class="bi bi-patch-check-fill"></i></span>`
          }
          ${r.sumber_jenis === "temuan_audit"
            ? `<button class="btn-icon-aksi aksi-link btn-taut-rtl-ptp" data-id="${r.id}" title="Taut ke RTL / PTP"><i class="bi bi-link-45deg"></i></button>`
            : ""
          }
        </div>
      </td>
    </tr>`;
  });

  tbody.html(html);

  dtTable = $("#tableMitigasi").DataTable({
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
}

function statusSelect(m) {
  return `
    <select class="form-select sel-inline sel-status-mitigasi" data-plan-id="${m.id}">
      <option value="Belum" ${m.status === "Belum" ? "selected" : ""}>Belum</option>
      <option value="Proses" ${m.status === "Proses" ? "selected" : ""}>Proses</option>
      <option value="Selesai" ${m.status === "Selesai" ? "selected" : ""}>Selesai</option>
    </select>
  `;
}

function efektivitasSelect(m) {
  const opts = ["Belum Dinilai", "Efektif", "Sebagian Efektif", "Tidak Efektif"];
  let html = `<select class="form-select sel-inline sel-efektivitas" data-plan-id="${m.id}">`;
  opts.forEach(function (o) {
    html += `<option value="${o}" ${m.status_efektivitas === o ? "selected" : ""}>${o}</option>`;
  });
  html += "</select>";
  return html;
}

$(document).on("click", ".btn-tambah-mitigasi", function () {
  mitigasiRiskId = $(this).data("id");
  $("#inputAkarMasalah, #inputTindakanMitigasi, #inputIndikatorKeberhasilan, #inputTargetTanggal").val("");
  $("#inputPicMitigasi").val("");
  new bootstrap.Modal(document.getElementById("modalTambahMitigasi")).show();
});

$("#btnSimpanMitigasi").on("click", function () {
  const tindakan = $("#inputTindakanMitigasi").val().trim();
  if (!tindakan) {
    Swal.fire("Belum Lengkap", "Tindakan Mitigasi wajib diisi.", "warning");
    return;
  }
  $.post(api + "?action=tambah_mitigasi", {
    risk_id: mitigasiRiskId,
    tindakan_mitigasi: tindakan,
    akar_masalah: $("#inputAkarMasalah").val().trim(),
    indikator_keberhasilan: $("#inputIndikatorKeberhasilan").val().trim(),
    pic_user_id: $("#inputPicMitigasi").val(),
    target_tanggal: $("#inputTargetTanggal").val(),
  }, function (res) {
    if (res.success) {
      bootstrap.Modal.getInstance(document.getElementById("modalTambahMitigasi")).hide();
      Swal.fire({ icon: "success", title: "Tersimpan", text: res.message, timer: 2000, showConfirmButton: false });
      loadData();
    } else {
      Swal.fire("Gagal", res.message, "error");
    }
  }, "json");
});

$(document).on("change", ".sel-status-mitigasi", function () {
  const planId = $(this).data("plan-id");
  const status = $(this).val();
  $.post(api + "?action=update_status_mitigasi", { plan_id: planId, status: status }, function (res) {
    if (!res.success) Swal.fire("Gagal", res.message, "error");
  }, "json");
});

$(document).on("change", ".sel-efektivitas", function () {
  const planId = $(this).data("plan-id");
  const status = $(this).val();
  $.post(api + "?action=update_status_efektivitas", { plan_id: planId, status: status }, function (res) {
    if (!res.success) Swal.fire("Gagal", res.message, "error");
  }, "json");
});

let tautRiskId = null;

let tautRiskStatus = null;

$(document).on("click", ".btn-taut-rtl-ptp", function () {
  tautRiskId = $(this).data("id");
  tautRiskStatus = $(this).closest("tr").data("risk-status");
  loadLinkOptions();

  const sudahTermitigasi = tautRiskStatus === "Termitigasi";
  $("#ptpAktifWrap").toggle(sudahTermitigasi);
  $("#ptpBelumTermitigasi").toggle(!sudahTermitigasi);

  new bootstrap.Modal(document.getElementById("modalTautRtlPtp")).show();
});

function loadLinkOptions() {
  $.getJSON(api, { action: "link_options", risk_id: tautRiskId }, function (res) {
    if (!res.success) return;

    let rtlOptHtml = '<option value="">-- Pilih RTL --</option>';
    res.data.rtl_options.forEach(function (o) {
      rtlOptHtml += `<option value="${o.id}">${escapeHtml(o.activity)} (${o.status})</option>`;
    });
    $("#selectRtlOption").html(rtlOptHtml);

    let ptpOptHtml = '<option value="">-- Pilih PTP --</option>';
    res.data.ptp_options.forEach(function (o) {
      ptpOptHtml += `<option value="${o.id}">${escapeHtml(o.description || o.new_indicator || "PTP #" + o.id)} (${o.status})</option>`;
    });
    $("#selectPtpOption").html(ptpOptHtml);

    let linkedRtlHtml = res.data.linked_rtl.length
      ? res.data.linked_rtl.map((r) => `<div class="linked-item"><i class="bi bi-check-circle-fill"></i> ${escapeHtml(r.activity)} <span class="text-muted">(${r.status})</span></div>`).join("")
      : '<div class="text-muted small fst-italic">Belum ada RTL yang ditaut ke Risiko ini.</div>';
    $("#linkedRtlList").html(linkedRtlHtml);

    let linkedPtpHtml = res.data.linked_ptp.length
      ? res.data.linked_ptp.map((p) => `<div class="linked-item"><i class="bi bi-check-circle-fill"></i> ${escapeHtml(p.description || "PTP #" + p.id)} <span class="text-muted">(${p.status})</span></div>`).join("")
      : '<div class="text-muted small fst-italic">Belum ada PTP yang ditaut ke Risiko ini.</div>';
    $("#linkedPtpList").html(linkedPtpHtml);
  });
}

$("#btnTautRtl").on("click", function () {
  const rtlId = $("#selectRtlOption").val();
  if (!rtlId) {
    Swal.fire("Belum Dipilih", "Pilih dulu RTL dari daftar.", "warning");
    return;
  }
  $.post(api + "?action=tautkan_rtl", { risk_id: tautRiskId, rtl_id: rtlId }, function (res) {
    if (res.success) {
      loadLinkOptions();
      Swal.fire({ icon: "success", title: "Berhasil Ditaut", text: "Lihat daftar \"RTL Tertaut\" di atas.", timer: 1800, showConfirmButton: false });
    } else {
      Swal.fire("Gagal", res.message, "error");
    }
  }, "json");
});

$("#btnTautPtp").on("click", function () {
  const ptpId = $("#selectPtpOption").val();
  if (!ptpId) {
    Swal.fire("Belum Dipilih", "Pilih dulu PTP dari daftar.", "warning");
    return;
  }
  $.post(api + "?action=tautkan_ptp", { risk_id: tautRiskId, ptp_id: ptpId }, function (res) {
    if (res.success) {
      loadLinkOptions();
      Swal.fire({ icon: "success", title: "Berhasil Ditaut", text: "Lihat daftar \"PTP Tertaut\" di atas.", timer: 1800, showConfirmButton: false });
    } else {
      Swal.fire("Gagal", res.message, "error");
    }
  }, "json");
});

$(document).on("click", ".btn-tandai-termitigasi", function () {
  const id = $(this).data("id");
  Swal.fire({
    icon: "question", title: "Tandai Risiko ini Termitigasi?",
    showCancelButton: true, confirmButtonText: "Ya, Tandai",
  }).then(function (result) {
    if (!result.isConfirmed) return;
    $.post(api + "?action=tandai_termitigasi", { risk_id: id }, function (res) {
      if (res.success) loadData();
      else Swal.fire("Gagal", res.message, "error");
    }, "json");
  });
});

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

loadUsers();
loadData();

$(document).on("mouseenter", "[title]", function () {
  if (!bootstrap.Tooltip.getInstance(this)) {
    new bootstrap.Tooltip(this, { placement: "top", trigger: "hover" });
  }
});

});
</script>