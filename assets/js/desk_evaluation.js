const DE = { modal: null };

$(document).ready(function () {
  DE.modal = new bootstrap.Modal(document.getElementById("deModal"));

  $(document).on("click", ".btn-fill-de", function () {
    const assignmentId = $(this).data("assignment");
    const standardId = $(this).data("standard");
    const standardName = $(this).data("standard-name");

    $("#de_assignment_id").val(assignmentId);
    $("#de_standard_id").val(standardId);
    $("#deModalTitle").text("Desk Evaluation - " + standardName);
    $("#de_indicator_forms").html(
      '<p class="text-muted">Memuat data indikator...</p>',
    );

    $.getJSON(
      BASE_URL_DE + "auditee/desk_evaluation/api.php",
      { action: "indicators", standard_id: standardId },
      function (indRes) {
        if (!indRes.success || !indRes.data.length) {
          $("#de_indicator_forms").html(
            '<p class="text-muted">Belum ada indikator untuk standar ini.</p>',
          );
          return;
        }

        $.getJSON(
          BASE_URL_DE + "auditee/desk_evaluation/api.php",
          {
            action: "get",
            assignment_id: assignmentId,
            standard_id: standardId,
          },
          function (getRes) {
            const itemNotes = getRes.success
              ? getRes.data.item_notes || {}
              : {};
            const itemDocuments = getRes.success
              ? getRes.data.item_documents || {}
              : {};

            let html = "";

            indRes.data.forEach(function (row) {
              const savedNote = itemNotes[row.id] || "";
              const savedDocs = itemDocuments[row.id] || [];

              let docsHtml = "";

              if (savedDocs.length) {
                docsHtml = '<div class="mt-2">';
                savedDocs.forEach(function (doc) {
                  if (doc.link_url) {
                    docsHtml += `<div class="d-flex justify-content-between align-items-center border rounded px-2 py-1 mb-1">
                  <a href="${doc.link_url}" target="_blank" class="small"><i class="bi bi-link-45deg"></i> ${doc.link_url}</a>
                  <button type="button" class="btn btn-sm btn-outline-danger btn-delete-item-doc" data-id="${doc.id}"><i class="bi bi-trash"></i></button>
                </div>`;
                  } else {
                    docsHtml += `<div class="d-flex justify-content-between align-items-center border rounded px-2 py-1 mb-1">
                  <a href="${BASE_URL_DE}${doc.document_file}" target="_blank" class="small">${doc.document_original_name}</a>
                  <button type="button" class="btn btn-sm btn-outline-danger btn-delete-item-doc" data-id="${doc.id}"><i class="bi bi-trash"></i></button>
                </div>`;
                  }
                });
                docsHtml += "</div>";
              } else {
                docsHtml =
                  '<small class="text-muted d-block mt-2">Belum ada dokumen untuk indikator ini.</small>';
              }

              html += `
            <div class="p-3 mb-3 rounded border">
              <div class="mb-1">
                <strong>${row.item_code}</strong> - ${row.indicator}
                <span class="text-muted small">(Target: ${row.target || "-"})</span>
              </div>
              <div class="text-muted small mb-2">${row.statement || ""}</div>
              <textarea class="form-control de-note-input" data-indicator-id="${row.id}" rows="3"
                placeholder="Sertakan capaian Anda dan uraikan proses pencapaiannya.">${savedNote}</textarea>

            <div class="mt-2">
                <label class="form-label small mb-1">Dokumen Pendukung Indikator Ini (Upload File atau Link)</label>
                <input type="file" class="form-control form-control-sm mb-1 de-file-input" data-indicator-id="${row.id}">
                <input type="url" class="form-control form-control-sm de-link-input" data-indicator-id="${row.id}" placeholder="https://drive.google.com/...">
                <div class="de-doc-holder">${docsHtml}</div>
              </div>
            </div>
          `;
            });

            $("#de_indicator_forms").html(html);
          },
        );
      },
    );

    DE.modal.show();
  });

  $(document).on("click", ".btn-delete-item-doc", function () {
    const docId = $(this).data("id");
    const $row = $(this).closest("div");

    $.ajax({
      url:
        BASE_URL_DE + "auditee/desk_evaluation/api.php?action=delete_document",
      type: "POST",
      data: { doc_id: docId },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          $row.remove();
        }
      },
    });
  });

  $("#btnSaveDe").on("click", function () {
    const formData = new FormData();
    formData.append("assignment_id", $("#de_assignment_id").val());
    formData.append("standard_id", $("#de_standard_id").val());

    $(".de-note-input").each(function () {
      const indicatorId = $(this).data("indicator-id");
      formData.append("notes[" + indicatorId + "]", $(this).val());
    });

    $(".de-file-input").each(function () {
      const indicatorId = $(this).data("indicator-id");
      if (this.files.length > 0) {
        formData.append("documents[" + indicatorId + "]", this.files[0]);
      }
    });

    $(".de-link-input").each(function () {
      const indicatorId = $(this).data("indicator-id");
      const linkVal = $(this).val().trim();
      if (linkVal) {
        formData.append("links[" + indicatorId + "]", linkVal);
      }
    });

    Swal.fire({
      title: "Menyimpan...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    $.ajax({
      url: BASE_URL_DE + "auditee/desk_evaluation/api.php?action=save",
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

        Swal.fire("Berhasil", response.message, "success").then(function () {
          location.reload();
        });
      },
      error: function () {
        Swal.close();
        Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
      },
    });
  });
});
