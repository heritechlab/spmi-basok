const MataKuliah = {
  api: SIQUA.BASE_URL + "obe/mata_kuliah/api.php",
  unitId: null,
  kurikulumId: null,
  modal: null,
  matriksModal: null,
  cplCache: [],
  matriksDataCache: null,
  dosenCache: [],
};

$(document).ready(function () {
  MataKuliah.modal = new bootstrap.Modal(document.getElementById("mkModal"));
  MataKuliah.matriksModal = new bootstrap.Modal(
    document.getElementById("matriksCplMkModal"),
  );

  if (MK_IS_AUDITEE) {
    loadCplCache();
    loadDosenCache();
    loadKurikulumOptions();
  }

  $(document).on("change", "#mkUnitSelector", function () {
    MataKuliah.unitId = $(this).val();
    $("#mkKurikulumSelector")
      .html('<option value="">-- Pilih Kurikulum --</option>')
      .prop("disabled", true);
    $("#mkListArea").html(
      '<p class="text-center text-muted">Pilih Program Studi dan Kurikulum terlebih dahulu.</p>',
    );

    if (MataKuliah.unitId) {
      loadCplCache();
      loadDosenCache();
      loadKurikulumOptions();
    }
  });

  $(document).on("change", "#mkKurikulumSelector", function () {
    MataKuliah.kurikulumId = $(this).val();
    if (MataKuliah.kurikulumId) {
      loadTable();
    } else {
      $("#mkListArea").html(
        '<p class="text-center text-muted">Pilih Kurikulum terlebih dahulu.</p>',
      );
    }
  });

  $("#btnAddMk").on("click", function () {
    $("#mkForm")[0].reset();
    $("#mk_id").val("");
    $("#mk_kurikulum_id").val(MataKuliah.kurikulumId || "");
    $("#mkModalTitle").text("Tambah Mata Kuliah");
    renderCplChecklist([]);
    renderDosenChecklist([]);
    renderDosenPengembangOptions(null);
    MataKuliah.modal.show();
  });

  $("#btnViewMatriksCplMk").on("click", function () {
    $("#matriksSemesterFilter").val("");
    loadMatriksCplMk();
    MataKuliah.matriksModal.show();
  });

  $(document).on("change", "#matriksSemesterFilter", function () {
    renderMatriksTable($(this).val());
  });

  $("#mkForm").on("submit", function (e) {
    e.preventDefault();
    saveData();
  });
});

function loadKurikulumOptions() {
  $.getJSON(
    MataKuliah.api,
    { action: "kurikulum_list", unit_id: MataKuliah.unitId || 0 },
    function (res) {
      if (!res.success) return;

      let html = '<option value="">-- Pilih Kurikulum --</option>';
      let defaultId = "";

      res.data.forEach(function (k) {
        html += `<option value="${k.id}">${k.tahun} - ${escapeHtml(k.nama)} (${k.jumlah_mk} MK)</option>`;
        if (k.is_active == 1) defaultId = k.id;
      });

      $("#mkKurikulumSelector").html(html).prop("disabled", false);

      if (defaultId) {
        $("#mkKurikulumSelector").val(defaultId).trigger("change");
      }
    },
  );
}

function loadCplCache() {
  $.getJSON(
    MataKuliah.api,
    { action: "cpl_list", unit_id: MataKuliah.unitId || 0 },
    function (res) {
      if (res.success) {
        MataKuliah.cplCache = res.data;
      }
    },
  );
}

function loadDosenCache() {
  $.getJSON(
    MataKuliah.api,
    { action: "dosen_list", unit_id: MataKuliah.unitId || 0 },
    function (res) {
      if (res.success) {
        MataKuliah.dosenCache = res.data;
      }
    },
  );
}

