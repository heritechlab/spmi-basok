const MetodePembelajaran = {
  api: SIQUA.BASE_URL + "obe/metode_pembelajaran/api.php",
  modal: null,
};

$(document).ready(function () {
  MetodePembelajaran.modal = new bootstrap.Modal(
    document.getElementById("mpModal"),
  );

  loadListing();

  $("#btnAddMetode").on("click", function () {
    $("#mpForm")[0].reset();
    $("#mp_id").val("");
    $("#mpModalTitle").text("Tambah Metode Pembelajaran");
    MetodePembelajaran.modal.show();
  });

  $("#mpForm").on("submit", function (e) {
    e.preventDefault();
    saveData();
  });
});

function loadListing() {
  $("#mpListArea").html('<p class="text-center text-muted">Memuat data...</p>');

  $.getJSON(MetodePembelajaran.api, { action: "list" }, function (res) {
    if (!res.success) {
      $("#mpListArea").html(
        '<p class="text-center text-danger">' + res.message + "</p>",
      );
      return;
    }

    const byKategori = {};
    res.data.forEach(function (row) {
      if (!byKategori[row.kategori]) byKategori[row.kategori] = [];
      byKategori[row.kategori].push(row);
    });

    let html = "";

    Object.keys(byKategori).forEach(function (kategori) {
      const items = byKategori[kategori];

      html += `
        <div class="kategori-card">
          <div class="kategori-header">
            <span class="kategori-name">${escapeHtml(kategori)}</span>
          </div>
          <table class="table table-bordered mb-0">
            <thead>
              <tr>
                <th width="50">No.</th>
                <th>Nama Metode</th>
                <th width="220">Aksi</th>
              </tr>
            </thead>
            <tbody>
      `;

      items.forEach(function (row, idx) {
        const hasDetail =
          (row.aktivitas_mahasiswa && row.aktivitas_mahasiswa.trim()) ||
          (row.aktivitas_dosen && row.aktivitas_dosen.trim());

        html += `
          <tr>
            <td>${idx + 1}</td>
            <td>${escapeHtml(row.nama_metode)}</td>
            <td>
              ${hasDetail ? `<button type="button" class="btn btn-sm btn-outline-secondary btn-toggle-detail" data-target="detail-${row.id}"><i class="bi bi-eye"></i> Lihat Detail</button>` : ""}
              ${
                MP_CAN_MANAGE
                  ? `
              <button type="button" class="btn btn-sm btn-outline-primary btn-edit-mp" data-id="${row.id}">
                <i class="bi bi-pencil"></i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger btn-delete-mp" data-id="${row.id}">
                <i class="bi bi-trash"></i>
              </button>`
                  : ""
              }
            </td>
          </tr>
          <tr class="detail-row" id="detail-${row.id}">
            <td colspan="3">
              ${row.aktivitas_mahasiswa ? `<div class="detail-label">Aktivitas Mahasiswa</div><div class="detail-text">${escapeHtml(row.aktivitas_mahasiswa)}</div>` : ""}
              ${row.aktivitas_dosen ? `<div class="detail-label">Aktivitas Dosen</div><div class="detail-text">${escapeHtml(row.aktivitas_dosen)}</div>` : ""}
            </td>
          </tr>
        `;
      });

      html += `</tbody></table></div>`;
    });

    $("#mpListArea").html(html);
  });
}

$(document).on("click", ".btn-toggle-detail", function () {
  $("#" + $(this).data("target")).toggle();
});

$(document).on("click", ".btn-edit-mp", function () {
  const id = $(this).data("id");

  $.getJSON(MetodePembelajaran.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    $("#mp_id").val(res.data.id);
    $("#mp_kategori").val(res.data.kategori);
    $("#mp_nama").val(res.data.nama_metode);
    $("#mp_aktivitas_mahasiswa").val(res.data.aktivitas_mahasiswa);
    $("#mp_aktivitas_dosen").val(res.data.aktivitas_dosen);
    $("#mp_sort_order").val(res.data.sort_order);
    $("#mpModalTitle").text("Edit Metode Pembelajaran");

    MetodePembelajaran.modal.show();
  });
});

$(document).on("click", ".btn-delete-mp", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Hapus Metode Pembelajaran ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(
      MetodePembelajaran.api + "?action=delete",
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
        loadListing();
      },
    );
  });
});

function saveData() {
  const id = $("#mp_id").val();
  const action = id ? "update" : "create";

  const formData = $("#mpForm").serialize();

  $.post(
    MetodePembelajaran.api + "?action=" + action,
    formData,
    function (response) {
      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      MetodePembelajaran.modal.hide();
      Swal.fire({
        icon: "success",
        title: "Berhasil",
        text: response.message,
        timer: 1200,
        showConfirmButton: false,
      });
      loadListing();
    },
  );
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}
