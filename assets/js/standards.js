/**
 * ==========================================================
 * SIQUA Enterprise
 * Master Standar
 * File : assets/standards.js
 * ==========================================================
 */

"use strict";

/* ==========================================================
 * CONFIGURATION
 * ==========================================================
 */

const Standard = {
  api: SIQUA.BASE_URL + "master/standards/api.php",

  table: null,

  modal: null,

  form: null,

  isEdit: false,

  currentId: null,

  maxUploadSize: 5 * 1024 * 1024,

  allowedFileTypes: [
    "application/pdf",

    "application/msword",

    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",

    "application/vnd.ms-excel",

    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
  ],
};

/* ==========================================================
 * INITIALIZE
 * ==========================================================
 */

$(document).ready(function () {
  Standard.modal = new bootstrap.Modal(
    document.getElementById("standardModal"),
  );

  Standard.form = $("#standardForm");

  initializeTable();

  registerEvents();
  loadDropdowns();
});

/* ==========================================================
 * DATATABLE
 * ==========================================================
 */

function initializeTable() {
  Standard.table = $("#tableStandards").DataTable({
    processing: true,

    responsive: true,

    destroy: true,

    autoWidth: false,

    ajax: {
      url: Standard.api,

      type: "GET",

      data: {
        action: "list",
      },

      dataSrc: function (response) {
        if (!response.success) {
          showError(response.message);

          return [];
        }

        return response.data;
      },
    },

    columns: [
      {
        data: null,
        className: "text-center",
        render: function (data, type, row, meta) {
          return meta.row + 1;
        },
      },

      {
        data: "code",
      },

      {
        data: "name",
      },

      {
        data: "publish_date",
        className: "text-center",
        defaultContent: "-",
      },

      {
        data: "revision",
        className: "text-center",
      },

      {
        data: "status",
        className: "text-center",
        render: function (data, type, row) {
          const badgeColor = row.badge_color || "secondary";
          const statusName = row.status_name || "-";

          return `<span class="badge bg-${badgeColor}">${statusName}</span>`;
        },
      },

      {
        data: "risk_level_tertinggi",
        className: "text-center",
        render: function (data, type, row) {
          if (!row.jumlah_risiko || row.jumlah_risiko == 0) {
            return '<span class="text-muted" style="font-size:10.5px;">Belum ada Risiko</span>';
          }
          const colorMap = {
            Ekstrem: "#dc2626",
            Tinggi: "#f97316",
            Sedang: "#eab308",
            Rendah: "#22c55e",
          };
          const color = colorMap[row.risk_level_tertinggi] || "#94a3b8";
          const label = row.risk_level_tertinggi || "Belum Dianalisis";
          return `<span class="badge" style="background:${color}; font-size:10px;">${label}</span> <span class="text-muted" style="font-size:9.5px;">(${row.jumlah_risiko})</span>`;
        },
      },

      {
        data: null,
        className: "text-center",
        orderable: false,
        searchable: false,
        render: function (data, type, row) {
          return `<a href="${SIQUA.BASE_URL}master/standards/print_document.php?id=${row.id}" target="_blank" class="btn btn-danger btn-sm" title="Cetak PDF Standar"><i class="bi bi-file-earmark-pdf"></i></a>`;
        },
      },
      {
        data: null,

        className: "text-center",

        orderable: false,

        searchable: false,

        render: function (data, type, row) {
          return `

                        <button
                            class="btn btn-info btn-sm btn-detail"
                            data-id="${row.id}">

                            <i class="bi bi-eye"></i>

                        </button>              
          
                        <button
                            class="btn btn-warning btn-sm btn-edit"
                            data-id="${row.id}">

                            <i class="bi bi-pencil"></i>

                        </button>

                        <button
                            class="btn btn-danger btn-sm btn-delete"
                            data-id="${row.id}">

                            <i class="bi bi-trash"></i>

                        </button>

                    `;
        },
      },
    ],

    language: {
      processing: "Memuat data...",

      search: "Cari :",

      lengthMenu: "Tampilkan _MENU_ data",

      info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",

      infoEmpty: "Tidak ada data",

      zeroRecords: "Data tidak ditemukan",

      paginate: {
        first: "Awal",

        last: "Akhir",

        next: ">",

        previous: "<",
      },
    },
  });
}
/* ==========================================================
 * REGISTER EVENTS
 * ==========================================================
 */

