const AkreditasiInstitusi = {
  api: SIQUA.BASE_URL + "akreditasi_institusi/api.php",
  table: null,
  uploadModal: null,
};

$(document).ready(function () {
  AkreditasiInstitusi.uploadModal = new bootstrap.Modal(
    document.getElementById("uploadModal"),
  );

  initializeTable();
  registerEvents();
});

function initializeTable() {
  AkreditasiInstitusi.table = $("#tableAkreditasiInstitusi").DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    ordering: false,
    pageLength: 10,

    ajax: {
      url: AkreditasiInstitusi.api,
      type: "GET",
      data: function (d) {
        d.action = "list";
        d.kriteria = $("#filterKriteria").val();
        d.academic_year = $("#filterAcademicYear").val();
      },
    },

    columnDefs: [
      { targets: [0, 2, 3, 4, 5], className: "text-center", orderable: false },
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
  AkreditasiInstitusi.table.ajax.reload(null, false);
}

function registerEvents() {
  $("#filterKriteria").on("change", function () {
    reloadTable();
  });

  $("#filterAcademicYear").on("input", function () {
    reloadTable();
  });

  $("#btnUpload").on("click", function () {
    document.getElementById("uploadForm").reset();
    AkreditasiInstitusi.uploadModal.show();
  });

  $("#uploadForm").on("submit", function (e) {
    e.preventDefault();
    uploadDokumen();
  });

  $("#tableAkreditasiInstitusi").on("click", ".btn-delete", function () {
    deleteDokumen($(this).data("id"));
  });
}

function uploadDokumen() {
  const fileInput = document.getElementById("bukti_file_input");
  const linkUrl = $("#link_url").val().trim();

  if (!fileInput.files.length && !linkUrl) {
    Swal.fire("Gagal", "Isi salah satu: Upload File atau Link.", "error");
    return;
  }

  const formData = new FormData(document.getElementById("uploadForm"));

  if (fileInput.files.length) {
    formData.append("bukti_file", fileInput.files[0]);
  }

  Swal.fire({
    title: "Mengunggah...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: AkreditasiInstitusi.api + "?action=upload",
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

      AkreditasiInstitusi.uploadModal.hide();
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
      url: AkreditasiInstitusi.api + "?action=delete",
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
