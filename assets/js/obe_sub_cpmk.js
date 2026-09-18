const SubCpmk = {
  api: SIQUA.BASE_URL + "obe/sub_cpmk/api.php",
  unitId: null,
  kurikulumId: null,
  kurikulumCache: [],
  modal: null,
};

$(document).ready(function () {
  SubCpmk.modal = new bootstrap.Modal(document.getElementById("subCpmkModal"));

  if (SUBCPMK_IS_AUDITEE) {
    loadKurikulumTabs();
  }

  $(document).on("change", "#subCpmkUnitSelector", function () {
    SubCpmk.unitId = $(this).val();
    $("#subCpmkListArea").html(
      '<p class="text-center text-muted">Pilih Program Studi terlebih dahulu.</p>',
    );
    $("#subCpmkKurikulumTabsWrap").hide();

    if (SubCpmk.unitId) {
      loadKurikulumTabs();
    }
  });

  $("#subCpmkForm").on("submit", function (e) {
    e.preventDefault();
    saveData();
  });
});

function loadKurikulumTabs() {
  $.getJSON(
    SubCpmk.api,
    { action: "kurikulum_list", unit_id: SubCpmk.unitId || 0 },
    function (res) {
      if (!res.success) return;

      SubCpmk.kurikulumCache = (res.data || []).filter((k) => k.is_active == 1);

      if (!SubCpmk.kurikulumCache.length) {
        $("#subCpmkKurikulumTabsWrap").hide();
        $("#subCpmkListArea").html(
          '<p class="text-center text-muted">Belum ada Kurikulum Aktif untuk Program Studi ini.</p>',
        );
        return;
      }

      let html = "";

      SubCpmk.kurikulumCache.forEach(function (k, idx) {
        html += `
        <li class="nav-item">
          <button type="button" class="nav-link sub-cpmk-kurikulum-tab ${idx === 0 ? "active" : ""}" data-kurikulum-id="${k.id}">
            ${k.tahun} - ${escapeHtml(k.nama)}
          </button>
        </li>
      `;
      });

      $("#subCpmkKurikulumTabs").html(html);
      $("#subCpmkKurikulumTabsWrap").toggle(SubCpmk.kurikulumCache.length > 1);

      selectKurikulumTab(SubCpmk.kurikulumCache[0].id);
    },
  );
}

$(document).on("click", ".sub-cpmk-kurikulum-tab", function () {
  $(".sub-cpmk-kurikulum-tab").removeClass("active");
  $(this).addClass("active");
  selectKurikulumTab($(this).data("kurikulum-id"));
});

function selectKurikulumTab(kurikulumId) {
  SubCpmk.kurikulumId = kurikulumId;
  loadListing();
}

function loadListing() {
  $("#subCpmkListArea").html(
    '<p class="text-center text-muted">Memuat data...</p>',
  );

  $.when(
    $.getJSON(SubCpmk.api, {
      action: "cpmk_list",
      kurikulum_id: SubCpmk.kurikulumId || 0,
    }),
    $.getJSON(SubCpmk.api, {
      action: "list_all",
      unit_id: SubCpmk.unitId || 0,
    }),
  ).done(function (cpmkRes, subRes) {
    const cpmkList = cpmkRes[0].success ? cpmkRes[0].data : [];
    const subList = subRes[0].success ? subRes[0].data : [];

    renderListing(cpmkList, subList);
  });
}

function renderSubCpmkTable(cpmk, subItems) {
  const totalBobot = subItems.reduce(
    (sum, s) => sum + parseFloat(s.bobot || 0),
    0,
  );
  const bobotColor =
    subItems.length === 0
      ? "#9ca3af"
      : Math.abs(totalBobot - 100) < 0.01
        ? "#059669"
        : totalBobot > 100
          ? "#dc2626"
          : "#d97706";

  let html = `
    <div class="d-flex justify-content-between align-items-center mt-2 mb-1">
      <span class="badge" style="background:${bobotColor}; font-size:10.5px;">Total Bobot Sub-CPMK: ${totalBobot.toFixed(2)}%</span>
      <button type="button" class="btn btn-sm btn-outline-primary btn-add-sub-cpmk" data-cpmk-id="${cpmk.id}" data-cpmk-label="${escapeAttr(cpmk.code + " - " + cpmk.description)}">
        <i class="bi bi-plus-circle"></i> Tambah Sub-CPMK
      </button>
    </div>
    <table class="table table-bordered table-sm align-middle mb-2" style="font-size:12.5px;">
      <thead class="table-light">
        <tr>
          <th width="110" style="padding:4px 6px;">Kode</th>
          <th style="padding:4px 6px;">Deskripsi</th>
          <th width="70" class="text-center" style="padding:4px 6px;">Bobot</th>
          <th width="100" class="text-center" style="padding:4px 6px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
  `;

  if (!subItems.length) {
    html += `<tr><td colspan="4" class="text-center text-muted" style="padding:4px 6px;">Belum ada Sub-CPMK.</td></tr>`;
  } else {
    subItems.forEach(function (s) {
      html += `
        <tr>
          <td style="padding:4px 6px;"><strong>${escapeHtml(s.code)}</strong></td>
          <td style="padding:4px 6px;">${escapeHtml(s.description)}</td>
          <td class="text-center" style="padding:4px 6px;">${parseFloat(s.bobot).toFixed(2)}%</td>
          <td class="text-center" style="padding:4px 6px;">
            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-sub-cpmk" data-id="${s.id}" data-cpmk-id="${cpmk.id}">
              <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-sub-cpmk" data-id="${s.id}">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
      `;
    });
  }

  html += `</tbody></table>`;

  return html;
}

