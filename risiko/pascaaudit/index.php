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

$namaUser = $_SESSION['full_name'] ?? 'Pengguna';
$jam = (int) date('G');
$sapaan = $jam < 11 ? 'Selamat Pagi' : ($jam < 15 ? 'Selamat Siang' : ($jam < 18 ? 'Selamat Sore' : 'Selamat Malam'));

$ringkasan = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM audit_assignments WHERE status = 'Selesai') AS total_selesai,
        (SELECT COUNT(*) FROM audit_assignments WHERE status = 'Selesai' AND pascaaudit_disampaikan = 1) AS sudah_disampaikan
")->fetch_assoc();

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

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

    #risikoPage .section-card { background: #fff; border: 1px solid #eceef1; border-radius: 14px; padding: 20px 22px; margin-bottom: 20px; box-shadow: 0 1px 2px rgba(16,24,40,0.04); }
    #risikoPage .field-label { font-size: 10.5px; font-weight: 700; color: #374151; margin-bottom: 5px; }
    #risikoPage .form-select, #risikoPage .form-control {
        border: 1.5px solid #e5e7eb; border-radius: 9px; font-size: 12.5px;
    }
    #risikoPage .form-select:focus, #risikoPage .form-control:focus {
        border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,0.08);
    }

    #risikoPage .info-note {
        background: #f3f4f6; border-radius: 10px; padding: 10px 14px; font-size: 12px; color: #374151;
    }

    #risikoPage .risk-item { background: #fff; border: 1px solid #eceef1; border-radius: 14px; padding: 18px 20px; margin-bottom: 14px; }
    #risikoPage .risk-item.reviewed { background: #f0fdf9; border-color: #99f6e4; }
    #risikoPage .risk-desc { font-size: 13.5px; font-weight: 600; color: #111827; margin-bottom: 3px; }
    #risikoPage .risk-meta { font-size: 10.5px; color: #9ca3af; margin-bottom: 14px; }
    #risikoPage .reviewed-badge { font-size: 10px; font-weight: 800; padding: 3px 10px; border-radius: 20px; background: #0d9488; color: #fff; }

    #risikoPage .btn-analyze {
        background: linear-gradient(135deg, #dc2626, #ea580c); border: none; color: #fff;
        font-size: 12px; font-weight: 700; padding: 8px 20px; border-radius: 9px;
        box-shadow: 0 4px 10px rgba(220,38,38,0.25); transition: transform .12s;
    }
    #risikoPage .btn-analyze:hover { transform: translateY(-1px); color: #fff; }
    #risikoPage .btn-kirim {
        background: linear-gradient(135deg, #16a34a, #22c55e); border: none; color: #fff;
        font-size: 13px; font-weight: 700; padding: 10px 22px; border-radius: 10px;
        box-shadow: 0 4px 10px rgba(22,163,74,0.25);
    }
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
            Penugasan Selesai: <strong><?= (int) ($ringkasan['total_selesai'] ?? 0) ?></strong> &middot;
            Sudah Disampaikan ke Auditee: <strong><?= (int) ($ringkasan['sudah_disampaikan'] ?? 0) ?></strong>
        </div>
        <img src="<?= BASE_URL ?>assets/img/dashboard/hero.png" alt="Admin" class="standard-summary-hero">
    </div>

    <div class="page-head">
        <div class="icon-chip"><i class="bi bi-arrow-repeat"></i></div>
        <div>
            <div class="eyebrow">SPMI Berbasis Risiko</div>
            <h1>Analisis Risiko Pascaaudit</h1>
            <p>Pasal 32 — langkah formal LPM setelah Laporan Audit disampaikan</p>
        </div>
    </div>

    <div class="section-card">
        <div class="field-label">Pilih Penugasan Audit (status Selesai)</div>
        <select class="form-select" id="assignmentSelector" style="max-width:480px;">
            <option value="">-- Pilih Penugasan --</option>
        </select>
    </div>

    <div id="assignmentInfo" class="info-note mb-3" style="display:none;"></div>

    <div id="listRisikoPascaaudit"></div>

    <div id="footerAction" style="display:none;" class="mt-3">
        <button class="btn btn-kirim" id="btnSampaikanAuditee">
            <i class="bi bi-send-fill"></i> Tandai Selesai &amp; Sampaikan ke Auditee
        </button>
    </div>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script>