function renderDosenChecklist(selectedDosenList) {
  if (!MataKuliah.dosenCache.length) {
    $("#mkDosenChecklist").html(
      '<p class="text-muted small mb-0">Belum ada Dosen untuk Program Studi ini. Silakan tambahkan dulu di menu Master Dosen Pengampu.</p>',
    );
    return;
  }

  const selectedMap = {};
  (selectedDosenList || []).forEach(function (item) {
    selectedMap[item.id] = item.peran;
  });

  let html = "";

  MataKuliah.dosenCache.forEach(function (d) {
    const isChecked = selectedMap.hasOwnProperty(d.id);
    const isCoord = selectedMap[d.id] === "Koordinator";
    const fullName =
      [d.gelar_depan, d.name].filter(Boolean).join(" ") +
      (d.gelar_belakang ? ", " + d.gelar_belakang : "");

    html += `
      <div class="d-flex align-items-center gap-2 mb-1 dosen-map-row">
        <div class="form-check flex-grow-1 mb-0">
          <input class="form-check-input mk-dosen-checkbox" type="checkbox" value="${d.id}" id="mk_dosen_${d.id}" ${isChecked ? "checked" : ""}>
          <label class="form-check-label small" for="mk_dosen_${d.id}">${escapeHtml(fullName)}</label>
        </div>
        <div class="form-check form-check-inline mb-0">
          <input class="form-check-input mk-dosen-koordinator" type="radio" name="koordinator_radio" value="${d.id}" ${isCoord ? "checked" : ""} ${isChecked ? "" : "disabled"}>
          <label class="form-check-label small">Koordinator</label>
        </div>
      </div>
    `;
  });

  $("#mkDosenChecklist").html(html);
}

$(document).on("change", ".mk-dosen-checkbox", function () {
  const row = $(this).closest(".dosen-map-row");
  const radio = row.find(".mk-dosen-koordinator");
  radio.prop("disabled", !$(this).is(":checked"));
  if (!$(this).is(":checked")) {
    radio.prop("checked", false);
  }
});

function renderDosenPengembangOptions(selectedId) {
  let html = '<option value="">-- Belum Ditentukan --</option>';

  MataKuliah.dosenCache.forEach(function (d) {
    const fullName =
      [d.gelar_depan, d.name].filter(Boolean).join(" ") +
      (d.gelar_belakang ? ", " + d.gelar_belakang : "");
    const selected =
      selectedId && String(selectedId) === String(d.id) ? "selected" : "";
    html += `<option value="${d.id}" ${selected}>${escapeHtml(fullName)}</option>`;
  });

  $("#mk_dosen_pengembang_rps").html(html);
}

function renderCplChecklist(selectedIds) {
  if (!MataKuliah.cplCache.length) {
    $("#mkCplChecklist").html(
      '<p class="text-muted small mb-0">Belum ada CPL untuk Program Studi ini.</p>',
    );
    return;
  }

  let html = "";

  MataKuliah.cplCache.forEach(function (c) {
    const checked =
      selectedIds.includes(c.id) || selectedIds.includes(String(c.id))
        ? "checked"
        : "";
    html += `
      <div class="form-check">
        <input class="form-check-input mk-cpl-checkbox" type="checkbox" value="${c.id}" id="mk_cpl_${c.id}" ${checked}>
        <label class="form-check-label small" for="mk_cpl_${c.id}"><strong>${escapeHtml(c.code)}</strong></label>
      </div>
    `;
  });

  $("#mkCplChecklist").html(html);
}

function loadMatriksCplMk() {
  $("#matriksCplMkTable thead").html("<tr><td>Memuat...</td></tr>");
  $("#matriksCplMkTable tbody").html("");

  $.getJSON(
    MataKuliah.api,
    { action: "matriks", unit_id: MataKuliah.unitId || 0 },
    function (res) {
      if (!res.success) {
        $("#matriksCplMkTable thead").html(
          '<tr><td class="text-danger">' + res.message + "</td></tr>",
        );
        return;
      }

      MataKuliah.matriksDataCache = res.data;

      renderMatriksTable("");
    },
  );
}

function renderMatriksTable(semesterFilter) {
  const data = MataKuliah.matriksDataCache;

  if (!data) return;

  const { cpl, mata_kuliah, map } = data;

  if (!cpl.length || !mata_kuliah.length) {
    $("#matriksCplMkTable thead").html(
      "<tr><td>Belum ada data CPL dan/atau Mata Kuliah yang cukup.</td></tr>",
    );
    $("#matriksCplMkTable tbody").html("");
    return;
  }

  const filteredMk = semesterFilter
    ? mata_kuliah.filter((mk) => String(mk.semester) === String(semesterFilter))
    : mata_kuliah;

  if (!filteredMk.length) {
    $("#matriksCplMkTable thead").html(
      "<tr><td>Tidak ada Mata Kuliah pada Semester ini.</td></tr>",
    );
    $("#matriksCplMkTable tbody").html("");
    return;
  }

  let theadHtml = '<tr><th style="min-width:110px;">CPL</th>';
  filteredMk.forEach(function (mk) {
    theadHtml += `<th style="writing-mode:vertical-rl; text-orientation:mixed; min-width:26px;">${escapeHtml(mk.name)}</th>`;
  });
  theadHtml += "</tr>";

  $("#matriksCplMkTable thead").html(theadHtml);

  let tbodyHtml = "";

  cpl.forEach(function (c) {
    tbodyHtml += `<tr><td><strong>${escapeHtml(c.code)}</strong></td>`;
    filteredMk.forEach(function (mk) {
      const key = c.id + "_" + mk.id;
      const mark = map[key]
        ? '<span style="color:#059669; font-weight:700;">&#10003;</span>'
        : "";
      tbodyHtml += `<td>${mark}</td>`;
    });
    tbodyHtml += "</tr>";
  });

  $("#matriksCplMkTable tbody").html(tbodyHtml);
}

