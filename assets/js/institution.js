/*
|--------------------------------------------------------------------------
| SIQUA Enterprise
|--------------------------------------------------------------------------
| Institution Profile
|--------------------------------------------------------------------------
*/

"use strict";

/*
|--------------------------------------------------------------------------
| API Endpoint
|--------------------------------------------------------------------------
*/

const InstitutionAPI = {
  get: SIQUA.BASE_URL + "master/institution/api/get.php",

  update: SIQUA.BASE_URL + "master/institution/api/update.php",
};

/*
|--------------------------------------------------------------------------
| Global Variable
|--------------------------------------------------------------------------
*/

let isSaving = false;

/*
|--------------------------------------------------------------------------
| Document Ready
|--------------------------------------------------------------------------
*/

$(function () {
  loadProfile();

  $("#btnSaveInstitution").on("click", function (e) {
    e.preventDefault();

    saveProfile();
  });

  /*
    |--------------------------------------------------------------------------
    | CTRL + S
    |--------------------------------------------------------------------------
    */

  $(document).on("keydown", function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "s") {
      e.preventDefault();

      saveProfile();
    }
  });
});

/*
|--------------------------------------------------------------------------
| Loading Button
|--------------------------------------------------------------------------
*/

function setLoading(status) {
  const btn = $("#btnSaveInstitution");

  if (status) {
    btn.prop("disabled", true);

    btn.html(
      '<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...',
    );
  } else {
    btn.prop("disabled", false);

    btn.html('<i class="bi bi-save"></i> Simpan Perubahan');
  }
}

/*
|--------------------------------------------------------------------------
| Fill Form
|--------------------------------------------------------------------------
*/

function fillForm(data) {
  Object.keys(data).forEach(function (key) {
    if (key === "logo") {
      return;
    }

    const element = $("#" + key);

    if (element.length) {
      element.val(data[key]);
    }
  });

  if (data.logo) {
    $("#logoPreview")
      .attr("src", SIQUA.BASE_URL + data.logo)
      .show();
  }
}

/*
|--------------------------------------------------------------------------
| Load Profile
|--------------------------------------------------------------------------
*/

function loadProfile() {
  $.ajax({
    url: InstitutionAPI.get,

    type: "GET",

    dataType: "json",

    success: function (response) {
      if (response.success) {
        fillForm(response.data);
      } else {
        Swal.fire({
          icon: "error",

          title: "Gagal",

          text: response.message,
        });
      }
    },

    error: function (xhr) {
      console.error(xhr);

      Swal.fire({
        icon: "error",

        title: "Server Error",

        text: "Gagal mengambil data Profil Institusi.",
      });
    },
  });
}

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

function validateForm() {
  if ($("#institution_name").val().trim() === "") {
    Swal.fire({
      icon: "warning",

      title: "Validasi",

      text: "Nama Institusi wajib diisi.",
    });

    $("#institution_name").focus();

    return false;
  }

  if ($("#institution_type").val() === "") {
    Swal.fire({
      icon: "warning",

      title: "Validasi",

      text: "Jenis Institusi wajib dipilih.",
    });

    $("#institution_type").focus();

    return false;
  }

  if ($("#email").val() !== "") {
    const email = $("#email").val();

    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!regex.test(email)) {
      Swal.fire({
        icon: "warning",

        title: "Validasi",

        text: "Format Email tidak valid.",
      });

      $("#email").focus();

      return false;
    }
  }

  return true;
}

/*
|--------------------------------------------------------------------------
| Save Profile
|--------------------------------------------------------------------------
*/

function saveProfile() {
  if (isSaving) {
    return;
  }

  if (!validateForm()) {
    return;
  }

  isSaving = true;

  setLoading(true);

  const formData = new FormData(document.getElementById("institutionForm"));

  $.ajax({
    url: InstitutionAPI.update,

    type: "POST",

    data: formData,

    processData: false,

    contentType: false,

    dataType: "json",

    success: function (response) {
      isSaving = false;

      setLoading(false);

      if (response.success) {
        Swal.fire({
          icon: "success",

          title: "Berhasil",

          text: response.message,

          timer: 1800,

          showConfirmButton: false,
        });

        loadProfile();
      } else {
        Swal.fire({
          icon: "warning",

          title: "Validasi",

          text: response.message,
        });
      }
    },

    error: function (xhr) {
      isSaving = false;

      setLoading(false);

      console.error(xhr);

      Swal.fire({
        icon: "error",

        title: "Server Error",

        text: "Gagal menyimpan Profil Institusi.",
      });
    },
  });
}
