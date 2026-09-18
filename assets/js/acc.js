const Acc = {
  api: SIQUA.BASE_URL + "acc/api.php",
  currentCriteriaId: null,
  currentUnitId: null,
  currentSemester: "Ganjil",
  currentAcademicYear: "",
  currentCode: null,
  currentTableIdCache: null,
  saveTimers: {},
};

$(document).ready(function () {
  Acc.currentSemester = $("#semesterSelector").val() || "Ganjil";
  registerEvents();
  loadCriteriaCards();
});

function registerEvents() {
  $("#criteriaSelector").on("change", function () {
    Acc.currentCriteriaId = $(this).val();
    Acc.currentCode = null;
    $("#tableContainer").html("");
    loadTablesForCriteria();
    tryLoadChecklist();
  });

  $(document).on("click", "#btnExportExcel", function () {
    exportCurrentTableToExcel();
  });

  if (!ACC_IS_AUDITEE) {
    $("#unitSelector").on("change", function () {
      Acc.currentUnitId = $(this).val();
      loadTablesForCriteria();
      tryLoadChecklist();
      tryLoadTable();
      loadCriteriaCards();
    });
  }

  $("#semesterSelector").on("change", function () {
    Acc.currentSemester = $(this).val();
    tryLoadChecklist();
  });

  $("#academicYearInput").on("change", function () {
    Acc.currentAcademicYear = $(this).val().trim();
    tryLoadChecklist();
  });

  $("#tableSelector").on("change", function () {
    Acc.currentCode = $(this).val();
    tryLoadTable();
  });

  $(document).on("click", ".btn-open-checklist-upload", function () {
    openChecklistUploadModal(
      $(this).data("document-id"),
      $(this).data("document-name"),
    );
  });

  $(document).on("click", ".btn-delete-checklist-upload", function () {
    const id = $(this).data("id");

    Swal.fire({
      icon: "warning",
      title: "Hapus Dokumen ini?",
      showCancelButton: true,
      confirmButtonText: "Ya, Hapus",
      cancelButtonText: "Batal",
    }).then(function (result) {
      if (!result.isConfirmed) return;

      $.post(
        Acc.api + "?action=delete_checklist_upload",
        { id: id },
        function (response) {
          if (response.success) {
            loadChecklist();
          } else {
            Swal.fire("Gagal", response.message, "error");
          }
        },
      );
    });
  });

  /* ===== Bagian Tabel Data Dukung (sudah ada sebelumnya) ===== */

  $(document).on(
    "input",
    ".acc-cell-input:not(.acc-cell-checkbox)",
    function () {
      const $input = $(this);

      if ($input.prop("readonly")) return;

      const rowId = $input.data("row");
      const columnId = $input.data("column");
      const key = rowId + "_" + columnId;

      clearTimeout(Acc.saveTimers[key]);

      $input.removeClass("is-saved").addClass("is-typing");

      Acc.saveTimers[key] = setTimeout(function () {
        const valueToSave = $input.hasClass("acc-currency-input")
          ? parseRupiah($input.val())
          : $input.val();
        saveCell(rowId, columnId, valueToSave, $input);
        recalcFormulaCells(rowId);
        recalcGroupTotals();
        recalculateTotalRow();
      }, 700);
    },
  );

  $(document).on("input", ".acc-currency-input", function () {
    const $input = $(this);
    const cursorAtEnd = $input[0].selectionStart === $input.val().length;

    const raw = parseRupiah($input.val());
    const formatted = raw === "" ? "" : formatRupiah(raw);

    $input.val(formatted);

    if (cursorAtEnd) {
      $input[0].setSelectionRange(formatted.length, formatted.length);
    }
  });

  $(document).on("change", ".acc-cell-checkbox", function () {
    const $input = $(this);
    const rowId = $input.data("row");
    const columnId = $input.data("column");
    const value = $input.is(":checked") ? "1" : "0";

    saveCell(rowId, columnId, value, $input);
    recalcGroupTotals();
    recalculateTotalRow();
  });

  $(document).on("click", ".btn-add-grouped-row", function () {
    const groupKey = $(this).data("group-key");
    const rowLabel = Acc.currentTableData?.table?.row_header_label || "Baris";

    Swal.fire({
      title: "Tambah " + rowLabel,
      input: "text",
      inputLabel: "Nama " + rowLabel,
      showCancelButton: true,
      confirmButtonText: "Tambah",
      cancelButtonText: "Batal",
      inputValidator: (value) => {
        if (!value) return "Label wajib diisi.";
      },
    }).then(function (result) {
      if (!result.isConfirmed) return;

      $.post(
        Acc.api + "?action=add_grouped_row",
        {
          code: Acc.currentCode,
          unit_id: Acc.currentUnitId || 0,
          group_key: groupKey,
          label: result.value,
        },
        function (response) {
          if (!response.success) {
            Swal.fire("Gagal", response.message, "error");
            return;
          }
          loadTable();
        },
      ).fail(showAjaxError);
    });
  });

  $(document).on("click", "#btnAddAccRow", function () {
    const rowLabel =
      Acc.currentTableData?.table?.row_header_label || "Tahun Akademik";

    Swal.fire({
      title: "Tambah " + rowLabel,
      input: "text",
      inputLabel: "Label " + rowLabel + " (contoh: 2026/2027)",
      inputPlaceholder: "",
      showCancelButton: true,
      confirmButtonText: "Tambah",
      cancelButtonText: "Batal",
      inputValidator: (value) => {
        if (!value) return "Label wajib diisi.";
      },
    }).then(function (result) {
      if (!result.isConfirmed) return;

      $.post(
        Acc.api + "?action=add_row",
        {
          code: Acc.currentCode,
          unit_id: Acc.currentUnitId || 0,
          label: result.value,
        },
        function (response) {
          if (!response.success) {
            Swal.fire("Gagal", response.message, "error");
            return;
          }
          loadTable();
        },
      ).fail(showAjaxError);
    });
  });

  $(document).on("click", "#btnAddAccColumn", function () {
    Swal.fire({
      title: "Tambah Kolom Tahun",
      input: "text",
      inputLabel: "Label Kolom (contoh: TS-6, TS-5, dst)",
      inputPlaceholder: "TS-6",
      showCancelButton: true,
      confirmButtonText: "Tambah",
      cancelButtonText: "Batal",
      inputValidator: (value) => {
        if (!value) return "Label Kolom wajib diisi.";
      },
    }).then(function (result) {
      if (!result.isConfirmed) return;

      $.post(
        Acc.api + "?action=add_column",
        {
          code: Acc.currentCode,
          unit_id: Acc.currentUnitId || 0,
          label: result.value,
        },
        function (response) {
          if (!response.success) {
            Swal.fire("Gagal", response.message, "error");
            return;
          }
          loadTable();
        },
      ).fail(showAjaxError);
    });
  });

  $(document).on("click", ".btn-delete-column", function () {
    const columnId = $(this).data("column-id");

    Swal.fire({
      icon: "warning",
      title: "Hapus Kolom ini?",
      text: "Seluruh data angka pada kolom ini akan ikut terhapus.",
      showCancelButton: true,
      confirmButtonText: "Ya, Hapus",
      cancelButtonText: "Batal",
    }).then(function (result) {
      if (!result.isConfirmed) return;

      $.post(
        Acc.api + "?action=delete_column",
        { column_id: columnId, unit_id: Acc.currentUnitId || 0 },
        function (response) {
          if (!response.success) {
            Swal.fire("Gagal", response.message, "error");
            return;
          }
          loadTable();
        },
      ).fail(showAjaxError);
    });
  });

  $(document).on("change", ".acc-row-label-input", function () {
    const $input = $(this);
    const rowId = $input.data("row-id");
    const label = $input.val().trim();

    if (!label) {
      Swal.fire("Gagal", "Label Tahun tidak boleh kosong.", "error");
      return;
    }

    $.post(
      Acc.api + "?action=update_row_label",
      { row_id: rowId, label: label },
      function (response) {
        if (!response.success) {
          Swal.fire("Gagal", response.message, "error");
          return;
        }
        const $status = $("#accSaveStatus");
        $status.stop(true, true).show().fadeOut(1500);
      },
    ).fail(showAjaxError);
  });

  $(document).on("click", ".btn-delete-row", function () {
    const rowId = $(this).data("row-id");

    Swal.fire({
      icon: "warning",
      title: "Hapus baris Tahun ini?",
      text: "Seluruh data angka pada baris ini akan ikut terhapus.",
      showCancelButton: true,
      confirmButtonText: "Ya, Hapus",
      cancelButtonText: "Batal",
    }).then(function (result) {
      if (!result.isConfirmed) return;

      $.post(
        Acc.api + "?action=delete_row",
        { row_id: rowId },
        function (response) {
          if (!response.success) {
            Swal.fire("Gagal", response.message, "error");
            return;
          }
          loadTable();
        },
      ).fail(showAjaxError);
    });
  });

  $(document).on("submit", "#accDocForm", function (e) {
    e.preventDefault();
    uploadAccDocument();
  });

  $(document).on("click", ".btn-delete-acc-doc", function () {
    deleteAccDocument($(this).data("id"));
  });

  $(document).on("click", ".btn-kelola-capaian", function () {
    const criteriaId = $(this).data("criteria-id");

    $("#criteriaCardsContainer").hide();
    $("#btnBackToCards").show();
    $("#accFilterCard").slideDown(200);
    $("#checklistContainer").show();
    $("#tableSelectorWrapper").show();

    $("#criteriaSelector").val(criteriaId).trigger("change");

    setTimeout(function () {
      $("html, body").animate({ scrollTop: 0 }, 300);
    }, 250);
  });

  $(document).on("click", "#btnBackToCards", function () {
    $("#criteriaCardsContainer").show();
    $("#btnBackToCards").hide();
    $("#accFilterCard").hide();
    $("#checklistContainer").hide();
    $("#tableSelectorWrapper").hide();
    $("#tableContainer").html("");

    $("#criteriaSelector").val("");
    Acc.currentCode = null;

    $("html, body").animate({ scrollTop: 0 }, 300);
  });
}

