const Workspace = {
  api: SIQUA.BASE_URL + "audit/workspace/api.php",
  modal: null,
  form: null,
  currentChecklistId: null,
};

$(document).ready(function () {
  Workspace.modal = new bootstrap.Modal(document.getElementById("resultModal"));
  Workspace.form = $("#resultForm");

  loadChecklist();
  loadProgress();
  registerEvents();
});

function registerEvents() {
  $("#checklistContainer").on("click", ".btn-fill", function () {
    loadItem($(this).data("id"));
  });

  $("#audit_status").on("change", function () {
    toggleConditionalFields($(this).val());
  });

  Workspace.form.on("submit", function (e) {
    e.preventDefault();
    saveResult();
  });
}

function toggleConditionalFields(status) {
  const needsGap = status === "Menyimpang" || status === "Belum Mencapai";
  const needsGood = status === "Mencapai" || status === "Melampaui";

  $("#blockGap").toggle(needsGap);
  $("#blockGood").toggle(needsGood);

  if (needsGap) {
    $("#labelRecommendation").text("Rekomendasi Perbaikan");
  } else if (needsGood) {
    $("#labelRecommendation").text("Rekomendasi Peningkatan");
  } else {
    $("#labelRecommendation").text("Rekomendasi");
  }
}

function badgeColor(status) {
  const map = {
    Menyimpang: "danger",
    "Belum Mencapai": "warning",
    Mencapai: "success",
    Melampaui: "primary",
  };
  return map[status] || "secondary";
}

function loadChecklist() {
  $.getJSON(
    Workspace.api,
    { action: "checklist", assignment_id: ASSIGNMENT_ID },
    function (res) {
      if (!res.success) {
        $("#checklistContainer").html(
          '<p class="text-danger">' + res.message + "</p>",
        );
        return;
      }

      if (!res.data.length) {
        $("#checklistContainer").html(
          '<p class="text-muted">Belum ada indikator aktif untuk diaudit.</p>',
        );
        return;
      }

      let grouped = {};

      res.data.forEach(function (row) {
        const key = row.standard_code + " - " + row.standard_name;
        if (!grouped[key]) grouped[key] = [];
        grouped[key].push(row);
      });

      let html = "";

      Object.keys(grouped).forEach(function (standardLabel) {
        html += `<h6 class="mt-3 mb-2 text-primary">${standardLabel}</h6>`;
        html += `<table class="table table-bordered table-sm align-middle">
        <thead>
          <tr>
            <th width="80">Kode</th>
            <th width="28%">Butir Standar</th>
            <th>Indikator</th>
            <th width="90">Target</th>
            <th width="90">Capaian</th>
            <th width="110">Status</th>
            <th width="70">Aksi</th>
          </tr>
        </thead>
        <tbody>`;

        grouped[standardLabel].forEach(function (row) {
          const statusBadge = row.audit_status
            ? `<span class="badge bg-${badgeColor(row.audit_status)}">${row.audit_status}</span>`
            : '<span class="badge bg-secondary">Belum</span>';

          html += `<tr>
          <td>${row.item_code}</td>
          <td><small>${row.statement ?? "-"}</small></td>
          <td>${row.indicator}</td>
          <td>${row.target ?? "-"}</td>
          <td>${row.achievement ?? "-"}</td>
          <td>${statusBadge}</td>
          <td>
            <button class="btn btn-sm btn-outline-primary btn-fill" data-id="${row.id}">
              <i class="bi bi-pencil-square"></i>
            </button>
          </td>
        </tr>`;
        });

        html += `</tbody></table>`;
      });

      $("#checklistContainer").html(html);
    },
  );
}

function loadProgress() {
  $.getJSON(
    Workspace.api,
    { action: "progress", assignment_id: ASSIGNMENT_ID },
    function (res) {
      if (!res.success) return;

      const d = res.data;

      $("#progressLabel").text(
        `Progres Pengisian: ${d.selesai} / ${d.total} (${d.percent}%)`,
      );
      $("#progressBar").css("width", d.percent + "%");
    },
  );
}

