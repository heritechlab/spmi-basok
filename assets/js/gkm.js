const Gkm = {
  api: SIQUA.BASE_URL + "gkm/api.php",
  table: null,
  modal: null,
};

$(document).ready(function () {
  Gkm.modal = new bootstrap.Modal(
    document.getElementById("addMonitoringModal"),
  );

  initializeTable();
  loadUnits();
  registerEvents();
});

function initializeTable() {
  Gkm.table = $("#tableGkm").DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    ordering: false,
    pageLength: 10,

    ajax: {
      url: Gkm.api,
      type: "GET",
      data: function (d) {
        d.action = "list";
        d.unit_id = $("#filterUnit").length ? $("#filterUnit").val() : "";
        d.semester = $("#filterSemester").val();
        d.academic_year = $("#filterAcademicYear").val();
      },
    },

    columnDefs: [
      { targets: [1, 2, 3, 4], className: "text-center", orderable: false },
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
  Gkm.table.ajax.reload(null, false);
}

function loadUnits() {
  $.getJSON(Gkm.api, { action: "units" }, function (res) {
    if (!res.success) return;
    res.data.forEach(function (row) {
      const option = `<option value="${row.id}">${row.code} - ${row.name}</option>`;
      $("#filterUnit").append(option);
      $("#new_unit_id").append(option);
    });
  });
}

function registerEvents() {
  $("#filterUnit, #filterSemester").on("change", function () {
    reloadTable();
  });

  $("#filterAcademicYear").on("input", function () {
    reloadTable();
  });

  $("#btnAddMonitoring").on("click", function () {
    document.getElementById("addMonitoringForm").reset();
    Gkm.modal.show();
  });

  $("#addMonitoringForm").on("submit", function (e) {
    e.preventDefault();

    const formData = $(this).serialize();

    Swal.fire({
      title: "Membuat...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    $.ajax({
      url: Gkm.api + "?action=create",
      type: "POST",
      dataType: "json",
      data: formData,
      success: function (response) {
        Swal.close();

        if (!response.success) {
          Swal.fire("Gagal", response.message, "error");
          return;
        }

        window.location.href =
          SIQUA.BASE_URL + "gkm/detail.php?id=" + response.data.id;
      },
      error: function () {
        Swal.close();
        Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
      },
    });
  });

  $("#tableGkm").on("click", ".btn-delete", function () {
    const id = $(this).data("id");

    Swal.fire({
      icon: "warning",
      title: "Hapus data Monitoring ini?",
      showCancelButton: true,
      confirmButtonText: "Ya, Hapus",
      cancelButtonText: "Batal",
    }).then(function (result) {
      if (!result.isConfirmed) return;

      $.ajax({
        url: Gkm.api + "?action=delete",
        type: "POST",
        dataType: "json",
        data: { id: id },
        success: function (response) {
          if (response.success) {
            reloadTable();
          } else {
            Swal.fire("Gagal", response.message, "error");
          }
        },
      });
    });
  });
}
