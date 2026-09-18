const AccManage = {
  api: SIQUA.BASE_URL + "acc/manage/api.php",
  currentCode: null,
  currentTableId: null,
  currentDocCriteriaId: null,
  tableModal: null,
  columnModal: null,
  documentModal: null,
};

$(document).ready(function () {
  AccManage.tableModal = new bootstrap.Modal(
    document.getElementById("tableModal"),
  );
  AccManage.columnModal = new bootstrap.Modal(
    document.getElementById("columnModal"),
  );
  AccManage.documentModal = new bootstrap.Modal(
    document.getElementById("documentModal"),
  );

  registerEvents();
});

function registerEvents() {
  /* ===== Navigasi Tab ===== */

  $("#manageTabs a").on("click", function (e) {
    e.preventDefault();

    $("#manageTabs a").removeClass("active");
    $(this).addClass("active");

    const tab = $(this).data("tab");

    $(".manage-tab-pane").hide();
    $("#pane-" + tab).show();

    if (tab === "tahunAkademik") {
      loadAcademicYears();
    }
  });

  /* ===== Tab: Tabel & Kolom ===== */

  $("#tableSelector").on("change", function () {
    AccManage.currentCode = $(this).val();
    if (AccManage.currentCode) {
      loadTableDetail();
    } else {
      $("#tableManageContainer").html(
        '<p class="text-muted">Silakan pilih Tabel terlebih dahulu.</p>',
      );
    }
  });

  $("#btnAddTable").on("click", function () {
    document.getElementById("tableForm").reset();
    $("#table_id").val("");
    $("#tableCodeWrapper").show();
    $("#tableModalTitle").text("Tambah Tabel");
    AccManage.tableModal.show();
  });

  $(document).on("click", ".btn-edit-table", function () {
    document.getElementById("tableForm").reset();
    $("#table_id").val($(this).data("id"));
    $("#table_title").val($(this).data("title"));
    $("#table_description").val($(this).data("description"));
    $("#tableCodeWrapper").hide();
    $("#tableModalTitle").text("Edit Tabel");
    AccManage.tableModal.show();
  });

  $("#tableForm").on("submit", function (e) {
    e.preventDefault();

    $.post(
      AccManage.api + "?action=save_table",
      $(this).serialize(),
      function (response) {
        if (!response.success) {
          Swal.fire("Gagal", response.message, "error");
          return;
        }
        AccManage.tableModal.hide();
        Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: response.message,
          timer: 1200,
          showConfirmButton: false,
        });
        location.reload();
      },
    ).fail(showAjaxError);
  });

  $(document).on("click", ".btn-add-column", function () {
    document.getElementById("columnForm").reset();
    $("#column_id").val("");
    $("#column_table_id").val(AccManage.currentTableId);
    $("#column_total_mode").val("sum");
    $("#columnModalTitle").text("Tambah Kolom");
    AccManage.columnModal.show();
  });

  $(document).on("click", ".btn-edit-column", function () {
    document.getElementById("columnForm").reset();
    $("#column_id").val($(this).data("id"));
    $("#column_table_id").val(AccManage.currentTableId);
    $("#column_group_label").val($(this).data("group"));
    $("#column_label").val($(this).data("label"));
    $("#column_data_type").val($(this).data("type"));
    $("#column_total_mode").val($(this).data("total-mode") || "sum");
    $("#columnModalTitle").text("Edit Kolom");
    AccManage.columnModal.show();
  });

  $("#columnForm").on("submit", function (e) {
    e.preventDefault();

    $.post(
      AccManage.api + "?action=save_column",
      $(this).serialize(),
      function (response) {
        if (!response.success) {
          Swal.fire("Gagal", response.message, "error");
          return;
        }
        AccManage.columnModal.hide();
        Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: response.message,
          timer: 1200,
          showConfirmButton: false,
        });
        loadTableDetail();
      },
    ).fail(showAjaxError);
  });

  $(document).on("click", ".btn-delete-column", function () {
    const id = $(this).data("id");
    Swal.fire({
      icon: "warning",
      title: "Hapus Kolom ini?",
      showCancelButton: true,
      confirmButtonText: "Ya, Hapus",
      cancelButtonText: "Batal",
    }).then(function (result) {
      if (!result.isConfirmed) return;
      $.post(
        AccManage.api + "?action=delete_column",
        { id: id },
        function (response) {
          if (response.success) {
            loadTableDetail();
          } else {
            Swal.fire("Gagal", response.message, "error");
          }
        },
      );
    });
  });

  $(document).on("click", ".btn-delete-table", function () {
    const id = $(this).data("id");
    Swal.fire({
      icon: "warning",
      title: "Hapus Tabel ini beserta seluruh Kolomnya?",
      showCancelButton: true,
      confirmButtonText: "Ya, Hapus",
      cancelButtonText: "Batal",
    }).then(function (result) {
      if (!result.isConfirmed) return;
      $.post(
        AccManage.api + "?action=delete_table",
        { id: id },
        function (response) {
          if (response.success) {
            location.href = SIQUA.BASE_URL + "acc/manage/";
          } else {
            Swal.fire("Gagal", response.message, "error");
          }
        },
      );
    });
  });

  $(document).on("submit", "#labelForm", function (e) {
    e.preventDefault();

    $.post(
      AccManage.api + "?action=update_labels",
      $(this).serialize(),
      function (response) {
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
      },
    ).fail(showAjaxError);
  });

  $(document).on("change", "#tableCriteriaSelector", function () {
    const criteriaId = $(this).val();

    $.post(
      AccManage.api + "?action=update_table_criteria",
      { table_id: AccManage.currentTableId, criteria_id: criteriaId },
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
    ).fail(showAjaxError);
  });

  $(document).on("change", "#table_all_units", function () {
    if ($(this).is(":checked")) {
      $("#tableUnitChecklistWrapper").hide();
      $(".table-unit-checkbox").prop("checked", false);
      saveTableUnitsNow([]);
    } else {
      $("#tableUnitChecklistWrapper").show();
    }
  });

  $(document).on("click", "#btnSaveTableUnits", function () {
    const unitIds = $(".table-unit-checkbox:checked")
      .map(function () {
        return $(this).val();
      })
      .get();
    saveTableUnitsNow(unitIds);
  });

  /* ===== Tab: Daftar Dokumen ===== */

  $("#docCriteriaSelector").on("change", function () {
    AccManage.currentDocCriteriaId = $(this).val();

    if (AccManage.currentDocCriteriaId) {
      loadDocuments();
    } else {
      $("#documentManageContainer").html(
        '<p class="text-muted">Silakan pilih Kriteria terlebih dahulu.</p>',
      );
    }
  });

  $(document).on("change", "#document_all_units", function () {
    if ($(this).is(":checked")) {
      $("#document_unit_checklist_wrapper").hide();
      $(".document-unit-checkbox").prop("checked", false);
    } else {
      $("#document_unit_checklist_wrapper").show();
    }
  });

  $(document).on("click", "#btnAddDocument", function () {
    document.getElementById("documentForm").reset();
    $("#document_id").val("");
    $("#document_criteria_id").val(AccManage.currentDocCriteriaId);
    $(".document-unit-checkbox").prop("checked", false);
    $("#document_all_units").prop("checked", true);
    $("#document_unit_checklist_wrapper").hide();
    $("#documentModalTitle").text("Tambah Dokumen");
    AccManage.documentModal.show();
  });

  $(document).on("click", ".btn-edit-document", function () {
    document.getElementById("documentForm").reset();
    const docId = $(this).data("id");
    $("#document_id").val(docId);
    $("#document_criteria_id").val(AccManage.currentDocCriteriaId);
    $("#document_name").val($(this).data("name"));
    $(".document-unit-checkbox").prop("checked", false);

    $.getJSON(
      AccManage.api,
      { action: "get_document_units", document_id: docId },
      function (res) {
        const unitIds = res.success ? res.data : [];

        if (unitIds.length > 0) {
          $("#document_all_units").prop("checked", false);
          $("#document_unit_checklist_wrapper").show();
          unitIds.forEach(function (uid) {
            $(`#doc_unit_${uid}`).prop("checked", true);
          });
        } else {
          $("#document_all_units").prop("checked", true);
          $("#document_unit_checklist_wrapper").hide();
        }

        $("#documentModalTitle").text("Edit Dokumen");
        AccManage.documentModal.show();
      },
    );
  });

  $("#documentForm").on("submit", function (e) {
    e.preventDefault();

    const isAllUnits = $("#document_all_units").is(":checked");
    const unitIds = isAllUnits
      ? []
      : $(".document-unit-checkbox:checked")
          .map(function () {
            return $(this).val();
          })
          .get();

    const payload = {
      id: $("#document_id").val(),
      criteria_id: $("#document_criteria_id").val(),
      document_name: $("#document_name").val(),
      unit_id: unitIds,
    };

    $.post(
      AccManage.api + "?action=save_document",
      payload,
      function (response) {
        if (!response.success) {
          Swal.fire("Gagal", response.message, "error");
          return;
        }
        AccManage.documentModal.hide();
        Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: response.message,
          timer: 1200,
          showConfirmButton: false,
        });
        loadDocuments();
      },
    ).fail(showAjaxError);
  });

  $(document).on("click", ".btn-delete-document", function () {
    const id = $(this).data("id");
    Swal.fire({
      icon: "warning",
      title: "Hapus Dokumen ini?",
      showCancelButton: true,
      confirmButtonText: "Ya, Hapus",
      cancelButtonText: "Batal",
    }).then(function (result) {
      if (!result.isConfirmed) return;
      $.post(
        AccManage.api + "?action=delete_document",
        { id: id },
        function (response) {
          if (response.success) {
            loadDocuments();
          } else {
            Swal.fire("Gagal", response.message, "error");
          }
        },
      );
    });
  });

  /* ===== Tab: Tahun Akademik ===== */

  $(document).on("click", "#btnAddAcademicYear", function () {
    Swal.fire({
      title: "Tambah Tahun Akademik",
      input: "text",
      inputLabel: "Contoh: 2027/2028",
      inputPlaceholder: "2027/2028",
      showCancelButton: true,
      confirmButtonText: "Tambah",
      cancelButtonText: "Batal",
      inputValidator: (value) => {
        if (!value) return "Label wajib diisi.";
      },
    }).then(function (result) {
      if (!result.isConfirmed) return;

      $.post(
        AccManage.api + "?action=save_academic_year",
        { label: result.value },
        function (response) {
          if (!response.success) {
            Swal.fire("Gagal", response.message, "error");
            return;
          }
          loadAcademicYears();
        },
      ).fail(showAjaxError);
    });
  });

  $(document).on("click", ".btn-delete-academic-year", function () {
    const id = $(this).data("id");
    Swal.fire({
      icon: "warning",
      title: "Hapus Tahun Akademik ini?",
      showCancelButton: true,
      confirmButtonText: "Ya, Hapus",
      cancelButtonText: "Batal",
    }).then(function (result) {
      if (!result.isConfirmed) return;
      $.post(
        AccManage.api + "?action=delete_academic_year",
        { id: id },
        function (response) {
          if (response.success) {
            loadAcademicYears();
          } else {
            Swal.fire("Gagal", response.message, "error");
          }
        },
      );
    });
  });
}

