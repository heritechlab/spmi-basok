const Akreditasi = {
  api: SIQUA.BASE_URL + "akreditasi/api.php",
  table: null,
  uploadModal: null,
};

$(document).ready(function () {
  Akreditasi.uploadModal = new bootstrap.Modal(
    document.getElementById("uploadAkreditasiModal"),
  );

  initializeTable();
  loadUnits();
  registerEvents();

  if (typeof AKREDITASI_AUTO_OPEN !== "undefined" && AKREDITASI_AUTO_OPEN) {
    const tingkat = AKREDITASI_AUTO_OPEN === "prodi" ? "Prodi" : "Institusi";
    openUploadModal(tingkat);
  }
});

function initializeTable() {
  Akreditasi.table = $("#tableAkreditasi").DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    ordering: false,
    pageLength: 10,

    ajax: {
      url: Akreditasi.api,
      type: "GET",
      data: function (d) {
        d.action = "list";
        d.tingkat = $("#filterTingkat").val();
        d.kriteria = $("#filterKriteria").val();
        d.unit_id = $("#filterUnit").val();
        d.academic_year = $("#filterAcademicYear").val();
      },
    },

    columnDefs: [
      {
        targets: [0, 1, 4, 5, 6, 7],
        className: "text-center",
        orderable: false,
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
  Akreditasi.table.ajax.reload(null, false);
}

function loadUnits() {
  $.getJSON(Akreditasi.api, { action: "units" }, function (res) {
    if (!res.success) return;
    res.data.forEach(function (row) {
      const option = `<option value="${row.id}">${row.code} - ${row.name}</option>`;
      $("#filterUnit").append(option);
      $("#unit_id").append(option);
    });
  });
}

function registerEvents() {
  $("#filterTingkat, #filterKriteria, #filterUnit").on("change", function () {
    reloadTable();
  });

  $("#filterAcademicYear").on("input", function () {
    reloadTable();
  });

  $("#cardProdi").on("click", function () {
    openUploadModal("Prodi");
  });

  $("#cardInstitusi").on("click", function () {
    openUploadModal("Institusi");
  });

  $("#uploadAkreditasiForm").on("submit", function (e) {
    e.preventDefault();
    uploadDokumen();
  });

  $("#tableAkreditasi").on("click", ".btn-delete", function () {
    deleteDokumen($(this).data("id"));
  });
}

function openUploadModal(tingkat) {
  document.getElementById("uploadAkreditasiForm").reset();

  $("#tingkat").val(tingkat);
  $("#uploadModalTitle").text(
    "Upload Dokumen " +
      (tingkat === "Prodi" ? "Akreditasi Prodi" : "Akreditasi Institusi"),
  );

  if (tingkat === "Prodi") {
    $("#unitFieldWrapper").show();
    $("#unit_id").prop("required", true);
  } else {
    $("#unitFieldWrapper").hide();
    $("#unit_id").prop("required", false);
  }

  Akreditasi.uploadModal.show();
}

function uploadDokumen() {
  const fileInput = document.getElementById("bukti_file_input");

  if (!fileInput.files.length) {
    Swal.fire("Gagal", "Pilih file terlebih dahulu.", "error");
    return;
  }

  const formData = new FormData(
    document.getElementById("uploadAkreditasiForm"),
  );
  formData.append("bukti_file", fileInput.files[0]);

  Swal.fire({
    title: "Mengunggah...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: Akreditasi.api + "?action=upload",
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

      Akreditasi.uploadModal.hide();
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

function deleteDokumen(id) {
  Swal.fire({
    icon: "warning",
    title: "Hapus dokumen ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: Akreditasi.api + "?action=delete",
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