/* ===================== KRITERIA -> DAFTAR TABEL ===================== */

function loadTablesForCriteria() {
  $("#tableSelectorWrapper").hide();
  $("#tableSelector").html('<option value="">-- Pilih Tabel --</option>');
  $("#tableContainer").html("");
  Acc.currentCode = null;

  if (!Acc.currentCriteriaId) return;

  if (!ACC_IS_AUDITEE && !Acc.currentUnitId) return;

  $.getJSON(
    Acc.api,
    {
      action: "tables_by_criteria",
      criteria_id: Acc.currentCriteriaId,
      unit_id: Acc.currentUnitId || 0,
    },
    function (res) {
      if (!res.success || !res.data.length) {
        $("#tableSelector").html(
          '<option value="">-- Tidak Ada Tabel untuk Unit ini --</option>',
        );
        $("#tableSelectorWrapper").show();
        return;
      }

      let options = '<option value="">-- Pilih Tabel --</option>';

      res.data.forEach(function (t) {
        options += `<option value="${t.code}">${escapeHtml(t.title)}</option>`;
      });

      $("#tableSelector").html(options);
      $("#tableSelectorWrapper").show();
    },
  );
}

function loadCriteriaCards() {
  if (!ACC_IS_AUDITEE && !Acc.currentUnitId) {
    $("#criteriaCardsContainer").html("");
    return;
  }

  $.getJSON(
    Acc.api,
    { action: "criteria_progress_by_year", unit_id: Acc.currentUnitId || 0 },
    function (res) {
      if (!res.success) {
        $("#criteriaCardsContainer").html("");
        return;
      }

      renderCriteriaCards(res.data);
    },
  );
}

