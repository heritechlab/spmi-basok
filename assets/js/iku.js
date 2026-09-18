const Iku = {
  api: SIQUA.BASE_URL + "iku/api.php",
  currentKategori: "wajib",
  currentUnitId: null,
  currentTahun: null,
  currentTriwulan: "TW1",
};

$(document).ready(function () {
  Iku.currentTahun = $("#ikuTahunSelector").val();
  Iku.currentTriwulan = $("#ikuTriwulanSelector").val();

  if (!IKU_IS_AUDITEE) {
    Iku.currentUnitId = $("#ikuUnitSelector").val() || null;
  }

  registerIkuEvents();
  loadIkuIndicators();
});

function registerIkuEvents() {
  $("#ikuKategoriTabs a").on("click", function (e) {
    e.preventDefault();
    $("#ikuKategoriTabs a").removeClass("active");
    $(this).addClass("active");
    Iku.currentKategori = $(this).data("kategori");
    loadIkuIndicators();
  });

  $(document).on("click", "#btnExportIku", function () {
    exportIkuToExcel();
  });

  $("#ikuTahunSelector").on("change", function () {
    Iku.currentTahun = $(this).val();
    loadIkuIndicators();
  });

  $("#ikuTriwulanSelector").on("change", function () {
    Iku.currentTriwulan = $(this).val();
    loadIkuIndicators();
  });

  if (!IKU_IS_AUDITEE) {
    $(document).on("change", "#ikuUnitSelector", function () {
      Iku.currentUnitId = $(this).val() || null;
      loadIkuIndicators();
    });
  }

  $(document).on("click", ".iku-toggle-btn", function () {
    const targetId = $(this).data("target");
    const $body = $("#" + targetId);
    $body.toggleClass("show");
    $(this).html(
      $body.hasClass("show")
        ? '<i class="bi bi-chevron-up"></i> Sembunyikan'
        : '<i class="bi bi-chevron-down"></i> Analisis & Bukti Dukung',
    );
  });

  $(document).on("submit", ".iku-save-form", function (e) {
    e.preventDefault();
    saveIkuRealization($(this));
  });
}

function loadIkuIndicators() {
  $("#ikuContainer").html('<p class="text-muted">Memuat data...</p>');

  $.getJSON(
    Iku.api,
    {
      action: "get_indicators",
      kategori: Iku.currentKategori,
      tahun: Iku.currentTahun,
      triwulan: Iku.currentTriwulan,
      unit_id: Iku.currentUnitId || 0,
    },
    function (res) {
      if (!res.success) {
        $("#ikuContainer").html(
          '<p class="text-danger">' + res.message + "</p>",
        );
        return;
      }

      if (!res.data.length) {
        $("#ikuContainer").html(
          '<p class="text-muted">Belum ada Indikator untuk kategori ini.</p>',
        );
        return;
      }

      let html = "";
      res.data.forEach(function (ind) {
        html += renderIkuCard(ind, false);
      });

      $("#ikuContainer").html(html);
    },
  );
}

function renderIkuCard(ind, isChild) {
  const target = ind.target || {};
  const realization = ind.realization || {};

  const baseline =
    target.baseline !== undefined && target.baseline !== null
      ? target.baseline
      : "-";
  const targetVal =
    target.target !== undefined && target.target !== null ? target.target : "-";
  const realisasiVal =
    realization.realisasi !== undefined && realization.realisasi !== null
      ? realization.realisasi
      : "";

  const hasRealisasi = realisasiVal !== "";

  const directionIcon =
    ind.direction === "rendah" ? "bi-arrow-down" : "bi-arrow-up";
  const directionLabel = ind.direction === "rendah" ? "Rendah" : "Tinggi";

  const bodyId = "ikuBody" + ind.id;

  let childrenHtml = "";
  if (ind.children && ind.children.length) {
    ind.children.forEach(function (child) {
      childrenHtml += renderIkuCard(child, true);
    });
  }

  const buktiInfo = realization.bukti_original_name
    ? `<a href="${SIQUA.BASE_URL}${realization.bukti_file}" target="_blank" class="small"><i class="bi bi-file-earmark-pdf"></i> ${escapeHtml(realization.bukti_original_name)}</a>`
    : '<span class="text-muted small">Belum ada file diunggah.</span>';

  const buktiUrl = realization.bukti_file
    ? SIQUA.BASE_URL + realization.bukti_file
    : "";
  const buktiName = realization.bukti_original_name || "";

  return `
    <div class="iku-card ${isChild ? "iku-child" : ""}"
      data-code="${escapeAttr(ind.code || "")}"
      data-title="${escapeAttr(ind.name)}"
      data-satuan="${escapeAttr(ind.satuan || "")}"
      data-baseline="${escapeAttr(String(baseline))}"
      data-target="${escapeAttr(String(targetVal))}"
      data-bukti-url="${escapeAttr(buktiUrl)}"
      data-bukti-name="${escapeAttr(buktiName)}">

      <form class="iku-save-form" data-indicator-id="${ind.id}">

        <input type="hidden" name="indicator_id" value="${ind.id}">
        <input type="hidden" name="tahun" value="${Iku.currentTahun}">
        <input type="hidden" name="triwulan" value="${Iku.currentTriwulan}">
        ${Iku.currentUnitId ? `<input type="hidden" name="unit_id" value="${Iku.currentUnitId}">` : ""}

        <div class="iku-card-header">
          <div>
            <div class="iku-card-title">
              ${isChild ? '<i class="bi bi-arrow-return-right text-muted"></i>' : ""}
              ${escapeHtml(ind.code || "")} ${escapeHtml(ind.name)}
            </div>
            <div class="iku-card-meta">
              Satuan: ${escapeHtml(ind.satuan || "-")}
              <span class="iku-direction ${ind.direction}"><i class="bi ${directionIcon}"></i> ${directionLabel}</span>
            </div>
          </div>

          <div class="iku-stat-row align-items-center">
            <div class="iku-stat-box">
              <div class="label">Baseline</div>
              <div class="value">${escapeHtml(String(baseline))}</div>
            </div>
            <div class="iku-stat-box">
              <div class="label">Target</div>
              <div class="value">${escapeHtml(String(targetVal))}</div>
            </div>
            <div class="iku-stat-box">
              <div class="label">Realisasi</div>
              <input type="text" name="realisasi" class="form-control form-control-sm iku-realisasi-input" value="${escapeAttr(String(realisasiVal))}" placeholder="Isi...">
            </div>
            <span class="iku-status-badge ${hasRealisasi ? "filled" : "empty"}">${hasRealisasi ? "Terisi" : "Belum ada"}</span>

            <button type="button" class="iku-toggle-btn" data-target="${bodyId}">
              <i class="bi bi-chevron-down"></i> Analisis & Bukti Dukung
            </button>
          </div>
        </div>

        <div class="iku-card-body" id="${bodyId}">

          <div class="mb-2">
            <label class="form-label small fw-bold"><i class="bi bi-chat-square-text"></i> Analisis & Faktor Keberhasilan</label>
            <textarea name="analisis" class="form-control form-control-sm" rows="2" placeholder="Jelaskan analisis teknis atas capaian ini...">${escapeHtml(realization.analisis || "")}</textarea>
          </div>

          <div class="row align-items-end g-2">
            <div class="col-md-6">
              <label class="form-label small fw-bold"><i class="bi bi-file-earmark-arrow-up"></i> Bukti Dukung (Source Data)</label>
              <input type="file" name="bukti_file" class="form-control form-control-sm" accept="application/pdf">
              <small class="text-muted">Format PDF, Maks 60 MB</small>
            </div>
            <div class="col-md-3">
              ${buktiInfo}
            </div>
            <div class="col-md-3">
              <button type="submit" class="btn btn-sm w-100 text-white" style="background:linear-gradient(135deg,#7c3aed,#2563eb); border:none;">
                <i class="bi bi-save"></i> Simpan
              </button>
            </div>
          </div>

        </div>

      </form>

      ${childrenHtml}
    </div>
  `;
}