document.addEventListener("DOMContentLoaded", function () {

const api = SIQUA.BASE_URL + "risiko/api.php";
let currentAssignmentId = null;

function loadAssignments() {
  $.getJSON(api, { action: "finished_assignments" }, function (res) {
    if (!res.success) return;
    let html = '<option value="">-- Pilih Penugasan --</option>';
    res.data.forEach(function (a) {
      const label = `${a.assignment_number} - ${a.unit_name} (${a.period_name || "-"})` + (parseInt(a.pascaaudit_disampaikan) === 1 ? " \u2713 Sudah Disampaikan" : "");
      html += `<option value="${a.id}">${escapeHtml(label)}</option>`;
    });
    $("#assignmentSelector").html(html);
  });
}

$("#assignmentSelector").on("change", function () {
  currentAssignmentId = $(this).val();
  $("#listRisikoPascaaudit").html("");
  $("#footerAction, #assignmentInfo").hide();
  if (!currentAssignmentId) return;

  $("#assignmentInfo").show().html('<i class="bi bi-info-circle"></i> Memuat daftar Risiko dari Temuan Audit penugasan ini...');
  loadRisks();
});

function loadRisks() {
  $.getJSON(api, { action: "pascaaudit_risks", assignment_id: currentAssignmentId }, function (res) {
    if (!res.success) return;
    renderRisks(res.data);
  });
}

function renderRisks(risks) {
  if (!risks.length) {
    $("#assignmentInfo").html('<i class="bi bi-info-circle"></i> Tidak ada Risiko (Temuan Audit buruk) pada penugasan ini — tidak perlu Analisis Pascaaudit.');
    $("#listRisikoPascaaudit").html("");
    return;
  }

  const belumReview = risks.filter(r => parseInt(r.reviewed_pascaaudit) === 0).length;
  $("#assignmentInfo").html(`<i class="bi bi-info-circle"></i> ${risks.length} Risiko ditemukan, <strong>${belumReview}</strong> belum direview.`);

  let html = "";
  risks.forEach(function (r) {
    const sudahReview = parseInt(r.reviewed_pascaaudit) === 1;
    html += `
      <div class="risk-item ${sudahReview ? "reviewed" : ""}" data-risk-id="${r.id}">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="risk-desc">${escapeHtml(r.item_code)} - ${escapeHtml(r.indicator || "")}</div>
            <div class="risk-meta">Standar: ${escapeHtml(r.standard_name)} &middot; Level saat ini: <strong>${escapeHtml(r.level_risiko || "-")}</strong></div>
          </div>
          ${sudahReview ? '<span class="reviewed-badge"><i class="bi bi-check-lg"></i> Sudah Direview</span>' : ""}
        </div>
        <div class="row g-3 mb-3">
          <div class="col-6 col-md-3">
            <div class="field-label">Likelihood</div>
            <select class="form-select form-select-sm inp-likelihood">
              <option value="">--</option>
              <option value="1" ${r.likelihood == 1 ? "selected" : ""}>1 - Sangat Jarang</option>
              <option value="2" ${r.likelihood == 2 ? "selected" : ""}>2 - Jarang</option>
              <option value="3" ${r.likelihood == 3 ? "selected" : ""}>3 - Mungkin</option>
              <option value="4" ${r.likelihood == 4 ? "selected" : ""}>4 - Sangat Mungkin</option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <div class="field-label">Impact</div>
            <select class="form-select form-select-sm inp-impact">
              <option value="">--</option>
              <option value="1" ${r.impact == 1 ? "selected" : ""}>1 - Rendah</option>
              <option value="2" ${r.impact == 2 ? "selected" : ""}>2 - Sedang</option>
              <option value="3" ${r.impact == 3 ? "selected" : ""}>3 - Tinggi</option>
              <option value="4" ${r.impact == 4 ? "selected" : ""}>4 - Sangat Tinggi</option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <div class="field-label">Detectability</div>
            <select class="form-select form-select-sm inp-detectability">
              <option value="">--</option>
              <option value="4" ${r.detectability == 4 ? "selected" : ""}>4 - Sangat Mudah</option>
              <option value="3" ${r.detectability == 3 ? "selected" : ""}>3 - Mudah</option>
              <option value="2" ${r.detectability == 2 ? "selected" : ""}>2 - Sulit</option>
              <option value="1" ${r.detectability == 1 ? "selected" : ""}>1 - Sangat Sulit</option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <div class="field-label">Control Readiness</div>
            <select class="form-select form-select-sm inp-control">
              <option value="">--</option>
              <option value="4" ${r.control_readiness == 4 ? "selected" : ""}>4 - Sangat Siap</option>
              <option value="3" ${r.control_readiness == 3 ? "selected" : ""}>3 - Siap</option>
              <option value="2" ${r.control_readiness == 2 ? "selected" : ""}>2 - Belum Siap</option>
              <option value="1" ${r.control_readiness == 1 ? "selected" : ""}>1 - Tidak Ada</option>
            </select>
          </div>
        </div>
        <div class="field-label">Rekomendasi Strategi Mitigasi Lanjutan (Pasal 32 ayat 2d)</div>
        <textarea class="form-control form-control-sm inp-rekomendasi mb-2" rows="2">${escapeHtml(r.rekomendasi_lanjutan || "")}</textarea>
        <button type="button" class="btn btn-analyze btn-simpan-review">
          <i class="bi bi-save"></i> Simpan Review
        </button>
      </div>
    `;
  });
  $("#listRisikoPascaaudit").html(html);
  $("#footerAction").show();
}

$(document).on("click", ".btn-simpan-review", function () {
  const $item = $(this).closest(".risk-item");
  const riskId = $item.data("risk-id");
  const likelihood = $item.find(".inp-likelihood").val();
  const impact = $item.find(".inp-impact").val();
  const detectability = $item.find(".inp-detectability").val();
  const control = $item.find(".inp-control").val();
  const rekomendasi = $item.find(".inp-rekomendasi").val().trim();

  if (!likelihood || !impact || !rekomendasi) {
    Swal.fire("Belum Lengkap", "Likelihood, Impact, dan Rekomendasi Strategi Mitigasi Lanjutan wajib diisi.", "warning");
    return;
  }

  $.post(api + "?action=simpan_review_pascaaudit", {
    risk_id: riskId, likelihood: likelihood, impact: impact,
    detectability: detectability, control_readiness: control,
    rekomendasi_lanjutan: rekomendasi,
  }, function (res) {
    if (res.success) {
      Swal.fire({ icon: "success", title: "Tersimpan", text: res.message, timer: 1800, showConfirmButton: false });
      loadRisks();
    } else {
      Swal.fire("Gagal", res.message, "error");
    }
  }, "json");
});

$("#btnSampaikanAuditee").on("click", function () {
  Swal.fire({
    icon: "question",
    title: "Tandai Selesai & Sampaikan ke Auditee?",
    text: "Pastikan semua Risiko sudah direview sebelum melanjutkan.",
    showCancelButton: true,
    confirmButtonText: "Ya, Sampaikan",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(api + "?action=sampaikan_pascaaudit", { assignment_id: currentAssignmentId }, function (res) {
      if (res.success) {
        Swal.fire({ icon: "success", title: "Selesai", text: res.message });
        loadAssignments();
        loadRisks();
      } else {
        Swal.fire("Belum Bisa", res.message, "warning");
      }
    }, "json");
  });
});

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

loadAssignments();

});
</script>