function renderCriteriaCards(criteriaList) {
  const yearPalette = [
    "#7c3aed",
    "#2563eb",
    "#0891b2",
    "#059669",
    "#d97706",
    "#dc2626",
    "#db2777",
    "#4f46e5",
  ];
  let gaugeCounter = 0;

  function radialGauge(label, filled, total, percent, colorHex) {
    gaugeCounter++;

    const size = 68;
    const strokeWidth = 6;
    const radius = size / 2 - strokeWidth;
    const circumference = 2 * Math.PI * radius;
    const pct = percent === null ? 0 : percent;
    const offset = circumference * (1 - pct / 100);

    const displayText = percent === null ? "-" : pct + "%";

    return `
      <div class="d-flex flex-column align-items-center" style="min-width:${size}px;">
        <svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
          <circle cx="${size / 2}" cy="${size / 2}" r="${radius}" fill="none" stroke="#f1f0f8" stroke-width="${strokeWidth}" />
          <circle cx="${size / 2}" cy="${size / 2}" r="${radius}" fill="none" stroke="${colorHex}" stroke-width="${strokeWidth}"
            stroke-linecap="round" stroke-dasharray="${circumference}" stroke-dashoffset="${offset}"
            transform="rotate(-90 ${size / 2} ${size / 2})" style="transition:stroke-dashoffset 0.6s ease;" />
          <text x="50%" y="47%" text-anchor="middle" dominant-baseline="middle" style="font-size:13px; font-weight:700; fill:#3a3348;">${displayText}</text>
          <text x="50%" y="66%" text-anchor="middle" dominant-baseline="middle" style="font-size:8px; fill:#8b8398;">${filled}/${total}</text>
        </svg>
        <span class="small text-muted mt-1 text-center" style="font-size:10px; line-height:1.2;">${escapeHtml(label)}</span>
      </div>
    `;
  }

  function colorFor(percent) {
    if (percent === null) return { bg: "#f1f0f8", text: "#8b8398", label: "-" };
    if (percent >= 100)
      return { bg: "#dcfce7", text: "#15803d", label: "Lengkap" };
    if (percent >= 50)
      return { bg: "#fef3c7", text: "#b45309", label: "Sebagian" };
    return { bg: "#fee2e2", text: "#b91c1c", label: "Belum" };
  }

  function miniTable(rows) {
    let body = "";

    rows.forEach(function (r) {
      const c = colorFor(r.percent);
      const displayPct = r.percent === null ? "-" : r.percent + "%";
      body += `
        <tr>
          <td>${escapeHtml(r.year)}</td>
          <td class="text-center">${r.filled}/${r.total}</td>
          <td class="text-center">${displayPct}</td>
          <td class="text-center"><span class="badge" style="background:${c.bg}; color:${c.text}; font-weight:600;">${c.label}</span></td>
        </tr>
      `;
    });

    return `
      <table class="table table-sm table-borderless mb-0" style="font-size:12px;">
        <thead>
          <tr class="text-muted">
            <th>Tahun</th>
            <th class="text-center">Terisi</th>
            <th class="text-center">Persen</th>
            <th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>${body}</tbody>
      </table>
    `;
  }

  function buildConclusion(c) {
    const validTable = c.tables_by_year.filter((y) => y.percent !== null);
    const validDoc = c.docs_by_year.filter((y) => y.percent !== null);

    if (
      validTable.length === 0 &&
      validDoc.length === 0 &&
      c.fixed_total === 0
    ) {
      return {
        text: "Belum ada Tabel/Dokumen yang ditugaskan untuk Kriteria ini.",
        color: "#8b8398",
        icon: "bi-dash-circle",
      };
    }

    const allPercents = [];
    validTable.forEach((y) => allPercents.push(y.percent));
    validDoc.forEach((y) => allPercents.push(y.percent));
    if (c.fixed_total > 0)
      allPercents.push(Math.round((c.fixed_filled / c.fixed_total) * 100));

    const avg = allPercents.length
      ? Math.round(allPercents.reduce((a, b) => a + b, 0) / allPercents.length)
      : 0;

    const weakYears = [];
    validTable.forEach((y) => {
      if (y.percent < 50) weakYears.push(y.year);
    });
    validDoc.forEach((y) => {
      if (y.percent < 50 && !weakYears.includes(y.year)) weakYears.push(y.year);
    });

    if (avg >= 80) {
      return {
        text: `Kelengkapan data sangat baik (rata-rata ${avg}%). Pertahankan konsistensi pengisian.`,
        color: "#22c55e",
        icon: "bi-check-circle-fill",
      };
    }

    if (avg >= 50) {
      const yearNote = weakYears.length
        ? ` Perlu perhatian pada tahun: ${weakYears.join(", ")}.`
        : "";
      return {
        text: `Kelengkapan data cukup (rata-rata ${avg}%).${yearNote}`,
        color: "#f59e0b",
        icon: "bi-exclamation-circle-fill",
      };
    }

    const yearNote = weakYears.length
      ? ` Terutama tahun: ${weakYears.join(", ")}.`
      : "";
    return {
      text: `Kelengkapan data masih rendah (rata-rata ${avg}%), perlu segera dilengkapi.${yearNote}`,
      color: "#ef4444",
      icon: "bi-exclamation-triangle-fill",
    };
  }

  let html = "";

  criteriaList.forEach(function (c, idx) {
    let tableGaugesHtml = "";
    c.tables_by_year.forEach(function (y, idx) {
      const color = yearPalette[idx % yearPalette.length];
      tableGaugesHtml += radialGauge(
        y.year,
        y.filled,
        y.total,
        y.percent,
        color,
      );
    });

    const conclusion = buildConclusion(c);

    html += `
      <div class="col-lg-6">
        <div class="card shadow-sm h-100 acc-criteria-card" style="animation-delay:${idx * 0.06}s;">
          <div class="card-body d-flex flex-column">

            <strong class="mb-3" style="font-size:16px; line-height:1.3; color:#3a3348;">${escapeHtml(c.criteria_name)}</strong>

            ${
              c.tables_by_year.length
                ? `
            <div class="mb-3">
              <span class="small text-muted d-block mb-2"><i class="bi bi-table"></i> Tabel Data Dukung</span>
              <div class="d-flex flex-wrap gap-3">${tableGaugesHtml}</div>
            </div>`
                : ""
            }

            ${
              c.docs_by_year.length
                ? `
            <div class="mb-2">
              <span class="small text-muted d-block mb-1"><i class="bi bi-file-earmark-text"></i> Daftar Dokumen</span>
              ${miniTable(c.docs_by_year)}
            </div>`
                : ""
            }

            <div class="mt-2 mb-3 p-2 rounded d-flex align-items-start gap-2" style="background:${conclusion.color}15; border:1px solid ${conclusion.color}40;">
              <i class="bi ${conclusion.icon}" style="color:${conclusion.color}; font-size:16px; margin-top:1px;"></i>
              <span class="small" style="color:#3a3348;">${conclusion.text}</span>
            </div>

            <button type="button" class="btn btn-sm mt-auto align-self-start btn-kelola-capaian text-white" data-criteria-id="${c.criteria_id}"
              style="background:linear-gradient(135deg, #7c3aed, #2563eb); border:none;">
              Kelola Capaian <i class="bi bi-arrow-right"></i>
            </button>

          </div>
        </div>
      </div>
    `;
  });

  $("#criteriaCardsContainer").html(html);
}

/* ===================== DAFTAR DOKUMEN (CHECKLIST) ===================== */

function tryLoadChecklist() {
  const unitReady = ACC_IS_AUDITEE || !!Acc.currentUnitId;

  if (
    !Acc.currentCriteriaId ||
    !unitReady ||
    !Acc.currentSemester ||
    !Acc.currentAcademicYear
  ) {
    const kritLabel = ACC_IS_AUDITEE ? "" : ", Program Studi,";
    $("#checklistContainer").html(
      `<p class="text-muted">Silakan pilih Kriteria${kritLabel} dan Tahun Akademik terlebih dahulu.</p>`,
    );
    return;
  }

  loadChecklist();
}

function loadChecklist() {
  $("#checklistContainer").html('<p class="text-muted">Memuat data...</p>');

  $.getJSON(
    Acc.api,
    {
      action: "checklist",
      criteria_id: Acc.currentCriteriaId,
      unit_id: Acc.currentUnitId || 0,
      semester: Acc.currentSemester,
      academic_year: Acc.currentAcademicYear,
    },
    function (res) {
      if (!res.success) {
        $("#checklistContainer").html(
          '<p class="text-danger">' + res.message + "</p>",
        );
        return;
      }

      renderChecklist(res.data);
    },
  );
}

