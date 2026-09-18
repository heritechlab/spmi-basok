const BentukPenilaian = {
  api: SIQUA.BASE_URL + "obe/bentuk_penilaian/api.php",
  modal: null,
};

$(document).ready(function () {
  BentukPenilaian.modal = new bootstrap.Modal(
    document.getElementById("bpnModal"),
  );

  loadTable();

  $("#btnAddBentukPenilaian").on("click", function () {
    $("#bpnForm")[0].reset();
    $("#bpn_id").val("");
    $("#bpnModalTitle").text("Tambah Bentuk Penilaian");
    BentukPenilaian.modal.show();
  });

  $("#bpnForm").on("submit", function (e) {
    e.preventDefault();
    saveData();
  });
});

function loadTable() {
  $("#bpnTableBody").html(
    '<tr><td colspan="4" class="text-center text-muted">Memuat data...</td></tr>',
  );

  $.getJSON(BentukPenilaian.api, { action: "list" }, function (res) {
    if (!res.success) {
      $("#bpnTableBody").html(
        '<tr><td colspan="4" class="text-center text-danger">' +
          res.message +
          "</td></tr>",
      );
      return;
    }

    if (!res.data.length) {
      $("#bpnTableBody").html(
        '<tr><td colspan="4" class="text-center text-muted">Belum ada data.</td></tr>',
      );
      return;
    }

    let html = "";

    res.data.forEach(function (row, idx) {
      html += `
        <tr>
          <td>${idx + 1}</td>
          <td><strong>${escapeHtml(row.nama_bentuk)}</strong></td>
          <td>${row.deskripsi ? escapeHtml(row.deskripsi) : '<span class="text-muted">-</span>'}</td>
          ${
            BPN_CAN_MANAGE
              ? `
          <td>
            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-bpn" data-id="${row.id}">
              <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-bpn" data-id="${row.id}">
              <i class="bi bi-trash"></i>
            </button>
          </td>`
              : ""
          }
        </tr>
      `;
    });

    $("#bpnTableBody").html(html);
  });
}

$(document).on("click", ".btn-edit-bpn", function () {
  const id = $(this).data("id");

  $.getJSON(BentukPenilaian.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    $("#bpn_id").val(res.data.id);
    $("#bpn_nama").val(res.data.nama_bentuk);
    $("#bpn_deskripsi").val(res.data.deskripsi);
    $("#bpn_sort_order").val(res.data.sort_order);
    $("#bpnModalTitle").text("Edit Bentuk Penilaian");

    BentukPenilaian.modal.show();
  });
});

$(document).on("click", ".btn-delete-bpn", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Hapus Bentuk Penilaian ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(
      BentukPenilaian.api + "?action=delete",
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
  const id = $("#bpn_id").val();
  const action = id ? "update" : "create";

  const formData = $("#bpnForm").serialize();

  $.post(
    BentukPenilaian.api + "?action=" + action,
    formData,
    function (response) {
      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      BentukPenilaian.modal.hide();
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
