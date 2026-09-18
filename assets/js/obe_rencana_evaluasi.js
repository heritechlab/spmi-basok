const Reo = {
  api: SIQUA.BASE_URL + "obe/rencana_evaluasi/api.php",
  unitId: null,
  kurikulumId: null,
  kurikulumCache: [],
  mataKuliahId: null,
  mkCache: [],
  subCpmkCache: [],
  bentukPenilaianCache: [],
  modal: null,
};

$(document).ready(function () {
  Reo.modal = new bootstrap.Modal(document.getElementById("reoModal"));

  if (REO_IS_AUDITEE) {
    loadKurikulumTabs();
  }

  if (typeof REO_EMBED !== "undefined" && REO_EMBED) {
    Reo.unitId = REO_PRESET_UNIT_ID;
    loadKurikulumTabs();
  }

  loadBentukPenilaianOptions();

  $(document).on("change", "#reoUnitSelector", function () {
    Reo.unitId = $(this).val();
    resetView();
    if (Reo.unitId) {
      loadKurikulumTabs();
    }
  });

  $(document).on("change", "#reoMkSelector", function () {
    Reo.mataKuliahId = $(this).val();
    if (Reo.mataKuliahId) {
      showMkInfo();
      loadSubCpmkCache();
      loadTable();
      $("#reoListCard").show();
      $("#reoMkInfoCard").show();
      $("#reoBatasInfo").show();
    } else {
      $("#reoListCard").hide();
      $("#reoMkInfoCard").hide();
    }
  });

  $("#btnAddReo").on("click", function () {
    $("#reoForm")[0].reset();
    $("#reo_id").val("");
    $("#reo_mata_kuliah_id").val(Reo.mataKuliahId);
    $("#reoModalTitle").text("Tambah Basis Evaluasi");
    renderSubCpmkChecklist([]);
    $(".reo-komponen-checkbox").prop("checked", false);
    $(".reo-komponen-bobot").val("").prop("disabled", true);
    $("#reoSiakadTotalInfo").html("");
    Reo.modal.show();
  });

  $(document).on("change", "#reo_sub_cpmk", function () {
    showSubCpmkDeskripsi();
  });

  $(document).on("change", "#reo_basis", function () {
    const defaultBobot = {
      "Aktivitas Partisipatif": 25,
      "Hasil Proyek": 25,
      Tugas: 10,
      UTS: 20,
      UAS: 20,
    };
    const val = $(this).val();
    if (defaultBobot.hasOwnProperty(val)) {
      $("#reo_bobot").val(defaultBobot[val]);
    }
  });

  $("#reoForm").on("submit", function (e) {
    e.preventDefault();
    saveData();
  });

  $(document).on("change", ".reo-komponen-checkbox", function () {
    const $bobotInput = $(this).closest(".d-flex").find(".reo-komponen-bobot");
    $bobotInput.prop("disabled", !$(this).is(":checked"));
    if (!$(this).is(":checked")) {
      $bobotInput.val("");
    }
    updateSiakadTotalInfo();
  });

  $(document).on("input", ".reo-komponen-bobot, #reo_bobot", function () {
    updateSiakadTotalInfo();
  });

  $(document).on("click", ".panduan-ind-item", function () {
    const targetId = $(this).closest(".panduan-ind-list").data("target");
    const teks = $(this).text().trim();
    const $field = $("#" + targetId);
    const existing = $field.val().trim();

    $field.val(existing ? existing + " " + teks : teks);
  });

  $("#reoPanduanIndikatorModal").on("show.bs.modal", function () {
    const basisAktif = $("#reo_basis").val();
    const tabMap = {
      "Aktivitas Partisipatif": "#pdAktivitas",
      Tugas: "#pdTugas",
      "Hasil Proyek": "#pdProyek",
      Quiz: "#pdQuiz",
      UTS: "#pdUts",
      UAS: "#pdUas",
    };
    const target = tabMap[basisAktif];
    if (target) {
      const tabEl = document.querySelector(
        `#panduanBasisTabs button[data-bs-target="${target}"]`,
      );
      if (tabEl) new bootstrap.Tab(tabEl).show();
    }
  });
});

function loadBentukPenilaianOptions() {
  $.getJSON(Reo.api, { action: "bentuk_penilaian_list" }, function (res) {
    if (!res.success) return;

    Reo.bentukPenilaianCache = res.data;

    let html = '<option value="">-- Pilih Basis Evaluasi --</option>';
    res.data.forEach(function (b) {
      html += `<option value="${escapeAttr(b.nama_bentuk)}">${escapeHtml(b.nama_bentuk)}</option>`;
    });

    $("#reo_basis").html(html);
  });
}

