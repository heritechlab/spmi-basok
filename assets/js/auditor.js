const Auditor = {
  api: SIQUA.BASE_URL + "master/auditor/api.php",
  table: null,
  modal: null,
  form: null,
  isEdit: false,
  currentId: null,
};

$(document).ready(function () {
  Auditor.modal = new bootstrap.Modal(document.getElementById("auditorModal"));
  Auditor.form = $("#auditorForm");

  initializeTable();
  registerEvents();
});

function initializeTable() {
  Auditor.table = $("#tableAuditor").DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    ordering: false,
    searching: true,
    pageLength: 10,
    lengthMenu: [
      [10, 25, 50, 100],
      [10, 25, 50, 100],
    ],

    ajax: {
      url: Auditor.api,
      type: "GET",
      data: function (d) {
        d.action = "list";
        d.status = $("#filterStatus").val();
      },
    },

    columnDefs: [
      {
        targets: [6, 7],
        className: "text-center",
        orderable: false,
        searchable: false,
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
        next: "\u203a",
        previous: "\u2039",
      },
    },
  });
}

function reloadTable() {
  Auditor.table.ajax.reload(null, false);
}

function resetForm() {
  document.getElementById("auditorForm").reset();
  $("#id").val("");
  Auditor.isEdit = false;
  Auditor.currentId = null;
}

function registerEvents() {
  $("#btnAddAuditor").on("click", function () {
    resetForm();
    Auditor.isEdit = false;
    Auditor.currentId = null;
    $("#modalTitle").text("Tambah Auditor");
    $("#password").prop("required", true);
    Auditor.modal.show();
  });

  $("#tableAuditor").on("click", ".btn-edit", function () {
    loadData($(this).data("id"));
  });

  $("#tableAuditor").on("click", ".btn-delete", function () {
    deleteData($(this).data("id"));
  });

  Auditor.form.on("submit", function (e) {
    e.preventDefault();
    saveData();
  });

  $("#auditorModal").on("hidden.bs.modal", function () {
    resetForm();
  });
  $("#filterStatus").on("change", function () {
    reloadTable();
  });
  $("#tableAuditor").on("click", ".btn-detail", function () {
    showDetail($(this).data("id"));
  });
}
function showDetail(id) {
  $.ajax({
    url: Auditor.api,
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

      $("#dt_full_name").text(row.full_name || "-");
      $("#dt_nidn_nip").text(row.nidn_nip || "-");
      $("#dt_username").text(row.username || "-");
      $("#dt_email").text(row.email || "-");
      $("#dt_phone").text(row.phone || "-");
      $("#dt_status").html(
        Number(row.status) === 1
          ? '<span class="badge bg-success">Aktif</span>'
          : '<span class="badge bg-secondary">Nonaktif</span>',
      );
      $("#dt_certificate_number").text(row.certificate_number || "-");

      if (row.certificate_file) {
        $("#dt_certificate").html(
          `<a href="${SIQUA.BASE_URL}${row.certificate_file}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-filetype-pdf"></i> ${row.certificate_original_name || "Lihat Sertifikat"}</a>`,
        );
      } else {
        $("#dt_certificate").text("-");
      }

      new bootstrap.Modal(document.getElementById("auditorDetailModal")).show();
    },
    error: function () {
      Swal.close();
      showError("Gagal mengambil data.");
    },
  });
}

function loadData(id) {
  $.ajax({
    url: Auditor.api,
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

      resetForm();

      $("#id").val(row.id);
      $("#full_name").val(row.full_name);
      $("#nidn_nip").val(row.nidn_nip);
      $("#username").val(row.username);
      $("#email").val(row.email);
      $("#phone").val(row.phone);
      $("#certificate_number").val(row.certificate_number);
      $(`input[name="status"][value="${row.status}"]`).prop("checked", true);

      $("#password").prop("required", false);

      Auditor.isEdit = true;
      Auditor.currentId = row.id;

      $("#modalTitle").text("Edit Auditor");

      Auditor.modal.show();
    },
    error: function () {
      Swal.close();
      showError("Gagal mengambil data.");
    },
  });
}

function saveData() {
  const formData = new FormData(document.getElementById("auditorForm"));
  const action = Auditor.isEdit ? "update" : "create";
  const idParam = Auditor.isEdit ? "&id=" + Auditor.currentId : "";

  $.ajax({
    url: Auditor.api + "?action=" + action + idParam,
    type: "POST",
    dataType: "json",
    data: formData,
    processData: false,
    contentType: false,
    beforeSend: function () {
      showLoading();
    },
    success: function (response) {
      Swal.close();

      if (!response.success) {
        showError(response.message);
        return;
      }

      Auditor.modal.hide();
      reloadTable();

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

function deleteData(id) {
  Swal.fire({
    icon: "warning",
    title: "Nonaktifkan auditor?",
    text: "Status auditor akan diubah menjadi Nonaktif.",
    showCancelButton: true,
    confirmButtonColor: "#dc3545",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Ya, Nonaktifkan",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: Auditor.api + "?action=delete",
      type: "POST",
      dataType: "json",
      data: { id: id },
      success: function (response) {
        if (response.success) {
          reloadTable();
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: response.message,
            timer: 1500,
            showConfirmButton: false,
          });
        } else {
          showError(response.message);
        }
      },
      error: function () {
        showError("Gagal menghapus data.");
      },
    });
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