function loadTable() {
  $("#mkListArea").html('<p class="text-center text-muted">Memuat data...</p>');

  $.getJSON(
    MataKuliah.api,
    { action: "list", kurikulum_id: MataKuliah.kurikulumId || 0 },
    function (res) {
      if (!res.success) {
        $("#mkListArea").html(
          '<p class="text-center text-danger">' + res.message + "</p>",
        );
        return;
      }

      if (!res.data.length) {
        $("#mkListArea").html(
          '<p class="text-center text-muted">Belum ada Mata Kuliah.</p>',
        );
        return;
      }

      const bySemester = {};
      res.data.forEach(function (row) {
        const sem = row.semester;
        if (!bySemester[sem]) bySemester[sem] = [];
        bySemester[sem].push(row);
      });

      let html = "";

      Object.keys(bySemester)
        .sort(function (a, b) {
          return a - b;
        })
        .forEach(function (sem) {
          const rows = bySemester[sem];
          const totalSks = rows.reduce(
            (sum, r) =>
              sum +
              parseInt(r.sks_tatap_muka) +
              parseInt(r.sks_praktikum) +
              parseInt(r.sks_praktek_lapangan) +
              parseInt(r.sks_simulasi),
            0,
          );

          html += `
        <h6 class="fw-bold mt-1 mb-1" style="color:#7c3aed;">Semester ${sem} <span class="badge bg-light text-dark border ms-1">${totalSks} SKS</span></h6>
        <table class="table table-bordered table-sm align-middle mb-1" style="font-size:12.5px;">
          <thead class="table-light">
            <tr>
              <th width="110" style="padding:4px 6px;">Kode</th>
              <th style="padding:4px 6px;">Nama Mata Kuliah</th>
              <th width="110" style="padding:4px 6px;">Jenis MK</th>
              <th width="60" class="text-center" style="padding:4px 6px;">T</th>
              <th width="60" class="text-center" style="padding:4px 6px;">P</th>
              <th width="70" class="text-center" style="padding:4px 6px;">Total</th>
              <th width="130" class="text-center" style="padding:4px 6px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
      `;

          rows.forEach(function (row) {
            const total =
              parseInt(row.sks_tatap_muka) +
              parseInt(row.sks_praktikum) +
              parseInt(row.sks_praktek_lapangan) +
              parseInt(row.sks_simulasi);
            const jenisBadgeColor = {
              "Wajib Nasional": "#dc2626",
              "Wajib Institusi": "#d97706",
              "Wajib Prodi": "#2563eb",
              "Pilihan Prodi": "#059669",
            };

            html += `
          <tr>
            <td style="padding:4px 6px;">${row.code ? escapeHtml(row.code) : '<span class="text-muted">-</span>'}</td>
            <td style="padding:4px 6px;">
  ${escapeHtml(row.name)}
  ${row.dosen_list && row.dosen_list.length ? `<div class="text-muted" style="font-size:10.5px;"><i class="bi bi-person-badge"></i> ${row.dosen_list.map((d) => (d.peran === "Koordinator" ? "<strong>" + escapeHtml(d.name) + "</strong>" : escapeHtml(d.name))).join(", ")}</div>` : ""}
</td>
            <td style="padding:4px 6px;"><span class="badge" style="background:${jenisBadgeColor[row.jenis_mk] || "#6b7280"}; font-size:10px;">${escapeHtml(row.jenis_mk)}</span></td>
            <td class="text-center" style="padding:4px 6px;">${row.sks_tatap_muka}</td>
            <td class="text-center" style="padding:4px 6px;">${row.sks_praktikum}</td>
            <td class="text-center" style="padding:4px 6px;"><strong>${total}</strong></td>
            <td class="text-center" style="padding:4px 6px;">
              <button type="button" class="btn btn-sm btn-outline-primary btn-edit-mk" data-id="${row.id}">
                <i class="bi bi-pencil"></i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger btn-delete-mk" data-id="${row.id}">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
        `;
          });

          html += `</tbody></table>`;
        });

      $("#mkListArea").html(html);
    },
  );
}

