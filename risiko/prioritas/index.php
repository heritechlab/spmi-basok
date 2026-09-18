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
        SUM(CASE WHEN level_risiko = 'Ekstrem' THEN 1 ELSE 0 END) AS ekstrem,
        SUM(CASE WHEN level_risiko = 'Tinggi' THEN 1 ELSE 0 END) AS tinggi
    FROM risk_register
    WHERE status IN ('Teranalisis','Termitigasi')
")->fetch_assoc();

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<style>
    #risikoPage {
        font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Arial, sans-serif;
        background: #f7f7fa; min-height: 100vh;
        margin: -1.5rem -1.5rem -1.5rem -1.5rem; padding: 1.75rem 2rem;
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

    #risikoPage .page-head { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
    #risikoPage .icon-chip {
        width: 46px; height: 46px; border-radius: 12px;
        background: linear-gradient(135deg, #dc2626, #ea580c);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 20px; box-shadow: 0 6px 16px rgba(220,38,38,0.25); flex-shrink: 0;
    }
    #risikoPage .eyebrow { font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: #b91c1c; margin-bottom: 4px; }
    #risikoPage .page-head h1 { font-size: 22px; font-weight: 700; color: #111827; margin: 0; }
    #risikoPage .page-head p { font-size: 12.5px; color: #6b7280; margin: 4px 0 0; }

    #risikoPage .alert-note {
        background: #fff; border: 1px solid #eceef1; border-left: 4px solid #dc2626;
        border-radius: 10px; padding: 12px 16px; font-size: 12px; color: #374151; margin-bottom: 18px;
    }

    #risikoPage .section-card { background: #fff; border: 1px solid #eceef1; border-radius: 14px; padding: 14px; box-shadow: 0 1px 2px rgba(16,24,40,0.04); }
    #risikoPage table.prioritas-table { width: 100%; font-size: 11.5px; }
    #risikoPage table.prioritas-table th { background: #4c1d95; color: #fff; white-space: nowrap; font-weight: 600; font-size: 10.5px; text-transform: uppercase; letter-spacing: .3px; text-align: center; }
    #risikoPage table.prioritas-table td { vertical-align: middle; }
    #risikoPage .badge-prioritas { font-size: 10px; font-weight: 800; padding: 4px 12px; border-radius: 20px; color: #fff; white-space: nowrap; }
    #risikoPage .count-pill { display: inline-block; min-width: 24px; text-align: center; border-radius: 10px; padding: 2px 8px; font-size: 11px; font-weight: 700; }
</style>

<div id="risikoPage">

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
            Unit Berisiko Ekstrem: <strong><?= (int) ($ringkasan['ekstrem'] ?? 0) ?></strong> &middot;
            Unit Berisiko Tinggi: <strong><?= (int) ($ringkasan['tinggi'] ?? 0) ?></strong>
        </div>
        <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Admin" class="standard-summary-hero">
    </div>

    <div class="page-head">
        <div class="icon-chip"><i class="bi bi-list-ol"></i></div>
        <div>
            <div class="eyebrow">SPMI Berbasis Risiko</div>
            <h1>Prioritas Audit Berbasis Risiko</h1>
            <p>Pasal 14 — Risk Register menjadi dasar penetapan prioritas Program Audit Tahunan (PAT)</p>
        </div>
    </div>

    <div class="alert-note">
        <strong>Ketentuan:</strong> Unit <strong>Prioritas Tinggi</strong> wajib diaudit minimal 1×/tahun (Pasal 14 ayat 3).
        Unit <strong>Prioritas Rendah</strong> boleh cukup diaudit lewat dokumen saja (Pasal 14 ayat 4).
    </div>

    <div class="section-card table-responsive">
        <table class="table table-bordered table-hover prioritas-table" id="tablePrioritas">
            <thead>
                <tr>
                    <th>Unit / Program Studi</th>
                    <th>Ekstrem</th>
                    <th>Tinggi</th>
                    <th>Sedang</th>
                    <th>Rendah</th>
                    <th>Prioritas Audit</th>
                    <th>Ketentuan</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {

const api = SIQUA.BASE_URL + "risiko/api.php";
const prioritasColor = { "Tinggi": "#dc2626", "Sedang": "#eab308", "Rendah": "#22c55e", "Belum Ada Data": "#94a3b8" };
let dtTable = null;

function loadData() {
  $.getJSON(api, { action: "prioritas_audit" }, function (res) {
    if (!res.success) return;
    renderTable(res.data);
  });
}

function renderTable(units) {
  if (dtTable) {
    dtTable.destroy();
    $("#tablePrioritas tbody").empty();
  }

  let html = "";
  units.forEach(function (u) {
    html += `
      <tr>
        <td><strong>${escapeHtml(u.code || "")}</strong> - ${escapeHtml(u.name)}</td>
        <td class="text-center">${countPill(u.jumlah_ekstrem, "#dc2626")}</td>
        <td class="text-center">${countPill(u.jumlah_tinggi, "#f97316")}</td>
        <td class="text-center">${countPill(u.jumlah_sedang, "#eab308")}</td>
        <td class="text-center">${countPill(u.jumlah_rendah, "#22c55e")}</td>
        <td class="text-center"><span class="badge-prioritas" style="background:${prioritasColor[u.prioritas] || "#888"};">${escapeHtml(u.prioritas)}</span></td>
        <td class="text-muted">${escapeHtml(u.ketentuan)}</td>
      </tr>
    `;
  });
  $("#tablePrioritas tbody").html(html);

  dtTable = $("#tablePrioritas").DataTable({
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    order: [[1, "desc"]],
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

function countPill(count, color) {
  count = parseInt(count) || 0;
  if (count === 0) return '<span class="text-muted">0</span>';
  return `<span class="count-pill" style="background:${color}22; color:${color};">${count}</span>`;
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

loadData();

});
</script>