const Dosen = {
  api: SIQUA.BASE_URL + "obe/dosen/api.php",
  unitId: null,
  modal: null,
};

$(document).ready(function () {
  Dosen.modal = new bootstrap.Modal(document.getElementById("dosenModal"));

  if (DOSEN_IS_AUDITEE) {
    loadTable();
  }

  $(document).on("change", "#dosenUnitSelector", function () {
    Dosen.unitId = $(this).val();
    if (Dosen.unitId) {
      loadTable();
    } else {
      $("#dosenTableBody").html(
        '<tr><td colspan="3" class="text-center text-muted">Pilih Program Studi terlebih dahulu.</td></tr>',
      );
    }
  });

  $("#btnAddDosen").on("click", function () {
    $("#dosenForm")[0].reset();
    $("#dosen_id").val("");
    $("#dosenModalTitle").text("Tambah Dosen");
    Dosen.modal.show();
  });

  $("#dosenForm").on("submit", function (e) {
    e.preventDefault();
    saveData();
  });
});

function loadTable() {
  $("#dosenTableBody").html(
    '<tr><td colspan="3" class="text-center text-muted">Memuat data...</td></tr>',
  );

  $.getJSON(
    Dosen.api,
    { action: "list", unit_id: Dosen.unitId || 0 },
    function (res) {
      if (!res.success) {
        $("#dosenTableBody").html(
          '<tr><td colspan="3" class="text-center text-danger">' +
            res.message +
            "</td></tr>",
        );
        return;
      }

      if (!res.data.length) {
        $("#dosenTableBody").html(
          '<tr><td colspan="3" class="text-center text-muted">Belum ada Dosen.</td></tr>',
        );
        return;
      }

      let html = "";

      res.data.forEach(function (row) {
        const fullName =
          [row.gelar_depan, row.name].filter(Boolean).join(" ") +
          (row.gelar_belakang ? ", " + row.gelar_belakang : "");
        html += `
        <tr>
          <td>${row.nidn ? escapeHtml(row.nidn) : '<span class="text-muted">-</span>'}</td>
          <td>${escapeHtml(fullName)}</td>
          <td>
            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-dosen" data-id="${row.id}">
              <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-dosen" data-id="${row.id}">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
      `;
      });

      $("#dosenTableBody").html(html);
    },
  );
}

$(document).on("click", ".btn-edit-dosen", function () {
  const id = $(this).data("id");

  $.getJSON(Dosen.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    $("#dosen_id").val(res.data.id);
    $("#dosen_nidn").val(res.data.nidn);
    $("#dosen_name").val(res.data.name);
    $("#dosen_gelar_depan").val(res.data.gelar_depan);
    $("#dosen_gelar_belakang").val(res.data.gelar_belakang);
    $("#dosenModalTitle").text("Edit Dosen");

    Dosen.modal.show();
  });
});

$(document).on("click", ".btn-delete-dosen", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Hapus Dosen ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(Dosen.api + "?action=delete", { id: id }, function (response) {
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
    });
  });
});

function saveData() {
  const id = $("#dosen_id").val();
  const action = id ? "update" : "create";

  const formData =
    $("#dosenForm").serialize() + "&unit_id=" + (Dosen.unitId || 0);

  $.post(Dosen.api + "?action=" + action, formData, function (response) {
    if (!response.success) {
      Swal.fire("Gagal", response.message, "error");
      return;
    }

    Dosen.modal.hide();
    Swal.fire({
      icon: "success",
      title: "Berhasil",
      text: response.message,
      timer: 1200,
      showConfirmButton: false,
    });
    loadTable();
  });
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}
