const Cpl = {
  api: SIQUA.BASE_URL + "obe/cpl/api.php",
  unitId: null,
  modal: null,
  profilLulusanCache: [],
};

$(document).ready(function () {
  Cpl.modal = new bootstrap.Modal(document.getElementById("cplModal"));
  Cpl.matriksModal = new bootstrap.Modal(
    document.getElementById("matriksModal"),
  );

  $("#btnViewMatriks").on("click", function () {
    loadMatriks();
    Cpl.matriksModal.show();
  });

  if (CPL_IS_AUDITEE) {
    loadProfilLulusanCache();
    loadTable();
  }

  $(document).on("change", "#cplUnitSelector", function () {
    Cpl.unitId = $(this).val();
    if (Cpl.unitId) {
      loadProfilLulusanCache();
      loadTable();
    } else {
      $("#cplTableBody").html(
        '<tr><td colspan="5" class="text-center text-muted">Pilih Program Studi terlebih dahulu.</td></tr>',
      );
    }
  });

  $("#btnAddCpl").on("click", function () {
    $("#cplForm")[0].reset();
    $("#cpl_id").val("");
    $("#cplModalTitle").text("Tambah CPL");
    $(".cpl-aspek-checkbox").prop("checked", false);
    renderProfilLulusanChecklist([]);
    Cpl.modal.show();
  });

  $("#cplForm").on("submit", function (e) {
    e.preventDefault();
    saveData();
  });
});

function loadProfilLulusanCache() {
  $.getJSON(
    Cpl.api,
    { action: "profil_lulusan_list", unit_id: Cpl.unitId || 0 },
    function (res) {
      if (res.success) {
        Cpl.profilLulusanCache = res.data;
      }
    },
  );
}

function renderProfilLulusanChecklist(selectedItems) {
  if (!Cpl.profilLulusanCache.length) {
    $("#cplProfilLulusanChecklist").html(
      '<p class="text-muted small mb-0">Belum ada Profil Lulusan untuk Program Studi ini. Silakan tambahkan dulu di menu Profil Lulusan.</p>',
    );
    return;
  }

  const selectedMap = {};
  (selectedItems || []).forEach(function (item) {
    if (typeof item === "object") {
      selectedMap[item.id] = item.bobot;
    } else {
      selectedMap[item] = 0;
    }
  });

  let html = "";

  Cpl.profilLulusanCache.forEach(function (pl) {
    const isChecked =
      selectedMap.hasOwnProperty(pl.id) ||
      selectedMap.hasOwnProperty(String(pl.id));
    const bobotVal = isChecked
      ? (selectedMap[pl.id] ?? selectedMap[String(pl.id)] ?? 0)
      : 0;

    html += `
      <div class="d-flex align-items-center gap-2 mb-1 pl-map-row">
        <div class="form-check flex-grow-1 mb-0">
          <input class="form-check-input cpl-pl-checkbox" type="checkbox" value="${pl.id}" id="pl_check_${pl.id}" ${isChecked ? "checked" : ""}>
          <label class="form-check-label small" for="pl_check_${pl.id}">
            <strong>${escapeHtml(pl.code)}</strong> - ${escapeHtml(pl.name)}
          </label>
        </div>
        <input type="number" class="form-control form-control-sm cpl-pl-bobot" data-pl-id="${pl.id}" value="${bobotVal}" min="0" max="100" step="0.01" style="width:80px;" placeholder="Bobot %" ${isChecked ? "" : "disabled"}>
      </div>
    `;
  });

  $("#cplProfilLulusanChecklist").html(html);
}

$(document).on("change", ".cpl-pl-checkbox", function () {
  const row = $(this).closest(".pl-map-row");
  const bobotInput = row.find(".cpl-pl-bobot");
  bobotInput.prop("disabled", !$(this).is(":checked"));
});

function loadTable() {
  $("#cplTableBody").html(
    '<tr><td colspan="5" class="text-center text-muted">Memuat data...</td></tr>',
  );

  $.getJSON(
    Cpl.api,
    { action: "list", unit_id: Cpl.unitId || 0 },
    function (res) {
      if (!res.success) {
        $("#cplTableBody").html(
          '<tr><td colspan="5" class="text-center text-danger">' +
            res.message +
            "</td></tr>",
        );
        return;
      }

      if (!res.data.length) {
        $("#cplTableBody").html(
          '<tr><td colspan="5" class="text-center text-muted">Belum ada CPL.</td></tr>',
        );
        return;
      }

      let html = "";

      res.data.forEach(function (row) {
        const plBadges = (row.profil_lulusan || [])
          .map(
            (pl) =>
              `<span class="badge bg-light text-dark border me-1 mb-1">${escapeHtml(pl.code)}</span>`,
          )
          .join("");

        const aspekBadges = (row.aspek_list || [])
          .map(
            (a) =>
              `<span class="badge bg-secondary me-1 mb-1">${escapeHtml(a)}</span>`,
          )
          .join("");

        html += `
        <tr>
          <td><strong>${escapeHtml(row.code)}</strong></td>
          <td>${aspekBadges || '<span class="text-muted small">-</span>'}</td>
          <td>${escapeHtml(row.description)}</td>
          <td>${plBadges || '<span class="text-muted small">-</span>'}</td>
          <td>
            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-cpl" data-id="${row.id}">
              <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-cpl" data-id="${row.id}">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
      `;
      });

      $("#cplTableBody").html(html);
    },
  );
}