function renderListing(cpmkList, subList) {
  if (!cpmkList.length) {
    $("#subCpmkListArea").html(
      '<p class="text-center text-muted">Belum ada CPMK untuk Program Studi ini. Silakan tambahkan dulu di menu CPMK.</p>',
    );
    return;
  }

  const byMk = {};
  cpmkList.forEach(function (c) {
    const key = c.mata_kuliah_id;
    if (!byMk[key]) {
      byMk[key] = { name: c.mk_name, semester: c.mk_semester, cpmkItems: [] };
    }
    byMk[key].cpmkItems.push(c);
  });

  const subByCpmk = {};
  subList.forEach(function (s) {
    const key = s.cpmk_id;
    if (!subByCpmk[key]) subByCpmk[key] = [];
    subByCpmk[key].push(s);
  });

  let html = "";

  const mkKeysSorted = Object.keys(byMk).sort(function (a, b) {
    return (
      byMk[a].semester - byMk[b].semester ||
      byMk[a].name.localeCompare(byMk[b].name)
    );
  });

  mkKeysSorted.forEach(function (mkId) {
    const mkGroup = byMk[mkId];

    html += `<div class="mb-4"><h5 class="fw-bold" style="color:#7c3aed;">Sem ${mkGroup.semester} - ${escapeHtml(mkGroup.name)}</h5>`;

    // Kelompokkan CPMK berdasar KODE (bukan id) - supaya CPMK dgn kode sama (tapi CPL beda) tampil jadi 1 kelompok
    const byCode = {};
    const codeOrder = [];
    mkGroup.cpmkItems.forEach(function (cpmk) {
      if (!byCode[cpmk.code]) {
        byCode[cpmk.code] = [];
        codeOrder.push(cpmk.code);
      }
      byCode[cpmk.code].push(cpmk);
    });

    codeOrder.forEach(function (code) {
      const cpmkGroup = byCode[code];

      html += `<div class="mb-3 ms-2">`;

      if (cpmkGroup.length === 1) {
        // Cuma 1 CPL - tampil polos seperti biasa, TIDAK dirubah
        const cpmk = cpmkGroup[0];
        const subItems = subByCpmk[cpmk.id] || [];

        html += `<h6 class="fw-bold mb-0">${escapeHtml(cpmk.code)}</h6>`;
        html += renderSubCpmkTable(cpmk, subItems);
      } else {
        // Lebih dari 1 CPL - gabung jadi 1 judul, dengan sub-bagian per CPL
        const cplLabels = cpmkGroup
          .map((c) => escapeHtml(c.cpl_code || "-"))
          .join(", ");

        html += `<h6 class="fw-bold mb-2">${escapeHtml(code)} <span class="text-muted fw-normal" style="font-size:12px;">(mencakup ${cplLabels})</span></h6>`;

        cpmkGroup.forEach(function (cpmk, idx) {
          const subItems = subByCpmk[cpmk.id] || [];

          html += `
            <div class="border-start border-3 ps-2 mb-2" style="border-color:#e2dff2 !important;">
              <div class="fw-semibold small mb-1" style="color:#7c3aed;">
                <i class="bi bi-arrow-return-right"></i> Terkait ${escapeHtml(cpmk.cpl_code || "-")}
              </div>
              ${renderSubCpmkTable(cpmk, subItems)}
            </div>
          `;
        });
      }

      html += `</div>`;
    });

    html += `</div>`;
  });

  $("#subCpmkListArea").html(html);
}

$(document).on("click", ".btn-add-sub-cpmk", function () {
  const cpmkId = $(this).data("cpmk-id");
  const cpmkLabel = $(this).data("cpmk-label");

  $("#subCpmkForm")[0].reset();
  $("#sub_cpmk_id").val("");
  $("#sub_cpmk_cpmk_id").val(cpmkId);
  $("#subCpmkModalCpmkLabel").text("CPMK: " + cpmkLabel);
  $("#subCpmkModalTitle").text("Tambah Sub-CPMK");
  SubCpmk.modal.show();
});

$(document).on("click", ".btn-edit-sub-cpmk", function () {
  const id = $(this).data("id");
  const cpmkId = $(this).data("cpmk-id");

  $.getJSON(SubCpmk.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    $("#sub_cpmk_id").val(res.data.id);
    $("#sub_cpmk_cpmk_id").val(cpmkId);
    $("#sub_cpmk_code").val(res.data.code);
    $("#sub_cpmk_description").val(res.data.description);
    $("#sub_cpmk_bobot").val(res.data.bobot);
    $("#sub_cpmk_sort_order").val(res.data.sort_order);
    $("#subCpmkModalTitle").text("Edit Sub-CPMK");
    $("#subCpmkModalCpmkLabel").text("");

    SubCpmk.modal.show();
  });
});

$(document).on("click", ".btn-delete-sub-cpmk", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Hapus Sub-CPMK ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(SubCpmk.api + "?action=delete", { id: id }, function (response) {
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
  const id = $("#sub_cpmk_id").val();
  const action = id ? "update" : "create";

  const formData = $("#subCpmkForm").serialize();

  $.post(SubCpmk.api + "?action=" + action, formData, function (response) {
    if (!response.success) {
      Swal.fire("Gagal", response.message, "error");
      return;
    }

    SubCpmk.modal.hide();
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
