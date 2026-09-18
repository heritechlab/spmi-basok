$(document).ready(function () {
  $("#profileForm").on("submit", function (e) {
    e.preventDefault();

    const formData = $(this).serialize();

    Swal.fire({
      title: "Menyimpan...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    $.ajax({
      url: SIQUA.BASE_URL + "profile/api.php?action=update_profile",
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

  $("#passwordForm").on("submit", function (e) {
    e.preventDefault();

    const newPassword = $("#new_password").val();
    const confirmPassword = $("#confirm_password").val();

    if (newPassword !== confirmPassword) {
      Swal.fire("Gagal", "Konfirmasi password baru tidak cocok.", "error");
      return;
    }

    const formData = $(this).serialize();

    Swal.fire({
      title: "Menyimpan...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    $.ajax({
      url: SIQUA.BASE_URL + "profile/api.php?action=change_password",
      type: "POST",
      dataType: "json",
      data: formData,
      success: function (response) {
        Swal.close();

        if (!response.success) {
          Swal.fire("Gagal", response.message, "error");
          return;
        }

        document.getElementById("passwordForm").reset();

        Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: response.message,
        });
      },
      error: function () {
        Swal.close();
        Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
      },
    });
  });
});
