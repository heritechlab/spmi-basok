const RtmDetail = {
  api: SIQUA.BASE_URL + "rtl/api.php",
  rtlModal: null,
  rtlForm: null,
  findings: [],
};

$(document).ready(function () {
  RtmDetail.rtlModal = new bootstrap.Modal(document.getElementById("rtlModal"));
  RtmDetail.rtlForm = $("#rtlForm");

  loadDocuments();
  loadFindings();
  loadActionPlans();
  registerEvents();
});

function registerEvents() {
  $("#signatureForm").on("submit", function (e) {
    e.preventDefault();
    saveBeritaAcaraSignature();
  });

  $("#btnUploadDoc").on("click", function () {
    uploadDocument();
  });

  $(document).on("click", ".btn-delete-doc", function () {
    deleteDocument($(this).data("id"));
  });

  $("#btnAddRtl").on("click", function () {
    resetRtlForm();
    $("#rtlModalTitle").text("Tambah RTL");
    RtmDetail.rtlModal.show();
  });

  $(document).on("click", ".btn-edit-rtl", function () {
    loadActionPlan($(this).data("id"));
  });

  $(document).on("click", ".btn-delete-rtl", function () {
    deleteActionPlan($(this).data("id"));
  });

  $("#checklist_result_id").on("change", function () {
    showFindingDetail($(this).val());
  });

  RtmDetail.rtlForm.on("submit", function (e) {
    e.preventDefault();
    saveActionPlan();
  });

  $("#rtlModal").on("hidden.bs.modal", function () {
    resetRtlForm();
  });
}

/* ===================== DOKUMEN ===================== */

function loadDocuments() {
  $.getJSON(
    RtmDetail.api,
    { action: "get", id: RTM_MEETING_ID },
    function (res) {
      if (!res.success) return;

      const docs = res.data.documents || [];

      if (!docs.length) {
        $("#documentList").html(
          '<p class="text-muted mb-0">Belum ada dokumen diupload.</p>',
        );
        return;
      }

      let html = '<div class="row g-2">';

      docs.forEach(function (doc) {
        html += `
        <div class="col-md-4">
          <div class="border rounded p-2 d-flex justify-content-between align-items-center">
            <div>
              <span class="badge bg-secondary mb-1">${doc.document_type}</span>
              <br>
              <a href="${SIQUA.BASE_URL}${doc.document_file}" target="_blank" class="small">${doc.document_original_name}</a>
            </div>
            <button class="btn btn-sm btn-outline-danger btn-delete-doc" data-id="${doc.id}">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
      `;
      });

      html += "</div>";

      $("#documentList").html(html);
    },
  );
}

function uploadDocument() {
  const fileInput = document.getElementById("docFile");

  if (!fileInput.files.length) {
    Swal.fire("Gagal", "Pilih file terlebih dahulu.", "error");
    return;
  }

  const formData = new FormData();
  formData.append("rtm_meeting_id", RTM_MEETING_ID);
  formData.append("document_type", $("#docType").val());
  formData.append("document", fileInput.files[0]);

  Swal.fire({
    title: "Mengunggah...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: RtmDetail.api + "?action=upload_document",
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

      fileInput.value = "";
      loadDocuments();

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
      Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
    },
  });
}

function deleteDocument(docId) {
  Swal.fire({
    icon: "warning",
    title: "Hapus dokumen ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: RtmDetail.api + "?action=delete_document",
      type: "POST",
      data: { doc_id: docId },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          loadDocuments();
        } else {
          Swal.fire("Gagal", response.message, "error");
        }
      },
    });
  });
}
function saveBeritaAcaraSignature() {
  const formData = new FormData(document.getElementById("signatureForm"));

  Swal.fire({
    title: "Menyimpan...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: RtmDetail.api + "?action=save_berita_acara_signature",
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
        timer: 1500,
        showConfirmButton: false,
      }).then(function () {
        location.reload();
      });
    },
    error: function () {
      Swal.close();
      Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
    },
  });
}

/* ===================== TEMUAN (Untuk Pilihan RTL) ===================== */

function loadFindings() {
  $.getJSON(
    RtmDetail.api,
    { action: "findings", period_id: RTM_PERIOD_ID },
    function (res) {
      if (!res.success) return;

      RtmDetail.findings = res.data;

      const $select = $("#checklist_result_id");
      $select.find("option:not(:first)").remove();

      res.data.forEach(function (row) {
        $select.append(
          `<option value="${row.checklist_result_id}">${row.item_code} - ${row.indicator} (${row.auditee_name}) - ${row.audit_status}</option>`,
        );
      });
    },
  );
}

function showFindingDetail(checklistResultId) {
  const row = RtmDetail.findings.find(function (f) {
    return String(f.checklist_result_id) === String(checklistResultId);
  });

  if (!row) {
    $("#findingDetail").hide();
    return;
  }

  $("#findingDetail")
    .html(
      `
    <strong>Standar:</strong> ${row.standard_code} - ${row.standard_name}<br>
    <strong>Unit:</strong> ${row.auditee_name} | <strong>Penugasan:</strong> ${row.assignment_number}<br>
    <strong>Status:</strong> ${row.audit_status}<br>
    ${row.finding ? "<strong>Temuan:</strong> " + row.finding + "<br>" : ""}
    ${row.root_cause ? "<strong>Akar Masalah:</strong> " + row.root_cause + "<br>" : ""}
    ${row.recommendation ? "<strong>Rekomendasi:</strong> " + row.recommendation : ""}
  `,
    )
    .show();
}