function registerEvents() {
  $("#type_id").on("change", function () {
    loadCategories($(this).val());
  });

  /*
    ----------------------------------------------------------
   IMPORT/EXPORT
    ----------------------------------------------------------
    */

  $("#btnImportStandard").on("click", function () {
    $("#importFile").trigger("click");
  });

  $("#importFile").on("change", function () {
    const file = this.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("file", file);

    showLoading();

    $.ajax({
      url: Standard.api.replace("api.php", "import.php"),
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: function (response) {
        Swal.close();
        $("#importFile").val("");
        reloadTable();

        let detail = "";
        if (
          response.data &&
          response.data.errors &&
          response.data.errors.length
        ) {
          detail =
            "<br><small>" +
            response.data.errors.slice(0, 5).join("<br>") +
            "</small>";
        }

        Swal.fire({
          icon: response.success ? "success" : "error",
          title: response.success ? "Import Selesai" : "Gagal",
          html: response.message + detail,
        });
      },
      error: function () {
        Swal.close();
        $("#importFile").val("");
        showError("Gagal mengimpor data.");
      },
    });
  });

  /*
    ----------------------------------------------------------
    Tambah Standar
    ----------------------------------------------------------
    */

  $("#btnAddStandard").on("click", function () {
    resetForm();

    loadCategories(null);

    Standard.isEdit = false;

    Standard.currentId = null;

    $("#modalTitle").text("Tambah Standar");

    Standard.modal.show();
  });

  /*
    ----------------------------------------------------------
    Detail
    ----------------------------------------------------------
    */

  $("#tableStandards").on("click", ".btn-detail", function () {
    showDetail($(this).data("id"));
  });
  /*
    ----------------------------------------------------------
    Edit
    ----------------------------------------------------------
    */

  $("#tableStandards").on("click", ".btn-edit", function () {
    loadData($(this).data("id"));
  });

  /*
    ----------------------------------------------------------
    Delete
    ----------------------------------------------------------
    */

  $("#tableStandards").on("click", ".btn-delete", function () {
    deleteData($(this).data("id"));
  });

  /*
    ----------------------------------------------------------
    Submit
    ----------------------------------------------------------
    */

  Standard.form.on("submit", function (e) {
    e.preventDefault();

    saveData();
  });

  /*
    ----------------------------------------------------------
    Reset Form
    ----------------------------------------------------------
    */

  /*
    ----------------------------------------------------------
    Modal Hidden
    ----------------------------------------------------------
    */

  $("#standardModal").on("hidden.bs.modal", function () {
    resetForm();
  });

  /*
    ----------------------------------------------------------
    Validasi Upload
    ----------------------------------------------------------
    */

  $("#document").on("change", validateUpload);
}
/*
    ----------------------------------------------------------
   Load Dropdown
    ----------------------------------------------------------
    */

function loadDropdowns() {
  $.getJSON(Standard.api, { action: "types" }, function (res) {
    if (!res.success) return;
    const $type = $("#type_id");
    res.data.forEach(function (row) {
      $type.append(
        `<option value="${row.id}">${row.code} - ${row.name}</option>`,
      );
    });
  });

  $.getJSON(Standard.api, { action: "statuses" }, function (res) {
    if (!res.success) return;
    const $status = $("#status_id");
    res.data.forEach(function (row) {
      $status.append(`<option value="${row.id}">${row.name}</option>`);
    });
  });

  $.getJSON(Standard.api, { action: "document_types" }, function (res) {
    if (!res.success) return;
    const $doc = $("#document_type_id");
    res.data.forEach(function (row) {
      $doc.append(
        `<option value="${row.id}" data-ext="${row.extension}">${row.name}</option>`,
      );
    });
  });
}

