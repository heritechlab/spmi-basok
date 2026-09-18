/*
|--------------------------------------------------------------------------
| SIQUA Enterprise
|--------------------------------------------------------------------------
| Module : Master Unit Kerja
| File   : assets/js/unit.js
|--------------------------------------------------------------------------
| Version : 2.0
|--------------------------------------------------------------------------
*/

"use strict";

/*
|--------------------------------------------------------------------------
| API Endpoint
|--------------------------------------------------------------------------
*/

const UnitAPI = {
  list: SIQUA.BASE_URL + "master/unit/api/list.php",

  get: SIQUA.BASE_URL + "master/unit/api/get.php",

  save: SIQUA.BASE_URL + "master/unit/api/save.php",

  update: SIQUA.BASE_URL + "master/unit/api/update.php",

  delete: SIQUA.BASE_URL + "master/unit/api/delete.php",

  parent: SIQUA.BASE_URL + "master/unit/api/parent.php",
};

/*
|--------------------------------------------------------------------------
| Namespace
|--------------------------------------------------------------------------
*/

const SIQUAUnit = {};

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

SIQUAUnit.table = null;

SIQUAUnit.modal = null;

SIQUAUnit.loading = null;

SIQUAUnit.currentId = 0;

SIQUAUnit.isSaving = false;

/*
|--------------------------------------------------------------------------
| Cached Selector
|--------------------------------------------------------------------------
*/

SIQUAUnit.ui = {
  table: $("#unitTable"),

  form: $("#unitForm"),

  modal: $("#unitModal"),

  loading: $("#loadingModal"),

  btnAdd: $("#btnAddUnit"),

  btnSave: $("#btnSaveUnit"),

  btnRefresh: $("#btnRefresh"),

  btnReset: $("#btnReset"),

  btnFilter: $("#btnFilter"),

  search: $("#searchUnit"),

  filterType: $("#filterType"),

  filterStatus: $("#filterStatus"),

  parent: $("#parent_id"),

  total: $("#totalUnitBadge"),
};

/*
|--------------------------------------------------------------------------
| Default Form Value
|--------------------------------------------------------------------------
*/

SIQUAUnit.defaultForm = {
  id: 0,

  code: "",

  name: "",

  short_name: "",

  type: "",

  parent_id: "",

  head_name: "",

  email: "",

  phone: "",

  description: "",

  is_auditable: 1,

  status: 1,

  sort_order: 0,
};
/*
|--------------------------------------------------------------------------
| Initialize
|--------------------------------------------------------------------------
*/

SIQUAUnit.init = function () {
  console.log("SIQUA :: Master Unit Initialized");

  this.modal = new bootstrap.Modal(document.getElementById("unitModal"));

  this.loading = new bootstrap.Modal(document.getElementById("loadingModal"));

  this.initializeTable();

  this.loadParentOptions();

  this.bindEvents();
};

/*
|--------------------------------------------------------------------------
| Loading
|--------------------------------------------------------------------------
*/

SIQUAUnit.showLoading = function () {
  if (this.loading) {
    this.loading.show();
  }
};

SIQUAUnit.hideLoading = function () {
  if (this.loading) {
    this.loading.hide();
  }
};

/*
|--------------------------------------------------------------------------
| Notification
|--------------------------------------------------------------------------
*/

SIQUAUnit.notifySuccess = function (message) {
  Swal.fire({
    icon: "success",

    title: "Berhasil",

    text: message,

    timer: 1800,

    showConfirmButton: false,
  });
};

SIQUAUnit.notifyWarning = function (message) {
  Swal.fire({
    icon: "warning",

    title: "Perhatian",

    text: message,
  });
};

SIQUAUnit.notifyError = function (message) {
  Swal.fire({
    icon: "error",

    title: "Terjadi Kesalahan",

    text: message,
  });
};
/*
|--------------------------------------------------------------------------
| Initialize DataTable
|--------------------------------------------------------------------------
*/

