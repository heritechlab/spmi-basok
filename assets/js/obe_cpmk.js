const Cpmk = {
  api: SIQUA.BASE_URL + "obe/cpmk/api.php",
  unitId: null,
  kurikulumId: null,
  kurikulumCache: [],
  modal: null,
  matriksModal: null,
  cplCache: [],
  mkCache: [],
  matriksDataCache: null,
};

$(document).ready(function () {
  Cpmk.modal = new bootstrap.Modal(document.getElementById("cpmkModal"));
  Cpmk.matriksModal = new bootstrap.Modal(
    document.getElementById("matriksCpmkCplModal"),
  );

  $("#btnViewMatriksCpmkCpl").on("click", function () {
    fillMatriksMkFilter();
    $("#matriksCpmkMkFilter").val("");
    loadMatriksCpmkCpl();
    Cpmk.matriksModal.show();
  });

  $(document).on("change", "#matriksCpmkMkFilter", function () {
    renderMatriksCpmkCplTable($(this).val());
  });

  if (CPMK_IS_AUDITEE) {
    loadKurikulumTabs();
  }

  $(document).on("change", "#cpmkUnitSelector", function () {
    Cpmk.unitId = $(this).val();
    $("#cpmkListArea").html(
      '<p class="text-center text-muted">Pilih Program Studi terlebih dahulu.</p>',
    );
    $("#cpmkKurikulumTabsWrap").hide();

    if (Cpmk.unitId) {
      loadKurikulumTabs();
    }
  });

  $("#btnAddCpmk").on("click", function () {
    $("#cpmkForm")[0].reset();
    $("#cpmk_id").val("");
    $("#cpmkModalTitle").text("Tambah CPMK");
    renderCplChecklist(null);
    Cpmk.modal.show();
  });

  $("#cpmkForm").on("submit", function (e) {
    e.preventDefault();
    saveData();
  });
});

function loadKurikulumTabs() {
  $.getJSON(
    Cpmk.api,
    { action: "kurikulum_list", unit_id: Cpmk.unitId || 0 },
    function (res) {
      if (!res.success) return;

      Cpmk.kurikulumCache = (res.data || []).filter((k) => k.is_active == 1);

      if (!Cpmk.kurikulumCache.length) {
        $("#cpmkKurikulumTabsWrap").hide();
        $("#cpmkListArea").html(
          '<p class="text-center text-muted">Belum ada Kurikulum Aktif untuk Program Studi ini.</p>',
        );
        return;
      }

      let html = "";

      Cpmk.kurikulumCache.forEach(function (k, idx) {
        html += `
        <li class="nav-item">
          <button type="button" class="nav-link cpmk-kurikulum-tab ${idx === 0 ? "active" : ""}" data-kurikulum-id="${k.id}">
            ${k.tahun} - ${escapeHtml(k.nama)}
          </button>
        </li>
      `;
      });

      $("#cpmkKurikulumTabs").html(html);
      $("#cpmkKurikulumTabsWrap").toggle(Cpmk.kurikulumCache.length > 1);

      selectKurikulumTab(Cpmk.kurikulumCache[0].id);
    },
  );
}

$(document).on("click", ".cpmk-kurikulum-tab", function () {
  $(".cpmk-kurikulum-tab").removeClass("active");
  $(this).addClass("active");
  selectKurikulumTab($(this).data("kurikulum-id"));
});

function selectKurikulumTab(kurikulumId) {
  Cpmk.kurikulumId = kurikulumId;
  loadAllData();
}

function loadAllData() {
  $("#cpmkListArea").html(
    '<p class="text-center text-muted">Memuat data...</p>',
  );

  $.when(
    $.getJSON(Cpmk.api, { action: "cpl_list", unit_id: Cpmk.unitId || 0 }),
    $.getJSON(Cpmk.api, {
      action: "mata_kuliah_list",
      kurikulum_id: Cpmk.kurikulumId || 0,
    }),
  ).done(function (cplRes, mkRes) {
    Cpmk.cplCache = cplRes[0].success ? cplRes[0].data : [];
    Cpmk.mkCache = mkRes[0].success ? mkRes[0].data : [];

    loadListing();
  });
}

function loadCplCache() {
  $.getJSON(
    Cpmk.api,
    { action: "cpl_list", unit_id: Cpmk.unitId || 0 },
    function (res) {
      if (res.success) Cpmk.cplCache = res.data;
    },
  );
}

function loadMkCache() {
  $.getJSON(
    Cpmk.api,
    { action: "mata_kuliah_list", unit_id: Cpmk.unitId || 0 },
    function (res) {
      if (res.success) Cpmk.mkCache = res.data;
    },
  );
}

