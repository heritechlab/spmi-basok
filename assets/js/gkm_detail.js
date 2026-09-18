$(document).ready(function () {
  $("#checklistForm").on("submit", function (e) {
    e.preventDefault();

    const formData = $(this).serialize();

    Swal.fire({
      title: "Menyimpan...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    $.ajax({
      url: SIQUA.BASE_URL + "gkm/api.php?action=save_responses",
      type: "POST",
      dataType: "json",
      data: formData,
      success: function (response) {
        Swal.close();

        if (!response.success) {
          Swal.fire("Gagal", response.message, "error");
          return;
        }

        Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: response.message,
          timer: 1500,
          showConfirmButton: false,
        }).then(function () {
          location.reload();
        });
      },
      error: function () {
        Swal.close();
        Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
      },
    });
  });
});
$(document).ready(function () {
  $("#signatureForm").on("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(document.getElementById("signatureForm"));

    Swal.fire({
      title: "Menyimpan...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    $.ajax({
      url: SIQUA.BASE_URL + "gkm/api.php?action=save_signatures",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: function (response) {
        Swal.close();

        if (!response.success) {
          Swal.fire("Gagal", response.message, "error");
          return;
        }

        Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: response.message,
          timer: 1500,
          showConfirmButton: false,
        }).then(function () {
          location.reload();
        });
      },
      error: function () {
        Swal.close();
        Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
      },
    });
  });
});
