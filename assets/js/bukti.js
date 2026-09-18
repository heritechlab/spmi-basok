const Bukti = {
  api: SIQUA.BASE_URL + "bukti/api.php",
  table: null,
};

$(document).ready(function () {
  initializeTable();
  loadUnits();
  registerEvents();
});

function initializeTable() {
  Bukti.table = $("#tableBukti").DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    ordering: false,
    pageLength: 10,

    ajax: {
      url: Bukti.api,
      type: "GET",
      data: function (d) {
        d.action = "list";
        d.category = $("#filterCategory").val();
        d.unit_id = $("#filterUnit").length ? $("#filterUnit").val() : "";
        d.semester = $("#filterSemester").val();
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
  Bukti.table.ajax.reload(null, false);
}

function loadUnits() {
  $.getJSON(Bukti.api, { action: "units" }, function (res) {
    if (!res.success) return;
    res.data.forEach(function (row) {
      const option = `<option value="${row.id}">${row.code} - ${row.name}</option>`;
      $("#filterUnit").append(option);
      $("#unit_id").append(option);
    });
  });
}

function registerEvents() {
  $("#filterCategory, #filterUnit, #filterSemester").on("change", function () {
    reloadTable();
  });

  $("#filterAcademicYear").on("input", function () {
    reloadTable();
  });

  $("#buktiForm").on("submit", function (e) {
    e.preventDefault();
    uploadBukti();
  });

  $("#tableBukti").on("click", ".btn-delete", function () {
    deleteBukti($(this).data("id"));
  });
}

function uploadBukti() {
  const fileInput = document.getElementById("bukti_file_input");

  if (!fileInput.files.length) {
    Swal.fire("Gagal", "Pilih file terlebih dahulu.", "error");
    return;
  }

  const formData = new FormData();
  formData.append("category", $("#category").val());
  formData.append("document_name", $("#document_name").val());
  formData.append("description", $("#description").val());
  formData.append("semester", $("#semester").val());
  formData.append("academic_year", $("#academic_year").val());
  if ($("#unit_id").length) {
    formData.append("unit_id", $("#unit_id").val());
  }
  formData.append("bukti_file", fileInput.files[0]);

  Swal.fire({
    title: "Mengunggah...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: Bukti.api + "?action=upload",
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

      document.getElementById("buktiForm").reset();
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

function deleteBukti(id) {
  Swal.fire({
    icon: "warning",
    title: "Hapus dokumen ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: Bukti.api + "?action=delete",
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