function loadListing() {
  $("#cpmkListArea").html(
    '<p class="text-center text-muted">Memuat data...</p>',
  );

  $.getJSON(
    Cpmk.api,
    { action: "list_all", kurikulum_id: Cpmk.kurikulumId || 0 },
    function (res) {
      if (!res.success) {
        $("#cpmkListArea").html(
          '<p class="text-center text-danger">' + res.message + "</p>",
        );
        return;
      }

      const byMk = {};
      res.data.forEach(function (row) {
        const key = row.mata_kuliah_id;
        if (!byMk[key]) {
          byMk[key] = {
            name: row.mk_name,
            semester: row.mk_semester,
            items: [],
          };
        }
        byMk[key].items.push(row);
      });

      let html = "";

      (Cpmk.mkCache || []).forEach(function (mk) {
        const group = byMk[mk.id];

        const totalBobot = group
          ? group.items.reduce((sum, r) => sum + parseFloat(r.bobot || 0), 0)
          : 0;
        const bobotColor =
          Math.abs(totalBobot - 100) < 0.01
            ? "#059669"
            : totalBobot > 100
              ? "#dc2626"
              : "#d97706";

        html += `
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mt-2 mb-1">
            <h6 class="fw-bold mb-0" style="color:#7c3aed;">
              Sem ${mk.semester} - ${escapeHtml(mk.name)}
              <span class="badge ms-1" style="background:${bobotColor}; font-size:10.5px;">Total Bobot: ${totalBobot.toFixed(2)}%</span>
            </h6>
            <button type="button" class="btn btn-sm btn-outline-primary btn-add-cpmk-for-mk" data-mk-id="${mk.id}" data-mk-name="${escapeAttr("Sem " + mk.semester + " - " + mk.name)}">
              <i class="bi bi-plus-circle"></i> Tambah CPMK
            </button>
          </div>
          <table class="table table-bordered table-sm align-middle mb-2" style="font-size:12.5px;">
            <thead class="table-light">
              <tr>
                <th width="90" style="padding:4px 6px;">Kode</th>
                <th style="padding:4px 6px;">Deskripsi</th>
                <th width="70" class="text-center" style="padding:4px 6px;">Bobot</th>
                <th width="150" style="padding:4px 6px;">CPL Terkait</th>
                <th width="100" class="text-center" style="padding:4px 6px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
      `;

        if (!group || !group.items.length) {
          html += `<tr><td colspan="4" class="text-center text-muted" style="padding:4px 6px;">Belum ada CPMK.</td></tr>`;
        } else {
          group.items.forEach(function (row) {
            const cplBadge = row.cpl_code
              ? `<span class="badge bg-light text-dark border">${escapeHtml(row.cpl_code)}</span>`
              : '<span class="text-muted small">-</span>';

            html += `
            <tr>
              <td style="padding:4px 6px;"><strong>${escapeHtml(row.code)}</strong></td>
              <td style="padding:4px 6px;">${escapeHtml(row.description)}</td>
              <td class="text-center" style="padding:4px 6px;">${parseFloat(row.bobot).toFixed(2)}%</td>
              <td style="padding:4px 6px;">${cplBadge}</td>
              <td class="text-center" style="padding:4px 6px;">
                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-cpmk" data-id="${row.id}" data-mk-id="${row.mata_kuliah_id}">
                  <i class="bi bi-pencil"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-cpmk" data-id="${row.id}" data-mk-id="${row.mata_kuliah_id}">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          `;
          });
        }

        html += `</tbody></table></div>`;
      });

      $("#cpmkListArea").html(
        html ||
          '<p class="text-center text-muted">Belum ada Mata Kuliah untuk Program Studi ini.</p>',
      );
    },
  );
}

function renderCplChecklist(selectedId) {
  let html = '<option value="">-- Pilih CPL --</option>';

  Cpmk.cplCache.forEach(function (c) {
    const selected =
      selectedId && String(selectedId) === String(c.id) ? "selected" : "";
    html += `<option value="${c.id}" ${selected}>${escapeHtml(c.code)}</option>`;
  });

  $("#cpmk_cpl_id").html(html);
}

$(document).on("click", ".btn-add-cpmk-for-mk", function () {
  const mkId = $(this).data("mk-id");
  const mkName = $(this).data("mk-name");

  $("#cpmkForm")[0].reset();
  $("#cpmk_id").val("");
  $("#cpmk_mata_kuliah_id").val(mkId);
  $("#cpmkModalMkLabel").text("Mata Kuliah: " + mkName);
  $("#cpmkModalTitle").text("Tambah CPMK");
  renderCplChecklist(null);
  Cpmk.modal.show();
});

