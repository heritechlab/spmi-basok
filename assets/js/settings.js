$(document).ready(function () {
  $("#generalForm").on("submit", function (e) {
    e.preventDefault();
    saveForm($(this), "update_general", "Pengaturan Umum berhasil disimpan.");
  });

  $("#emailForm").on("submit", function (e) {
    e.preventDefault();
    saveForm($(this), "update_email", "Pengaturan Email berhasil disimpan.");
  });

  $("#btnTestEmail").on("click", function () {
    const testTo = $("#test_email_to").val().trim();

    if (!testTo) {
      Swal.fire(
        "Gagal",
        "Isi alamat email tujuan tes terlebih dahulu.",
        "error",
      );
      return;
    }

    Swal.fire({
      title: "Mengirim email tes...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    $.ajax({
      url: SIQUA.BASE_URL + "settings/api.php?action=test_email",
      type: "POST",
      dataType: "json",
      data: { test_email_to: testTo },
      success: function (response) {
        Swal.close();

        if (response.success) {
          Swal.fire("Berhasil", response.message, "success");
        } else {
          Swal.fire("Gagal", response.message, "error");
        }
      },
      error: function () {
        Swal.close();
        Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
      },
    });
  });
});

function saveForm($form, action, successMessage) {
  const formData = $form.serialize();

  Swal.fire({
    title: "Menyimpan...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: SIQUA.BASE_URL + "settings/api.php?action=" + action,
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
      });
    },
    error: function () {
      Swal.close();
      Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
    },
  });
}
