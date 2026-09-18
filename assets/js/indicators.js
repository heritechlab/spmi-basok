/*
==========================================================
SIQUA Enterprise
Master Indicator
assets/js/indicators.js
Version : Enterprise Final
==========================================================
*/

$(document).ready(function () {
  /*
    ==========================================================
    DATATABLE
    ==========================================================
    */

  const table = $("#indicatorTable").DataTable({
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
      url: SIQUA.API_URL + "datatable.php",

      type: "GET",

      data: function (d) {
        d.standard_id = $("#filterStandard").val();

        d.indicator_type = $("#filterType").val();
      },
    },

    columnDefs: [
      {
        targets: [5, 6],

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

        next: "›",

        previous: "‹",
      },
    },
  });

  /*
    ==========================================================
    GLOBAL RELOAD
    ==========================================================
    */

  window.reloadIndicator = function () {
    table.ajax.reload(null, false);
  };

  /*
    ==========================================================
    FILTER STANDAR
    ==========================================================
    */

  $("#filterStandard").on("change", function () {
    table.ajax.reload();
  });

  /*
    ==========================================================
    FILTER STATUS
    ==========================================================
    */

  $("#filterType").on("change", function () {
    table.ajax.reload();
  });

  /*
    ==========================================================
    RESET FILTER
    ==========================================================
    */

  $("#btnResetFilter").on("click", function (e) {
    e.preventDefault();

    $("#filterStandard").val("");

    $("#filterType").val("");

    table.search("").draw();

    table.ajax.reload();
  });

  /*
    ==========================================================
    EDIT
    ==========================================================
    */

  $(document).on("click", ".btn-edit", function () {
    let id = $(this).data("id");

    window.location.href =
      SIQUA.BASE_URL + "master/indicators/edit.php?id=" + id;
  });

  function reloadSummary() {
    $.getJSON(
      SIQUA.API_URL + "statistics.php",

      function (r) {
        $("#cardTotal").text(r.total);

        $("#cardActive").text(r.active);

        $("#cardInactive").text(r.inactive);

        $("#cardStandard").text(r.standard);
      },
    );
  }

  /*
==========================================================
DELETE
==========================================================
*/

  $(document).on("click", ".btn-delete", function () {
    let id = $(this).data("id");

    Swal.fire({
      icon: "warning",

      title: "Nonaktifkan indikator?",

      text: "Status indikator akan diubah menjadi Nonaktif.",

      showCancelButton: true,

      confirmButtonColor: "#dc3545",

      cancelButtonColor: "#6c757d",

      confirmButtonText: "Ya, Nonaktifkan",

      cancelButtonText: "Batal",
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }

      $.ajax({
        url: SIQUA.API_URL + "delete.php",

        type: "POST",

        dataType: "json",

        data: {
          id: id,
        },

        success: function (response) {
          if (response.success) {
            Swal.fire({
              icon: "success",

              title: "Berhasil",

              text: "Indikator berhasil dinonaktifkan.",

              timer: 1500,

              showConfirmButton: false,
            });

            reloadIndicator();

            if (typeof loadIndicatorSummary === "function") {
              loadIndicatorSummary();
            }
          } else {
            Swal.fire({
              icon: "error",

              title: "Gagal",

              text: response.message,
            });
          }
        },

        error: function (xhr) {
          console.log(xhr.responseText);

          Swal.fire({
            icon: "error",

            title: "Server Error",

            text: "Terjadi kesalahan server.",
          });
        },
      });
    });
  });
});