function loadIndikatorChecklist(bentuk, selectedIds) {
  if (!bentuk) {
    $("#reoIndikatorChecklist").html(
      '<p class="text-muted small mb-0">Pilih Basis Evaluasi terlebih dahulu.</p>',
    );
    return;
  }

  $("#reoIndikatorChecklist").html(
    '<p class="text-muted small mb-0">Memuat...</p>',
  );

  $.getJSON(
    Reo.api,
    { action: "indikator_by_bentuk", bentuk: bentuk },
    function (res) {
      if (!res.success || !res.data.length) {
        $("#reoIndikatorChecklist").html(
          '<p class="text-muted small mb-0">Belum ada Indikator untuk Basis Evaluasi ini. Silakan tambahkan dulu di Master Indikator & Kriteria Penilaian.</p>',
        );
        return;
      }

      let html = "";

      res.data.forEach(function (item) {
        const checked =
          selectedIds.includes(item.id) || selectedIds.includes(String(item.id))
            ? "checked"
            : "";
        html += `
        <div class="form-check">
          <input class="form-check-input reo-indikator-checkbox" type="checkbox" value="${item.id}" id="reo_ind_${item.id}" ${checked}>
          <label class="form-check-label small" for="reo_ind_${item.id}">
            <span class="badge bg-light text-dark border me-1" style="font-size:9.5px;">${escapeHtml(item.taksonomi_ranah)} - ${escapeHtml(item.taksonomi_jenjang)}</span>
            ${escapeHtml(item.indikator)}
          </label>
        </div>
      `;
      });

      $("#reoIndikatorChecklist").html(html);
    },
  );
}

function resetView() {
  $("#reoKurikulumTabsWrap").hide();
  $("#reoMkSelectorCard").hide();
  $("#reoMkInfoCard").hide();
  $("#reoListCard").hide();
}

function loadKurikulumTabs() {
  $.getJSON(
    Reo.api,
    { action: "kurikulum_list", unit_id: Reo.unitId || 0 },
    function (res) {
      if (!res.success) return;

      Reo.kurikulumCache = (res.data || []).filter((k) => k.is_active == 1);

      if (!Reo.kurikulumCache.length) {
        $("#reoKurikulumTabsWrap").hide();
        $("#reoMkSelectorCard").hide();
        return;
      }

      let html = "";

      Reo.kurikulumCache.forEach(function (k, idx) {
        html += `
        <li class="nav-item">
          <button type="button" class="nav-link reo-kurikulum-tab ${idx === 0 ? "active" : ""}" data-kurikulum-id="${k.id}">
            ${k.tahun} - ${escapeHtml(k.nama)}
          </button>
        </li>
      `;
      });

      $("#reoKurikulumTabs").html(html);
      $("#reoKurikulumTabsWrap").toggle(Reo.kurikulumCache.length > 1);

      const presetKurId =
        typeof REO_PRESET_KURIKULUM_ID !== "undefined" &&
        REO_PRESET_KURIKULUM_ID
          ? String(REO_PRESET_KURIKULUM_ID)
          : null;
      const matchKur = presetKurId
        ? Reo.kurikulumCache.find((k) => String(k.id) === presetKurId)
        : null;
      selectKurikulumTab(matchKur ? matchKur.id : Reo.kurikulumCache[0].id);
    },
  );
}

$(document).on("click", ".reo-kurikulum-tab", function () {
  $(".reo-kurikulum-tab").removeClass("active");
  $(this).addClass("active");
  selectKurikulumTab($(this).data("kurikulum-id"));
});

function selectKurikulumTab(kurikulumId) {
  Reo.kurikulumId = kurikulumId;
  Reo.mataKuliahId = null;
  $("#reoMkInfoCard").hide();
  $("#reoListCard").hide();

  $.getJSON(
    Reo.api,
    { action: "mata_kuliah_list", kurikulum_id: kurikulumId },
    function (res) {
      if (!res.success) return;

      Reo.mkCache = res.data;

      let html = '<option value="">-- Pilih Mata Kuliah --</option>';
      Reo.mkCache.forEach(function (mk) {
        html += `<option value="${mk.id}">${mk.code ? mk.code + " - " : ""}${escapeHtml(mk.name)}</option>`;
      });

      $("#reoMkSelector").html(html).val("");
      $("#reoMkSelectorCard").show();
      if (typeof REO_PRESET_MK_ID !== "undefined" && REO_PRESET_MK_ID) {
        const matchMk = Reo.mkCache.find(
          (m) => String(m.id) === String(REO_PRESET_MK_ID),
        );
        if (matchMk) {
          $("#reoMkSelector").val(matchMk.id).trigger("change");
        }
      }
    },
  );
}

