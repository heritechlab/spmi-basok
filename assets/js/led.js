const Led = {
  api: SIQUA.BASE_URL + "led_shared/api.php",
  entryModal: null,
};

$(document).ready(function () {
  if (document.getElementById("ledEntryModal")) {
    Led.entryModal = new bootstrap.Modal(
      document.getElementById("ledEntryModal"),
    );
  }

  if (LED_ASSIGNMENT_ID > 0) {
    loadStructure();
  }

  $(document).on("submit", "#ledEntryForm", function (e) {
    e.preventDefault();
    saveEntry();
  });

  $(document).on("click", ".btn-delete-led-doc", function () {
    deleteDocument($(this).data("id"));
  });
});

function loadStructure() {
  $("#ledContainer").html('<p class="text-muted">Memuat data...</p>');

  $.getJSON(
    Led.api,
    { action: "structure", level: LED_LEVEL, assignment_id: LED_ASSIGNMENT_ID },
    function (res) {
      if (!res.success) {
        $("#ledContainer").html(
          '<p class="text-danger">' + res.message + "</p>",
        );
        return;
      }

      let html = "";

      res.data.forEach(function (kriteria) {
        const critMatch = kriteria.criteria_name.match(/Kriteria\s+([\d.]+)/i);
        const critNumber = critMatch ? critMatch[1].replace(/\.+$/, "") : "";

        html += `
        <div class="card shadow-sm mb-3 led-card">
          <div class="card-body">
            <h5 class="mb-3 led-kriteria-title">${escapeHtml(kriteria.criteria_name)}</h5>
      `;

        kriteria.standards.forEach(function (std, sIdx) {
          html += `
            <div class="mb-4 led-standard-block">
              <h6 class="led-standard-title">${critNumber}.${sIdx + 1} Pernyataan ${escapeHtml(std.standard_name)}</h6>
              <p class="small text-muted led-instruction-text">
                Program Studi (PS) menyatakan secara terbuka mengenai ${escapeHtml(std.standard_name)}
                yang dijelaskan melalui elemen utama (${escapeHtml(std.standard_name)}) dan pernyataan butir dan indikator sebagai sub-elemen.
                Jelaskan secara singkat dan ringkas bagaimana PS mencapai sub-elemen (butir standar dan indikator mutu) sertakan dokumen pendukung.
              </p>

              <div class="table-responsive">
                <table class="table table-bordered table-sm led-table">
                  <thead>
                    <tr>
                      <th width="16%">Elemen Utama</th>
                      <th width="22%">Sub-Elemen (Indikator &amp; Target)</th>
                      <th width="13%">Capaian Target</th>
                      <th width="27%">Pernyataan Evaluasi</th>
                      <th width="12%">Dokumen Pendukung</th>
                      <th width="10%">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
        `;

          if (!std.indicators.length) {
            html += `<tr><td colspan="6" class="text-center text-muted py-3">Belum ada Indikator untuk Standar ini.</td></tr>`;
          }

          std.indicators.forEach(function (ind) {
            const entry = ind.entry || {};
            const documents = entry.documents || [];
            const isFilled = entry.notes || entry.capaian_realisasi;

            let docHtml =
              '<div class="d-flex align-items-center justify-content-center">';

            documents.forEach(function (doc) {
              if (doc.document_file) {
                docHtml += `<a href="${SIQUA.BASE_URL}${doc.document_file}" target="_blank" class="led-doc-chip led-doc-file" title="${escapeAttr(doc.document_original_name || "Buka Dokumen")}"><i class="bi bi-file-earmark-pdf-fill"></i></a>`;
              }
              if (doc.link_url) {
                docHtml += `<a href="${doc.link_url}" target="_blank" class="led-doc-chip led-doc-link" title="Buka Link Dokumen"><i class="bi bi-link-45deg"></i></a>`;
              }
            });

            docHtml += "</div>";

            if (!documents.length) {
              docHtml = '<span class="text-muted small">Belum ada</span>';
            }

            html += `
            <tr>
              <td><strong>${escapeHtml(std.standard_code)}</strong><div class="text-muted small">${escapeHtml(std.standard_name)}</div></td>
              <td>${escapeHtml(ind.item_code)} - ${escapeHtml(ind.indicator)}<div class="text-muted small">Target: ${escapeHtml(ind.target || "-")}</div></td>
              <td>${entry.capaian_realisasi ? escapeHtml(entry.capaian_realisasi) : '<span class="text-muted">-</span>'}</td>
              <td>${entry.notes ? escapeHtml(entry.notes) : '<span class="text-muted">Belum diisi</span>'}</td>
              <td class="text-center">${docHtml}</td>
              <td class="text-center">
                <button type="button" class="btn btn-sm led-action-btn btn-edit-led"
                  data-standard="${std.standard_id}"
                  data-id="${ind.id}" data-label="${escapeAttr(std.standard_name + " - " + ind.indicator)}"
                  data-capaian="${escapeAttr(entry.capaian_realisasi || "")}"
                  data-evaluasi="${escapeAttr(entry.notes || "")}"
                  data-docs='${JSON.stringify(documents)}'>
                  ${isFilled ? '<i class="bi bi-pencil-square"></i> Edit' : '<i class="bi bi-play-circle-fill"></i> Mulai Isi LED'}
                </button>
              </td>
            </tr>
          `;
          });

          html += `
                  </tbody>
                </table>
              </div>
            </div>
        `;
        });

        html += `</div></div>`;
      });

      $("#ledContainer").html(
        html ||
          '<p class="text-muted">Belum ada Standar yang ditugaskan pada Penugasan ini.</p>',
      );
    },
  );
}

