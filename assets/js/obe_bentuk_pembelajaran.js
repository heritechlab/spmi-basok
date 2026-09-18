const BentukPembelajaran = {
  api: SIQUA.BASE_URL + "obe/bentuk_pembelajaran/api.php",
  modal: null,
  waktuKomponenCache: [],
};

$(document).ready(function () {
  BentukPembelajaran.modal = new bootstrap.Modal(
    document.getElementById("bpModal"),
  );

  loadWaktuKomponenCache();

  $("#btnAddBentuk").on("click", function () {
    $("#bpForm")[0].reset();
    $("#bp_id").val("");
    $("#bpModalTitle").text("Tambah Bentuk Pembelajaran");
    BentukPembelajaran.modal.show();
  });

  $("#bpForm").on("submit", function (e) {
    e.preventDefault();
    saveData();
  });
});

function loadWaktuKomponenCache() {
  $.getJSON(
    BentukPembelajaran.api,
    { action: "waktu_komponen" },
    function (res) {
      if (res.success) {
        BentukPembelajaran.waktuKomponenCache = res.data;
      }

      loadListing();
    },
  );
}

function loadListing() {
  $("#bpListArea").html('<p class="text-center text-muted">Memuat data...</p>');

  $.getJSON(BentukPembelajaran.api, { action: "list" }, function (res) {
    if (!res.success) {
      $("#bpListArea").html(
        '<p class="text-center text-danger">' + res.message + "</p>",
      );
      return;
    }

    const byKategori = {};
    res.data.forEach(function (row) {
      if (!byKategori[row.kategori]) byKategori[row.kategori] = [];
      byKategori[row.kategori].push(row);
    });

    const komponenByKategori = {};
    BentukPembelajaran.waktuKomponenCache.forEach(function (k) {
      if (!komponenByKategori[k.kategori]) komponenByKategori[k.kategori] = [];
      komponenByKategori[k.kategori].push(k);
    });

    let html = "";

    Object.keys(byKategori).forEach(function (kategori) {
      const items = byKategori[kategori];
      const komponen = komponenByKategori[kategori] || [];
      const totalMenit = komponen.reduce(
        (sum, k) => sum + parseInt(k.menit),
        0,
      );
      const rincianText = komponen
        .map((k) => `${k.nama_komponen} ${k.menit} menit`)
        .join(" + ");

      html += `
        <div class="kategori-card">
          <div class="kategori-header">
            <div>
              <span class="kategori-name">${escapeHtml(kategori)}</span>
              <span class="kategori-waktu-badge">Total ${totalMenit} menit/minggu</span>
            </div>
          </div>
          <div class="px-3 pt-2">
            <small class="text-muted">${escapeHtml(rincianText)}</small>
          </div>
          <table class="table table-bordered mt-2 mb-0">
            <thead>
              <tr>
                <th width="50">No.</th>
                <th>Nama Bentuk Pembelajaran</th>
                ${BP_CAN_MANAGE ? '<th width="100">Aksi</th>' : ""}
              </tr>
            </thead>
            <tbody>
      `;

      if (!items.length) {
        html += `<tr><td colspan="3" class="text-center text-muted">Belum ada data.</td></tr>`;
      } else {
        items.forEach(function (row, idx) {
          html += `
            <tr>
              <td>${idx + 1}</td>
              <td>${escapeHtml(row.nama_bentuk)}</td>
              ${
                BP_CAN_MANAGE
                  ? `
              <td>
                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-bp" data-id="${row.id}">
                  <i class="bi bi-pencil"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-bp" data-id="${row.id}">
                  <i class="bi bi-trash"></i>
                </button>
              </td>`
                  : ""
              }
            </tr>
          `;
        });
      }

      html += `</tbody></table></div>`;
    });

    $("#bpListArea").html(html);
  });
}

$(document).on("click", ".btn-edit-bp", function () {
  const id = $(this).data("id");

  $.getJSON(BentukPembelajaran.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    $("#bp_id").val(res.data.id);
    $("#bp_kategori").val(res.data.kategori);
    $("#bp_nama").val(res.data.nama_bentuk);
    $("#bp_sort_order").val(res.data.sort_order);
    $("#bpModalTitle").text("Edit Bentuk Pembelajaran");

    BentukPembelajaran.modal.show();
  });
});

$(document).on("click", ".btn-delete-bp", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Hapus Bentuk Pembelajaran ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(
      BentukPembelajaran.api + "?action=delete",
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
  const id = $("#bp_id").val();
  const action = id ? "update" : "create";

  const formData = $("#bpForm").serialize();

  $.post(
    BentukPembelajaran.api + "?action=" + action,
    formData,
    function (response) {
      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      BentukPembelajaran.modal.hide();
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