function loadItem(id) {
  $.getJSON(Workspace.api, { action: "item", id: id }, function (res) {
    if (!res.success) {
      showError(res.message);
      return;
    }

    const row = res.data;

    document.getElementById("resultForm").reset();

    $("#checklist_id").val(row.id);

    $("#dt_standard").text(`${row.standard_code} - ${row.standard_name}`);
    $("#dt_statement").text(row.statement || "-");
    $("#dt_indicator").text(`${row.item_code} - ${row.indicator}`);
    $("#dt_target").text(row.target || "-");
    $("#achievement").val(row.achievement || "");

    $("#document_audit_result").val(row.document_audit_result || "");
    $("#field_audit_result").val(row.field_audit_result || "");
    $("#audit_status").val(row.audit_status || "");
    $("#root_cause").val(row.root_cause || "");
    $("#supporting_factor").val(row.supporting_factor || "");
    $("#recommendation").val(row.recommendation || "");
    $("#evidence").val(row.evidence || "");
    $("#notes").val(row.notes || "");

    $("#is_temuan_risiko_tinggi").prop(
      "checked",
      parseInt(row.is_temuan_risiko_tinggi) === 1,
    );
    $("#rekomendasi_mitigasi_segera").val(
      row.rekomendasi_mitigasi_segera || "",
    );
    $("#blockRekomendasiSegera").toggle(
      parseInt(row.is_temuan_risiko_tinggi) === 1,
    );

    toggleConditionalFields(row.audit_status || "");
    loadDeskEvaluationReference(
      ASSIGNMENT_ID,
      row.standard_id,
      row.audit_indicator_id,
    );
    loadRiskRegisterReference(row.standard_id, row.audit_indicator_id);

    Workspace.modal.show();
  });
}

$(document).on("change", "#is_temuan_risiko_tinggi", function () {
  $("#blockRekomendasiSegera").toggle($(this).is(":checked"));
});

function loadRiskRegisterReference(standardId, indicatorId) {
  $("#riskRegisterPanel").hide();

  $.getJSON(
    Workspace.api,
    {
      action: "risk_register_ref",
      standard_id: standardId,
      indicator_id: indicatorId,
    },
    function (res) {
      if (!res.success || !res.data.length) return;

      const colorMap = {
        Ekstrem: "#dc2626",
        Tinggi: "#f97316",
        Sedang: "#eab308",
        Rendah: "#22c55e",
      };
      let html = "";
      res.data.forEach(function (r) {
        const color = colorMap[r.level_risiko] || "#94a3b8";
        html += `<div class="small mb-1">
          <span class="badge" style="background:${color};">${r.level_risiko || "Belum Dianalisis"}</span>
          ${r.deskripsi_risiko} <span class="text-muted">(${r.kategori_risiko || "-"})</span>
        </div>`;
      });
      $("#riskRegisterList").html(html);
      $("#riskRegisterPanel").show();
    },
  );
}

let currentDeskEvalNotes = "";

function loadDeskEvaluationReference(assignmentId, standardId, indicatorId) {
  $("#deskEvalReference").hide();
  $("#btnCopyDeskEval").hide();
  currentDeskEvalNotes = "";

  $.getJSON(
    Workspace.api,
    {
      action: "desk_eval",
      assignment_id: assignmentId,
      standard_id: standardId,
      indicator_id: indicatorId,
    },
    function (res) {
      if (!res.success || !res.data) {
        return;
      }

      const d = res.data;
      currentDeskEvalNotes = d.notes || "";

      $("#deskEvalNotes").text(d.notes || "(Auditee belum mengisi catatan)");

      let docsHtml = "";
      if (d.documents && d.documents.length) {
        docsHtml += '<div class="d-flex flex-wrap gap-2">';
        d.documents.forEach(function (doc) {
          if (doc.link_url) {
            docsHtml += `<a href="${doc.link_url}" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-link-45deg"></i> Buka Link
          </a>`;
          } else {
            docsHtml += `<a href="${SIQUA.BASE_URL}${doc.document_file}" target="_blank" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-arrow-down"></i> ${doc.document_original_name}
          </a>`;
          }
        });
        docsHtml += "</div>";
      } else {
        docsHtml =
          '<small class="text-muted">Belum ada dokumen diupload Auditee.</small>';
      }

      $("#deskEvalDocs").html(docsHtml);
      $("#deskEvalReference").show();

      if (currentDeskEvalNotes) {
        $("#btnCopyDeskEval").show();
      }
    },
  );
}

$(document).on("click", "#btnCopyDeskEval", function () {
  const existing = $("#document_audit_result").val();
  const addition =
    "Berdasarkan Desk Evaluation Auditee:\n" + currentDeskEvalNotes;

  $("#document_audit_result").val(
    existing ? existing + "\n\n" + addition : addition,
  );
});

function saveResult() {
  const formData =
    Workspace.form.serialize() + "&assignment_id=" + ASSIGNMENT_ID;

  $.ajax({
    url: Workspace.api + "?action=save",
    type: "POST",
    dataType: "json",
    data: formData,
    beforeSend: function () {
      showLoading();
    },
    success: function (response) {
      Swal.close();

      if (!response.success) {
        showError(response.message);
        return;
      }

      Workspace.modal.hide();
      loadChecklist();
      loadProgress();

      Swal.fire({
        icon: "success",
        title: "Berhasil",
        text: response.message,
        timer: 1500,
        showConfirmButton: false,
      });
    },
    error: function () {
      Swal.close();
      showError("Terjadi kesalahan pada server.");
    },
  });
}

function showLoading() {
  Swal.fire({
    title: "Memproses...",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });
}

function showError(message) {
  Swal.fire({
    icon: "error",
    title: "Gagal",
    text: message,
  });
}