$(document).on("click", ".btn-edit-led", function () {
  $("#entry_standard_id").val($(this).data("standard"));
  $("#entry_indicator_id").val($(this).data("id"));
  $("#entryIndicatorLabel").text($(this).data("label"));
  $("#entry_capaian").val($(this).data("capaian"));
  $("#entry_evaluasi").val($(this).data("evaluasi"));
  $("#entry_link").val("");
  $("#ledEntryForm")[0].querySelector('[name="dokumen_file"]').value = "";

  const docs = $(this).data("docs") || [];
  let docsHtml = "";

  if (docs.length) {
    docsHtml = '<label class="form-label small">Dokumen Tersimpan</label><div>';
    docs.forEach(function (doc) {
      if (doc.document_file) {
        docsHtml += `<span class="badge bg-light text-dark border me-1 mb-1">
          <a href="${SIQUA.BASE_URL}${doc.document_file}" target="_blank" class="text-decoration-none"><i class="bi bi-file-earmark-pdf-fill text-danger"></i> ${escapeHtml(doc.document_original_name || "File")}</a>
          <button type="button" class="btn-close btn-close-sm ms-1 btn-delete-led-doc" data-id="${doc.id}" style="font-size:8px;"></button>
        </span>`;
      }
      if (doc.link_url) {
        docsHtml += `<span class="badge bg-light text-dark border me-1 mb-1">
          <a href="${doc.link_url}" target="_blank" class="text-decoration-none"><i class="bi bi-link-45deg text-primary"></i> Link</a>
          <button type="button" class="btn-close btn-close-sm ms-1 btn-delete-led-doc" data-id="${doc.id}" style="font-size:8px;"></button>
        </span>`;
      }
    });
    docsHtml += "</div>";
  }

  $("#entryExistingDocs").html(docsHtml);

  Led.entryModal.show();
});

function saveEntry() {
  const formData = new FormData($("#ledEntryForm")[0]);

  Swal.fire({
    title: "Menyimpan...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: Led.api + "?action=save_entry",
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

      Led.entryModal.hide();
      Swal.fire({
        icon: "success",
        title: "Berhasil",
        text: response.message,
        timer: 1200,
        showConfirmButton: false,
      });

      loadStructure();
    },
    error: function () {
      Swal.close();
      Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
    },
  });
}

function deleteDocument(docId) {
  Swal.fire({
    icon: "warning",
    title: "Hapus dokumen ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(
      Led.api + "?action=delete_document",
      { doc_id: docId },
      function (response) {
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
        Led.entryModal.hide();
        loadStructure();
      },
    );
  });
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

function escapeAttr(text) {
  return (text || "").replace(/"/g, "&quot;");
}
