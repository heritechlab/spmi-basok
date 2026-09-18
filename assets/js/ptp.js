const Ptp = {
  api: SIQUA.BASE_URL + "ptp/api.php",
  table: null,
  modal: null,
  form: null,
  isEdit: false,
  currentId: null,
};

$(document).ready(function () {
  Ptp.modal = new bootstrap.Modal(document.getElementById("ptpModal"));
  Ptp.form = $("#ptpForm");

  initializeTable();
  loadPeriods();
  loadUnits();
  registerEvents();
});

function initializeTable() {
  Ptp.table = $("#tablePtp").DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    ordering: false,
    searching: false,
    pageLength: 10,

    ajax: {
      url: Ptp.api,
      type: "GET",
      data: function (d) {
        d.action = "list";
        d.period_id = $("#filterPeriod").val();
        d.unit_id = $("#filterUnit").length ? $("#filterUnit").val() : "";
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
  Ptp.table.ajax.reload(null, false);
}

function loadPeriods() {
  $.getJSON(Ptp.api, { action: "periods" }, function (res) {
    if (!res.success) return;
    res.data.forEach(function (row) {
      $("#period_id").append(
        `<option value="${row.id}">${row.period_name}</option>`,
      );
    });
  });
}

function loadUnits() {
  $.getJSON(Ptp.api, { action: "units" }, function (res) {
    if (!res.success) return;
    res.data.forEach(function (row) {
      const option = `<option value="${row.id}">${row.code} - ${row.name}</option>`;
      $("#filterUnit").append(option);
      $("#unit_id").append(option);
    });
  });
}

function addParticipantRow(name = "", position = "") {
  const row = `
    <div class="row mb-2 participant-row">
      <div class="col-md-6">
        <input type="text" class="form-control form-control-sm" name="participant_name[]" placeholder="Nama" value="${name}">
      </div>
      <div class="col-md-5">
        <input type="text" class="form-control form-control-sm" name="participant_position[]" placeholder="Jabatan" value="${position}">
      </div>
      <div class="col-md-1">
        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-participant"><i class="bi bi-x"></i></button>
      </div>
    </div>
  `;
  $("#participantList").append(row);
}

function resetForm() {
  document.getElementById("ptpForm").reset();
  $("#id").val("");
  $("#status").val("Draft");
  $("#participantList").empty();
  Ptp.isEdit = false;
  Ptp.currentId = null;
}

function registerEvents() {
  $("#filterPeriod, #filterUnit, #filterStatus").on("change", function () {
    reloadTable();
  });

  $("#btnAddPtp").on("click", function () {
    resetForm();
    $("#modalTitle").text("Tambah Rapat PTP");
    addParticipantRow();
    Ptp.modal.show();
  });

  $("#btnAddParticipant").on("click", function () {
    addParticipantRow();
  });

  $(document).on("click", ".btn-remove-participant", function () {
    $(this).closest(".participant-row").remove();
  });

  Ptp.form.on("submit", function (e) {
    e.preventDefault();
    saveData();
  });

  $("#ptpModal").on("hidden.bs.modal", function () {
    resetForm();
  });
}

function saveData() {
  const formData = Ptp.form.serialize();
  const action = Ptp.isEdit ? "update" : "create";
  const idParam = Ptp.isEdit ? "&id=" + Ptp.currentId : "";

  $.ajax({
    url: Ptp.api + "?action=" + action + idParam,
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

      Ptp.modal.hide();
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
