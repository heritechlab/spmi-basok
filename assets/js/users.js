const Users = {
  api: SIQUA.BASE_URL + "users/api.php",
  table: null,
  modal: null,
  form: null,
  isEdit: false,
  currentId: null,
};

$(document).ready(function () {
  Users.modal = new bootstrap.Modal(document.getElementById("userModal"));
  Users.form = $("#userForm");

  initializeTable();
  loadUnits();
  registerEvents();
});

function initializeTable() {
  Users.table = $("#tableUsers").DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    ordering: false,
    searching: true,
    pageLength: 10,

    ajax: {
      url: Users.api,
      type: "GET",
      data: function (d) {
        d.action = "list";
        d.role_id = $("#filterRole").val();
        d.status = $("#filterStatus").val();
      },
    },

    columnDefs: [
      {
        targets: [2, 5, 6],
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
  Users.table.ajax.reload(null, false);
}

function loadUnits() {
  $.getJSON(Users.api, { action: "units" }, function (res) {
    if (!res.success) return;
    res.data.forEach(function (row) {
      $("#unit_id").append(
        `<option value="${row.id}">${row.code} - ${row.name}</option>`,
      );
    });
  });
}

function toggleUnitField() {
  const role = $("#role_id").val();
  if (role === "4") {
    $("#unitFieldWrapper").show();
  } else {
    $("#unitFieldWrapper").hide();
    $("#unit_id").val("");
  }
}

function resetForm() {
  document.getElementById("userForm").reset();
  $("#id").val("");
  $("#status").val("1");
  $("#passwordLabel").text("Password");
  $("#passwordHint").text("Wajib diisi saat menambah user baru.");
  $("#password").prop("required", true);
  toggleUnitField();
  Users.isEdit = false;
  Users.currentId = null;
}

function registerEvents() {
  $("#filterRole, #filterStatus").on("change", function () {
    reloadTable();
  });

  $("#role_id").on("change", function () {
    toggleUnitField();
  });

  $("#btnAddUser").on("click", function () {
    resetForm();
    $("#modalTitle").text("Tambah User");
    Users.modal.show();
  });

  $("#tableUsers").on("click", ".btn-edit", function () {
    loadData($(this).data("id"));
  });

  $("#tableUsers").on("click", ".btn-toggle-status", function () {
    toggleStatus($(this).data("id"), $(this).data("status"));
  });

  Users.form.on("submit", function (e) {
    e.preventDefault();
    saveData();
  });

  $("#userModal").on("hidden.bs.modal", function () {
    resetForm();
  });
}

function loadData(id) {
  $.getJSON(Users.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    const row = res.data;

    resetForm();

    $("#id").val(row.id);
    $("#full_name").val(row.full_name);
    $("#role_id").val(row.role_id);
    $("#username").val(row.username);
    $("#email").val(row.email);
    $("#phone").val(row.phone);
    $("#status").val(row.status);
    $("#unit_id").val(row.unit_id);

    toggleUnitField();

    $("#passwordLabel").text("Password Baru (Opsional)");
    $("#passwordHint").text("Kosongkan jika tidak ingin mengubah password.");
    $("#password").prop("required", false);

    Users.isEdit = true;
    Users.currentId = row.id;

    $("#modalTitle").text("Edit User");

    Users.modal.show();
  });
}

function saveData() {
  const formData = Users.form.serialize();
  const action = Users.isEdit ? "update" : "create";
  const idParam = Users.isEdit ? "&id=" + Users.currentId : "";

  $.ajax({
    url: Users.api + "?action=" + action + idParam,
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

      Users.modal.hide();
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
      Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
    },
  });
}

function toggleStatus(id, status) {
  const actionText = status == 1 ? "mengaktifkan" : "menonaktifkan";

  Swal.fire({
    icon: "question",
    title: `Yakin ingin ${actionText} pengguna ini?`,
    showCancelButton: true,
    confirmButtonText: "Ya, Lanjutkan",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: Users.api + "?action=toggle_status",
      type: "POST",
      dataType: "json",
      data: { id: id, status: status },
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
          Swal.fire("Gagal", response.message, "error");
        }
      },
    });
  });
}
