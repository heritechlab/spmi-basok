const LedManage = {
  api: SIQUA.BASE_URL + "laporan/led/api.php",
  sigApi: SIQUA.BASE_URL + "laporan/signatures/api.php",
  unitId: null,
  periodId: null,
  reportData: null,
};

$(document).ready(function () {
  LedManage.unitId = $("#ledUnitSelector").val();

  $(document).on("change", "#ledUnitSelector, #ledPeriodSelector", function () {
    LedManage.unitId = $("#ledUnitSelector").val();
    LedManage.periodId = $("#ledPeriodSelector").val();

    if (LedManage.unitId && LedManage.periodId) {
      loadReportData();
    } else {
      $("#ledManageArea").hide();
      $("#btnPrintLed").css({ "pointer-events": "none", opacity: 0.5 });
    }
  });

  $("#ledPeriodSelector").on("change", function () {
    LedManage.periodId = $(this).val();

    if (LedManage.unitId && LedManage.periodId) {
      loadReportData();
    }
  });

  $("#btnSaveNarratives").on("click", function () {
    saveAllNarratives();
  });

  $("#ledSignatureForm").on("submit", function (e) {
    e.preventDefault();
    saveSignature();
  });
});

function loadReportData() {
  $("#ledManageArea").hide();

  $.getJSON(
    LedManage.api,
    {
      action: "data",
      unit_id: LedManage.unitId,
      period_id: LedManage.periodId,
    },
    function (res) {
      if (!res.success) {
        Swal.fire("Gagal", res.message, "error");
        return;
      }

      LedManage.reportData = res.data;

      renderProgress(res.data);
      fillNarratives(res.data.narratives);
      renderAnalysisTab(res.data.bab_ii, res.data.kriteria_analysis);
      fillSignatureIds();
      loadSignature();

      $("#ledManageArea").show();

      const printUrl =
        SIQUA.BASE_URL +
        "laporan/led/print.php?unit_id=" +
        LedManage.unitId +
        "&period_id=" +
        LedManage.periodId;
      $("#btnPrintLed")
        .attr("href", printUrl)
        .css({ "pointer-events": "auto", opacity: 1 });
    },
  );
}

function renderProgress(data) {
  const pct = data.persen_terisi || 0;
  $("#ledProgressBar")
    .css("width", pct + "%")
    .text(pct + "%");
  $("#ledProgressLabel").text(
    data.total_terisi + " dari " + data.total_indikator + " Indikator terisi",
  );
}

function fillNarratives(narratives) {
  narratives = narratives || {};
  $(".led-narrative-input").each(function () {
    const section = $(this).data("section");
    $(this).val(narratives[section] || "");
  });
}

function saveAllNarratives() {
  const requests = [];

  $(".led-narrative-input").each(function () {
    const section = $(this).data("section");
    const content = $(this).val();

    requests.push(
      $.post(LedManage.api + "?action=save_narrative", {
        unit_id: LedManage.unitId,
        period_id: LedManage.periodId,
        section: section,
        content: content,
      }),
    );
  });

  Swal.fire({
    title: "Menyimpan...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  Promise.all(requests)
    .then(function () {
      Swal.fire({
        icon: "success",
        title: "Berhasil",
        text: "Semua narasi tersimpan.",
        timer: 1200,
        showConfirmButton: false,
      });
    })
    .catch(function () {
      Swal.close();
      Swal.fire("Gagal", "Terjadi kesalahan saat menyimpan.", "error");
    });
}

function renderAnalysisTab(babII, kriteriaAnalysis) {
  kriteriaAnalysis = kriteriaAnalysis || {};

  let html = "";

  babII.forEach(function (kriteria) {
    const analysis = kriteriaAnalysis[kriteria.criteria_id] || {};

    html += `
      <div class="card shadow-sm mb-3">
        <div class="card-header"><strong>${escapeHtml(kriteria.criteria_name)}</strong></div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-success">Kekuatan</label>
            <textarea class="form-control form-control-sm led-analysis-input" data-criteria-id="${kriteria.criteria_id}" data-field="kekuatan" rows="3">${escapeHtml(analysis.kekuatan || "")}</textarea>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-semibold text-danger">Kelemahan</label>
            <textarea class="form-control form-control-sm led-analysis-input" data-criteria-id="${kriteria.criteria_id}" data-field="kelemahan" rows="3">${escapeHtml(analysis.kelemahan || "")}</textarea>
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary btn-save-analysis" data-criteria-id="${kriteria.criteria_id}">
            <i class="bi bi-save"></i> Simpan Kriteria Ini
          </button>
        </div>
      </div>
    `;
  });

  $("#ledAnalysisList").html(
    html ||
      '<p class="text-muted">Belum ada Kriteria dengan Standar yang ditugaskan.</p>',
  );
}

$(document).on("click", ".btn-save-analysis", function () {
  const criteriaId = $(this).data("criteria-id");
  const kekuatan = $(
    `.led-analysis-input[data-criteria-id="${criteriaId}"][data-field="kekuatan"]`,
  ).val();
  const kelemahan = $(
    `.led-analysis-input[data-criteria-id="${criteriaId}"][data-field="kelemahan"]`,
  ).val();

  $.post(
    LedManage.api + "?action=save_kriteria_analysis",
    {
      unit_id: LedManage.unitId,
      period_id: LedManage.periodId,
      criteria_id: criteriaId,
      kekuatan: kekuatan,
      kelemahan: kelemahan,
    },
    function (response) {
      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      Swal.fire({
        icon: "success",
        title: "Tersimpan",
        timer: 900,
        showConfirmButton: false,
      });
    },
  );
});

function fillSignatureIds() {
  $("#sig_unit_id").val(LedManage.unitId);
  $("#sig_period_id").val(LedManage.periodId);
}

function loadSignature() {
  $.getJSON(
    LedManage.sigApi,
    {
      action: "get",
      report_type: "led",
      unit_id: LedManage.unitId,
      period_id: LedManage.periodId,
    },
    function (res) {
      if (!res.success || !res.data) {
        $("#ledSignatureForm")[0].reset();
        fillSignatureIds();
        $("#preview_ketua_tim_ttd, #preview_ketua_lpm_ttd").empty();
        return;
      }

      const d = res.data;

      $('[name="ketua_tim_nama"]').val(d.ketua_tim_nama || "");
      $('[name="ketua_tim_tanggal"]').val(d.ketua_tim_tanggal || "");
      $('[name="ketua_lpm_nama"]').val(d.ketua_lpm_nama || "");
      $('[name="ketua_lpm_tanggal"]').val(d.ketua_lpm_tanggal || "");

      $("#preview_ketua_tim_ttd").html(
        d.ketua_tim_ttd
          ? `<img src="${SIQUA.BASE_URL}${d.ketua_tim_ttd}" style="height:50px;">`
          : "",
      );
      $("#preview_ketua_lpm_ttd").html(
        d.ketua_lpm_ttd
          ? `<img src="${SIQUA.BASE_URL}${d.ketua_lpm_ttd}" style="height:50px;">`
          : "",
      );
    },
  );
}

function saveSignature() {
  const formData = new FormData($("#ledSignatureForm")[0]);

  Swal.fire({
    title: "Menyimpan...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: LedManage.sigApi + "?action=save",
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
        timer: 1200,
        showConfirmButton: false,
      });
      loadSignature();
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