function saveIkuRealization($form) {
  const formData = new FormData($form[0]);

  const $body = $form.find(".iku-card-body");
  const fileInput = $body.find('input[name="bukti_file"]')[0];
  const analisisVal = $body.find('textarea[name="analisis"]').val();

  if (fileInput && fileInput.files.length) {
    formData.append("bukti_file", fileInput.files[0]);
  }

  formData.append("analisis", analisisVal || "");

  Swal.fire({
    title: "Menyimpan...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: Iku.api + "?action=save_realization",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (response) {
      Swal.close();

      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      Swal.fire({
        icon: "success",
        title: "Berhasil",
        text: response.message,
        timer: 1000,
        showConfirmButton: false,
      });
      loadIkuIndicators();
    },
    error: function () {
      Swal.close();
      Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
    },
  });
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

function escapeAttr(text) {
  return (text || "").replace(/"/g, "&quot;");
}
function exportIkuToExcel() {
  const aoa = [];
  const hyperlinks = [];

  aoa.push([
    "Indikator Kinerja Utama " +
      Iku.currentKategori.toUpperCase() +
      " - " +
      Iku.currentTahun +
      " " +
      Iku.currentTriwulan,
  ]);
  aoa.push([]);
  aoa.push([
    "Kode",
    "Indikator Kinerja Utama",
    "Satuan",
    "Baseline",
    "Target (TA)",
    "Realisasi",
    "Analisis",
    "Bukti Dukung",
  ]);

  function collectRows(container) {
    container.find("> .iku-card").each(function () {
      const $card = $(this);

      const code = $card.data("code") || "";
      const title = $card.data("title") || "";
      const satuan = $card.data("satuan") || "";
      const baseline = $card.data("baseline") || "";
      const target = $card.data("target") || "";
      const buktiUrl = $card.data("bukti-url") || "";
      const buktiName = $card.data("bukti-name") || "";

      const realisasi = $card.find("> form .iku-realisasi-input").val() || "";
      const analisis =
        $card.find("> form textarea[name='analisis']").val() || "";

      const rowIndex = aoa.length;

      aoa.push([
        code,
        title,
        satuan,
        baseline,
        target,
        realisasi,
        analisis,
        buktiName || "-",
      ]);

      if (buktiUrl) {
        hyperlinks.push({ row: rowIndex, col: 7, url: buktiUrl });
      }

      collectRows($card);
    });
  }

  collectRows($("#ikuContainer"));

  const ws = XLSX.utils.aoa_to_sheet(aoa);
  ws["!cols"] = [
    { wch: 12 },
    { wch: 55 },
    { wch: 10 },
    { wch: 10 },
    { wch: 10 },
    { wch: 14 },
    { wch: 40 },
    { wch: 30 },
  ];

  hyperlinks.forEach(function (h) {
    const cellRef = XLSX.utils.encode_cell({ r: h.row, c: h.col });
    if (ws[cellRef]) {
      ws[cellRef].l = { Target: h.url };
    }
  });

  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "IKU");

  XLSX.writeFile(
    wb,
    "IKU_" +
      Iku.currentKategori +
      "_" +
      Iku.currentTahun +
      "_" +
      Iku.currentTriwulan +
      ".xlsx",
  );
}