/* ===================== RTL (Action Plans) ===================== */

function loadActionPlans() {
  $.getJSON(
    RtmDetail.api,
    { action: "get", id: RTM_MEETING_ID },
    function (res) {
      if (!res.success) return;

      const plans = res.data.action_plans || [];

      if (!plans.length) {
        $("#rtlTableBody").html(
          '<tr><td colspan="8" class="text-muted">Belum ada RTL untuk rapat ini.</td></tr>',
        );
        return;
      }

      const statusBadge = {
        Belum: "secondary",
        Proses: "warning",
        Selesai: "success",
      };

      let html = "";

      plans.forEach(function (row) {
        const isSurveyItem = row.source_type === "survey";

        const isOrphaned = !isSurveyItem && !row.item_code;

        const temuanCell = isSurveyItem
          ? `<span class="badge bg-info text-dark mb-1"><i class="bi bi-magic"></i> ${row.survey_type_name || "Survey"}</span><br><strong>${row.survey_category_name || "-"}</strong>`
          : isOrphaned
            ? `<span class="text-danger small"><i class="bi bi-exclamation-triangle-fill"></i> Temuan sumber sudah dihapus</span>`
            : `<strong>${row.item_code}</strong> - ${row.indicator}<br><small class="text-muted">${row.auditee_name} | ${row.standard_code}</small>`;

        const aksiCell = `<button class="btn btn-warning btn-sm btn-edit-rtl" data-id="${row.id}"><i class="bi bi-pencil"></i></button>
             <button class="btn btn-danger btn-sm btn-delete-rtl" data-id="${row.id}"><i class="bi bi-trash"></i></button>`;

        html += `<tr>
        <td>${temuanCell}</td>
        <td><span class="badge bg-dark">${row.importance}</span><br><span class="badge bg-info text-dark mt-1">${row.urgency}</span></td>
        <td>${row.activity}</td>
        <td>${row.implementation_time || "-"}</td>
        <td>${row.pic || "-"}</td>
        <td>${row.budget || "-"}</td>
        <td><span class="badge bg-${statusBadge[row.status] || "secondary"}">${row.status}</span></td>
        <td>${aksiCell}</td>
      </tr>`;
      });

      $("#rtlTableBody").html(html);
    },
  );
}

function resetRtlForm() {
  document.getElementById("rtlForm").reset();
  $("#rtl_id").val("");
  $("#findingDetail").hide();
  $("#findingSelectWrapper").show();
  $("#checklist_result_id").prop("required", true);
}

function loadActionPlan(id) {
  const plan = findPlanFromTable(id);

  $.getJSON(
    RtmDetail.api,
    { action: "get", id: RTM_MEETING_ID },
    function (res) {
      if (!res.success) return;

      const row = (res.data.action_plans || []).find(function (p) {
        return String(p.id) === String(id);
      });

      if (!row) return;

      resetRtlForm();

      $("#rtl_id").val(row.id);

      if (row.source_type === "survey") {
        $("#findingSelectWrapper").hide();
        $("#checklist_result_id").prop("required", false);
        $("#findingDetail").hide();
      } else {
        $("#findingSelectWrapper").show();
        $("#checklist_result_id").prop("required", true);
        $("#checklist_result_id").val(row.checklist_result_id);
        showFindingDetail(row.checklist_result_id);
      }

      $(`input[name="importance"][value="${row.importance}"]`).prop(
        "checked",
        true,
      );
      $(`input[name="urgency"][value="${row.urgency}"]`).prop("checked", true);
      $("#activity").val(row.activity);
      $("#implementation_time").val(row.implementation_time);
      $("#pic").val(row.pic);
      $("#budget").val(row.budget);
      $("#rtl_status").val(row.status);

      $("#rtlModalTitle").text("Edit RTL");

      RtmDetail.rtlModal.show();
    },
  );
}

function findPlanFromTable() {
  return null;
}

function saveActionPlan() {
  const formData = RtmDetail.rtlForm.serialize();

  $.ajax({
    url: RtmDetail.api + "?action=save_action_plan",
    type: "POST",
    dataType: "json",
    data: formData,
    beforeSend: function () {
      Swal.fire({
        title: "Menyimpan...",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading(),
      });
    },
    success: function (response) {
      Swal.close();

      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      RtmDetail.rtlModal.hide();
      loadActionPlans();

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
      Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
    },
  });
}

function deleteActionPlan(id) {
  Swal.fire({
    icon: "warning",
    title: "Hapus RTL ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: RtmDetail.api + "?action=delete_action_plan",
      type: "POST",
      data: { id: id },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          loadActionPlans();
        } else {
          Swal.fire("Gagal", response.message, "error");
        }
      },
    });
  });
}
