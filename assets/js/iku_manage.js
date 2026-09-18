const IkuManage = {
  api: SIQUA.BASE_URL + "iku/api.php",
  editModal: null,
  currentTahun: null,
};

$(document).ready(function () {
  IkuManage.editModal = new bootstrap.Modal(
    document.getElementById("ikuEditModal"),
  );
  IkuManage.currentTahun = $("#ikuManageTahun").val();

  loadIkuManageList();

  $("#ikuManageTahun").on("change", function () {
    IkuManage.currentTahun = $(this).val();
    loadIkuManageList();
  });

  let ikuAllUnits = [];
  let ikuUnitModal = new bootstrap.Modal(
    document.getElementById("ikuUnitModal"),
  );

  $.getJSON(IkuManage.api, { action: "units" }, function (res) {
    if (res.success) {
      ikuAllUnits = res.data;
    }
  });

  $(document).on("change", "#iku_all_units", function () {
    if ($(this).is(":checked")) {
      $("#ikuUnitChecklistWrapper").hide();
    } else {
      $("#ikuUnitChecklistWrapper").show();
    }
  });

  $(document).on("click", ".btn-assign-iku-unit", function () {
    const indicatorId = $(this).data("id");
    const indicatorName = $(this).data("name");

    $("#unit_indicator_id").val(indicatorId);
    $("#unitModalIndicatorName").text(indicatorName);

    $.getJSON(
      IkuManage.api,
      { action: "get_indicator_units", indicator_id: indicatorId },
      function (res) {
        const selectedIds = res.success ? res.data.map(String) : [];

        let checklistHtml = "";
        ikuAllUnits.forEach(function (u) {
          const checked = selectedIds.includes(String(u.id)) ? "checked" : "";
          checklistHtml += `
          <div class="form-check">
            <input class="form-check-input iku-unit-checkbox" type="checkbox" value="${u.id}" id="ikuu_${u.id}" ${checked}>
            <label class="form-check-label small" for="ikuu_${u.id}">${escapeHtml(u.code + " - " + u.name)}</label>
          </div>
        `;
        });

        $("#ikuUnitCheckboxList").html(checklistHtml);

        if (selectedIds.length > 0) {
          $("#iku_all_units").prop("checked", false);
          $("#ikuUnitChecklistWrapper").show();
        } else {
          $("#iku_all_units").prop("checked", true);
          $("#ikuUnitChecklistWrapper").hide();
        }

        ikuUnitModal.show();
      },
    );
  });

  $(document).on("click", "#btnSaveIkuUnits", function () {
    const indicatorId = $("#unit_indicator_id").val();
    const isAllUnits = $("#iku_all_units").is(":checked");
    const unitIds = isAllUnits
      ? []
      : $(".iku-unit-checkbox:checked")
          .map(function () {
            return $(this).val();
          })
          .get();

    $.post(
      IkuManage.api + "?action=save_indicator_units",
      { indicator_id: indicatorId, unit_ids: unitIds },
      function (response) {
        if (!response.success) {
          Swal.fire("Gagal", response.message, "error");
          return;
        }

        ikuUnitModal.hide();
        Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: response.message,
          timer: 1200,
          showConfirmButton: false,
        });
      },
    ).fail(function () {
      Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
    });
  });

  $(document).on("click", ".btn-edit-iku-indicator", function () {
    $("#edit_id").val($(this).data("id"));
    $("#edit_code").val($(this).data("code"));
    $("#edit_name").val($(this).data("name"));
    $("#edit_satuan").val($(this).data("satuan"));
    $("#edit_direction").val($(this).data("direction"));
    $("#edit_kategori").val($(this).data("kategori"));
    $("#edit_is_selected").prop("checked", $(this).data("selected") == 1);
    IkuManage.editModal.show();
  });

  $("#ikuEditForm").on("submit", function (e) {
    e.preventDefault();

    $.post(
      IkuManage.api + "?action=update_indicator",
      $(this).serialize() +
        "&is_selected=" +
        ($("#edit_is_selected").is(":checked") ? 1 : 0),
      function (response) {
        if (!response.success) {
          Swal.fire("Gagal", response.message, "error");
          return;
        }
        IkuManage.editModal.hide();
        Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: response.message,
          timer: 1000,
          showConfirmButton: false,
        });
        loadIkuManageList();
      },
    ).fail(function () {
      Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
    });
  });

  $(document).on("change", ".iku-target-input", function () {
    const $input = $(this);
    const indicatorId = $input.data("indicator-id");
    const field = $input.data("field");

    const baseline =
      field === "baseline"
        ? $input.val()
        : $input.closest("tr").find('[data-field="target"]').val();
    const target =
      field === "target"
        ? $input.val()
        : $input.closest("tr").find('[data-field="baseline"]').val();

    $.post(
      IkuManage.api + "?action=save_target",
      {
        indicator_id: indicatorId,
        tahun: IkuManage.currentTahun,
        baseline: baseline,
        target: target,
      },
      function (response) {
        if (!response.success) {
          Swal.fire("Gagal", response.message, "error");
          return;
        }

        $input.removeClass("border-warning").addClass("border-success");
        setTimeout(() => $input.removeClass("border-success"), 1200);
      },
    );
  });
});

