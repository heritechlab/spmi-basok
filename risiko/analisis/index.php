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
        SUM(CASE WHEN status = 'Teridentifikasi' THEN 1 ELSE 0 END) AS menunggu_analisis,
        SUM(CASE WHEN status IN ('Teranalisis','Termitigasi') THEN 1 ELSE 0 END) AS sudah_dianalisis
    FROM risk_register
")->fetch_assoc();

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

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

    #risikoPage .page-head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 24px;
    }
    #risikoPage .page-head .eyebrow {
        font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase;
        color: #b91c1c; margin-bottom: 4px;
    }
    #risikoPage .page-head h1 {
        font-size: 22px; font-weight: 700; color: #111827; margin: 0;
    }
    #risikoPage .page-head p {
        font-size: 12.5px; color: #6b7280; margin: 4px 0 0;
    }
    #risikoPage .icon-chip {
        width: 46px; height: 46px; border-radius: 12px;
        background: linear-gradient(135deg, #dc2626, #ea580c);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 20px; box-shadow: 0 6px 16px rgba(220,38,38,0.25);
    }

    #risikoPage .section-card {
        background: #fff; border: 1px solid #eceef1; border-radius: 14px;
        padding: 20px 22px; margin-bottom: 20px;
        box-shadow: 0 1px 2px rgba(16,24,40,0.04);
    }
    #risikoPage .section-title {
        font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 14px;
        display: flex; align-items: center; gap: 8px;
    }
    #risikoPage .section-title i { color: #b91c1c; }

    /* Matrix */
    #risikoPage table.matrix-table { border-collapse: separate; border-spacing: 4px; margin: 0 auto; }
    #risikoPage table.matrix-table td, #risikoPage table.matrix-table th {
        padding: 12px 8px; text-align: center; font-size: 11.5px; width: 76px; border-radius: 8px;
    }
    #risikoPage table.matrix-table th { background: #111827; color: #fff; font-size: 10px; font-weight: 600; }
    #risikoPage .cell-rendah { background: #dcfce7; color: #166534; font-weight: 700; }
    #risikoPage .cell-sedang { background: #fef9c3; color: #854d0e; font-weight: 700; }
    #risikoPage .cell-tinggi { background: #ffedd5; color: #9a3412; font-weight: 700; }
    #risikoPage .cell-ekstrem { background: #fee2e2; color: #991b1b; font-weight: 700; }

    /* Risk cards */
    #risikoPage .risk-card {
        background: #fff; border: 1px solid #eceef1; border-radius: 14px;
        padding: 18px 20px; margin-bottom: 14px; transition: box-shadow .15s, border-color .15s;
    }
    #risikoPage .risk-card:hover { box-shadow: 0 4px 14px rgba(16,24,40,0.07); border-color: #e5e7eb; }
    #risikoPage .risk-desc { font-size: 13.5px; font-weight: 600; color: #111827; margin-bottom: 3px; }
    #risikoPage .risk-meta { font-size: 10.5px; color: #9ca3af; margin-bottom: 14px; display: flex; gap: 6px; align-items: center; }
    #risikoPage .risk-meta .dot { width: 3px; height: 3px; border-radius: 50%; background: #d1d5db; }
    #risikoPage .meta-chip {
        display: inline-block; background: #f3f4f6; color: #4b5563; border-radius: 6px;
        padding: 2px 8px; font-size: 10px; font-weight: 600;
    }

    #risikoPage .field-label {
        font-size: 10.5px; font-weight: 700; color: #374151; margin-bottom: 5px;
        text-transform: uppercase; letter-spacing: .3px;
    }
    #risikoPage .form-select-sm, #risikoPage textarea {
        border: 1.5px solid #e5e7eb; border-radius: 9px; font-size: 12px;
    }
    #risikoPage .form-select-sm:focus, #risikoPage textarea:focus {
        border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,0.08);
    }

    #risikoPage .btn-analyze {
        background: linear-gradient(135deg, #dc2626, #ea580c); border: none; color: #fff;
        font-size: 12px; font-weight: 700; padding: 8px 20px; border-radius: 9px;
        box-shadow: 0 4px 10px rgba(220,38,38,0.25); transition: transform .12s;
    }
    #risikoPage .btn-analyze:hover { transform: translateY(-1px); color: #fff; }

    #risikoPage .empty-state {
        text-align: center; padding: 50px 20px; color: #9ca3af;
    }
    #risikoPage .empty-state i { font-size: 34px; margin-bottom: 10px; display: block; color: #d1d5db; }
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
            Menunggu Analisis: <strong><?= (int) ($ringkasan['menunggu_analisis'] ?? 0) ?></strong> &middot;
            Sudah Dianalisis: <strong><?= (int) ($ringkasan['sudah_dianalisis'] ?? 0) ?></strong>
        </div>
        <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Admin" class="standard-summary-hero">
    </div>

    <div class="page-head">
        <div style="display:flex; align-items:center; gap:16px;">
            <div class="icon-chip"><i class="bi bi-graph-up-arrow"></i></div>
            <div>
                <div class="eyebrow">SPMI Berbasis Risiko</div>
                <h1>Analisis Risiko</h1>
                <p>Nilai Likelihood, Impact, Detectability, dan Control Readiness — sistem menghitung Level Risiko secara otomatis</p>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title"><i class="bi bi-grid-3x3-gap-fill"></i> Matriks Risiko (yang sudah dianalisis)</div>
        <div id="matrixWrap" class="text-center"></div>
    </div>

    <div class="section-title" style="padding-left:2px;"><i class="bi bi-hourglass-split"></i> Risiko Menunggu Analisis</div>
    <div id="listRisiko"></div>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script>