$(document).on("click", ".btn-edit-mk", function () {
  const id = $(this).data("id");

  $.getJSON(MataKuliah.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    $("#mk_id").val(res.data.id);
    $("#mk_kurikulum_id").val(res.data.kurikulum_id);
    $("#mk_code").val(res.data.code);
    $("#mk_name").val(res.data.name);
    $("#mk_semester").val(res.data.semester);
    $("#mk_jenis").val(res.data.jenis_mk);
    $("#mk_kelompok").val(res.data.kelompok_mk);
    $("#mk_konsentrasi").val(res.data.konsentrasi);
    $("#mk_sks_tatap_muka").val(res.data.sks_tatap_muka);
    $("#mk_sks_praktikum").val(res.data.sks_praktikum);
    $("#mk_sks_praktek_lapangan").val(res.data.sks_praktek_lapangan);
    $("#mk_sks_simulasi").val(res.data.sks_simulasi);
    $("#mk_minimal_nilai").val(res.data.minimal_nilai_lulus);
    $("#mk_rumpun").val(res.data.rumpun_mk);
    renderDosenPengembangOptions(res.data.dosen_pengembang_rps_id);

    // Preserve field-field baru dari tab Detail MK (RPS) - form ini tidak punya UI untuk field ini,
    // jadi dibawa apa adanya lewat hidden input supaya tidak ke-NULL-kan saat simpan.
    $("#mk_tahun_ajaran").val(res.data.tahun_ajaran || "");
    $("#mk_tanggal_revisi_rps").val(res.data.tanggal_revisi_rps || "");
    $("#mk_gkm_dosen_id").val(res.data.gkm_dosen_id || "");
    $("#mk_deskripsi").val(res.data.deskripsi || "");
    $("#mk_media_pembelajaran").val(res.data.media_pembelajaran || "");
    $("#mk_prasyarat_mk").val(res.data.prasyarat_mk || "");
    $("#mk_pustaka_utama").val(res.data.pustaka_utama || "");
    $("#mk_pustaka_pendukung").val(res.data.pustaka_pendukung || "");
    $("#mk_ada_diktat").prop("checked", res.data.ada_diktat == 1);
    $("#mk_ada_silabus").prop("checked", res.data.ada_silabus == 1);
    $("#mk_validasi_rps").val(res.data.validasi_rps);
    $("#mkModalTitle").text("Edit Mata Kuliah");

    renderCplChecklist(res.data.cpl_ids || []);
    renderDosenChecklist(res.data.dosen_list || []);

    MataKuliah.modal.show();
  });
});

$(document).on("click", ".btn-delete-mk", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Hapus Mata Kuliah ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(MataKuliah.api + "?action=delete", { id: id }, function (response) {
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
      loadTable();
    });
  });
});

function saveData() {
  const id = $("#mk_id").val();
  const action = id ? "update" : "create";

  const selectedCplIds = $(".mk-cpl-checkbox:checked")
    .map(function () {
      return $(this).val();
    })
    .get();

  const selectedDosenIds = $(".mk-dosen-checkbox:checked")
    .map(function () {
      return $(this).val();
    })
    .get();

  const koordinatorId = $(".mk-dosen-koordinator:checked").val() || "";

  let formData =
    $("#mkForm").serialize() + "&unit_id=" + (MataKuliah.unitId || 0);

  selectedCplIds.forEach(function (cplId) {
    formData += "&cpl_ids[]=" + cplId;
  });

  selectedDosenIds.forEach(function (dosenId) {
    formData += "&dosen_ids[]=" + dosenId;
  });

  formData += "&koordinator_id=" + koordinatorId;

  $.post(MataKuliah.api + "?action=" + action, formData, function (response) {
    if (!response.success) {
      Swal.fire("Gagal", response.message, "error");
      return;
    }

    MataKuliah.modal.hide();
    Swal.fire({
      icon: "success",
      title: "Berhasil",
      text: response.message,
      timer: 1200,
      showConfirmButton: false,
    });
    loadTable();
  });
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}