function loadCategories(typeId, selectedId) {
  const $category = $("#category_id");

  if (!typeId) {
    $category.html('<option value="">Pilih Jenis Standar dahulu</option>');
    return;
  }

  $category.html('<option value="">Memuat...</option>');

  $.getJSON(
    Standard.api,
    { action: "categories", type_id: typeId },
    function (res) {
      $category.html('<option value="">Pilih Kategori</option>');
      if (!res.success) return;
      res.data.forEach(function (row) {
        const selected =
          selectedId && Number(selectedId) === Number(row.id) ? "selected" : "";
        $category.append(
          `<option value="${row.id}" ${selected}>${row.code} - ${row.name}</option>`,
        );
      });
    },
  );
}
/* ==========================================================
 * LOAD DATA
 * ==========================================================
 */

function loadData(id) {
  $.ajax({
    url: Standard.api,

    type: "GET",

    dataType: "json",

    data: {
      action: "get",

      id: id,
    },

    beforeSend: function () {
      showLoading();
    },

    success: function (response) {
      Swal.close();

      if (!response.success) {
        showError(response.message);

        return;
      }

      const row = response.data;

      Standard.isEdit = true;

      Standard.currentId = row.id;

      $("#modalTitle").text("Edit Standar");

      $("#id").val(row.id);

      $("#code").val(row.code);

      $("#name").val(row.name);

      $("#publish_date").val(row.publish_date);

      $("#revision").val(row.revision);

      $("#status").val(row.status);

      $("#document").val("");

      $("#type_id").val(row.type_id);
      $("#status_id").val(row.status_id);
      $("#document_type_id").val(row.document_type_id);
      $("#document_number").val(row.document_number);

      $("#rasional").val(row.rasional);
      $("#pihak_bertanggung_jawab").val(row.pihak_bertanggung_jawab);
      $("#definisi_istilah").val(row.definisi_istilah);
      $("#dokumen_terkait").val(row.dokumen_terkait);
      $("#referensi").val(row.referensi);

      const processKeys = [
        "perumusan",
        "pemeriksaan",
        "persetujuan",
        "penetapan",
        "pengendalian",
      ];

      processKeys.forEach(function (key) {
        $("#" + key + "_nama").val(row[key + "_nama"]);
        $("#" + key + "_jabatan").val(row[key + "_jabatan"]);
        $("#" + key + "_tanggal").val(row[key + "_tanggal"]);

        const ttdPath = row[key + "_ttd"];
        if (ttdPath) {
          $("#" + key + "_ttd_preview").html(
            '<img src="' +
              SIQUA.BASE_URL +
              ttdPath +
              '" style="max-height:50px; border:1px solid #ddd; border-radius:4px; padding:2px;">',
          );
        } else {
          $("#" + key + "_ttd_preview").html(
            '<span class="text-muted small">Belum ada TTD.</span>',
          );
        }
      });

      loadCategories(row.type_id, row.category_id);

      resetStatements();
      loadNonProdiUnitsForStatements();

      $.getJSON(
        Standard.api,
        { action: "statements", standard_id: row.id },
        function (res) {
          if (res.success && res.data.length) {
            res.data.forEach(function (s) {
              addStatementRow(
                s.statement_text,
                s.owner_type || "prodi",
                s.owner_unit_id || "",
                s.id || "",
              );
            });
          } else {
            addStatementRow();
          }

          Standard.modal.show();
        },
      );
    },

    error: function () {
      Swal.close();

      showError("Gagal mengambil data.");
    },
  });
}

function showDetail(id) {
  $.ajax({
    url: Standard.api,
    type: "GET",
    dataType: "json",
    data: { action: "get", id: id },
    beforeSend: function () {
      showLoading();
    },
    success: function (response) {
      Swal.close();
      if (!response.success) {
        showError(response.message);
        return;
      }
      const row = response.data;

      $("#dt_code").text(row.code || "-");
      $("#dt_revision").text(row.revision ?? "-");
      $("#dt_status").html(
        `<span class="badge bg-${row.badge_color || "secondary"}">${row.status_name || "-"}</span>`,
      );
      $("#dt_publish_date").text(row.publish_date || "-");
      $("#dt_type").text(row.type_name || "-");
      $("#dt_category").text(row.category_name || "-");
      $("#dt_doctype").text(row.document_type_name || "-");
      $("#dt_name").text(row.name || "-");
      $("#dt_document_number").text(row.document_number || "-");

      $("#dt_print_link").attr(
        "href",
        SIQUA.BASE_URL + "master/standards/print_document.php?id=" + row.id,
      );

      new bootstrap.Modal(
        document.getElementById("standardDetailModal"),
      ).show();
    },
    error: function () {
      Swal.close();
      showError("Gagal mengambil data.");
    },
  });
}