/* ===================== TABEL & KOLOM ===================== */

function loadTableDetail() {
  $("#tableManageContainer").html('<p class="text-muted">Memuat data...</p>');

  $.getJSON(
    AccManage.api,
    { action: "detail", code: AccManage.currentCode },
    function (res) {
      if (!res.success) {
        $("#tableManageContainer").html(
          '<p class="text-danger">' + res.message + "</p>",
        );
        return;
      }

      const table = res.data;
      AccManage.currentTableId = table.id;

      let criteriaOptions = '<option value="">-- Belum Ditentukan --</option>';

      $.getJSON(AccManage.api, { action: "criteria" }, function (critRes) {
        if (critRes.success) {
          critRes.data.forEach(function (c) {
            const selected = table.criteria_id == c.id ? "selected" : "";
            criteriaOptions += `<option value="${c.id}" ${selected}>${escapeHtml(c.name)}</option>`;
          });
        }

        $.getJSON(AccManage.api, { action: "units" }, function (unitRes) {
          const allUnits = unitRes.success ? unitRes.data : [];

          $.getJSON(
            AccManage.api,
            { action: "get_table_units", table_id: table.id },
            function (tuRes) {
              const selectedUnitIds = tuRes.success
                ? tuRes.data.map(String)
                : [];

              renderTableDetail(
                table,
                criteriaOptions,
                allUnits,
                selectedUnitIds,
              );
            },
          );
        });
      });
    },
  );
}