document.addEventListener("DOMContentLoaded", function () {

const api = SIQUA.BASE_URL + "risiko/api.php";

function loadData() {
  $.getJSON(api, { action: "analisis_data" }, function (res) {
    if (!res.success) return;
    renderMatrix(res.data.matrix);
    renderList(res.data.risks);
  });
}

function renderMatrix(matrix) {
  const levelClass = { Rendah: "cell-rendah", Sedang: "cell-sedang", Tinggi: "cell-tinggi", Ekstrem: "cell-ekstrem" };
  function levelOf(l, i) {
    if (l >= 4 && i >= 3) return "Ekstrem";
    if (l >= 3 && i >= 3) return l == 4 ? "Ekstrem" : "Tinggi";
    const skor = l * i;
    if (skor >= 12) return "Ekstrem";
    if (skor >= 8) return "Tinggi";
    if (skor >= 4) return "Sedang";
    return "Rendah";
  }

  let html = '<table class="matrix-table"><tr><th></th>';
  for (let i = 1; i <= 4; i++) html += `<th>Dampak ${i}</th>`;
  html += "</tr>";
  for (let l = 4; l >= 1; l--) {
    html += `<tr><th>Kemungkinan ${l}</th>`;
    for (let i = 1; i <= 4; i++) {
      const lvl = levelOf(l, i);
      const count = (matrix[l] && matrix[l][i]) ? matrix[l][i] : 0;
      html += `<td class="${levelClass[lvl]}">${count}</td>`;
    }
    html += "</tr>";
  }
  html += "</table>";
  $("#matrixWrap").html(html);
}

function renderList(risks) {
  if (!risks.length) {
    $("#listRisiko").html(`
      <div class="section-card empty-state">
        <i class="bi bi-check2-circle"></i>
        Semua Risiko sudah dianalisis. Tidak ada yang menunggu.
      </div>
    `);
    return;
  }
  let html = "";
  risks.forEach(function (r) {
    html += `
      <div class="risk-card">
        <div class="risk-desc">${escapeHtml(r.deskripsi_risiko)}</div>
        <div class="risk-meta">
          <span class="meta-chip">${escapeHtml(r.kategori_risiko || "Tanpa Kategori")}</span>
          <span class="dot"></span>
          <span>${escapeHtml(r.unit_name || "Semua Unit")}</span>
          <span class="dot"></span>
          <span>Sumber: ${escapeHtml(r.sumber_jenis)}</span>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-6 col-lg-3">
            <div class="field-label">Likelihood</div>
            <select class="form-select form-select-sm sel-likelihood" data-id="${r.id}">
              <option value="">Pilih</option>
              <option value="1">1 · Sangat Jarang</option>
              <option value="2">2 · Jarang</option>
              <option value="3">3 · Mungkin</option>
              <option value="4">4 · Sangat Mungkin</option>
            </select>
          </div>
          <div class="col-6 col-lg-3">
            <div class="field-label">Impact</div>
            <select class="form-select form-select-sm sel-impact" data-id="${r.id}">
              <option value="">Pilih</option>
              <option value="1">1 · Rendah</option>
              <option value="2">2 · Sedang</option>
              <option value="3">3 · Tinggi</option>
              <option value="4">4 · Sangat Tinggi</option>
            </select>
          </div>
          <div class="col-6 col-lg-3">
            <div class="field-label">Detectability</div>
            <select class="form-select form-select-sm sel-detectability" data-id="${r.id}">
              <option value="">Pilih</option>
              <option value="4">4 · Sangat Mudah</option>
              <option value="3">3 · Mudah</option>
              <option value="2">2 · Sulit</option>
              <option value="1">1 · Sangat Sulit</option>
            </select>
          </div>
          <div class="col-6 col-lg-3">
            <div class="field-label">Control Readiness</div>
            <select class="form-select form-select-sm sel-control" data-id="${r.id}">
              <option value="">Pilih</option>
              <option value="4">4 · Sangat Siap</option>
              <option value="3">3 · Siap</option>
              <option value="2">2 · Belum Siap</option>
              <option value="1">1 · Tidak Ada</option>
            </select>
          </div>
        </div>

        <div class="mb-3">
          <div class="field-label">Narasi Dampak</div>
          <textarea class="form-control form-control-sm inp-deskripsi-dampak" rows="2" placeholder="Jelaskan dampak konkret yang mungkin terjadi bila risiko ini terwujud..."></textarea>
        </div>

        <button type="button" class="btn btn-analyze btn-simpan-analisis" data-id="${r.id}">
          <i class="bi bi-lightning-charge-fill"></i> Simpan Analisis
        </button>
      </div>
    `;
  });
  $("#listRisiko").html(html);
}

$(document).on("click", ".btn-simpan-analisis", function () {
  const $card = $(this).closest(".risk-card");
  const id = $(this).data("id");
  const likelihood = $card.find(".sel-likelihood").val();
  const impact = $card.find(".sel-impact").val();
  const detectability = $card.find(".sel-detectability").val();
  const control = $card.find(".sel-control").val();
  const deskripsiDampak = $card.find(".inp-deskripsi-dampak").val().trim();

  if (!likelihood || !impact) {
    Swal.fire("Belum Lengkap", "Pilih Likelihood dan Impact terlebih dahulu.", "warning");
    return;
  }

  $.post(api + "?action=simpan_analisis", {
    risk_id: id, likelihood: likelihood, impact: impact,
    detectability: detectability, control_readiness: control,
    deskripsi_dampak: deskripsiDampak,
  }, function (res) {
    if (res.success) {
      Swal.fire({ icon: "success", title: "Level Risiko: " + res.data.level_risiko, timer: 1800, showConfirmButton: false });
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

});
</script>