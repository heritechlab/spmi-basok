console.log("Indicator Create Loaded");

$(document).ready(function () {
  function loadStatements(standardId, selectedId) {
    const $statement = $("#statement_id");

    if (!standardId) {
      $statement.html(
        '<option value="">-- Pilih Standar terlebih dahulu --</option>',
      );
      return;
    }

    $statement.html('<option value="">Memuat...</option>');

    $.getJSON(
      SIQUA.BASE_URL + "master/indicators/api/statements.php",
      { standard_id: standardId },
      function (res) {
        $statement.html(
          '<option value="">-- Pilih Pernyataan Standar --</option>',
        );

        if (!res.success || !res.data.length) {
          $statement.append(
            '<option value="" disabled>Belum ada Pernyataan Standar untuk Standar ini</option>',
          );
          return;
        }

        res.data.forEach(function (row) {
          const selected =
            selectedId && Number(selectedId) === Number(row.id)
              ? "selected"
              : "";
          $statement.append(
            `<option value="${row.id}" ${selected}>${row.statement_text}</option>`,
          );
        });
      },
    );
  }

  $('select[name="standard_id"]').on("change", function () {
    loadStatements($(this).val());
  });

  $("#indicatorForm").on("submit", function (e) {
    e.preventDefault();

    $.ajax({
      url: SIQUA.BASE_URL + "master/indicators/api/save.php",

      method: "POST",

      data: $(this).serialize(),

      dataType: "json",

      beforeSend: function () {
        Swal.fire({
          title: "Menyimpan...",

          allowOutsideClick: false,

          didOpen: () => {
            Swal.showLoading();
          },
        });
      },

      success: function (res) {
        Swal.close();

        console.log(res);

        if (res.success) {
          Swal.fire({
            icon: "success",

            title: "Berhasil",

            text: res.message,
          }).then(() => {
            location.href = SIQUA.BASE_URL + "master/indicators/";
          });
        } else {
          Swal.fire({
            icon: "error",

            title: "Gagal",

            text: res.message,
          });
        }
      },

      error: function (xhr) {
        Swal.close();

        console.log(xhr.responseText);

        Swal.fire({
          icon: "error",

          title: "SERVER ERROR",

          html: "<pre>" + xhr.responseText + "</pre>",
        });
      },
    });
  });
});
