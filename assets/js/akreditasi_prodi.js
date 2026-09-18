const AkreditasiProdi = {
  api: SIQUA.BASE_URL + "akreditasi_prodi/api.php",
  table: null,
  uploadModal: null,
};

$(document).ready(function () {
  AkreditasiProdi.uploadModal = new bootstrap.Modal(
    document.getElementById("uploadModal"),
  );

  initializeTable();
  loadUnits();
  registerEvents();
});

function initializeTable() {
  AkreditasiProdi.table = $("#tableAkreditasiProdi").DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    ordering: false,
    pageLength: 10,

    ajax: {
      url: AkreditasiProdi.api,
      type: "GET",
      data: function (d) {
        d.action = "list";
        d.kriteria = $("#filterKriteria").val();
        d.unit_id = $("#filterUnit").val();
        d.academic_year = $("#filterAcademicYear").val();
      },
    },

    columnDefs: [
      { targets: [0, 3, 4, 5, 6], className: "text-center", orderable: false },
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
  AkreditasiProdi.table.ajax.reload(null, false);
}

function loadUnits() {
  $.getJSON(AkreditasiProdi.api, { action: "units" }, function (res) {
    if (!res.success) return;
    res.data.forEach(function (row) {
      const option = `<option value="${row.id}">${row.code} - ${row.name}</option>`;
      $("#filterUnit").append(option);
      $("#unit_id").append(option);
    });
  });
}

function registerEvents() {
  $("#filterKriteria, #filterUnit").on("change", function () {
    reloadTable();
  });

  $("#filterAcademicYear").on("input", function () {
    reloadTable();
  });

  $("#btnUpload").on("click", function () {
    document.getElementById("uploadForm").reset();
    AkreditasiProdi.uploadModal.show();
  });

  $("#uploadForm").on("submit", function (e) {
    e.preventDefault();
    uploadDokumen();
  });

  $("#tableAkreditasiProdi").on("click", ".btn-delete", function () {
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
    url: AkreditasiProdi.api + "?action=upload",
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

      AkreditasiProdi.uploadModal.hide();
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
      url: AkreditasiProdi.api + "?action=delete",
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
