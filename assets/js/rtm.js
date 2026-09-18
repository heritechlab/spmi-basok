const Rtm = {
  api: SIQUA.BASE_URL + "rtl/api.php",
  table: null,
  modal: null,
  form: null,
  isEdit: false,
  currentId: null,
};

$(document).ready(function () {
  Rtm.modal = new bootstrap.Modal(document.getElementById("rtmModal"));
  Rtm.form = $("#rtmForm");

  initializeTable();
  loadPeriods();
  registerEvents();
});

function initializeTable() {
  Rtm.table = $("#tableRtm").DataTable({
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
      url: Rtm.api,
      type: "GET",
      data: function (d) {
        d.action = "list";
        d.period_id = $("#filterPeriod").val();
        d.status = $("#filterStatus").val();
      },
    },

    columnDefs: [
      {
        targets: [3, 4, 5],
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
  Rtm.table.ajax.reload(null, false);
}

function loadPeriods() {
  $.getJSON(Rtm.api, { action: "periods" }, function (res) {
    if (!res.success) return;
    res.data.forEach(function (row) {
      $("#period_id").append(
        `<option value="${row.id}">${row.period_name}</option>`,
      );
    });
  });
}

function resetForm() {
  document.getElementById("rtmForm").reset();
  $("#id").val("");
  $("#status").val("Draft");
  Rtm.isEdit = false;
  Rtm.currentId = null;
}

function registerEvents() {
  $("#filterPeriod, #filterStatus").on("change", function () {
    reloadTable();
  });

  $("#btnAddRtm").on("click", function () {
    resetForm();
    Rtm.isEdit = false;
    Rtm.currentId = null;
    $("#modalTitle").text("Tambah Rapat RTM");
    Rtm.modal.show();
  });

  $("#tableRtm").on("click", ".btn-edit", function () {
    loadData($(this).data("id"));
  });

  Rtm.form.on("submit", function (e) {
    e.preventDefault();
    saveData();
  });

  $("#rtmModal").on("hidden.bs.modal", function () {
    resetForm();
  });
}

function loadData(id) {
  $.ajax({
    url: Rtm.api,
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
      $("#meeting_number").val(row.meeting_number);
      $("#period_id").val(row.period_id);
      $("#meeting_date").val(row.meeting_date);
      $("#status").val(row.status);
      $("#agenda").val(row.agenda);
      $("#minutes").val(row.minutes);

      Rtm.isEdit = true;
      Rtm.currentId = row.id;

      $("#modalTitle").text("Edit Rapat RTM");

      Rtm.modal.show();
    },
    error: function () {
      Swal.close();
      showError("Gagal mengambil data.");
    },
  });
}

function saveData() {
  const formData = Rtm.form.serialize();
  const action = Rtm.isEdit ? "update" : "create";
  const idParam = Rtm.isEdit ? "&id=" + Rtm.currentId : "";

  $.ajax({
    url: Rtm.api + "?action=" + action + idParam,
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

      Rtm.modal.hide();
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

function showLoading() {
  Swal.fire({
    title: "Memproses...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });
}

function showError(message) {
  Swal.fire({ icon: "error", title: "Gagal", text: message });
}