SIQUAUnit.initializeTable = function () {
  const self = this;

  self.table = self.ui.table.DataTable({
    processing: true,

    responsive: true,

    autoWidth: false,

    destroy: true,

    pageLength: 10,

    searching: true,

    ordering: true,

    info: true,

    lengthChange: true,

    ajax: {
      url: UnitAPI.list,

      type: "GET",

      data: function (d) {
        d.keyword = $("#searchUnit").val();

        d.type = $("#filterType").val();

        d.status = $("#filterStatus").val();

        d.page = 1;

        d.limit = 20;
      },

      dataSrc: function (response) {
        if (!response.success) {
          self.notifyError(response.message);

          return [];
        }

        self.updateTotalBadge(response);

        return response.data;
      },

      error: function (xhr) {
        console.error(xhr);

        self.notifyError("Gagal mengambil data Unit Kerja.");
      },
    },

    columns: [
      {
        data: "code",
      },

      {
        data: "name",
      },

      {
        data: "type",
        render: self.renderTypeBadge,
      },

      {
        data: "head_name",
        defaultContent: "-",
      },

      {
        data: "is_auditable",
        className: "text-center",
        render: self.renderAuditableBadge,
      },

      {
        data: null,
        orderable: false,
        searchable: false,
        className: "text-center",
        render: self.renderActionButton,
      },
    ],

    order: [[1, "asc"]],

    language: {
      emptyTable: "Belum ada data Unit Kerja.",

      zeroRecords: "Data tidak ditemukan.",

      processing: "Memuat data...",

      search: "Cari :",

      lengthMenu: "Tampilkan _MENU_ data",

      info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",

      paginate: {
        previous: "←",

        next: "→",
      },
    },
  });
};
/*
|--------------------------------------------------------------------------
| Reload Table
|--------------------------------------------------------------------------
*/

SIQUAUnit.reloadTable = function () {
  SIQUAUnit.table.ajax.reload(null, true);
};

/*
|--------------------------------------------------------------------------
| Load Parent Options
|--------------------------------------------------------------------------
*/

SIQUAUnit.loadParentOptions = function () {
  $.getJSON(UnitAPI.parent, function (response) {
    if (!response.success) return;

    const $select = $("#parent_id");

    $select.empty();
    $select.append('<option value="">-- Tanpa Parent --</option>');

    response.data.forEach(function (row) {
      $select.append(
        `<option value="${row.id}">${row.code} - ${row.name}</option>`,
      );
    });
  });
};

/*
|--------------------------------------------------------------------------
| Bind Events (placeholder — event handler sudah didaftarkan
| langsung lewat $(document).on(...) di bagian lain file ini)
|--------------------------------------------------------------------------
*/

SIQUAUnit.bindEvents = function () {
  // Sengaja dikosongkan.
};
/*
|--------------------------------------------------------------------------
| Reload DataTable
|--------------------------------------------------------------------------
*/

SIQUAUnit.reloadTable = function () {
  if (SIQUAUnit.table) {
    SIQUAUnit.table.ajax.reload(null, false);
  }
};

/*
|--------------------------------------------------------------------------
| Reload Table
|--------------------------------------------------------------------------
*/

SIQUAUnit.reloadTable = function (resetPaging = false) {
  if (SIQUAUnit.table) {
    SIQUAUnit.table.ajax.reload(null, resetPaging);
  }
};
/*
|--------------------------------------------------------------------------
| Update Total Badge
|--------------------------------------------------------------------------
*/

SIQUAUnit.updateTotalBadge = function (response) {
  $("#totalUnitBadge").text(response.meta.total + " Unit");
};
/*
|--------------------------------------------------------------------------
| Render Type Badge
|--------------------------------------------------------------------------
*/

SIQUAUnit.renderTypeBadge = function (type) {
  let color = "secondary";

  switch (type) {
    case "Program Studi":
      color = "success";
      break;

    case "Lembaga":
      color = "info";
      break;

    case "Bagian":
      color = "primary";
      break;

    case "UPT":
      color = "warning";
      break;

    case "Pusat":
      color = "dark";
      break;

    case "Fakultas":
      color = "danger";
      break;

    case "Direktorat":
      color = "secondary";
      break;

    case "Biro":
      color = "secondary";
      break;

    case "Unit Pendukung":
      color = "secondary";
      break;
  }

  return `
        <span class="badge badge-unit bg-${color}">
            ${type}
        </span>
    `;
};
/*
|--------------------------------------------------------------------------
| Render Status Badge
|--------------------------------------------------------------------------
*/

SIQUAUnit.renderStatusBadge = function (status) {
  if (Number(status) === 1) {
    return `
            <span class="badge bg-success">
                Aktif
            </span>
        `;
  }

  return `
        <span class="badge bg-secondary">
            Nonaktif
        </span>
    `;
};
/*
|--------------------------------------------------------------------------
| Render Action Button
|--------------------------------------------------------------------------
*/

