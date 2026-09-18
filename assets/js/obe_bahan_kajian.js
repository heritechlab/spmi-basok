const BahanKajian = {
  api: SIQUA.BASE_URL + "obe/bahan_kajian/api.php",
  unitId: null,
  modal: null,
};

$(document).ready(function () {
  BahanKajian.modal = new bootstrap.Modal(document.getElementById("bkModal"));

  if (BK_IS_AUDITEE) {
    loadListing();
  }

  $(document).on("change", "#bkUnitSelector", function () {
    BahanKajian.unitId = $(this).val();
    $("#bkListArea").html(
      '<p class="text-center text-muted">Pilih Program Studi terlebih dahulu.</p>',
    );

    if (BahanKajian.unitId) {
      loadListing();
    }
  });

  $("#bkForm").on("submit", function (e) {
    e.preventDefault();
    saveData();
  });
});

function loadListing() {
  $("#bkListArea").html('<p class="text-center text-muted">Memuat data...</p>');

  $.when(
    $.getJSON(BahanKajian.api, {
      action: "sub_cpmk_list",
      unit_id: BahanKajian.unitId || 0,
    }),
    $.getJSON(BahanKajian.api, {
      action: "list_all",
      unit_id: BahanKajian.unitId || 0,
    }),
  ).done(function (subRes, bkRes) {
    const subCpmkList = subRes[0].success ? subRes[0].data : [];
    const bahanKajianList = bkRes[0].success ? bkRes[0].data : [];

    if (!subCpmkList.length) {
      $("#bkListArea").html(
        '<p class="text-center text-muted">Belum ada Sub-CPMK untuk Program Studi ini. Silakan tambahkan dulu di menu Sub-CPMK.</p>',
      );
      return;
    }

    const bkBySubCpmk = {};
    bahanKajianList.forEach(function (item) {
      if (!bkBySubCpmk[item.sub_cpmk_id]) bkBySubCpmk[item.sub_cpmk_id] = [];
      bkBySubCpmk[item.sub_cpmk_id].push(item);
    });

    const byMk = {};
    subCpmkList.forEach(function (sub) {
      const mkKey = sub.mata_kuliah_id;
      if (!byMk[mkKey])
        byMk[mkKey] = {
          name: sub.mk_name,
          semester: sub.mk_semester,
          cpmk: {},
        };
      if (!byMk[mkKey].cpmk[sub.cpmk_code])
        byMk[mkKey].cpmk[sub.cpmk_code] = [];
      byMk[mkKey].cpmk[sub.cpmk_code].push(sub);
    });

    let html = "";

    Object.keys(byMk)
      .sort(function (a, b) {
        return byMk[a].semester - byMk[b].semester;
      })
      .forEach(function (mkId) {
        const mk = byMk[mkId];

        html += `<div class="mk-title">Sem ${mk.semester} - ${escapeHtml(mk.name)}</div>`;

        Object.keys(mk.cpmk).forEach(function (cpmkCode) {
          html += `<div class="cpmk-card"><div class="cpmk-header">${escapeHtml(cpmkCode)}</div>`;

          mk.cpmk[cpmkCode].forEach(function (sub) {
            const items = bkBySubCpmk[sub.id] || [];

            html += `
            <div class="subcpmk-block">
              <div class="subcpmk-head">
                <span class="subcpmk-name">${escapeHtml(sub.code)}</span>
                <button type="button" class="btn btn-sm btn-outline-primary btn-add-bk" data-sub-cpmk-id="${sub.id}" data-sub-cpmk-label="${escapeAttr(sub.code)}">
                  <i class="bi bi-plus-circle"></i> Tambah Bahan Kajian
                </button>
              </div>
              <table class="table table-bordered table-sm mb-0">
                <thead>
                  <tr>
                    <th width="40">No.</th>
                    <th>Nama Bahan Kajian</th>
                    <th>Deskripsi</th>
                    <th width="90">Aksi</th>
                  </tr>
                </thead>
                <tbody>
          `;

            if (!items.length) {
              html += `<tr><td colspan="4" class="text-center text-muted">Belum ada Bahan Kajian.</td></tr>`;
            } else {
              items.forEach(function (item, idx) {
                html += `
                <tr>
                  <td>${idx + 1}</td>
                  <td>${escapeHtml(item.nama_bahan_kajian)}</td>
                  <td>${item.deskripsi ? escapeHtml(item.deskripsi) : '<span class="text-muted">-</span>'}</td>
                  <td>
                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-bk" data-id="${item.id}">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-bk" data-id="${item.id}">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              `;
              });
            }

            html += `</tbody></table></div>`;
          });

          html += `</div>`;
        });
      });

    $("#bkListArea").html(html);
  });
}

$(document).on("click", ".btn-add-bk", function () {
  const subCpmkId = $(this).data("sub-cpmk-id");
  const subCpmkLabel = $(this).data("sub-cpmk-label");

  $("#bkForm")[0].reset();
  $("#bk_id").val("");
  $("#bk_sub_cpmk_id").val(subCpmkId);
  $("#bkModalSubLabel").text("Sub-CPMK: " + subCpmkLabel);
  $("#bkModalTitle").text("Tambah Bahan Kajian");
  BahanKajian.modal.show();
});

$(document).on("click", ".btn-edit-bk", function () {
  const id = $(this).data("id");

  $.getJSON(BahanKajian.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    $("#bk_id").val(res.data.id);
    $("#bk_sub_cpmk_id").val(res.data.sub_cpmk_id);
    $("#bk_nama").val(res.data.nama_bahan_kajian);
    $("#bk_deskripsi").val(res.data.deskripsi);
    $("#bk_sort_order").val(res.data.sort_order);
    $("#bkModalTitle").text("Edit Bahan Kajian");
    $("#bkModalSubLabel").text("");

    BahanKajian.modal.show();
  });
});

$(document).on("click", ".btn-delete-bk", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Hapus Bahan Kajian ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(BahanKajian.api + "?action=delete", { id: id }, function (response) {
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
    });
  });
});

function saveData() {
  const id = $("#bk_id").val();
  const action = id ? "update" : "create";

  const formData = $("#bkForm").serialize();

  $.post(BahanKajian.api + "?action=" + action, formData, function (response) {
    if (!response.success) {
      Swal.fire("Gagal", response.message, "error");
      return;
    }

    BahanKajian.modal.hide();
    Swal.fire({
      icon: "success",
      title: "Berhasil",
      text: response.message,
      timer: 1200,
      showConfirmButton: false,
    });
    loadListing();
  });
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

function escapeAttr(text) {
  return (text || "").replace(/"/g, "&quot;");
}