function loadIkuManageList() {
  $("#ikuManageTbody").html(
    '<tr><td colspan="8" class="text-center text-muted">Memuat data...</td></tr>',
  );

  $.getJSON(
    IkuManage.api,
    { action: "list_all", tahun: IkuManage.currentTahun },
    function (res) {
      if (!res.success) {
        $("#ikuManageTbody").html(
          `<tr><td colspan="8" class="text-center text-danger">${res.message}</td></tr>`,
        );
        return;
      }

      if (!res.data.length) {
        $("#ikuManageTbody").html(
          '<tr><td colspan="8" class="text-center text-muted">Belum ada Indikator.</td></tr>',
        );
        return;
      }

      $.getJSON(
        IkuManage.api,
        {
          action: "get_indicators",
          kategori: "wajib",
          tahun: IkuManage.currentTahun,
          triwulan: "TW1",
        },
        function () {
          renderIkuManageTable(res.data);
        },
      );
    },
  );
}

function renderIkuManageTable(indicators) {
  let html = "";

  indicators.forEach(function (ind) {
    const isChild = ind.parent_id !== null;
    const kategoriBadge = {
      wajib: "danger",
      pilihan: "warning",
      partisipatif: "secondary",
    };
    const directionIcon =
      ind.direction === "rendah"
        ? "bi-arrow-down text-danger"
        : "bi-arrow-up text-success";

    html += `
      <tr>
        <td>${isChild ? "&nbsp;&nbsp;↳ " : ""}${escapeHtml(ind.code || "")}</td>
        <td style="font-size:12px;">${escapeHtml(ind.name)}</td>
        <td class="text-center">${escapeHtml(ind.satuan || "-")}</td>
        <td class="text-center"><i class="bi ${directionIcon}"></i></td>
        <td class="text-center"><span class="badge bg-${kategoriBadge[ind.kategori] || "secondary"}">${escapeHtml(ind.kategori)}</span></td>
        <td>
          <input type="text" class="form-control form-control-sm iku-target-input" data-indicator-id="${ind.id}" data-field="baseline" value="${escapeAttr(ind.target_baseline || "")}">
        </td>
        <td>
          <input type="text" class="form-control form-control-sm iku-target-input" data-indicator-id="${ind.id}" data-field="target" value="${escapeAttr(ind.target_target || "")}">
        </td>
<td class="text-center">
          <button type="button" class="btn btn-outline-primary btn-sm btn-edit-iku-indicator"
            data-id="${ind.id}" data-code="${escapeAttr(ind.code || "")}" data-name="${escapeAttr(ind.name)}"
            data-satuan="${escapeAttr(ind.satuan || "")}" data-direction="${ind.direction}" data-kategori="${ind.kategori}"
            data-selected="${ind.is_selected}">
            <i class="bi bi-pencil"></i>
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm btn-assign-iku-unit"
            data-id="${ind.id}" data-name="${escapeAttr(ind.name)}" title="Penugasan Unit Kerja">
            <i class="bi bi-people"></i>
          </button>
        </td>
      </tr>
    `;
  });

  $("#ikuManageTbody").html(html);
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

function escapeAttr(text) {
  return (text || "").replace(/"/g, "&quot;");
}