function renderChecklist(documents) {
  if (!documents.length) {
    $("#checklistContainer").html(`
      <div class="card shadow-sm">
        <div class="card-header"><strong>Daftar Dokumen</strong></div>
        <div class="card-body">
          <p class="text-muted mb-0">Belum ada Daftar Dokumen untuk Kriteria ini. Silakan atur di menu Kelola Tabel Akreditasi.</p>
        </div>
      </div>
    `);
    return;
  }

  let rowsHtml = "";

  documents.forEach(function (doc, i) {
    const isUploaded = !!doc.upload_id;

    let actionCell;

    if (isUploaded) {
      const docBtn = doc.file_path
        ? `<a href="${SIQUA.BASE_URL}${doc.file_path}" target="_blank" class="acc-icon-btn acc-icon-doc" title="Lihat Dokumen"><i class="bi bi-file-earmark-text"></i></a>`
        : `<span class="acc-icon-btn acc-icon-doc disabled" title="Tidak ada file"><i class="bi bi-file-earmark-text"></i></span>`;

      const linkBtn = doc.link_url
        ? `<a href="${doc.link_url}" target="_blank" class="acc-icon-btn acc-icon-link" title="Buka Link"><i class="bi bi-box-arrow-up-right"></i></a>`
        : `<span class="acc-icon-btn acc-icon-link disabled" title="Tidak ada link"><i class="bi bi-box-arrow-up-right"></i></span>`;

      const deleteBtn = `<button type="button" class="acc-icon-btn acc-icon-delete btn-delete-checklist-upload" data-id="${doc.upload_id}" title="Hapus"><i class="bi bi-trash"></i></button>`;

      actionCell = `<div class="d-flex gap-2 justify-content-center">${docBtn}${linkBtn}${deleteBtn}</div>`;
    } else {
      actionCell = `
        <div class="d-flex justify-content-center">
          <button type="button" class="acc-icon-btn acc-icon-upload btn-open-checklist-upload"
            data-document-id="${doc.id}" data-document-name="${escapeAttr(doc.document_name)}" title="Upload Dokumen">
            <i class="bi bi-cloud-arrow-up"></i>
          </button>
        </div>
      `;
    }

    rowsHtml += `
      <tr>
        <td class="text-center">${i + 1}</td>
        <td>${escapeHtml(doc.document_name)}</td>
        <td>${actionCell}</td>
      </tr>
    `;
  });

  const html = `
    <div class="card shadow-sm">
      <div class="card-header"><strong>Daftar Dokumen</strong> <small class="text-muted">(${Acc.currentSemester} ${Acc.currentAcademicYear})</small></div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered align-middle" style="font-size:13px;">
            <thead class="table-light">
              <tr>
                <th width="40">No</th>
                <th>Nama Dokumen</th>
                <th width="150" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>${rowsHtml}</tbody>
          </table>
        </div>
      </div>
    </div>
  `;

  $("#checklistContainer").html(html);
}

