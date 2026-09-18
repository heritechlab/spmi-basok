const RtmImpl = {
  api: SIQUA.BASE_URL + "rtl/implementation/api.php",
  table: null,
  modal: null,
  currentId: null,
};

$(document).ready(function () {
  RtmImpl.modal = new bootstrap.Modal(document.getElementById("implModal"));

  initializeTable();
  registerEvents();
  loadStatusChart();
});
function loadStatusChart() {
  $.getJSON(RtmImpl.api, { action: "statistics" }, function (res) {
    if (!res.success) return;

    const d = res.data;

    new Chart(document.getElementById("chartImplStatus"), {
      type: "doughnut",
      data: {
        labels: ["Belum", "Proses", "Selesai"],
        datasets: [
          {
            data: [d.belum || 0, d.proses || 0, d.selesai || 0],
            backgroundColor: ["#9ca3af", "#f59e0b", "#22c55e"],
          },
        ],
      },
      options: {
        responsive: false,
        plugins: {
          legend: {
            position: "bottom",
            labels: { boxWidth: 12, font: { size: 11 } },
          },
        },
      },
    });
  });
}

function initializeTable() {
  RtmImpl.table = $("#tableImplementation").DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    ordering: false,
    searching: false,
    pageLength: 10,
    lengthMenu: [
      [10, 25, 50, 100],
      [10, 25, 50, 100],
    ],

    ajax: {
      url: RtmImpl.api,
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
        targets: [2, 3, 4],
        className: "text-center",
        orderable: false,
        searchable: false,
      },
    ],

    language: {
      processing: "Memuat data...",
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

function reloadTable() {
  RtmImpl.table.ajax.reload(null, false);
}

function registerEvents() {
  $("#filterPeriod, #filterUnit, #filterStatus").on("change", function () {
    reloadTable();
  });

  $("#tableImplementation").on(
    "click",
    ".btn-detail-implementation",
    function () {
      openDetail($(this).data("id"));
    },
  );

  $("#btnSaveImplementation").on("click", function () {
    saveImplementation();
  });

  $("#evidenceFile").on("change", function () {
    if (this.files.length > 0) {
      uploadEvidence(this.files[0], null);
    }
  });

  $("#btnAddEvidenceLink").on("click", function () {
    const linkVal = $("#evidenceLink").val().trim();

    if (!linkVal) {
      Swal.fire("Gagal", "Isi Link terlebih dahulu.", "error");
      return;
    }

    uploadEvidence(null, linkVal);
  });

  $(document).on("click", ".btn-delete-evidence", function () {
    deleteEvidence($(this).data("id"));
  });

  $("#btnVerify").on("click", function () {
    saveVerification();
  });
}

function openDetail(id) {
  RtmImpl.currentId = id;

  $("#impl_finding_info").html('<p class="text-muted mb-0">Memuat data...</p>');
  $("#evidenceList").html("");

  RtmImpl.modal.show();

  $.getJSON(RtmImpl.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      $("#impl_finding_info").html(
        '<p class="text-danger mb-0">' + res.message + "</p>",
      );
      return;
    }

    const d = res.data;

    $("#impl_id").val(d.id);

    $("#impl_finding_info").html(`
      <small class="text-muted d-block">Standar & Indikator</small>
      <strong>${d.standard_code} - ${d.item_code} - ${d.indicator}</strong>
      <div class="text-muted small mt-1">Unit: ${d.auditee_name || "-"} | Target: ${d.target || "-"} | Rapat: ${d.meeting_number}</div>
      <div class="mt-1"><span class="badge bg-dark">${d.importance}</span> <span class="badge bg-info text-dark">${d.urgency}</span></div>
    `);

    $("#impl_activity").text(d.activity || "-");

    if (CAN_MANAGE_RTM) {
      $("#implAuditeeSection").show();
      $("#progress_note").val(d.progress_note || "");
      $("#impl_status").val(d.status || "Belum");
    } else {
      $("#implAuditeeSection").hide();
    }

    const verifBadge = {
      "Belum Diverifikasi": "secondary",
      Sesuai: "success",
      "Perlu Revisi": "danger",
    };
    const currentVerif = d.verification_status || "Belum Diverifikasi";

    $("#impl_current_verification").html(
      `Status: <span class="badge bg-${verifBadge[currentVerif] || "secondary"}">${currentVerif}</span>` +
        (d.verification_note
          ? `<div class="small text-muted mt-2">Catatan Verifikator: ${d.verification_note}</div>`
          : '<div class="small text-muted mt-2">Belum ada catatan.</div>'),
    );

    if (CAN_VERIFY_RTL) {
      $("#implVerifierSection").show();
      $("#verification_status").val("");
      $("#verification_note").val("");
    } else {
      $("#implVerifierSection").hide();
    }

    renderEvidences(d.evidences || []);
  });
}