SIQUAUnit.renderActionButton = function (data, type, row) {
  if (!window.SIQUA_CAN_MANAGE) {
    return `
        <button class="btn btn-secondary btn-sm btn-locked" title="Anda tidak memiliki akses untuk membuat akun">
            <i class="bi bi-person-plus"></i>
        </button>
        <button class="btn btn-warning btn-sm btn-locked" title="Anda tidak memiliki akses untuk mengubah data">
            <i class="bi bi-pencil"></i>
        </button>
        <button class="btn btn-danger btn-sm btn-locked" title="Anda tidak memiliki akses untuk menghapus data">
            <i class="bi bi-trash"></i>
        </button>
    `;
  }

  const accountBtn = row.account_user_id
    ? `<button class="btn btn-info btn-sm btn-reset-account" data-id="${row.id}" data-name="${row.name}" title="Reset Password Akun">
         <i class="bi bi-key"></i>
       </button>`
    : `<button class="btn btn-secondary btn-sm btn-create-account" data-id="${row.id}" data-name="${row.name}" title="Buat Akun Login">
         <i class="bi bi-person-plus"></i>
       </button>`;

  return `
        ${accountBtn}

        <button
            class="btn btn-warning btn-sm btn-edit"
            data-id="${row.id}"
            title="Edit">

            <i class="bi bi-pencil"></i>

        </button>

        <button
            class="btn btn-danger btn-sm btn-delete"
            data-id="${row.id}"
            data-name="${row.name}"
            title="Hapus">

            <i class="bi bi-trash"></i>

        </button>
    `;
};
/*
|--------------------------------------------------------------------------
| Render Audit Badge
|--------------------------------------------------------------------------
*/

SIQUAUnit.renderAuditableBadge = function (value) {
  if (parseInt(value) === 1) {
    return `
            <span class="audit-badge audit-yes">
                <i class="bi bi-check-circle-fill"></i>
                Diaudit
            </span>
        `;
  }

  return `
        <span class="audit-badge audit-no">
            <i class="bi bi-x-circle-fill"></i>
            Tidak Diaudit
        </span>
    `;
};
/*
|--------------------------------------------------------------------------
| Tambah Unit
|--------------------------------------------------------------------------
*/

$(document).on("click", "#btnAddUnit", function () {
  $("#unitForm")[0].reset();

  $("#id").val("");

  $("#parent_id").trigger("change");

  $("#unitModalLabel").text("Tambah Unit");

  $("#unitModalLabel").text("Edit Unit Kerja");

  $("#unitModal").modal("show");
});

/*
|--------------------------------------------------------------------------
| Filter Jenis
|--------------------------------------------------------------------------
*/

$(document).on("change", "#filterType", function () {
  SIQUAUnit.reloadTable();
});
/*
|--------------------------------------------------------------------------
| Filter Status
|--------------------------------------------------------------------------
*/

$(document).on("change", "#filterStatus", function () {
  SIQUAUnit.reloadTable();
});

/*
|--------------------------------------------------------------------------
| Edit Unit
|--------------------------------------------------------------------------
*/

$(document).on("click", ".btn-edit", function () {
  const id = $(this).data("id");

  $.get(
    "api/get.php",
    {
      id: id,
    },
    function (response) {
      if (!response.success) {
        Swal.fire("Error", response.message, "error");

        return;
      }

      const d = response.data;

      $("#id").val(d.id);

      $("#code").val(d.code);

      $("#short_name").val(d.short_name);

      $("#name").val(d.name);

      $("#type").val(d.type);

      $("#parent_id").val(d.parent_id);

      $("#head_name").val(d.head_name);

      $("#email").val(d.email);

      $("#phone").val(d.phone);

      $("#description").val(d.description);

      $("#is_auditable").val(d.is_auditable);

      $("#status").val(d.status);

      $("#sort_order").val(d.sort_order);

      $("#unitModalLabel").text("Edit Unit Kerja");

      $("#unitModal").modal("show");
    },
    "json",
  );
});

/*
|--------------------------------------------------------------------------
|reset
|--------------------------------------------------------------------------
*/

$(document).on("click", ".btn-create-account, .btn-reset-account", function () {
  const id = $(this).data("id");
  const name = $(this).data("name");
  const isReset = $(this).hasClass("btn-reset-account");

  Swal.fire({
    icon: "question",
    title: isReset
      ? `Reset password akun untuk "${name}"?`
      : `Buat akun login untuk "${name}"?`,
    text: "Password baru akan dibuat otomatis dan dikirim ke email unit ini.",
    showCancelButton: true,
    confirmButtonText: "Ya, Lanjutkan",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    const loadingModal = new bootstrap.Modal(
      document.querySelector(window.SIQUA_UNIT.loading),
    );
    loadingModal.show();

    $.ajax({
      url: SIQUA.BASE_URL + "master/unit/api/generate_account.php",
      type: "POST",
      dataType: "json",
      data: { unit_id: id },
      success: function (response) {
        loadingModal.hide();

        if (!response.success) {
          Swal.fire("Gagal", response.message, "error");
          return;
        }

        Swal.fire({
          icon: "success",
          title: "Berhasil",
          html: `${response.message}<br><br><strong>Username:</strong> ${response.data.username}<br><strong>Password:</strong> ${response.data.password}<br><small class="text-muted">Catat sekarang, informasi ini tidak akan tampil lagi.</small>`,
        });

        SIQUAUnit.reloadTable();
      },
      error: function () {
        loadingModal.hide();
        Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
      },
    });
  });
});

