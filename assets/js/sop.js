const Sop = {
  api: SIQUA.BASE_URL + "sop/api.php",
  table: null,
  modal: null,
  form: null,
  isEdit: false,
  currentId: null,
};

$(document).ready(function () {
  Sop.modal = new bootstrap.Modal(document.getElementById("sopModal"));
  Sop.form = $("#sopForm");

  initializeTable();
  loadStandards();
  registerEvents();
});

function initializeTable() {
  Sop.table = $("#tableSop").DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    ordering: false,
    pageLength: 10,

    ajax: {
      url: Sop.api,
      type: "GET",
      data: function (d) {
        d.action = "list";
        d.status = $("#filterStatus").val();
      },
    },

    columnDefs: [
      { targets: [2, 3, 4, 5], className: "text-center", orderable: false },
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

function reloadTable() {
  Sop.table.ajax.reload(null, false);
}

function loadStandards() {
  $.getJSON(Sop.api, { action: "standards" }, function (res) {
    if (!res.success) return;
    res.data.forEach(function (row) {
      $("#standard_id").append(
        `<option value="${row.id}">${row.code} - ${row.name}</option>`,
      );
    });
  });
}

function resetForm() {
  document.getElementById("sopForm").reset();
  $("#id").val("");
  $("#baganAlirLockedNotice").show();
  $("#baganAlirUploadRow").hide();
  $("#baganAlirPreview").empty();
  $("#rejectionNoticeBox").hide();
  $("#btnApproveSop, #btnRejectSop").hide();
  $("#sopForm :input").prop("disabled", false);
  $("#btnSaveSop").show();

  [
    "perumusan",
    "pemeriksaan",
    "persetujuan",
    "penetapan",
    "pengendalian",
  ].forEach(function (key) {
    $("#" + key + "_ttd_preview").empty();
  });

  Sop.isEdit = false;
  Sop.currentId = null;
}

function registerEvents() {
  $("#filterStatus").on("change", function () {
    reloadTable();
  });

  $("#btnAddSop").on("click", function () {
    resetForm();
    $("#modalTitle").text("Ajukan SOP");
    Sop.modal.show();
  });

  $("#tableSop").on("click", ".btn-approve", function () {
    approveSop($(this).data("id"));
  });

  $("#tableSop").on("click", ".btn-reject", function () {
    rejectSop($(this).data("id"));
  });

  $("#tableSop").on("click", ".btn-edit", function () {
    loadData($(this).data("id"));
  });

  $("#tableSop").on("click", ".btn-delete", function () {
    deleteData($(this).data("id"));
  });

  Sop.form.on("submit", function (e) {
    e.preventDefault();
    saveData();
  });

  $("#btnUploadBaganAlir").on("click", function () {
    uploadBaganAlir();
  });

  $("#sopModal").on("hidden.bs.modal", function () {
    resetForm();
  });
}

function loadData(id) {
  $.getJSON(Sop.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    const row = res.data;

    resetForm();

    $("#id").val(row.id);
    $("#document_number").val(row.document_number);
    $("#title").val(row.title);
    $("#standard_id").val(row.standard_id);
    $("#effective_date").val(
      row.effective_date ? row.effective_date.substring(0, 7) : "",
    );
    $("#revision").val(row.revision);
    $("#total_pages").val(row.total_pages);
    $("#perumusan_nama").val(row.perumusan_nama);
    $("#perumusan_jabatan").val(row.perumusan_jabatan);
    $("#pemeriksaan_nama").val(row.pemeriksaan_nama);
    $("#pemeriksaan_jabatan").val(row.pemeriksaan_jabatan);
    $("#persetujuan_nama").val(row.persetujuan_nama);
    $("#persetujuan_jabatan").val(row.persetujuan_jabatan);
    $("#penetapan_nama").val(row.penetapan_nama);
    $("#penetapan_jabatan").val(row.penetapan_jabatan);
    const processKeys = [
      "perumusan",
      "pemeriksaan",
      "persetujuan",
      "penetapan",
      "pengendalian",
    ];

    processKeys.forEach(function (key) {
      $("#" + key + "_nama").val(row[key + "_nama"]);
      $("#" + key + "_jabatan").val(row[key + "_jabatan"]);
      $("#" + key + "_tanggal").val(row[key + "_tanggal"]);

      const ttdPath = row[key + "_ttd"];
      if (ttdPath) {
        $("#" + key + "_ttd_preview").html(
          '<img src="' +
            SIQUA.BASE_URL +
            ttdPath +
            '" style="max-height:50px; border:1px solid #ddd; border-radius:4px; padding:2px;">',
        );
      } else {
        $("#" + key + "_ttd_preview").html(
          '<span class="text-muted small">Belum ada TTD.</span>',
        );
      }
    });

    if (row.status === "Disahkan") {
      $("#sopForm :input").prop("disabled", true);
      $("#btnSaveSop").hide();
    } else {
      $("#sopForm :input").prop("disabled", false);
      $("#btnSaveSop").show();
    }

    if (row.status === "Ditolak" && row.rejection_note) {
      $("#rejectionNoticeBox")
        .html(
          '<i class="bi bi-exclamation-triangle"></i> <strong>Ditolak:</strong> ' +
            row.rejection_note,
        )
        .show();
    } else {
      $("#rejectionNoticeBox").hide();
    }

    if (
      typeof CAN_VERIFY_SOP !== "undefined" &&
      CAN_VERIFY_SOP &&
      row.status === "Menunggu Persetujuan"
    ) {
      $("#btnApproveSop, #btnRejectSop").show();
    } else {
      $("#btnApproveSop, #btnRejectSop").hide();
    }
    $("#tujuan_prosedur").val(row.tujuan_prosedur);
    $("#ruang_lingkup").val(row.ruang_lingkup);
    $("#definisi").val(row.definisi);
    $("#prosedur").val(row.prosedur);
    $("#pihak_pelaksana").val(row.pihak_pelaksana);
    $("#catatan").val(row.catatan);
    $("#status").val(row.status);

    $("#baganAlirLockedNotice").hide();
    $("#baganAlirUploadRow").show();

    if (row.bagan_alir_file) {
      $("#baganAlirPreview").html(
        `<img src="${SIQUA.BASE_URL}${row.bagan_alir_file}" style="max-width:100%; max-height:200px; border:1px solid #ddd; border-radius:6px;">`,
      );
    } else {
      $("#baganAlirPreview").html(
        '<span class="text-muted small">Belum ada Bagan Alir yang diunggah.</span>',
      );
    }

    Sop.isEdit = true;
    Sop.currentId = row.id;

    $("#modalTitle").text("Edit SOP");

    Sop.modal.show();
  });
}

function saveData() {
  const formData = new FormData(document.getElementById("sopForm"));
  const action = Sop.isEdit ? "update" : "create";
  const idParam = Sop.isEdit ? "&id=" + Sop.currentId : "";

  $.ajax({
    url: Sop.api + "?action=" + action + idParam,
    type: "POST",
    dataType: "json",
    data: formData,
    processData: false,
    contentType: false,
    beforeSend: function () {
      Swal.fire({
        title: "Menyimpan...",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading(),
      });
    },
    success: function (response) {
      Swal.close();

      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      if (!Sop.isEdit && response.data && response.data.id) {
        Sop.isEdit = true;
        Sop.currentId = response.data.id;
        $("#id").val(response.data.id);
        $("#baganAlirLockedNotice").hide();
        $("#baganAlirUploadRow").show();
        $("#baganAlirPreview").html(
          '<span class="text-muted small">Belum ada Bagan Alir yang diunggah.</span>',
        );
        $("#modalTitle").text("Edit SOP");

        reloadTable();

        Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: "Dokumen SOP tersimpan. Anda bisa lanjut upload Bagan Alir sekarang.",
        });
        return;
      }

      Sop.modal.hide();
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

function uploadBaganAlir() {
  const fileInput = document.getElementById("bagan_alir_file_input");

  if (!fileInput.files.length) {
    Swal.fire("Gagal", "Pilih file terlebih dahulu.", "error");
    return;
  }

  const formData = new FormData();
  formData.append("id", Sop.currentId);
  formData.append("bagan_alir", fileInput.files[0]);

  Swal.fire({
    title: "Mengunggah...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: Sop.api + "?action=upload_bagan_alir",
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

      loadData(Sop.currentId);

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

function deleteData(id) {
  Swal.fire({
    icon: "warning",
    title: "Hapus dokumen SOP ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: Sop.api + "?action=delete",
      type: "POST",
      dataType: "json",
      data: { id: id },
      success: function (response) {
        if (response.success) {
          reloadTable();
        } else {
          Swal.fire("Gagal", response.message, "error");
        }
      },
    });
  });
}

function approveSop(id) {
  Swal.fire({
    icon: "question",
    title: "Sahkan dokumen SOP ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Sahkan",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: Sop.api + "?action=approve",
      type: "POST",
      dataType: "json",
      data: { id: id },
      success: function (response) {
        if (response.success) {
          Sop.modal.hide();
          reloadTable();
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

function rejectSop(id) {
  Swal.fire({
    icon: "warning",
    title: "Tolak dokumen SOP ini?",
    input: "textarea",
    inputLabel: "Catatan Penolakan",
    inputPlaceholder: "Jelaskan alasan penolakan / revisi yang diperlukan...",
    showCancelButton: true,
    confirmButtonText: "Ya, Tolak",
    cancelButtonText: "Batal",
    inputValidator: function (value) {
      if (!value) {
        return "Catatan penolakan wajib diisi.";
      }
    },
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: Sop.api + "?action=reject",
      type: "POST",
      dataType: "json",
      data: { id: id, rejection_note: result.value },
      success: function (response) {
        if (response.success) {
          Sop.modal.hide();
          reloadTable();
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
