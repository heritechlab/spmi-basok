const PPD = {
  api: SIQUA.BASE_URL + "obe/profil_prodi/api.php",
  unitId: null,
};

$(document).ready(function () {
  if (PPD_IS_AUDITEE) {
    loadProfil();
    $("#ppdFormCard").show();
  }

  $(document).on("change", "#ppdUnitSelector", function () {
    PPD.unitId = $(this).val();
    if (PPD.unitId) {
      loadProfil();
      $("#ppdFormCard").show();
    } else {
      $("#ppdFormCard").hide();
    }
  });

  $("#ppdForm").on("submit", function (e) {
    e.preventDefault();

    $("#btnSaveProfilProdi")
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm"></span> Menyimpan...',
      );

    $.post(PPD.api, {
      action: "save",
      unit_id: PPD.unitId,
      visi: $("#ppd_visi").val(),
      misi: $("#ppd_misi").val(),
      unggulan: $("#ppd_unggulan").val(),
    })
      .done(function (res) {
        if (res.success) {
          Swal.fire({
            icon: "success",
            title: "Tersimpan",
            text: res.message,
            timer: 1500,
            showConfirmButton: false,
          });
        } else {
          Swal.fire({ icon: "error", title: "Gagal", text: res.message });
        }
      })
      .fail(function () {
        Swal.fire({
          icon: "error",
          title: "Gagal",
          text: "Tidak dapat menghubungi server.",
        });
      })
      .always(function () {
        $("#btnSaveProfilProdi")
          .prop("disabled", false)
          .html('<i class="bi bi-save"></i> Simpan Profil Program Studi');
      });
  });
});

function loadProfil() {
  $.getJSON(
    PPD.api,
    { action: "get", unit_id: PPD.unitId || 0 },
    function (res) {
      if (!res.success) return;

      $("#ppd_unit_id").val(res.data.unit_id || PPD.unitId);
      $("#ppd_visi").val(res.data.visi || "");
      $("#ppd_misi").val(res.data.misi || "");
      $("#ppd_unggulan").val(res.data.unggulan || "");
    },
  );
}
