const ProfilLulusan = {
  api: SIQUA.BASE_URL + "obe/profil_lulusan/api.php",
  unitId: null,
  modal: null,
};

$(document).ready(function () {
  ProfilLulusan.modal = new bootstrap.Modal(document.getElementById("plModal"));

  if (PL_IS_AUDITEE) {
    loadTable();
  }

  $(document).on("change", "#plUnitSelector", function () {
    ProfilLulusan.unitId = $(this).val();
    if (ProfilLulusan.unitId) {
      loadTable();
    } else {
      $("#plTableBody").html(
        '<tr><td colspan="3" class="text-center text-muted">Pilih Program Studi terlebih dahulu.</td></tr>',
      );
    }
  });

  $("#btnAddPl").on("click", function () {
    $("#plForm")[0].reset();
    $("#pl_id").val("");
    $("#plModalTitle").text("Tambah Profil Lulusan");
    ProfilLulusan.modal.show();
  });

  $("#plForm").on("submit", function (e) {
    e.preventDefault();
    saveData();
  });
});

function loadTable() {
  $("#plTableBody").html(
    '<tr><td colspan="3" class="text-center text-muted">Memuat data...</td></tr>',
  );

  $.getJSON(
    ProfilLulusan.api,
    { action: "list", unit_id: ProfilLulusan.unitId || 0 },
    function (res) {
      if (!res.success) {
        $("#plTableBody").html(
          '<tr><td colspan="3" class="text-center text-danger">' +
            res.message +
            "</td></tr>",
        );
        return;
      }

      if (!res.data.length) {
        $("#plTableBody").html(
          '<tr><td colspan="3" class="text-center text-muted">Belum ada Profil Lulusan.</td></tr>',
        );
        return;
      }

      let html = "";

      res.data.forEach(function (row) {
        html += `
        <tr>
          <td><strong>${escapeHtml(row.code)}</strong></td>
          <td>${escapeHtml(row.name)}</td>
          <td>${escapeHtml(row.description)}</td>
          <td>
            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-pl" data-id="${row.id}">
              <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-pl" data-id="${row.id}">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
      `;
      });

      $("#plTableBody").html(html);
    },
  );
}

$(document).on("click", ".btn-edit-pl", function () {
  const id = $(this).data("id");

  $.getJSON(ProfilLulusan.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    $("#pl_id").val(res.data.id);
    $("#pl_code").val(res.data.code);
    $("#pl_name").val(res.data.name);
    $("#pl_description").val(res.data.description);
    $("#pl_sort_order").val(res.data.sort_order);
    $("#plModalTitle").text("Edit Profil Lulusan");

    ProfilLulusan.modal.show();
  });
});

$(document).on("click", ".btn-delete-pl", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Hapus Profil Lulusan ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(
      ProfilLulusan.api + "?action=delete",
      { id: id },
      function (response) {
        if (!response.success) {
          Swal.fire("Gagal", response.message, "error");
          return;
        }

        Swal.fire({
          icon: "success",
          title: "Terhapus",
          timer: 1000,
          showConfirmButton: false,
        });
        loadTable();
      },
    );
  });
});

function saveData() {
  const id = $("#pl_id").val();
  const action = id ? "update" : "create";

  const formData =
    $("#plForm").serialize() + "&unit_id=" + (ProfilLulusan.unitId || 0);

  $.post(
    ProfilLulusan.api + "?action=" + action,
    formData,
    function (response) {
      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      ProfilLulusan.modal.hide();
      Swal.fire({
        icon: "success",
        title: "Berhasil",
        text: response.message,
        timer: 1200,
        showConfirmButton: false,
      });
      loadTable();
    },
  );
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}
