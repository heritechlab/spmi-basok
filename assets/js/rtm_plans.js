const RtmPlans = {
  api: SIQUA.BASE_URL + "rtl/plans/api.php",
  table: null,
};

$(document).ready(function () {
  initializeTable();
  registerEvents();
});

function initializeTable() {
  RtmPlans.table = $("#tablePlans").DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    ordering: false,
    searching: false,
    pageLength: 10,
    lengthMenu: [
      [10, 25, 50, 100],
      [10, 25, 50, 100],
    ],

    ajax: {
      url: RtmPlans.api,
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
        targets: [2, 3, 4, 5, 6],
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

function registerEvents() {
  $("#filterPeriod, #filterUnit, #filterStatus").on("change", function () {
    RtmPlans.table.ajax.reload(null, false);
  });
}