function renderEvidences(evidences) {
  if (!evidences.length) {
    $("#evidenceList").html(
      '<p class="text-muted small mb-0">Belum ada bukti diupload.</p>',
    );
    return;
  }

  let html = '<ul class="list-group">';

  evidences.forEach(function (ev) {
    const deleteBtn = CAN_MANAGE_RTM
      ? `<button class="btn btn-sm btn-outline-danger btn-delete-evidence" data-id="${ev.id}"><i class="bi bi-trash"></i></button>`
      : "";

    if (ev.link_url) {
      html += `<li class="list-group-item d-flex justify-content-between align-items-center">
        <a href="${ev.link_url}" target="_blank"><i class="bi bi-link-45deg"></i> ${ev.link_url}</a>
        ${deleteBtn}
      </li>`;
    } else {
      html += `<li class="list-group-item d-flex justify-content-between align-items-center">
        <a href="${SIQUA.BASE_URL}${ev.document_file}" target="_blank">${ev.document_original_name}</a>
        ${deleteBtn}
      </li>`;
    }
  });

  html += "</ul>";

  $("#evidenceList").html(html);
}

function saveImplementation() {
  const formData = {
    id: RtmImpl.currentId,
    progress_note: $("#progress_note").val(),
    status: $("#impl_status").val(),
  };

  Swal.fire({
    title: "Menyimpan...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: RtmImpl.api + "?action=save_implementation",
    type: "POST",
    dataType: "json",
    data: formData,
    success: function (response) {
      Swal.close();

      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      reloadTable();
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

function uploadEvidence(file, linkUrl) {
  const formData = new FormData();
  formData.append("action_plan_id", RtmImpl.currentId);

  if (file) {
    formData.append("evidence", file);
  }

  if (linkUrl) {
    formData.append("link_url", linkUrl);
  }

  Swal.fire({
    title: "Mengunggah...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: RtmImpl.api + "?action=upload_evidence",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (response) {
      Swal.close();

      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      $("#evidenceFile").val("");
      $("#evidenceLink").val("");
      openDetail(RtmImpl.currentId);

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

function deleteEvidence(evidenceId) {
  Swal.fire({
    icon: "warning",
    title: "Hapus bukti ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: RtmImpl.api + "?action=delete_evidence",
      type: "POST",
      data: { evidence_id: evidenceId },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          openDetail(RtmImpl.currentId);
        } else {
          Swal.fire("Gagal", response.message, "error");
        }
      },
    });
  });
}

function saveVerification() {
  const verifStatus = $("#verification_status").val();

  if (!verifStatus) {
    Swal.fire("Gagal", "Pilih hasil verifikasi terlebih dahulu.", "error");
    return;
  }

  Swal.fire({
    title: "Menyimpan...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: RtmImpl.api + "?action=verify",
    type: "POST",
    dataType: "json",
    data: {
      id: RtmImpl.currentId,
      verification_status: verifStatus,
      verification_note: $("#verification_note").val(),
    },
    success: function (response) {
      Swal.close();

      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      reloadTable();
      openDetail(RtmImpl.currentId);

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
