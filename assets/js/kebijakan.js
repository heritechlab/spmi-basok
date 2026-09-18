const Kebijakan = {
  api: SIQUA.BASE_URL + "kebijakan/api.php",
  uploadModal: null,
};

$(document).ready(function () {
  Kebijakan.uploadModal = new bootstrap.Modal(
    document.getElementById("uploadModal"),
  );

  loadList("kebijakan_mutu", "#listKebijakanMutu");
  loadList("peraturan_mutu", "#listPeraturanMutu");

  $("#uploadForm").on("submit", function (e) {
    e.preventDefault();
    submitUpload();
  });
});

Kebijakan.openUpload = function (category) {
  $("#uploadForm")[0].reset();
  $("#upload_category").val(category);
  $("#uploadModalTitle").text(
    category === "kebijakan_mutu"
      ? "Unggah Kebijakan Mutu"
      : "Unggah Peraturan Mutu",
  );
  Kebijakan.uploadModal.show();
};

function loadList(category, targetSelector) {
  $(targetSelector).html(
    '<p class="mb-0" style="color:rgba(255,255,255,0.75); font-size:12.5px;">Memuat data...</p>',
  );

  $.getJSON(
    Kebijakan.api,
    { action: "list", category: category },
    function (res) {
      if (!res.success) {
        $(targetSelector).html(
          '<p class="mb-0" style="color:#fecaca; font-size:12.5px;">' +
            res.message +
            "</p>",
        );
        return;
      }

      if (!res.data.length) {
        $(targetSelector).html(
          '<p class="mb-0 text-center py-3" style="color:rgba(255,255,255,0.7); font-size:12.5px;">Belum ada dokumen diunggah.</p>',
        );
        return;
      }

      let html = '<div class="row g-2">';

      res.data.forEach(function (doc) {
        html += `
        <div class="col-4 col-md-3">
          <div class="kebijakan-doc-tile p-2" onclick="window.open('${SIQUA.BASE_URL}${doc.file_path}', '_blank')">
            <i class="bi bi-file-earmark-pdf-fill pdf-icon-big"></i>
            <div class="doc-title mt-1 text-truncate" title="${escapeHtml(doc.title)}">${escapeHtml(doc.title)}</div>
            ${
              KEBIJAKAN_CAN_MANAGE
                ? `
              <button type="button" class="doc-delete-btn mt-1 py-1 px-2" onclick="event.stopPropagation(); deleteDocument(${doc.id}, '${category}');">
                <i class="bi bi-trash"></i>
              </button>
            `
                : ""
            }
          </div>
        </div>
      `;
      });

      html += "</div>";

      $(targetSelector).html(html);
    },
  );
}

function submitUpload() {
  const formData = new FormData($("#uploadForm")[0]);
  const category = $("#upload_category").val();

  Swal.fire({
    title: "Mengunggah...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: Kebijakan.api + "?action=upload",
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

      Kebijakan.uploadModal.hide();
      Swal.fire({
        icon: "success",
        title: "Berhasil",
        text: response.message,
        timer: 1200,
        showConfirmButton: false,
      });

      loadList(
        category,
        category === "kebijakan_mutu"
          ? "#listKebijakanMutu"
          : "#listPeraturanMutu",
      );
    },
    error: function () {
      Swal.close();
      Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
    },
  });
}

function deleteDocument(id, category) {
  Swal.fire({
    icon: "warning",
    title: "Hapus dokumen ini?",
    text: "Dokumen yang dihapus tidak bisa dikembalikan.",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(Kebijakan.api + "?action=delete", { id: id }, function (response) {
      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      Swal.fire({
        icon: "success",
        title: "Terhapus",
        timer: 1000,
        showConfirmButton: false,
      });
      loadList(
        category,
        category === "kebijakan_mutu"
          ? "#listKebijakanMutu"
          : "#listPeraturanMutu",
      );
    });
  });
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}