/* ==========================================================
 * VALIDATE FILE
 * ==========================================================
 */

function validateUpload() {
  const file = this.files[0];

  if (!file) {
    return;
  }

  if (!Standard.allowedFileTypes.includes(file.type)) {
    showWarning("Dokumen hanya boleh PDF, DOC, DOCX, XLS atau XLSX.");

    $(this).val("");

    return;
  }

  if (file.size > Standard.maxUploadSize) {
    showWarning("Ukuran maksimum file adalah 5 MB.");

    $(this).val("");
  }
}

/* ==========================================================
 * RESET FORM
 * ==========================================================
 */

function resetForm() {
  Standard.form.trigger("reset");
  $("#id").val("");
  $("#document").val("");
  $("#revision").val(0);
  resetStatements();
  loadNonProdiUnitsForStatements();
  addStatementRow();
  $("#category_id").html(
    '<option value="">Pilih Jenis Standar dahulu</option>',
  );

  [
    "perumusan",
    "pemeriksaan",
    "persetujuan",
    "penetapan",
    "pengendalian",
  ].forEach(function (key) {
    $("#" + key + "_ttd_preview").empty();
  });

  Standard.isEdit = false;
  Standard.currentId = null;
}

/* ==========================================================
 * HELPER
 * ==========================================================
 */

function showSuccess(message) {
  Swal.fire({
    icon: "success",

    title: "Berhasil",

    text: message,

    timer: 1800,

    showConfirmButton: false,
  });
}

function showError(message) {
  Swal.fire({
    icon: "error",

    title: "Gagal",

    text: message,
  });
}

function showWarning(message) {
  Swal.fire({
    icon: "warning",

    title: "Perhatian",

    text: message,
  });
}

function showLoading() {
  Swal.fire({
    title: "Memproses...",

    text: "Mohon tunggu",

    allowOutsideClick: false,

    didOpen: () => {
      Swal.showLoading();
    },
  });
}
/* ==========================================================
 * SAVE DATA
 * ==========================================================
 */

function saveData() {
  const formData = new FormData(document.getElementById("standardForm"));

  const action = Standard.isEdit ? "update" : "create";

  setSaveButton(true);

  $.ajax({
    url: Standard.api + "?action=" + action,

    type: "POST",

    data: formData,

    processData: false,

    contentType: false,

    dataType: "json",

    success: function (response) {
      setSaveButton(false);

      if (!response.success) {
        showError(response.message);

        return;
      }

      Standard.modal.hide();

      resetForm();

      reloadTable();

      showSuccess(response.message);
    },

    error: function (xhr) {
      setSaveButton(false);

      let message = "Terjadi kesalahan pada server.";

      if (xhr.responseJSON && xhr.responseJSON.message) {
        message = xhr.responseJSON.message;
      }

      showError(message);
    },
  });
}

/* ==========================================================
 * DELETE DATA
 * ==========================================================
 */

function deleteData(id) {
  Swal.fire({
    title: "Hapus Standar?",

    text: "Data yang dihapus tidak dapat dikembalikan.",

    icon: "warning",

    showCancelButton: true,

    confirmButtonText: "Ya, Hapus",

    cancelButtonText: "Batal",

    confirmButtonColor: "#dc3545",
  }).then(function (result) {
    if (!result.isConfirmed) {
      return;
    }

    showLoading();

    $.ajax({
      url: Standard.api + "?action=delete",

      type: "POST",

      dataType: "json",

      data: {
        id: id,
      },

      success: function (response) {
        Swal.close();

        if (!response.success) {
          showError(response.message);

          return;
        }

        reloadTable();

        showSuccess(response.message);
      },

      error: function (xhr) {
        Swal.close();

        let message = "Terjadi kesalahan pada server.";

        if (xhr.responseJSON && xhr.responseJSON.message) {
          message = xhr.responseJSON.message;
        }

        showError(message);
      },
    });
  });
}