$(document).on("click", ".btn-edit-cpl", function () {
  const id = $(this).data("id");

  $.getJSON(Cpl.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    $("#cpl_id").val(res.data.id);
    $("#cpl_code").val(res.data.code);
    $("#cpl_description").val(res.data.description);
    $("#cpl_sort_order").val(res.data.sort_order);
    $("#cplModalTitle").text("Edit CPL");

    $(".cpl-aspek-checkbox").prop("checked", false);
    (res.data.aspek_list || []).forEach(function (a) {
      $(`.cpl-aspek-checkbox[value="${a}"]`).prop("checked", true);
    });

    renderProfilLulusanChecklist(
      res.data.profil_lulusan_detail || res.data.profil_lulusan_ids || [],
    );

    Cpl.modal.show();
  });
});

$(document).on("click", ".btn-delete-cpl", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Hapus CPL ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(Cpl.api + "?action=delete", { id: id }, function (response) {
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
  const id = $("#cpl_id").val();
  const action = id ? "update" : "create";

  const selectedPlIds = $(".cpl-pl-checkbox:checked")
    .map(function () {
      return $(this).val();
    })
    .get();

  const selectedAspek = $(".cpl-aspek-checkbox:checked")
    .map(function () {
      return $(this).val();
    })
    .get();

  let formData = $("#cplForm").serialize() + "&unit_id=" + (Cpl.unitId || 0);

  selectedPlIds.forEach(function (plId) {
    formData += "&profil_lulusan_ids[]=" + plId;
    const bobotVal = $(`.cpl-pl-bobot[data-pl-id="${plId}"]`).val() || 0;
    formData += "&bobot_map[" + plId + "]=" + bobotVal;
  });

  selectedAspek.forEach(function (a) {
    formData += "&aspek_list[]=" + encodeURIComponent(a);
  });

  $.post(Cpl.api + "?action=" + action, formData, function (response) {
    if (!response.success) {
      Swal.fire("Gagal", response.message, "error");
      return;
    }

    Cpl.modal.hide();
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

function loadMatriks() {
  $("#matriksTable thead").html('<tr><td colspan="2">Memuat...</td></tr>');
  $("#matriksTable tbody").html("");

  $.getJSON(
    Cpl.api,
    { action: "matriks", unit_id: Cpl.unitId || 0 },
    function (res) {
      if (!res.success) {
        $("#matriksTable thead").html(
          '<tr><td colspan="2" class="text-danger">' +
            res.message +
            "</td></tr>",
        );
        return;
      }

      const { profil_lulusan, cpl, map } = res.data;

      if (!profil_lulusan.length || !cpl.length) {
        $("#matriksTable thead").html(
          '<tr><td colspan="2">Belum ada data Profil Lulusan dan/atau CPL yang cukup untuk ditampilkan.</td></tr>',
        );
        return;
      }

      let theadHtml =
        "<tr><th rowspan='2' style='vertical-align:middle;'>No</th><th rowspan='2' style='vertical-align:middle;'>Profil Lulusan</th>";
      theadHtml += `<th colspan="${cpl.length}">Capaian Pembelajaran Lulusan</th><th rowspan="2" style="vertical-align:middle;">Total</th></tr><tr>`;
      cpl.forEach(function (c) {
        theadHtml += `<th>${escapeHtml(c.code)}</th>`;
      });
      theadHtml += "</tr>";

      $("#matriksTable thead").html(theadHtml);

      let tbodyHtml = "";
      const colTotals = {};

      profil_lulusan.forEach(function (pl, idx) {
        tbodyHtml += `<tr><td>${idx + 1}</td><td class="text-start"><strong>${escapeHtml(pl.code)}</strong> - ${escapeHtml(pl.name)}</td>`;
        let rowTotal = 0;
        cpl.forEach(function (c) {
          const key = pl.id + "_" + c.id;
          const hasMapping = map.hasOwnProperty(key);
          const bobotVal = hasMapping ? parseFloat(map[key]) : 0;
          if (hasMapping) rowTotal += bobotVal;
          colTotals[c.id] = (colTotals[c.id] || 0) + bobotVal;
          const cellContent = hasMapping
            ? `<span style="color:#059669; font-weight:700;">${bobotVal}%</span>`
            : "";
          tbodyHtml += `<td>${cellContent}</td>`;
        });
        tbodyHtml += `<td class="fw-bold">${rowTotal.toFixed(2)}%</td></tr>`;
      });

      let grandTotal = 0;
      Object.values(colTotals).forEach(function (v) {
        grandTotal += v;
      });

      tbodyHtml += `<tr class="table-light"><td colspan="2" class="fw-bold text-start">Total per CPL</td>`;
      cpl.forEach(function (c) {
        const total = colTotals[c.id] || 0;
        const color =
          Math.abs(total - 100) < 0.01
            ? "#059669"
            : total > 100
              ? "#dc2626"
              : "#d97706";
        tbodyHtml += `<td class="fw-bold" style="color:${color};">${total.toFixed(2)}%</td>`;
      });
      tbodyHtml += `<td class="fw-bold" style="background:#f1f0f8;">${grandTotal.toFixed(2)}%</td></tr>`;

      $("#matriksTable tbody").html(tbodyHtml);
    },
  );
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}