$(document).on("click", ".btn-edit-cpmk", function () {
  const id = $(this).data("id");
  const mkId = $(this).data("mk-id");

  $.getJSON(Cpmk.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    const mk = (Cpmk.mkCache || []).find((m) => String(m.id) === String(mkId));

    $("#cpmk_id").val(res.data.id);
    $("#cpmk_mata_kuliah_id").val(mkId);
    $("#cpmkModalMkLabel").text(
      "Mata Kuliah: " + (mk ? "Sem " + mk.semester + " - " + mk.name : ""),
    );
    $("#cpmk_code").val(res.data.code);
    $("#cpmk_description").val(res.data.description);
    $("#cpmk_bobot").val(res.data.bobot);
    $("#cpmk_sort_order").val(res.data.sort_order);
    $("#cpmkModalTitle").text("Edit CPMK");

    renderCplChecklist(res.data.cpl_id);

    Cpmk.modal.show();
  });
});

$(document).on("click", ".btn-delete-cpmk", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Hapus CPMK ini?",
    text: "Sub-CPMK di bawahnya (jika ada) juga akan ikut terhapus.",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(Cpmk.api + "?action=delete", { id: id }, function (response) {
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
  const id = $("#cpmk_id").val();
  const action = id ? "update" : "create";

  const formData = $("#cpmkForm").serialize();

  $.post(Cpmk.api + "?action=" + action, formData, function (response) {
    if (!response.success) {
      Swal.fire("Gagal", response.message, "error");
      return;
    }

    Cpmk.modal.hide();
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

function fillMatriksMkFilter() {
  let html = '<option value="">Semua Mata Kuliah</option>';

  (Cpmk.mkCache || []).forEach(function (mk) {
    html += `<option value="${mk.id}">Sem ${mk.semester} - ${escapeHtml(mk.name)}</option>`;
  });

  $("#matriksCpmkMkFilter").html(html);
}

function loadMatriksCpmkCpl() {
  $("#matriksCpmkCplTable thead").html("<tr><td>Memuat...</td></tr>");
  $("#matriksCpmkCplTable tbody").html("");

  $.getJSON(
    Cpmk.api,
    {
      action: "matriks",
      unit_id: Cpmk.unitId || 0,
      kurikulum_id: Cpmk.kurikulumId || 0,
    },
    function (res) {
      if (!res.success) {
        $("#matriksCpmkCplTable thead").html(
          '<tr><td class="text-danger">' + res.message + "</td></tr>",
        );
        return;
      }

      Cpmk.matriksDataCache = res.data;

      renderMatriksCpmkCplTable("");
    },
  );
}

function renderMatriksCpmkCplTable(mkFilter) {
  const data = Cpmk.matriksDataCache;

  if (!data) return;

  const { cpmk, cpl, map } = data;

  if (!cpmk.length || !cpl.length) {
    $("#matriksCpmkCplTable thead").html(
      "<tr><td>Belum ada data CPMK dan/atau CPL yang cukup.</td></tr>",
    );
    $("#matriksCpmkCplTable tbody").html("");
    return;
  }

  const filteredCpmk = mkFilter
    ? cpmk.filter((c) => String(c.mata_kuliah_id) === String(mkFilter))
    : cpmk;

  if (!filteredCpmk.length) {
    $("#matriksCpmkCplTable thead").html(
      "<tr><td>Belum ada CPMK untuk Mata Kuliah ini.</td></tr>",
    );
    $("#matriksCpmkCplTable tbody").html("");
    return;
  }

  let theadHtml = '<tr><th style="min-width:200px;">CPMK</th>';
  cpl.forEach(function (c) {
    theadHtml += `<th style="min-width:60px;">${escapeHtml(c.code)}</th>`;
  });
  theadHtml += "</tr>";

  $("#matriksCpmkCplTable thead").html(theadHtml);

  let tbodyHtml = "";

  filteredCpmk.forEach(function (row) {
    tbodyHtml += `<tr><td class="text-start"><strong>${escapeHtml(row.code)}</strong><div class="text-muted small">${escapeHtml(row.mk_name)}</div></td>`;
    cpl.forEach(function (c) {
      const key = row.id + "_" + c.id;
      const mark = map[key]
        ? '<span style="color:#059669; font-weight:700;">&#10003;</span>'
        : "";
      tbodyHtml += `<td>${mark}</td>`;
    });
    tbodyHtml += "</tr>";
  });

  $("#matriksCpmkCplTable tbody").html(tbodyHtml);
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

function escapeAttr(text) {
  return (text || "").replace(/"/g, "&quot;");
}