/*
|--------------------------------------------------------------------------
| Save / Update Unit
|--------------------------------------------------------------------------
*/

$(document).on("submit", "#unitForm", function (e) {
  e.preventDefault();

  const id = $("#id").val();

  const url = id && id !== "0" ? "api/update.php" : "api/save.php";

  $.ajax({
    url: url,

    type: "POST",

    data: $(this).serialize(),

    dataType: "json",

    success: function (res) {
      if (res.success) {
        Swal.fire({
          icon: "success",

          title: "Berhasil",

          text: res.message,
        });

        $("#unitModal").modal("hide");

        $("#unitTable").DataTable().ajax.reload(null, false);
      } else {
        Swal.fire({
          icon: "warning",

          title: "Validasi",

          text: res.message,
        });
      }
    },

    error: function () {
      Swal.fire({
        icon: "error",

        title: "Error",

        text: "Terjadi kesalahan pada server.",
      });
    },
  });
});

/*
|--------------------------------------------------------------------------
| Delete Unit
|--------------------------------------------------------------------------
*/

$(document).on("click", ".btn-delete", function () {
  const id = $(this).data("id");

  const name = $(this).data("name");

  Swal.fire({
    icon: "warning",

    title: "Hapus Unit Kerja",

    html: `
        <div class="text-center">

            <div class="fw-bold fs-5 text-danger mb-3">

                ${name}

            </div>

            <div class="mb-2">

                Unit kerja ini akan
                <strong>dinonaktifkan</strong>.

            </div>

            <div class="small text-muted">

                Data masih dapat dipulihkan
                oleh Administrator.

            </div>

        </div>
    `,

    showCancelButton: true,

    confirmButtonColor: "#dc3545",

    cancelButtonColor: "#6c757d",

    confirmButtonText: '<i class="bi bi-trash"></i> Ya, Nonaktifkan',

    cancelButtonText: '<i class="bi bi-x-circle"></i> Batal',

    reverseButtons: true,

    focusCancel: true,
  }).then((result) => {
    if (!result.isConfirmed) {
      return;
    }

    deleteUnit(id);
  });
});
function deleteUnit(id) {
  $.ajax({
    url: "api/delete.php",

    type: "POST",

    data: {
      id: id,
    },

    dataType: "json",

    success: function (res) {
      if (res.success) {
        Swal.fire({
          icon: "success",

          title: "Berhasil",

          text: res.message,
        });

        $("#unitTable").DataTable().ajax.reload(null, false);
      } else {
        Swal.fire({
          icon: "warning",

          title: "Validasi",

          text: res.message,
        });
      }
    },

    error: function () {
      Swal.fire({
        icon: "error",

        title: "Error",

        text: "Server Error.",
      });
    },
  });
}

/*
|--------------------------------------------------------------------------
| Terapkan Filter
|--------------------------------------------------------------------------
*/

$(document).on("click", "#btnFilter", function () {
  $("#unitTable").DataTable().ajax.reload();
});

/*
|--------------------------------------------------------------------------
| Reset Filter
|--------------------------------------------------------------------------
*/

$(document).on("click", "#btnReset", function () {
  $("#searchUnit").val("");

  $("#filterType").val("");

  $("#filterStatus").val("");

  $("#unitTable").DataTable().ajax.reload();
});
/*
|--------------------------------------------------------------------------
| Refresh
|--------------------------------------------------------------------------
*/

$(document).on("click", "#btnRefresh", function () {
  $("#searchUnit").val("");

  $("#filterType").val("");

  $("#filterStatus").val("");

  SIQUAUnit.reloadTable(true);
});

/*
|--------------------------------------------------------------------------
| Live Search
|--------------------------------------------------------------------------
*/

let searchTimer;

$(document).on("keyup", "#searchUnit", function () {
  clearTimeout(searchTimer);

  searchTimer = setTimeout(function () {
    $("#unitTable").DataTable().ajax.reload();
  }, 400);
});

/* ==========================================================
 * SAVE UNIT
 * ========================================================== */

$(document).on("submit", "#unitForm", function (e) {
  e.preventDefault();

  saveUnit();
});

function saveUnit() {
  console.log("SAVE UNIT");
}
/*
|--------------------------------------------------------------------------
| Render Parent
|--------------------------------------------------------------------------
*/

SIQUAUnit.renderParent = function (parent) {
  if (!parent) {
    return "-";
  }

  return parent;
};

/*
|--------------------------------------------------------------------------
| Document Ready
|--------------------------------------------------------------------------
*/

$(function () {
  SIQUAUnit.init();
});