function loadSubCpmkCache() {
  $.getJSON(
    Reo.api,
    { action: "sub_cpmk_list", mata_kuliah_id: Reo.mataKuliahId },
    function (res) {
      if (res.success) Reo.subCpmkCache = res.data;
    },
  );
}

function renderSubCpmkChecklist(selectedIds) {
  let html = '<option value="">-- Pilih Sub-CPMK --</option>';

  Reo.subCpmkCache.forEach(function (s) {
    const selected =
      selectedIds.includes(s.id) || selectedIds.includes(String(s.id))
        ? "selected"
        : "";
    html += `<option value="${s.id}" data-deskripsi="${escapeAttr(s.description || "")}" ${selected}>${escapeHtml(s.cpmk_code)} - ${escapeHtml(s.code)}</option>`;
  });

  $("#reo_sub_cpmk").html(html);
  showSubCpmkDeskripsi();
}

function showSubCpmkDeskripsi() {
  const deskripsi = $("#reo_sub_cpmk option:selected").data("deskripsi");

  if (deskripsi && deskripsi.trim() !== "") {
    $("#reoSubCpmkDeskripsi").text(deskripsi).show();
  } else {
    $("#reoSubCpmkDeskripsi").hide();
  }
}

function showMkInfo() {
  const mk = Reo.mkCache.find((m) => String(m.id) === String(Reo.mataKuliahId));
  const kur = Reo.kurikulumCache.find(
    (k) => String(k.id) === String(Reo.kurikulumId),
  );

  if (!mk) return;

  $("#reoInfoTahun").text(kur ? kur.tahun : "-");
  $("#reoInfoKode").text(mk.code || "-");
  $("#reoInfoNama").text(mk.name);

  const totalSks =
    parseInt(mk.sks_tatap_muka || 0) +
    parseInt(mk.sks_praktikum || 0) +
    parseInt(mk.sks_praktek_lapangan || 0) +
    parseInt(mk.sks_simulasi || 0);
  $("#reoInfoSks").text(totalSks);
}

function loadTable() {
  $("#reoTableBody").html(
    '<tr><td colspan="7" class="text-center text-muted">Memuat data...</td></tr>',
  );

  $.getJSON(
    Reo.api,
    { action: "list", mata_kuliah_id: Reo.mataKuliahId },
    function (res) {
      if (!res.success) {
        $("#reoTableBody").html(
          '<tr><td colspan="7" class="text-center text-danger">' +
            res.message +
            "</td></tr>",
        );
        return;
      }

      let totalBobot = 0;

      if (!res.data.length) {
        $("#reoTableBody").html(
          '<tr><td colspan="7" class="text-center text-muted">Belum ada data.</td></tr>',
        );
      } else {
        let html = "";

        res.data.forEach(function (row, idx) {
          totalBobot += parseFloat(row.bobot_persen || 0);

          const subCpmkBadges = (row.sub_cpmk_list || [])
            .map(
              (s) =>
                `<span class="badge bg-light text-dark border me-1 mb-1" style="font-size:10px;">${escapeHtml(s.code)}</span>`,
            )
            .join("");

          html += `
          <tr>
            <td class="text-center">${idx + 1}</td>
            <td class="text-center"><span class="badge bg-light text-dark border">${row.pertemuan ? "Mg " + row.pertemuan : "-"}</span></td>
            <td>${escapeHtml(row.basis_evaluasi)}</td>
            <td>${row.komponen_siakad ? escapeHtml(formatKomponenSiakad(row.komponen_siakad)) : '<span class="text-muted">-</span>'}</td>
            <td>${subCpmkBadges || '<span class="text-muted small">-</span>'}</td>
            <td class="text-center">${parseFloat(row.bobot_persen).toFixed(2)}%</td>
            <td>
              <button type="button" class="btn btn-sm btn-outline-primary btn-edit-reo" data-id="${row.id}">
                <i class="bi bi-pencil"></i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger btn-delete-reo" data-id="${row.id}">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
        `;
        });

        $("#reoTableBody").html(html);
      }

      const color =
        Math.abs(totalBobot - 100) < 0.01
          ? "#059669"
          : totalBobot > 100
            ? "#dc2626"
            : "#d97706";
      $("#reoTotalBobotBadge")
        .css("background", color)
        .text("Total: " + totalBobot.toFixed(2) + "%");
    },
  );
}