/* ==========================================================
 * RELOAD DATATABLE
 * ==========================================================
 */

function reloadTable() {
  if (Standard.table) {
    Standard.table.ajax.reload(
      null,

      false,
    );
  }
}

/* ==========================================================
 * SAVE BUTTON
 * ==========================================================
 */

function setSaveButton(loading) {
  const button = $("#btnSaveStandard");

  if (loading) {
    button

      .prop("disabled", true)

      .html('<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...');
  } else {
    button

      .prop("disabled", false)

      .html("Simpan");
  }
}

/* ==========================================================
 * DOWNLOAD DOCUMENT
 * ==========================================================
 */

function downloadDocument(filePath) {
  if (!filePath) {
    showWarning("Dokumen tidak tersedia.");

    return;
  }

  window.open(encodeURI(filePath), "_blank");
}

let statementCount = 0;

let nonProdiUnitsCache = [];

function loadNonProdiUnitsForStatements() {
  $.getJSON(
    SIQUA.BASE_URL + "master/standards/api.php",
    { action: "non_prodi_units" },
    function (res) {
      if (res.success) {
        nonProdiUnitsCache = res.data;
      }
    },
  );
}

function buildUnitOptionsHtml(selectedUnitId) {
  let html = '<option value="">-- Pilih Unit --</option>';
  nonProdiUnitsCache.forEach(function (u) {
    const selected = String(u.id) === String(selectedUnitId) ? "selected" : "";
    html += `<option value="${u.id}" ${selected}>${u.code} - ${u.name}</option>`;
  });
  return html;
}

function addStatementRow(
  value = "",
  ownerType = "prodi",
  ownerUnitId = "",
  statementId = "",
) {
  statementCount++;

  const row = `
    <div class="border rounded p-2 mb-2 statement-row">
      <input type="hidden" name="pernyataan_id[]" value="${statementId}">
      <div class="input-group mb-2">
        <textarea class="form-control form-control-sm" name="pernyataan_text[]" rows="2" placeholder="Contoh: Ketua Program Studi menyatakan...">${value}</textarea>
        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-statement" title="Hapus">
          <i class="bi bi-trash"></i>
        </button>
      </div>
      <div class="row g-2">
        <div class="col-md-5">
          <label class="form-label small text-muted mb-1">Kepemilikan</label>
          <select class="form-select form-select-sm statement-owner-type" name="pernyataan_owner_type[]">
            <option value="prodi" ${ownerType === "prodi" ? "selected" : ""}>Program Studi (semua Prodi)</option>
            <option value="unit" ${ownerType === "unit" ? "selected" : ""}>Unit Non-Prodi Tertentu</option>
          </select>
        </div>
        <div class="col-md-7 statement-owner-unit-wrapper" style="${ownerType === "unit" ? "" : "display:none;"}">
          <label class="form-label small text-muted mb-1">Unit</label>
          <select class="form-select form-select-sm statement-owner-unit" name="pernyataan_owner_unit_id[]">
            ${buildUnitOptionsHtml(ownerUnitId)}
          </select>
        </div>
      </div>
    </div>
  `;

  $("#statementList").append(row);
}

function resetStatements() {
  $("#statementList").empty();
  statementCount = 0;
}

$(document).on("click", "#btnAddStatement", function () {
  addStatementRow();
});

$(document).on("click", ".btn-remove-statement", function () {
  $(this).closest(".statement-row").remove();
});

$(document).on("change", ".statement-owner-type", function () {
  const wrapper = $(this)
    .closest(".statement-row")
    .find(".statement-owner-unit-wrapper");
  if ($(this).val() === "unit") {
    wrapper.show();
  } else {
    wrapper.hide();
  }
});

/* ==========================================================
 * END OF FILE
 * ==========================================================
 */
