$(document).ready(function () {
  loadCkMappingTable();

  $(document).on("change", ".ck-mapping-select", function () {
    const $select = $(this);
    const $row = $select.closest("tr");
    const standardId = $select.data("standard-id");
    const prodiCriteriaId = $row.find(".ck-prodi-select").val();
    const institutionCriteriaId = $row.find(".ck-institution-select").val();

    $.post(
      SIQUA.BASE_URL + "capaian_kriteria/manage/api.php?action=save_mapping",
      {
        standard_id: standardId,
        prodi_criteria_id: prodiCriteriaId,
        institution_criteria_id: institutionCriteriaId,
      },
      function (response) {
        if (!response.success) {
          Swal.fire("Gagal", response.message, "error");
          return;
        }

        $select.removeClass("border-warning").addClass("border-success");
        setTimeout(() => $select.removeClass("border-success"), 1000);
      },
    ).fail(function () {
      Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
    });
  });
});

function loadCkMappingTable() {
  $.getJSON(
    SIQUA.BASE_URL + "capaian_kriteria/manage/api.php",
    { action: "standards" },
    function (res) {
      if (!res.success) {
        $("#ckMappingTbody").html(
          `<tr><td colspan="4" class="text-center text-danger">${res.message}</td></tr>`,
        );
        return;
      }

      renderCkMappingTable(res.data);
    },
  );
}

function renderCkMappingTable(standards) {
  let html = "";

  standards.forEach(function (s) {
    let prodiOpts = '<option value="0">-- Belum Dipetakan --</option>';
    CK_PRODI_CRITERIA.forEach(function (c) {
      const selected = c.id == s.prodi_criteria_id ? "selected" : "";
      prodiOpts += `<option value="${c.id}" ${selected}>${escapeHtml(c.name)}</option>`;
    });

    let instOpts = '<option value="0">-- Belum Dipetakan --</option>';
    CK_INST_CRITERIA.forEach(function (c) {
      const selected = c.id == s.institution_criteria_id ? "selected" : "";
      const prefix = c.parent_id ? "&nbsp;&nbsp;↳ " : "";
      instOpts += `<option value="${c.id}" ${selected}>${prefix}${escapeHtml(c.name)}</option>`;
    });

    html += `
      <tr>
        <td>${escapeHtml(s.code || "")}</td>
        <td style="font-size:12px;">${escapeHtml(s.name)}</td>
        <td>
          <select class="form-select form-select-sm ck-mapping-select ck-prodi-select" data-standard-id="${s.id}">
            ${prodiOpts}
          </select>
        </td>
        <td>
          <select class="form-select form-select-sm ck-mapping-select ck-institution-select" data-standard-id="${s.id}">
            ${instOpts}
          </select>
        </td>
      </tr>
    `;
  });

  $("#ckMappingTbody").html(html);
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}