$(document).on("click", ".btn-edit-reo", function () {
  const id = $(this).data("id");

  $.getJSON(Reo.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    $("#reo_id").val(res.data.id);
    $("#reo_mata_kuliah_id").val(res.data.mata_kuliah_id);
    $("#reo_pertemuan").val(res.data.pertemuan);
    $("#reo_basis").val(res.data.basis_evaluasi);
    $(".reo-komponen-checkbox").prop("checked", false);
    $(".reo-komponen-bobot").val("").prop("disabled", true);
    (res.data.siakad_bobot_list || []).forEach(function (item) {
      const $cb = $(`.reo-komponen-checkbox[value="${item.komponen_siakad}"]`);
      $cb.prop("checked", true);
      $cb
        .closest(".d-flex")
        .find(".reo-komponen-bobot")
        .val(item.bobot_persen)
        .prop("disabled", false);
    });
    updateSiakadTotalInfo();
    $("#reoModalTitle").text("Edit Basis Evaluasi");

    renderSubCpmkChecklist(res.data.sub_cpmk_ids || []);
    $("#reo_indikator_kognitif").val(res.data.indikator_kognitif || "");
    $("#reo_indikator_afektif").val(res.data.indikator_afektif || "");
    $("#reo_indikator_psikomotorik").val(res.data.indikator_psikomotorik || "");

    Reo.modal.show();
  });
});

$(document).on("click", ".btn-delete-reo", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Hapus data ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(Reo.api + "?action=delete", { id: id }, function (response) {
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
  const id = $("#reo_id").val();
  const action = id ? "update" : "create";

  const subCpmkVal = $("#reo_sub_cpmk").val();
  const selectedSubCpmkIds = subCpmkVal ? [subCpmkVal] : [];

  const selectedKomponen = $(".reo-komponen-checkbox:checked")
    .map(function () {
      return $(this).val();
    })
    .get();

  let formData =
    $("#reoForm").serialize() +
    "&komponen_siakad=" +
    encodeURIComponent(selectedKomponen.join(","));

  selectedSubCpmkIds.forEach(function (subId) {
    formData += "&sub_cpmk_ids[]=" + subId;
  });

  $(".reo-komponen-checkbox:checked").each(function () {
    const komponen = $(this).val();
    const bobot =
      $(this).closest(".d-flex").find(".reo-komponen-bobot").val() || 0;
    formData += "&siakad_komponen[]=" + encodeURIComponent(komponen);
    formData += "&siakad_bobot[]=" + encodeURIComponent(bobot);
  });

  $.post(Reo.api + "?action=" + action, formData, function (response) {
    if (!response.success) {
      Swal.fire("Gagal", response.message, "error");
      return;
    }

    Reo.modal.hide();
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

function formatKomponenSiakad(text) {
  return (text || "")
    .split(",")
    .map(function (part) {
      return part
        .trim()
        .toLowerCase()
        .replace(/\b\w/g, function (c) {
          return c.toUpperCase();
        });
    })
    .join(", ");
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

function escapeAttr(text) {
  return (text || "").replace(/"/g, "&quot;");
}

function updateSiakadTotalInfo() {
  let total = 0;
  $(".reo-komponen-checkbox:checked").each(function () {
    const bobot =
      parseFloat(
        $(this).closest(".d-flex").find(".reo-komponen-bobot").val(),
      ) || 0;
    total += bobot;
  });

  const bobotInti = parseFloat($("#reo_bobot").val()) || 0;
  const selisih = Math.abs(total - bobotInti);

  let html = `Total rincian: <strong>${total.toFixed(2)}%</strong> (harus = Bobot inti <strong>${bobotInti.toFixed(2)}%</strong>)`;

  if (selisih < 0.01) {
    $("#reoSiakadTotalInfo").html(
      `<span class="text-success"><i class="bi bi-check-circle-fill"></i> ${html}</span>`,
    );
  } else {
    $("#reoSiakadTotalInfo").html(
      `<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> ${html} — belum cocok</span>`,
    );
  }
}