function renderTableDetail(table, criteriaOptions, allUnits, selectedUnitIds) {
  let columnsHtml = "";

  if (!table.columns.length) {
    columnsHtml =
      '<li class="list-group-item text-muted small">Belum ada Kolom.</li>';
  } else {
    const totalModeLabels = {
      sum: "Jumlah",
      average: "Rata-rata",
      min: "Minimum",
      max: "Maksimum",
      none: "Tidak Dihitung",
    };

    table.columns.forEach(function (col) {
      columnsHtml += `
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            ${col.group_label ? `<span class="badge bg-secondary me-1">${escapeHtml(col.group_label)}</span>` : ""}
            <strong>${escapeHtml(col.label)}</strong>
            <span class="text-muted small">(${col.data_type === "decimal" ? "Desimal" : "Bulat"}, ${totalModeLabels[col.total_mode] || "Jumlah"})</span>
          </div>
          <div class="d-flex gap-1">
            <button class="btn btn-outline-primary btn-sm btn-edit-column"
              data-id="${col.id}" data-group="${escapeAttr(col.group_label || "")}"
              data-label="${escapeAttr(col.label)}" data-type="${col.data_type}" data-total-mode="${col.total_mode || "sum"}">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-outline-danger btn-sm btn-delete-column" data-id="${col.id}"><i class="bi bi-trash"></i></button>
          </div>
        </li>
      `;
    });
  }

  const html = `
    <div class="card shadow-sm mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>${escapeHtml(table.title)}</strong>
        <div class="d-flex gap-1">
          <button class="btn btn-outline-primary btn-sm btn-edit-table" data-id="${table.id}" data-title="${escapeAttr(table.title)}" data-description="${escapeAttr(table.description || "")}"><i class="bi bi-pencil"></i> Edit Tabel</button>
          <button class="btn btn-outline-danger btn-sm btn-delete-table" data-id="${table.id}"><i class="bi bi-trash"></i></button>
        </div>
      </div>
      <div class="card-body">

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label small mb-1">Kriteria untuk Tabel ini</label>
            <select class="form-select form-select-sm" id="tableCriteriaSelector">
              ${criteriaOptions}
            </select>
          </div>
          <div class="col-md-6">
            <div class="form-check form-switch mb-1">
              <input class="form-check-input" type="checkbox" id="table_all_units" ${selectedUnitIds.length === 0 ? "checked" : ""}>
              <label class="form-check-label small" for="table_all_units">Berlaku untuk Semua Unit Kerja</label>
            </div>
            <div id="tableUnitChecklistWrapper" style="display:${selectedUnitIds.length === 0 ? "none" : "block"};">
              <div class="border rounded p-2" style="max-height:180px; overflow-y:auto;">
                ${allUnits
                  .map(function (u) {
                    const checked = selectedUnitIds.includes(String(u.id))
                      ? "checked"
                      : "";
                    const typeLabel = u.type ? ` (${u.type})` : "";
                    return `<div class="form-check">
                    <input class="form-check-input table-unit-checkbox" type="checkbox" value="${u.id}" id="tbl_unit_${u.id}" ${checked}>
                    <label class="form-check-label small" for="tbl_unit_${u.id}">${escapeHtml(u.code + " - " + u.name + typeLabel)}</label>
                  </div>`;
                  })
                  .join("")}
              </div>
              <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="btnSaveTableUnits"><i class="bi bi-save"></i> Simpan Unit Kerja</button>
            </div>
          </div>
        </div>

        <form id="labelForm" class="row g-2 align-items-end mb-3 p-2 rounded" style="background:#f7f5ff;">
          <input type="hidden" name="table_id" value="${table.id}">
          <div class="col-md-3">
            <label class="form-label small mb-1">Label TS-2 (opsional)</label>
            <input type="text" class="form-control form-control-sm" name="label_ts2" value="${escapeAttr(table.label_ts2 || "")}" placeholder="2023/2024">
          </div>
          <div class="col-md-3">
            <label class="form-label small mb-1">Label TS-1 (opsional)</label>
            <input type="text" class="form-control form-control-sm" name="label_ts1" value="${escapeAttr(table.label_ts1 || "")}" placeholder="2024/2025">
          </div>
          <div class="col-md-3">
            <label class="form-label small mb-1">Label TS (opsional)</label>
            <input type="text" class="form-control form-control-sm" name="label_ts" value="${escapeAttr(table.label_ts || "")}" placeholder="2025/2026">
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-save"></i> Simpan Label Tahun</button>
          </div>
        </form>

        <div class="d-flex justify-content-end mb-2">
          <button class="btn btn-outline-success btn-sm btn-add-column"><i class="bi bi-plus-circle"></i> Tambah Kolom</button>
        </div>

        <ul class="list-group">
          ${columnsHtml}
        </ul>

      </div>
    </div>
  `;

  $("#tableManageContainer").html(html);
}

