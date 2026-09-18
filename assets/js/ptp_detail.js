const PtpDetail = {
  api: SIQUA.BASE_URL + "ptp/api.php",
  itemModal: null,
  itemForm: null,
};

$(document).ready(function () {
  PtpDetail.itemModal = new bootstrap.Modal(
    document.getElementById("itemModal"),
  );
  PtpDetail.itemForm = $("#itemForm");

  if (CAN_MANAGE_PTP) {
    loadEligibleItems();
  }

  loadItems();
  registerEvents();
});

function registerEvents() {
  $(document).on("click", ".btn-propose", function () {
    openItemModal($(this));
  });

  PtpDetail.itemForm.on("submit", function (e) {
    e.preventDefault();
    saveItem();
  });

  $(document).on("click", ".btn-delete-item", function () {
    deleteItem($(this).data("id"));
  });

  $(document).on("click", ".btn-apply-item", function () {
    applyItem($(this).data("id"));
  });
}

/* ===================== INDIKATOR MENCAPAI/MELAMPAUI ===================== */

function loadEligibleItems() {
  $.getJSON(
    PtpDetail.api,
    {
      action: "eligible_items",
      unit_id: PTP_UNIT_ID,
      period_id: PTP_PERIOD_ID,
    },
    function (res) {
      if (!res.success || !res.data.length) {
        $("#eligibleTableBody").html(
          '<tr><td colspan="6" class="text-muted">Tidak ada indikator Mencapai/Melampaui untuk unit ini.</td></tr>',
        );
        return;
      }

      const badge = { Mencapai: "success", Melampaui: "primary" };

      let html = "";

      res.data.forEach(function (row) {
        html += `<tr>
        <td>${row.item_code}</td>
        <td>${row.standard_code} - ${row.standard_name}</td>
        <td>${row.indicator}</td>
        <td>${row.target || "-"}</td>
        <td><span class="badge bg-${badge[row.audit_status] || "secondary"}">${row.audit_status}</span></td>
        <td>
          <button type="button" class="btn btn-primary btn-sm btn-propose"
            data-indicator-id="${row.audit_indicator_id}"
            data-checklist-result-id="${row.checklist_result_id}"
            data-item-code="${row.item_code}"
            data-standard="${row.standard_code} - ${row.standard_name}"
            data-indicator="${row.indicator}"
            data-statement="${row.statement || ""}"
            data-target="${row.target || ""}">
            <i class="bi bi-arrow-up-circle"></i> Usulkan
          </button>
        </td>
      </tr>`;
      });

      $("#eligibleTableBody").html(html);
    },
  );
}

function openItemModal($btn) {
  const indicatorId = $btn.data("indicator-id");
  const checklistResultId = $btn.data("checklist-result-id");
  const itemCode = $btn.data("item-code");
  const standard = $btn.data("standard");
  const indicator = $btn.data("indicator");
  const statement = $btn.data("statement");
  const target = $btn.data("target");

  document.getElementById("itemForm").reset();

  $("#audit_indicator_id").val(indicatorId);
  $("#checklist_result_id").val(checklistResultId);
  $("#old_indicator").val(indicator);
  $("#old_statement").val(statement);
  $("#old_target").val(target);

  $("#item_current_info").html(`
    <strong>${itemCode}</strong> - ${standard}<br>
    <strong>Pernyataan:</strong> ${statement || "-"}<br>
    <strong>Indikator:</strong> ${indicator}<br>
    <strong>Target:</strong> ${target || "-"}
  `);

  $("#new_statement").val("");
  $("#new_indicator").val("");
  $("#new_target").val("");

  PtpDetail.itemModal.show();
}

function saveItem() {
  const formData = PtpDetail.itemForm.serialize();

  Swal.fire({
    title: "Menyimpan...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: PtpDetail.api + "?action=save_item",
    type: "POST",
    dataType: "json",
    data: formData,
    success: function (response) {
      Swal.close();

      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      PtpDetail.itemModal.hide();
      loadItems();

      Swal.fire({
        icon: "success",
        title: "Berhasil",
        text: response.message,
        timer: 1500,
        showConfirmButton: false,
      });
    },
    error: function () {
      Swal.close();
      Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
    },
  });
}

/* ===================== DAFTAR USULAN ===================== */

function loadItems() {
  $.getJSON(
    PtpDetail.api,
    { action: "get", id: PTP_MEETING_ID },
    function (res) {
      if (!res.success) return;

      const items = res.data.items || [];

      if (!items.length) {
        $("#itemsTableBody").html(
          '<tr><td colspan="5" class="text-muted">Belum ada usulan peningkatan.</td></tr>',
        );
        return;
      }

      const statusBadge = { Diusulkan: "warning", Ditingkatkan: "success" };

      let html = "";

      items.forEach(function (item) {
        let before = "";
        let after = "";

        if (item.new_statement) {
          before += `<div><small class="text-muted">Pernyataan:</small> ${item.old_statement || "-"}</div>`;
          after += `<div><small class="text-muted">Pernyataan:</small> ${item.new_statement}</div>`;
        }
        if (item.new_indicator) {
          before += `<div><small class="text-muted">Indikator:</small> ${item.old_indicator || "-"}</div>`;
          after += `<div><small class="text-muted">Indikator:</small> ${item.new_indicator}</div>`;
        }
        if (item.new_target) {
          before += `<div><small class="text-muted">Target:</small> ${item.old_target || "-"}</div>`;
          after += `<div><small class="text-muted">Target:</small> ${item.new_target}</div>`;
        }

        let aksi = "";

        if (item.status === "Diusulkan") {
          if (CAN_APPLY_PTP) {
            aksi += `<button class="btn btn-success btn-sm btn-apply-item" data-id="${item.id}" title="Terapkan ke Master Indikator"><i class="bi bi-check-circle"></i></button> `;
          }
          if (CAN_MANAGE_PTP) {
            aksi += `<button class="btn btn-danger btn-sm btn-delete-item" data-id="${item.id}"><i class="bi bi-trash"></i></button>`;
          }
        }

        html += `<tr>
        <td>${item.standard_code || ""}</td>
        <td>${before}</td>
        <td>${after}</td>
        <td><span class="badge bg-${statusBadge[item.status] || "secondary"}">${item.status}</span></td>
        <td>${aksi}</td>
      </tr>`;
      });

      $("#itemsTableBody").html(html);
    },
  );
}

function deleteItem(id) {
  Swal.fire({
    icon: "warning",
    title: "Hapus usulan ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: PtpDetail.api + "?action=delete_item",
      type: "POST",
      data: { id: id },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          loadItems();
        } else {
          Swal.fire("Gagal", response.message, "error");
        }
      },
    });
  });
}

function applyItem(id) {
  Swal.fire({
    icon: "question",
    title: "Tingkatkan Master Indikator dengan perubahan ini?",
    text: "Perubahan akan berlaku untuk seluruh unit yang menggunakan indikator ini.",
    showCancelButton: true,
    confirmButtonText: "Ya, Tingkatkan",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: PtpDetail.api + "?action=apply_item",
      type: "POST",
      data: { id: id },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          loadItems();
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: response.message,
          });
        } else {
          Swal.fire("Gagal", response.message, "error");
        }
      },
    });
  });
}
