const Finding = {
  api: SIQUA.BASE_URL + "audit/findings/api.php",
  table: null,
};

$(document).ready(function () {
  initializeTable();
  registerEvents();
});

function initializeTable() {
  Finding.table = $("#tableFinding").DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    ordering: false,
    searching: true,
    pageLength: 10,
    lengthMenu: [
      [10, 25, 50, 100],
      [10, 25, 50, 100],
    ],

    ajax: {
      url: Finding.api,
      type: "GET",
      data: function (d) {
        d.action = "list";
        d.period_id = $("#filterPeriod").val();
        d.unit_id = $("#filterUnit").length ? $("#filterUnit").val() : "";
        d.status = $("#filterStatus").val();
      },
    },

    columnDefs: [
      {
        targets: [0, 3, 4, 5, 8, 9],
        className: "text-center",
        orderable: false,
        searchable: false,
      },
    ],

    language: {
      processing: "Memuat data...",
      search: "Cari :",
      lengthMenu: "Tampilkan _MENU_ data",
      info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
      infoEmpty: "Tidak ada data",
      zeroRecords: "Data tidak ditemukan",
      paginate: {
        first: "Awal",
        last: "Akhir",
        next: "\u203a",
        previous: "\u2039",
      },
    },
  });
}

function registerEvents() {
  $("#filterPeriod, #filterUnit, #filterStatus").on("change", function () {
    Finding.table.ajax.reload(null, false);
  });

  $("#tableFinding").on("click", ".btn-detail", function () {
    showDetail($(this).data("id"));
  });
}

function showDetail(id) {
  const modal = new bootstrap.Modal(
    document.getElementById("findingDetailModal"),
  );

  $("#findingDetailBody").html('<p class="text-muted">Memuat data...</p>');

  modal.show();

  $.getJSON(Finding.api, { action: "detail", id: id }, function (res) {
    if (!res.success) {
      $("#findingDetailBody").html(
        '<p class="text-danger">' + res.message + "</p>",
      );
      return;
    }

    const d = res.data;

    const statusBadgeMap = {
      Menyimpang: "danger",
      "Belum Mencapai": "warning",
      Mencapai: "success",
      Melampaui: "primary",
    };

    const badge = statusBadgeMap[d.audit_status] || "secondary";

    let html = `
      <div class="p-3 mb-3 rounded" style="background:#f7f5ff; border:1px solid #e6ddfb;">
        <small class="text-muted d-block">Standar</small>
        <strong>${d.standard_code} - ${d.standard_name}</strong>
        <small class="text-muted d-block mt-2">Butir / Pernyataan Standar</small>
        <div>${d.statement || "-"}</div>
        <div class="row mt-2">
          <div class="col-md-8">
            <small class="text-muted d-block">Indikator</small>
            <div>${d.item_code} - ${d.indicator}</div>
          </div>
          <div class="col-md-2">
            <small class="text-muted d-block">Target</small>
            <div>${d.target || "-"}</div>
          </div>
          <div class="col-md-2">
            <small class="text-muted d-block">Capaian</small>
            <div>${d.achievement || "-"}</div>
          </div>
        </div>
      </div>

      <div class="mb-2"><strong>Unit Kerja:</strong> ${d.auditee_name} | <strong>Periode:</strong> ${d.period_name || "-"} | <strong>No. Penugasan:</strong> ${d.assignment_number}</div>
      <div class="mb-3"><span class="badge bg-${badge}">${d.audit_status}</span></div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Hasil Audit Dokumen</label>
        <div class="border rounded p-2 bg-light">${d.document_audit_result ? d.document_audit_result.replace(/\n/g, "<br>") : "-"}</div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Hasil Audit Lapangan / Visitasi</label>
        <div class="border rounded p-2 bg-light">${d.field_audit_result ? d.field_audit_result.replace(/\n/g, "<br>") : "-"}</div>
      </div>
    `;

    if (d.root_cause) {
      html += `
        <div class="mb-3">
          <label class="form-label fw-semibold">Akar Masalah</label>
          <div class="border rounded p-2 bg-light">${d.root_cause.replace(/\n/g, "<br>")}</div>
        </div>
      `;
    }

    if (d.supporting_factor) {
      html += `
        <div class="mb-3">
          <label class="form-label fw-semibold">Faktor Pendukung</label>
          <div class="border rounded p-2 bg-light">${d.supporting_factor.replace(/\n/g, "<br>")}</div>
        </div>
      `;
    }

    if (d.recommendation) {
      html += `
        <div class="mb-3">
          <label class="form-label fw-semibold">Rekomendasi</label>
          <div class="border rounded p-2 bg-light">${d.recommendation.replace(/\n/g, "<br>")}</div>
        </div>
      `;
    }

    if (d.evidence) {
      html += `
        <div class="mb-3">
          <label class="form-label fw-semibold">Eviden</label>
          <div class="border rounded p-2 bg-light">${d.evidence.replace(/\n/g, "<br>")}</div>
        </div>
      `;
    }

    if (d.notes) {
      html += `
        <div class="mb-2">
          <label class="form-label fw-semibold">Catatan Tambahan</label>
          <div class="border rounded p-2 bg-light">${d.notes.replace(/\n/g, "<br>")}</div>
        </div>
      `;
    }

    $("#findingDetailBody").html(html);
  });
}