/* ===================== DAFTAR DOKUMEN ===================== */

function loadDocuments() {
  $("#documentManageContainer").html(
    '<p class="text-muted">Memuat data...</p>',
  );

  $.getJSON(
    AccManage.api,
    {
      action: "documents_by_criteria",
      criteria_id: AccManage.currentDocCriteriaId,
    },
    function (res) {
      if (!res.success) {
        $("#documentManageContainer").html(
          '<p class="text-danger">' + res.message + "</p>",
        );
        return;
      }

      let rowsHtml = "";

      if (!res.data.length) {
        rowsHtml =
          '<li class="list-group-item text-muted small">Belum ada Dokumen untuk Kriteria ini.</li>';
      } else {
        res.data.forEach(function (doc) {
          const unitBadge =
            doc.unit_count > 0
              ? `<span class="badge bg-secondary ms-2">${doc.unit_count} Unit Tertentu</span>`
              : `<span class="badge bg-light text-muted ms-2">Semua Unit</span>`;

          rowsHtml += `
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>${escapeHtml(doc.document_name)} ${unitBadge}</span>
            <div class="d-flex gap-1">
              <button class="btn btn-outline-primary btn-sm btn-edit-document" data-id="${doc.id}" data-name="${escapeAttr(doc.document_name)}"><i class="bi bi-pencil"></i></button>
              <button class="btn btn-outline-danger btn-sm btn-delete-document" data-id="${doc.id}"><i class="bi bi-trash"></i></button>
            </div>
          </li>
        `;
        });
      }

      const html = `
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>Daftar Dokumen</strong>
          <button class="btn btn-outline-success btn-sm" id="btnAddDocument"><i class="bi bi-plus-circle"></i> Tambah Dokumen</button>
        </div>
        <div class="card-body">
          <ul class="list-group">${rowsHtml}</ul>
        </div>
      </div>
    `;

      $("#documentManageContainer").html(html);
    },
  );
}

