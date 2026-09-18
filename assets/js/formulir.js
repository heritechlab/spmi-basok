const Formulir = {
  api: SIQUA.BASE_URL + "formulir/api.php",
  table: null,
  modal: null,
  form: null,
  isEdit: false,
  currentId: null,
};

$(document).ready(function () {
  Formulir.modal = new bootstrap.Modal(
    document.getElementById("formulirModal"),
  );
  Formulir.form = $("#formulirForm");

  initializeTable();
  registerEvents();
});

function initializeTable() {
  Formulir.table = $("#tableFormulir").DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    ordering: false,
    pageLength: 10,

    ajax: {
      url: Formulir.api,
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
  Formulir.table.ajax.reload(null, false);
}

function resetForm() {
  document.getElementById("formulirForm").reset();
  $("#id").val("");
  $("#filePreview").empty();
  $("#fileLockedNotice").show();
  $("#fileUploadRow").hide();
  $("#rejectionNoticeBox").hide();
  $("#btnApproveFormulir, #btnRejectFormulir").hide();
  $("#formulirForm :input").prop("disabled", false);
  $("#btnSaveFormulir").show();

  [
    "perumusan",
    "pemeriksaan",
    "persetujuan",
    "penetapan",
    "pengendalian",
  ].forEach(function (key) {
    $("#" + key + "_ttd_preview").empty();
  });

  Formulir.isEdit = false;
  Formulir.currentId = null;
}

function registerEvents() {
  $("#filterStatus").on("change", function () {
    reloadTable();
  });

  $("#btnAddFormulir").on("click", function () {
    resetForm();
    $("#modalTitle").text("Ajukan Formulir");
    Formulir.modal.show();
  });

  $("#tableFormulir").on("click", ".btn-edit", function () {
    loadData($(this).data("id"));
  });

  $("#tableFormulir").on("click", ".btn-delete", function () {
    deleteData($(this).data("id"));
  });

  $("#tableFormulir").on("click", ".btn-approve", function () {
    approveFormulir($(this).data("id"));
  });

  $("#tableFormulir").on("click", ".btn-reject", function () {
    rejectFormulir($(this).data("id"));
  });

  Formulir.form.on("submit", function (e) {
    e.preventDefault();
    saveData();
  });

  $("#btnUploadFile").on("click", function () {
    uploadFile();
  });

  $("#formulirModal").on("hidden.bs.modal", function () {
    resetForm();
  });
}

function loadData(id) {
  $.getJSON(Formulir.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    const row = res.data;

    resetForm();

    $("#id").val(row.id);
    $("#document_number").val(row.document_number);
    $("#title").val(row.title);
    $("#description").val(row.description);
    $("#effective_date").val(
      row.effective_date ? row.effective_date.substring(0, 7) : "",
    );
    $("#revision").val(row.revision);
    $("#total_pages").val(row.total_pages);

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
    $("#perumusan_nama").val(row.perumusan_nama);
    $("#perumusan_jabatan").val(row.perumusan_jabatan);
    $("#pemeriksaan_nama").val(row.pemeriksaan_nama);
    $("#pemeriksaan_jabatan").val(row.pemeriksaan_jabatan);
    $("#persetujuan_nama").val(row.persetujuan_nama);
    $("#persetujuan_jabatan").val(row.persetujuan_jabatan);
    $("#penetapan_nama").val(row.penetapan_nama);
    $("#penetapan_jabatan").val(row.penetapan_jabatan);
    $("#pengendalian_nama").val(row.pengendalian_nama);
    $("#pengendalian_jabatan").val(row.pengendalian_jabatan);

    $("#fileLockedNotice").hide();
    $("#fileUploadRow").show();

    if (row.file_path) {
      $("#filePreview").html(
        `<a href="${SIQUA.BASE_URL}${row.file_path}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-arrow-down"></i> ${row.file_original_name || "Lihat File"}</a>`,
      );
    } else {
      $("#filePreview").html(
        '<span class="text-muted small">Belum ada file yang diunggah.</span>',
      );
    }

    if (row.status === "Disahkan") {
      $("#formulirForm :input").prop("disabled", true);
      $("#btnSaveFormulir").hide();
    } else {
      $("#formulirForm :input").prop("disabled", false);
      $("#btnSaveFormulir").show();
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
      typeof CAN_VERIFY_FORMULIR !== "undefined" &&
      CAN_VERIFY_FORMULIR &&
      row.status === "Menunggu Persetujuan"
    ) {
      $("#btnApproveFormulir, #btnRejectFormulir").show();
    } else {
      $("#btnApproveFormulir, #btnRejectFormulir").hide();
    }

    Formulir.isEdit = true;
    Formulir.currentId = row.id;

    $("#modalTitle").text("Edit Formulir");

    Formulir.modal.show();
  });
}

function saveData() {
  const formData = new FormData(document.getElementById("formulirForm"));
  const action = Formulir.isEdit ? "update" : "create";
  const idParam = Formulir.isEdit ? "&id=" + Formulir.currentId : "";

  $.ajax({
    url: Formulir.api + "?action=" + action + idParam,
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

      if (!Formulir.isEdit && response.data && response.data.id) {
        Formulir.isEdit = true;
        Formulir.currentId = response.data.id;
        $("#id").val(response.data.id);
        $("#fileLockedNotice").hide();
        $("#fileUploadRow").show();
        $("#filePreview").html(
          '<span class="text-muted small">Belum ada file yang diunggah.</span>',
        );
        $("#modalTitle").text("Edit Formulir");

        reloadTable();

        Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: "Formulir tersimpan. Anda bisa lanjut upload file sekarang.",
        });
        return;
      }

      Formulir.modal.hide();
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

function uploadFile() {
  const fileInput = document.getElementById("formulir_file_input");

  if (!fileInput.files.length) {
    Swal.fire("Gagal", "Pilih file terlebih dahulu.", "error");
    return;
  }

  const formData = new FormData();
  formData.append("id", Formulir.currentId);
  formData.append("formulir_file", fileInput.files[0]);

  Swal.fire({
    title: "Mengunggah...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: Formulir.api + "?action=upload_file",
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

      loadData(Formulir.currentId);

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
    title: "Hapus dokumen Formulir ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: Formulir.api + "?action=delete",
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

function approveFormulir(id) {
  Swal.fire({
    icon: "question",
    title: "Sahkan dokumen Formulir ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Sahkan",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: Formulir.api + "?action=approve",
      type: "POST",
      dataType: "json",
      data: { id: id },
      success: function (response) {
        if (response.success) {
          Formulir.modal.hide();
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

function rejectFormulir(id) {
  Swal.fire({
    icon: "warning",
    title: "Tolak dokumen Formulir ini?",
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
      url: Formulir.api + "?action=reject",
      type: "POST",
      dataType: "json",
      data: { id: id, rejection_note: result.value },
      success: function (response) {
        if (response.success) {
          Formulir.modal.hide();
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
