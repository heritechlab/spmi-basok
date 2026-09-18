const Kurikulum = {
  api: SIQUA.BASE_URL + "obe/kurikulum/api.php",
  unitId: null,
  modal: null,
};

$(document).ready(function () {
  Kurikulum.modal = new bootstrap.Modal(
    document.getElementById("kurikulumModal"),
  );

  if (KURIKULUM_IS_AUDITEE) {
    loadTable();
  }

  $(document).on("change", "#kurikulumUnitSelector", function () {
    Kurikulum.unitId = $(this).val();
    if (Kurikulum.unitId) {
      loadTable();
    } else {
      $("#kurikulumTableBody").html(
        '<tr><td colspan="5" class="text-center text-muted">Pilih Program Studi terlebih dahulu.</td></tr>',
      );
    }
  });

  $("#btnAddKurikulum").on("click", function () {
    $("#kurikulumForm")[0].reset();
    $("#kurikulum_id").val("");
    $("#kurikulumModalTitle").text("Tambah Kurikulum");
    Kurikulum.modal.show();
  });

  $("#kurikulumForm").on("submit", function (e) {
    e.preventDefault();
    saveData();
  });
});

function loadTable() {
  $("#kurikulumTableBody").html(
    '<tr><td colspan="5" class="text-center text-muted">Memuat data...</td></tr>',
  );

  $.getJSON(
    Kurikulum.api,
    { action: "list", unit_id: Kurikulum.unitId || 0 },
    function (res) {
      if (!res.success) {
        $("#kurikulumTableBody").html(
          '<tr><td colspan="5" class="text-center text-danger">' +
            res.message +
            "</td></tr>",
        );
        return;
      }

      if (!res.data.length) {
        $("#kurikulumTableBody").html(
          '<tr><td colspan="5" class="text-center text-muted">Belum ada Kurikulum.</td></tr>',
        );
        return;
      }

      let html = "";

      res.data.forEach(function (row) {
        html += `
        <tr>
          <td>${row.tahun}</td>
          <td>${escapeHtml(row.nama)}</td>
          <td class="text-center">${row.jumlah_mk} Mata Kuliah</td>
          <td class="text-center">${row.is_active == 1 ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Non-Aktif</span>'}</td>
          <td>
            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-kurikulum" data-id="${row.id}">
              <i class="bi bi-pencil"></i>
            </button>
          </td>
        </tr>
      `;
      });

      $("#kurikulumTableBody").html(html);
    },
  );
}

$(document).on("click", ".btn-edit-kurikulum", function () {
  const id = $(this).data("id");

  $.getJSON(Kurikulum.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    $("#kurikulum_id").val(res.data.id);
    $("#kurikulum_tahun").val(res.data.tahun);
    $("#kurikulum_nama").val(res.data.nama);
    $("#kurikulum_is_active").prop("checked", res.data.is_active == 1);
    $("#kurikulumModalTitle").text("Edit Kurikulum");

    Kurikulum.modal.show();
  });
});

function saveData() {
  const id = $("#kurikulum_id").val();
  const action = id ? "update" : "create";

  const formData =
    $("#kurikulumForm").serialize() +
    "&unit_id=" +
    (Kurikulum.unitId || 0) +
    "&is_active=" +
    ($("#kurikulum_is_active").is(":checked") ? "1" : "0");

  $.post(Kurikulum.api + "?action=" + action, formData, function (response) {
    if (!response.success) {
      Swal.fire("Gagal", response.message, "error");
      return;
    }

    Kurikulum.modal.hide();
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