/* ===================== TAHUN AKADEMIK ===================== */

function loadAcademicYears() {
  $("#academicYearList").html(
    '<li class="list-group-item text-muted">Memuat data...</li>',
  );

  $.getJSON(AccManage.api, { action: "academic_years" }, function (res) {
    if (!res.success) {
      $("#academicYearList").html(
        '<li class="list-group-item text-danger">' + res.message + "</li>",
      );
      return;
    }

    if (!res.data.length) {
      $("#academicYearList").html(
        '<li class="list-group-item text-muted">Belum ada Tahun Akademik.</li>',
      );
      return;
    }

    let html = "";

    res.data.forEach(function (y) {
      html += `
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span>${escapeHtml(y.label)}</span>
          <button class="btn btn-outline-danger btn-sm btn-delete-academic-year" data-id="${y.id}"><i class="bi bi-trash"></i></button>
        </li>
      `;
    });

    $("#academicYearList").html(html);
  });
}

/* ===================== UTIL ===================== */

function showAjaxError() {
  Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

function saveTableUnitsNow(unitIds) {
  $.post(
    AccManage.api + "?action=save_table_units",
    { table_id: AccManage.currentTableId, unit_ids: unitIds },
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
  ).fail(showAjaxError);
}
function escapeAttr(text) {
  return (text || "").replace(/"/g, "&quot;");
}
