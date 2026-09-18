const Period = {
  api: SIQUA.BASE_URL + "master/periods/api.php",
  table: null,
  modal: null,
  form: null,
  isEdit: false,
  currentId: null,
};

$(document).ready(function () {
  Period.modal = new bootstrap.Modal(document.getElementById("periodModal"));
  Period.form = $("#periodForm");

  initializeTable();
  registerEvents();
});

function initializeTable() {
  Period.table = $("#tablePeriod").DataTable({
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
      url: Period.api,
      type: "GET",
      data: function (d) {
        d.action = "list";
        d.status = $("#filterStatus").val();
      },
    },

    columnDefs: [
      {
        targets: [4, 5],
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
  Period.table.ajax.reload(null, false);
}

function resetForm() {
  document.getElementById("periodForm").reset();
  $("#id").val("");
  $("#status").val("Draft");
  $("#activeWarning").hide();
  Period.isEdit = false;
  Period.currentId = null;
}

function registerEvents() {
  $("#filterStatus").on("change", function () {
    reloadTable();
  });

  $("#btnAddPeriod").on("click", function () {
    resetForm();
    Period.isEdit = false;
    Period.currentId = null;
    $("#modalTitle").text("Tambah Periode Audit");
    Period.modal.show();
  });

  $("#tablePeriod").on("click", ".btn-edit", function () {
    loadData($(this).data("id"));
  });

  $("#tablePeriod").on("click", ".btn-delete", function () {
    deleteData($(this).data("id"));
  });

  $("#status").on("change", function () {
    $("#activeWarning").toggle($(this).val() === "Aktif");
  });

  Period.form.on("submit", function (e) {
    e.preventDefault();
    saveData();
  });

  $("#periodModal").on("hidden.bs.modal", function () {
    resetForm();
  });
}

function loadData(id) {
  $.ajax({
    url: Period.api,
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
      $("#period_name").val(row.period_name);
      $("#year").val(row.year);
      $("#academic_year").val(row.academic_year);
      $("#start_date").val(row.start_date);
      $("#end_date").val(row.end_date);
      $("#status").val(row.status);
      $("#description").val(row.description);
      $("#activeWarning").toggle(row.status === "Aktif");

      Period.isEdit = true;
      Period.currentId = row.id;

      $("#modalTitle").text("Edit Periode Audit");

      Period.modal.show();
    },
    error: function () {
      Swal.close();
      showError("Gagal mengambil data.");
    },
  });
}

function saveData() {
  const formData = Period.form.serialize();
  const action = Period.isEdit ? "update" : "create";
  const idParam = Period.isEdit ? "&id=" + Period.currentId : "";

  $.ajax({
    url: Period.api + "?action=" + action + idParam,
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

      Period.modal.hide();
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
    title: "Tutup periode ini?",
    text: "Status periode akan diubah menjadi Ditutup.",
    showCancelButton: true,
    confirmButtonColor: "#dc3545",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Ya, Tutup",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: Period.api + "?action=delete",
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
        showError("Gagal memproses data.");
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