function openChecklistUploadModal(documentId, documentName) {
  Swal.fire({
    title: "Upload Dokumen",
    html: `
      <div class="text-start">
        <p class="mb-2 text-muted small">${escapeHtml(documentName)}</p>
        <label class="form-label small mb-1">Upload File</label>
        <input type="file" id="swalDocFile" class="form-control form-control-sm mb-2">
        <label class="form-label small mb-1">Atau Link</label>
        <input type="url" id="swalDocLink" class="form-control form-control-sm" placeholder="https://drive.google.com/...">
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: "Upload",
    cancelButtonText: "Batal",
    preConfirm: () => {
      const fileInput = document.getElementById("swalDocFile");
      const link = document.getElementById("swalDocLink").value.trim();

      if (!fileInput.files.length && !link) {
        Swal.showValidationMessage("Isi salah satu: Upload File atau Link.");
        return false;
      }

      return { file: fileInput.files[0] || null, link: link };
    },
  }).then(function (result) {
    if (!result.isConfirmed) return;

    const formData = new FormData();
    formData.append("document_id", documentId);
    formData.append("unit_id", Acc.currentUnitId || 0);
    formData.append("semester", Acc.currentSemester);
    formData.append("academic_year", Acc.currentAcademicYear);
    formData.append("link_url", result.value.link);

    if (result.value.file) {
      formData.append("document", result.value.file);
    }

    Swal.fire({
      title: "Mengunggah...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    $.ajax({
      url: Acc.api + "?action=upload_checklist_file",
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

        Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: response.message,
          timer: 1200,
          showConfirmButton: false,
        });
        loadChecklist();
      },
      error: function () {
        Swal.close();
        Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
      },
    });
  });
}

function uploadChecklistDocument($form) {
  const documentId = $form.data("document-id");
  const fileInput = $form.find(".acc-doc-file")[0];
  const link = $form.find(".acc-doc-link").val().trim();

  if (!fileInput.files.length && !link) {
    Swal.fire("Gagal", "Isi salah satu: Upload File atau Link.", "error");
    return;
  }

  const formData = new FormData();
  formData.append("document_id", documentId);
  formData.append("unit_id", Acc.currentUnitId || 0);
  formData.append("semester", Acc.currentSemester);
  formData.append("academic_year", Acc.currentAcademicYear);
  formData.append("link_url", link);

  if (fileInput.files.length) {
    formData.append("document", fileInput.files[0]);
  }

  Swal.fire({
    title: "Mengunggah...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: Acc.api + "?action=upload_checklist_file",
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

      Swal.fire({
        icon: "success",
        title: "Berhasil",
        text: response.message,
        timer: 1200,
        showConfirmButton: false,
      });
      loadChecklist();
    },
    error: function () {
      Swal.close();
      Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
    },
  });
}

/* ===================== TABEL DATA DUKUNG (sudah ada, tidak diubah) ===================== */

function tryLoadTable() {
  if (!Acc.currentCode) {
    $("#tableContainer").html("");
    $("#btnExportExcel").hide();
    return;
  }

  if (!ACC_IS_AUDITEE && !Acc.currentUnitId) {
    $("#tableContainer").html(
      '<p class="text-muted">Silakan pilih Program Studi terlebih dahulu.</p>',
    );
    $("#btnExportExcel").hide();
    return;
  }

  $("#btnExportExcel").show();
  loadTable();
}

function exportCurrentTableToExcel() {
  const data = Acc.currentTableData;

  if (!data) {
    Swal.fire("Gagal", "Data belum dimuat.", "error");
    return;
  }

  const table = data.table;
  const columns = window.ACC_CURRENT_COLUMNS || data.columns;

  const groups = [];
  let lastGroup = null;

  columns.forEach(function (col, idx) {
    if (idx === 0 || col.group_label !== lastGroup) {
      groups.push({ label: col.group_label, count: 1 });
      lastGroup = col.group_label;
    } else {
      groups[groups.length - 1].count++;
    }
  });

  const aoa = [];
  const merges = [];

  aoa.push([table.title]);
  aoa.push([]);

  const groupRow = [table.row_header_label || "Tahun Akademik"];
  const subRow = [""];

  let colCursor = 1;

  groups.forEach(function (g) {
    groupRow.push(g.label || "");
    for (let i = 1; i < g.count; i++) groupRow.push("");

    if (g.count > 1) {
      merges.push({
        s: { r: 2, c: colCursor },
        e: { r: 2, c: colCursor + g.count - 1 },
      });
    } else {
      merges.push({ s: { r: 2, c: colCursor }, e: { r: 3, c: colCursor } });
    }

    colCursor += g.count;
  });

  columns.forEach(function (col) {
    subRow.push(col.label);
  });

  merges.push({ s: { r: 2, c: 0 }, e: { r: 3, c: 0 } });

  aoa.push(groupRow);
  aoa.push(subRow);

  /* ===== Ambil isi baris langsung dari kotak yang sedang tampil di layar ===== */

  $(".acc-row-label-input").each(function () {
    const rowId = $(this).data("row-id");
    const rowLabel = $(this).val();
    const line = [rowLabel];

    columns.forEach(function (col) {
      const $cellEl = $(
        `.acc-cell-input[data-row="${rowId}"][data-column="${col.id}"]`,
      );

      if (!$cellEl.length) {
        line.push("");
        return;
      }

      if (col.data_type === "checkbox") {
        line.push($cellEl.is(":checked") ? "\u221A" : "");
      } else if (col.data_type === "text") {
        line.push($cellEl.val() || "");
      } else {
        const raw = $cellEl.val();
        line.push(
          raw === "" || raw === undefined || raw === null ? "" : formatNum(raw),
        );
      }
    });

    aoa.push(line);
  });

  /* ===== Ambil baris Jumlah langsung dari layar (sudah live-update) ===== */

  const totalLine = ["Jumlah"];
  columns.forEach(function (col) {
    const text = $(`.acc-total-cell[data-column="${col.id}"]`).text().trim();
    totalLine.push(text !== "" ? text : "-");
  });
  aoa.push(totalLine);

  if ($(".acc-average-cell").length) {
    const avgLine = ["Rata-rata"];
    columns.forEach(function (col) {
      const text = $(`.acc-average-cell[data-column="${col.id}"]`)
        .text()
        .trim();
      avgLine.push(text !== "" ? text : "-");
    });
    aoa.push(avgLine);
  }

  const hasFootnoteCode = columns.some((c) => c.footnote_code);

  if (hasFootnoteCode) {
    const kodeLine = ["Kode"];
    columns.forEach(function (col) {
      kodeLine.push(col.footnote_code || "-");
    });
    aoa.push(kodeLine);
  }

  if (table.description) {
    aoa.push([]);
    aoa.push(["Keterangan: " + table.description]);
  }

  const ws = XLSX.utils.aoa_to_sheet(aoa);
  ws["!merges"] = merges;
  ws["!cols"] = [{ wch: 20 }].concat(columns.map(() => ({ wch: 14 })));

  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Data");

  XLSX.writeFile(wb, table.code + ".xlsx");
}

function loadTable() {
  $("#tableContainer").html('<p class="text-muted">Memuat data...</p>');

  $.getJSON(
    Acc.api,
    {
      action: "get_table",
      code: Acc.currentCode,
      unit_id: Acc.currentUnitId || 0,
    },
    function (res) {
      if (!res.success) {
        $("#tableContainer").html(
          '<p class="text-danger">' + res.message + "</p>",
        );
        return;
      }

      Acc.currentTableIdCache = res.data.table.id;
      Acc.currentTableData = res.data;

      renderTable(res.data);
    },
  );
}

function renderTable(data) {
  const table = data.table;
  const columns = data.columns;
  const rows = data.rows;
  const total = data.total;
  const isFixedRows = table.row_mode === "fixed";
  const isGroupedRows = table.row_mode === "grouped";

  const hasSuperGroup = columns.some((c) => c.super_group_label);
  const headerRowCount = hasSuperGroup ? 3 : 2;

  let superGroupHtml = "";
  let groupHeaderHtml = "";
  let subHeaderHtml = "";

  function renderColLabel(col) {
    if (col.unit_id) {
      return `<div class="d-flex align-items-center justify-content-center gap-1">
        <span>${escapeHtml(col.label)}</span>
        <button type="button" class="btn-close btn-close-sm btn-delete-column" data-column-id="${col.id}" style="font-size:9px;" title="Hapus Kolom"></button>
      </div>`;
    }
    return escapeHtml(col.label);
  }

  const leftHeaderCell = isGroupedRows
    ? `<th rowspan="${headerRowCount}" width="160">Sumber Dana</th>`
    : "";

  if (!hasSuperGroup) {
    groupHeaderHtml =
      leftHeaderCell +
      `<th rowspan="2" width="140">${table.row_header_label || (isFixedRows ? "Hal" : "Tahun Akademik")}</th>`;

    const groups = [];
    let lastGroup = "@@INIT@@";

    columns.forEach(function (col) {
      const key = col.group_label || null;
      if (groups.length === 0 || key !== lastGroup) {
        groups.push({ label: key, cols: [col] });
        lastGroup = key;
      } else {
        groups[groups.length - 1].cols.push(col);
      }
    });

    groups.forEach(function (g) {
      if (g.label) {
        groupHeaderHtml += `<th colspan="${g.cols.length}">${escapeHtml(g.label)}</th>`;
      } else {
        g.cols.forEach(function (col) {
          groupHeaderHtml += `<th rowspan="2" width="100">${renderColLabel(col)}</th>`;
        });
      }
    });

    columns.forEach(function (col) {
      if (!col.group_label) return;
      subHeaderHtml += `<th width="100">${renderColLabel(col)}</th>`;
    });
  } else {
    superGroupHtml =
      leftHeaderCell +
      `<th rowspan="${headerRowCount}" width="140">${table.row_header_label || (isFixedRows ? "Hal" : "Tahun Akademik")}</th>`;

    const superChunks = [];
    let lastSuper = "@@INIT@@";

    columns.forEach(function (col) {
      const key = col.super_group_label || null;
      if (superChunks.length === 0 || key !== lastSuper) {
        superChunks.push({ label: key, cols: [col] });
        lastSuper = key;
      } else {
        superChunks[superChunks.length - 1].cols.push(col);
      }
    });

    superChunks.forEach(function (chunk) {
      if (chunk.label) {
        superGroupHtml += `<th colspan="${chunk.cols.length}">${escapeHtml(chunk.label)}</th>`;

        const groupChunks = [];
        let lastGroup = "@@INIT@@";

        chunk.cols.forEach(function (col) {
          const key = col.group_label || null;
          if (groupChunks.length === 0 || key !== lastGroup) {
            groupChunks.push({ label: key, cols: [col] });
            lastGroup = key;
          } else {
            groupChunks[groupChunks.length - 1].cols.push(col);
          }
        });

        groupChunks.forEach(function (g) {
          if (g.label) {
            groupHeaderHtml += `<th colspan="${g.cols.length}">${escapeHtml(g.label)}</th>`;
            g.cols.forEach(function (col) {
              subHeaderHtml += `<th width="100">${renderColLabel(col)}</th>`;
            });
          } else {
            g.cols.forEach(function (col) {
              groupHeaderHtml += `<th rowspan="2" width="100">${renderColLabel(col)}</th>`;
            });
          }
        });
      } else {
        chunk.cols.forEach(function (col) {
          superGroupHtml += `<th rowspan="${headerRowCount}" width="100">${renderColLabel(col)}</th>`;
        });
      }
    });
  }

  function renderDataCellsHtml(row) {
    let html = "";

    columns.forEach(function (col) {
      const isSelfRow =
        col.formula && normalizeLabel(row.label) === normalizeLabel(col.label);
      const isFormulaCell = col.formula && !isSelfRow;
      const readonlyAttr = isFormulaCell ? "readonly" : "";
      const readonlyStyle = isFormulaCell ? "background:#f3f4f6;" : "";

      if (col.data_type === "checkbox") {
        const isChecked = row.cells[col.id] == 1 ? "checked" : "";
        html += `<td class="text-center">
          <input type="checkbox" class="form-check-input acc-cell-input acc-cell-checkbox" style="width:18px; height:18px;"
            data-row="${row.id}" data-column="${col.id}" data-group="${row.group_key || ""}" ${isChecked}>
        </td>`;
      } else if (col.data_type === "text") {
        const val =
          row.cells[col.id] !== null && row.cells[col.id] !== undefined
            ? row.cells[col.id]
            : "";
        html += `<td>
          <input type="text" class="form-control form-control-sm acc-cell-input"
            data-row="${row.id}" data-column="${col.id}" data-group="${row.group_key || ""}" ${readonlyAttr} style="${readonlyStyle}"
            value="${escapeAttr(val)}">
        </td>`;
      } else if (col.is_currency == 1) {
        const val = formatRupiah(row.cells[col.id]);
        html += `<td>
          <input type="text" inputmode="numeric" class="form-control form-control-sm acc-cell-input acc-currency-input text-end"
            data-row="${row.id}" data-column="${col.id}" data-group="${row.group_key || ""}" ${readonlyAttr} style="${readonlyStyle}"
            value="${val}">
        </td>`;
      } else {
        const val = formatNum(row.cells[col.id]);
        const step = col.data_type === "decimal" ? "0.01" : "1";
        html += `<td>
          <input type="number" step="${step}" class="form-control form-control-sm acc-cell-input text-center"
            data-row="${row.id}" data-column="${col.id}" data-group="${row.group_key || ""}" ${readonlyAttr} style="${readonlyStyle}"
            value="${val}">
        </td>`;
      }
    });

    return html;
  }

  let bodyHtml = "";
  const totalColspan = columns.length + (isGroupedRows ? 2 : 1);

  if (!rows.length) {
    bodyHtml = `<tr><td colspan="${totalColspan}" class="text-center text-muted">Belum ada baris.</td></tr>`;
  }

  let itemNumberByGroup = {};

  if (isGroupedRows) {
    const groupsMap = {};
    const groupOrder = [];

    rows.forEach(function (row) {
      if (row.type === "header") {
        groupsMap[row.group_key] = { label: row.label, dataRows: [] };
        groupOrder.push(row.group_key);
      } else if (row.type === "item" || row.type === "total") {
        if (!groupsMap[row.group_key]) {
          groupsMap[row.group_key] = { label: row.group_key, dataRows: [] };
          groupOrder.push(row.group_key);
        }
        groupsMap[row.group_key].dataRows.push(row);
      }
    });

    groupOrder.forEach(function (gk) {
      const group = groupsMap[gk];
      const rowSpanCount = group.dataRows.length + 1;
      let isFirstRowOfGroup = true;

      group.dataRows.forEach(function (row) {
        let rowLabelCell;

        if (row.type === "total") {
          rowLabelCell = `<strong>${escapeHtml(row.label)}</strong>`;
        } else {
          rowLabelCell = `<div class="d-flex align-items-center gap-1">
              <input type="text" class="form-control form-control-sm acc-row-label-input" data-row-id="${row.id}" value="${escapeAttr(row.label)}" style="flex:1; min-width:0; padding:2px 6px;">
              <button class="btn btn-outline-danger btn-delete-row" data-row-id="${row.id}" title="Hapus baris ini"
                style="width:22px; height:22px; padding:0; font-size:10px; flex-shrink:0; display:flex; align-items:center; justify-content:center;">
                <i class="bi bi-trash"></i>
              </button>
            </div>`;
        }

        bodyHtml += "<tr>";

        if (isFirstRowOfGroup) {
          bodyHtml += `<td rowspan="${rowSpanCount}" class="fw-bold text-start align-middle" style="background:#f7f5ff;">${escapeHtml(group.label)}</td>`;
          isFirstRowOfGroup = false;
        }

        bodyHtml += `<td class="p-1 text-start">${rowLabelCell}</td>`;

        if (row.type === "total") {
          columns.forEach(function (col) {
            const val = row.cells[col.id];
            const display =
              val !== null && val !== undefined
                ? col.is_currency == 1
                  ? formatRupiah(val)
                  : formatNum(val)
                : "-";
            bodyHtml += `<td class="text-center fw-bold acc-group-total-cell" data-column="${col.id}" data-group="${row.group_key || ""}">${display}</td>`;
          });
        } else {
          bodyHtml += renderDataCellsHtml(row);
        }

        bodyHtml += "</tr>";
      });

      bodyHtml += `<tr>
        <td colspan="${columns.length + 1}" class="p-1 text-start">
          <button type="button" class="btn btn-outline-success btn-sm btn-add-grouped-row" data-group-key="${gk}">
            <i class="bi bi-plus-circle"></i> Tambah ${table.row_header_label || "Baris"}
          </button>
        </td>
      </tr>`;
    });
  } else {
    rows.forEach(function (row) {
      if (row.type === "header") {
        bodyHtml += `<tr class="table-secondary"><td colspan="${totalColspan}" class="fw-bold text-start">${escapeHtml(row.label)}</td></tr>`;
        return;
      }

      if (row.type === "total") {
        bodyHtml += `<tr class="table-light"><td class="fw-bold text-start">${escapeHtml(row.label)}</td>`;
        columns.forEach(function (col) {
          const val = row.cells[col.id];
          bodyHtml += `<td class="text-center fw-bold acc-group-total-cell" data-column="${col.id}" data-group="${row.group_key || ""}">${val !== null && val !== undefined ? formatNum(val) : "-"}</td>`;
        });
        bodyHtml += "</tr>";
        return;
      }

      let rowLabelCell;

      if (isFixedRows) {
        const groupKey = row.group_key || "@";
        itemNumberByGroup[groupKey] = (itemNumberByGroup[groupKey] || 0) + 1;
        rowLabelCell = `<div class="small px-1" style="line-height:1.3;">${itemNumberByGroup[groupKey]}. ${escapeHtml(row.label)}</div>`;
      } else {
        rowLabelCell = `<div class="d-flex align-items-center gap-1">
            <input type="text" class="form-control form-control-sm acc-row-label-input" data-row-id="${row.id}" value="${escapeAttr(row.label)}" style="flex:1; min-width:0; padding:2px 6px;">
            <button class="btn btn-outline-danger btn-delete-row" data-row-id="${row.id}" title="Hapus baris ini"
              style="width:22px; height:22px; padding:0; font-size:10px; flex-shrink:0; display:flex; align-items:center; justify-content:center;">
              <i class="bi bi-trash"></i>
            </button>
          </div>`;
      }

      bodyHtml += `<tr>
        <td class="p-1 text-start">
          ${rowLabelCell}
        </td>`;

      bodyHtml += renderDataCellsHtml(row);

      bodyHtml += "</tr>";
    });
  }

  let totalHtml = `<tr class="table-light"><td colspan="${isGroupedRows ? 2 : 1}" class="text-center fw-bold">Jumlah</td>`;
  columns.forEach(function (col) {
    const val = total[col.id];
    const displayVal =
      val === null
        ? "-"
        : col.is_currency == 1
          ? formatRupiah(val)
          : formatNum(val);
    totalHtml += `<td class="text-center fw-bold acc-total-cell" data-column="${col.id}">${displayVal}</td>`;
  });
  totalHtml += "</tr>";

  if (table.show_average_row == 1) {
    totalHtml += `<tr class="table-light"><td colspan="${isGroupedRows ? 2 : 1}" class="text-center fw-bold">Rata-rata</td>`;
    columns.forEach(function (col) {
      const val = data.average[col.id];
      const displayVal =
        val === null || val === undefined
          ? "-"
          : col.is_currency == 1
            ? formatRupiah(val)
            : formatNum(val);
      totalHtml += `<td class="text-center fw-bold acc-average-cell" data-column="${col.id}">${displayVal}</td>`;
    });
    totalHtml += "</tr>";
  }

  const hasFootnoteCode = columns.some((c) => c.footnote_code);

  if (hasFootnoteCode) {
    totalHtml += `<tr><td colspan="${isGroupedRows ? 2 : 1}" class="text-center text-muted small">Kode</td>`;
    columns.forEach(function (col) {
      totalHtml += `<td class="text-center text-muted small">${col.footnote_code ? escapeHtml(col.footnote_code) : "-"}</td>`;
    });
    totalHtml += "</tr>";
  }

  const html = `
<div class="card shadow-sm mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>${escapeHtml(table.title)}</strong>
        <span class="badge bg-success" id="accSaveStatus" style="display:none;"><i class="bi bi-check2"></i> Tersimpan</span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered align-middle text-center" style="font-size:13px;">
<thead class="table-light">
              ${hasSuperGroup ? `<tr>${superGroupHtml}</tr>` : ""}
              <tr>${groupHeaderHtml}</tr>
              <tr>${subHeaderHtml}</tr>
            </thead>
            <tbody>
              ${bodyHtml}
              ${totalHtml}
            </tbody>
          </table>
        </div>

${!isFixedRows && !isGroupedRows ? `<button class="btn btn-outline-primary btn-sm" id="btnAddAccRow"><i class="bi bi-plus-circle"></i> Tambah ${table.row_header_label || "Tahun"}</button>` : ""}
        ${table.allow_dynamic_columns == 1 ? '<button class="btn btn-outline-secondary btn-sm ms-2" id="btnAddAccColumn"><i class="bi bi-plus-circle"></i> Tambah Kolom Tahun</button>' : ""}

        <small class="text-muted d-block mt-2"><i class="bi bi-info-circle"></i> Data & label Tahun tersimpan otomatis. Baris "Jumlah" dihitung otomatis.</small>

        ${
          table.description
            ? `
        <div class="mt-3 p-3 rounded" style="background:#f7f5ff; border:1px solid #e6ddfb;">
          <strong class="d-block mb-1" style="font-size:12.5px; color:#5b21b6;"><i class="bi bi-journal-text"></i> Keterangan Pengisian</strong>
          <div class="text-muted" style="font-size:12px; line-height:1.6;">${escapeHtml(table.description)}</div>
        </div>
        `
            : ""
        }
      </div>
    </div>
  `;

  $("#tableContainer").html(html);

  window.ACC_CURRENT_COLUMNS = columns;

  rows.forEach(function (row) {
    if (row.type === "item") {
      recalcFormulaCells(row.id);
    }
  });

  recalcGroupTotals();
}

function saveCell(rowId, columnId, value, $input) {
  const payload = {
    code: Acc.currentCode,
    unit_id: Acc.currentUnitId || 0,
    cells: {},
  };

  payload.cells[rowId] = {};
  payload.cells[rowId][columnId] = value;

  $.ajax({
    url: Acc.api + "?action=save",
    type: "POST",
    dataType: "json",
    data: payload,
    traditional: false,
    success: function (response) {
      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      $input.removeClass("is-typing").addClass("is-saved");

      const $status = $("#accSaveStatus");
      $status.stop(true, true).show().fadeOut(1500);
    },
    error: function () {
      Swal.fire("Gagal", "Terjadi kesalahan saat menyimpan.", "error");
    },
  });
}

function normalizeLabel(text) {
  return (text || "").trim().toLowerCase();
}

function computePercentFormula(formula, rowId, columns) {
  const match = formula.match(/^PCT\(([A-Za-z0-9]+)\)$/);
  if (!match) return null;

  const code = match[1];
  const targetCol = columns.find((c) => c.footnote_code === code);
  if (!targetCol) return null;

  const $rowCell = $(
    `.acc-cell-input[data-row="${rowId}"][data-column="${targetCol.id}"]`,
  );
  if (!$rowCell.length) return null;

  const rowValRaw = getCellNumericValue($rowCell);
  if (rowValRaw === "") return null;

  const rowVal = parseFloat(rowValRaw);
  if (isNaN(rowVal)) return null;

  const totalText = $(`.acc-total-cell[data-column="${targetCol.id}"]`)
    .text()
    .trim();
  const totalVal = parseFloat(parseRupiah(totalText));

  if (!totalVal || totalVal === 0 || isNaN(totalVal)) return null;

  return Math.round((rowVal / totalVal) * 10000) / 100;
}

function computeFormulaValue(formula, rowId, columns) {
  let expr = formula;

  const rowLabel =
    $(`.acc-row-label-input[data-row-id="${rowId}"]`).val() || "";

  if (expr.includes("SELF")) {
    const selfCol = columns.find(
      (c) => normalizeLabel(c.label) === normalizeLabel(rowLabel),
    );

    if (!selfCol) return null;

    const selfVal =
      parseFloat(
        getCellNumericValue(
          $(
            `.acc-cell-input[data-row="${rowId}"][data-column="${selfCol.id}"]`,
          ),
        ),
      ) || 0;
    expr = expr.replace(/SELF/g, selfVal);
  }

  let hasEmptySource = false;

  columns.forEach(function (col) {
    if (col.footnote_code) {
      const $srcCell = $(
        `.acc-cell-input[data-row="${rowId}"][data-column="${col.id}"]`,
      );
      const raw = getCellNumericValue($srcCell);
      if (raw === "" || raw === undefined) hasEmptySource = true;
      const val = parseFloat(raw) || 0;
      const regex = new RegExp("\\b" + col.footnote_code + "\\b", "g");
      expr = expr.replace(regex, val);
    }
  });

  if (hasEmptySource) return null;

  if (!/^[0-9+\-*/(). ]+$/.test(expr)) return null;

  try {
    const result = Function('"use strict";return (' + expr + ")")();

    if (!isFinite(result)) return null;

    return Math.round(result * 100) / 100;
  } catch (e) {
    return null;
  }
}

function recalcFormulaCells(rowId) {
  const rowLabel =
    $(`.acc-row-label-input[data-row-id="${rowId}"]`).val() || "";
  const columns = window.ACC_CURRENT_COLUMNS || [];

  columns.forEach(function (col) {
    if (!col.formula) return;

    if (normalizeLabel(rowLabel) === normalizeLabel(col.label)) return;

    const result = col.formula.startsWith("PCT(")
      ? computePercentFormula(col.formula, rowId, columns)
      : computeFormulaValue(col.formula, rowId, columns);

    const $cell = $(
      `.acc-cell-input[data-row="${rowId}"][data-column="${col.id}"]`,
    );

    const finalVal = result === null ? "" : result;
    const displayVal =
      col.is_currency == 1 && finalVal !== ""
        ? formatRupiah(finalVal)
        : finalVal;

    $cell.val(displayVal);
    saveCell(rowId, col.id, String(finalVal), $cell);
  });

  recalculateTotalRow();
}

function recalcGroupTotals() {
  $(".acc-group-total-cell").each(function () {
    const $cell = $(this);
    const columnId = $cell.data("column");
    const groupKey = $cell.data("group");

    let sum = 0;
    let has = false;

    $(
      `.acc-cell-input[data-column="${columnId}"][data-group="${groupKey}"]`,
    ).each(function () {
      const v = getCellNumericValue($(this));
      if (v !== "") {
        sum += parseFloat(v);
        has = true;
      }
    });

    const col = (window.ACC_CURRENT_COLUMNS || []).find(
      (c) => c.id == columnId,
    );
    const isCurrency = col && col.is_currency == 1;
    const roundedSum = Math.round(sum * 10) / 10;

    $cell.text(
      has ? (isCurrency ? formatRupiah(sum) : formatNum(roundedSum)) : "-",
    );
  });
}

function recalculateTotalRow() {
  const columns = window.ACC_CURRENT_COLUMNS || [];

  columns.forEach(function (col) {
    const $totalCell = $(`.acc-total-cell[data-column="${col.id}"]`);

    if (!$totalCell.length) return;

    const mode = col.total_mode || (col.is_summable ? "sum" : "none");

    if (mode === "none") {
      if (col.formula) {
        const result = computeFormulaFromTotals(col.formula, columns);
        $totalCell.text(result === null ? "-" : result);
      } else {
        $totalCell.text("-");
      }
      return;
    }

    if (col.data_type === "checkbox") {
      const checkedCount = $(
        `.acc-cell-checkbox[data-column="${col.id}"]:checked`,
      ).length;
      $totalCell.text(checkedCount);
      return;
    }

    const values = [];

    $(`.acc-cell-input[data-column="${col.id}"]`).each(function () {
      const $this = $(this);
      if ($this.closest("tr").find(".acc-group-total-cell").length) return;
      const v = getCellNumericValue($this);
      if (v !== "") {
        values.push(parseFloat(v));
      }
    });

    if (values.length === 0) {
      $totalCell.text("0");
      return;
    }

    let result;

    switch (mode) {
      case "average":
        result =
          Math.round(
            (values.reduce((a, b) => a + b, 0) / values.length) * 100,
          ) / 100;
        break;
      case "min":
        result = Math.min(...values);
        break;
      case "max":
        result = Math.max(...values);
        break;
      default:
        result = values.reduce((a, b) => a + b, 0);
    }

    $totalCell.text(col.is_currency == 1 ? formatRupiah(result) : result);
  });

  $(".acc-average-cell").each(function () {
    const $cell = $(this);
    const columnId = $cell.data("column");

    const values = [];

    $(`.acc-cell-input[data-column="${columnId}"]`).each(function () {
      const v = getCellNumericValue($(this));
      if (v !== "") {
        values.push(parseFloat(v));
      }
    });

    if (values.length === 0) {
      $cell.text("-");
      return;
    }

    const avg =
      Math.round((values.reduce((a, b) => a + b, 0) / values.length) * 100) /
      100;

    const col = (window.ACC_CURRENT_COLUMNS || []).find(
      (c) => c.id == columnId,
    );
    const isCurrency = col && col.is_currency == 1;

    $cell.text(isCurrency ? formatRupiah(avg) : formatNum(avg));
  });
}

function computeFormulaFromTotals(formula, columns) {
  let expr = formula;
  let hasEmpty = false;

  columns.forEach(function (col) {
    if (col.footnote_code) {
      const text = $(`.acc-total-cell[data-column="${col.id}"]`).text().trim();
      if (text === "" || text === "-") hasEmpty = true;
      const val = parseFloat(parseRupiah(text)) || 0;
      const regex = new RegExp("\\b" + col.footnote_code + "\\b", "g");
      expr = expr.replace(regex, val);
    }
  });

  if (hasEmpty) return null;

  if (!/^[0-9+\-*/(). ]+$/.test(expr)) return null;

  try {
    const result = Function('"use strict";return (' + expr + ")")();

    if (!isFinite(result)) return null;

    return Math.round(result * 100) / 100;
  } catch (e) {
    return null;
  }
}

function uploadAccDocument() {
  const fileInput = document.getElementById("accDocFile");
  const name = document.getElementById("accDocName").value.trim();
  const link = document.getElementById("accDocLink").value.trim();

  if (!name) {
    Swal.fire("Gagal", "Nama Dokumen wajib diisi.", "error");
    return;
  }

  if (!fileInput.files.length && !link) {
    Swal.fire("Gagal", "Isi salah satu: Upload File atau Link.", "error");
    return;
  }

  const formData = new FormData();
  formData.append("table_id", Acc.currentTableIdCache);
  formData.append("unit_id", Acc.currentUnitId || 0);
  formData.append("document_name", name);
  formData.append("link_url", link);

  if (fileInput.files.length) {
    formData.append("document", fileInput.files[0]);
  }

  Swal.fire({
    title: "Mengunggah...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.ajax({
    url: Acc.api + "?action=upload_document",
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

      Swal.fire({
        icon: "success",
        title: "Berhasil",
        text: response.message,
        timer: 1200,
        showConfirmButton: false,
      });
      loadTable();
    },
    error: function () {
      Swal.close();
      Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
    },
  });
}

function deleteAccDocument(id) {
  Swal.fire({
    icon: "warning",
    title: "Hapus Dokumen ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(
      Acc.api + "?action=delete_document",
      { id: id },
      function (response) {
        if (response.success) {
          loadTable();
        } else {
          Swal.fire("Gagal", response.message, "error");
        }
      },
    );
  });
}

/* ===================== UTIL ===================== */

function formatNum(val) {
  if (val === null || val === undefined || val === "") return "";
  const num = parseFloat(val);
  return isNaN(num) ? "" : num;
}

function formatRupiah(num) {
  if (num === null || num === undefined || num === "") return "";
  const n = Math.round(parseFloat(num) * 100) / 100;
  if (isNaN(n)) return "";
  return "Rp " + n.toLocaleString("id-ID");
}

function parseRupiah(str) {
  if (!str) return "";
  const cleaned = String(str)
    .replace(/[^0-9,-]/g, "")
    .replace(",", ".");
  return cleaned === "" ? "" : cleaned;
}

function getCellNumericValue($el) {
  const raw = $el.val();
  if (raw === "" || raw === undefined) return "";
  if ($el.hasClass("acc-currency-input")) {
    return parseRupiah(raw);
  }
  return raw;
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

function escapeAttr(text) {
  return (text || "").replace(/"/g, "&quot;");
}

function showAjaxError() {
  Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
}
