const Cp = {
  api: SIQUA.BASE_URL + "obe/capaian_pembelajaran/api.php",
  unitId: null,
  kurikulumId: null,
  kurikulumCache: [],
  mataKuliahId: null,
  mkCache: [],
  cpmkCache: [],
  modal: null,
};

$(document).ready(function () {
  Cp.modal = new bootstrap.Modal(document.getElementById("cpModal"));

  if (CP_IS_AUDITEE) {
    loadKurikulumTabs();
  }

  $(document).on("change", "#cpUnitSelector", function () {
    Cp.unitId = $(this).val();
    resetView();
    if (Cp.unitId) {
      loadKurikulumTabs();
    }
  });

  $(document).on("change", "#cpMkSelector", function () {
    Cp.mataKuliahId = $(this).val();
    if (Cp.mataKuliahId) {
      showMkInfo();
      loadCpmkCache();
      loadTable();
      $("#cpListCard").show();
      $("#cpMkInfoCard").show();
    } else {
      $("#cpListCard").hide();
      $("#cpMkInfoCard").hide();
    }
  });

  $("#btnAddCp").on("click", function () {
    $("#cpForm")[0].reset();
    $("#cp_id").val("");
    $("#cp_mata_kuliah_id").val(Cp.mataKuliahId);
    $("#cpModalTitle").text("Tambah Capaian Pembelajaran");
    fillCpmkOptions();
    $("#cp_sub_cpmk_id").html(
      '<option value="">-- Pilih CPMK dulu --</option>',
    );
    $("#cp_bobot_per_evaluasi").val(0);
    Cp.modal.show();
  });

  $(document).on("change", "#cp_cpmk_id", function () {
    loadSubCpmkOptions($(this).val(), null);
  });

  $(document).on("change", "#cp_sub_cpmk_id", function () {
    const bobot = $(this).find("option:selected").data("bobot");
    $("#cp_bobot_per_evaluasi").val(bobot || 0);
  });

  $("#cpForm").on("submit", function (e) {
    e.preventDefault();
    saveData();
  });
});

function resetView() {
  $("#cpKurikulumTabsWrap").hide();
  $("#cpMkSelectorCard").hide();
  $("#cpMkInfoCard").hide();
  $("#cpListCard").hide();
}

function loadKurikulumTabs() {
  $.getJSON(
    Cp.api,
    { action: "kurikulum_list", unit_id: Cp.unitId || 0 },
    function (res) {
      if (!res.success) return;

      Cp.kurikulumCache = (res.data || []).filter((k) => k.is_active == 1);

      if (!Cp.kurikulumCache.length) {
        $("#cpKurikulumTabsWrap").hide();
        $("#cpMkSelectorCard").hide();
        return;
      }

      let html = "";

      Cp.kurikulumCache.forEach(function (k, idx) {
        html += `
        <li class="nav-item">
          <button type="button" class="nav-link cp-kurikulum-tab ${idx === 0 ? "active" : ""}" data-kurikulum-id="${k.id}">
            ${k.tahun} - ${escapeHtml(k.nama)}
          </button>
        </li>
      `;
      });

      $("#cpKurikulumTabs").html(html);
      $("#cpKurikulumTabsWrap").toggle(Cp.kurikulumCache.length > 1);

      selectKurikulumTab(Cp.kurikulumCache[0].id);
    },
  );
}

$(document).on("click", ".cp-kurikulum-tab", function () {
  $(".cp-kurikulum-tab").removeClass("active");
  $(this).addClass("active");
  selectKurikulumTab($(this).data("kurikulum-id"));
});

function selectKurikulumTab(kurikulumId) {
  Cp.kurikulumId = kurikulumId;
  Cp.mataKuliahId = null;
  $("#cpMkInfoCard").hide();
  $("#cpListCard").hide();

  $.getJSON(
    Cp.api,
    { action: "mata_kuliah_list", kurikulum_id: kurikulumId },
    function (res) {
      if (!res.success) return;

      Cp.mkCache = res.data;

      let html = '<option value="">-- Pilih Mata Kuliah --</option>';
      Cp.mkCache.forEach(function (mk) {
        html += `<option value="${mk.id}">${mk.code ? mk.code + " - " : ""}${escapeHtml(mk.name)}</option>`;
      });

      $("#cpMkSelector").html(html).val("");
      $("#cpMkSelectorCard").show();
    },
  );
}

function showMkInfo() {
  const mk = Cp.mkCache.find((m) => String(m.id) === String(Cp.mataKuliahId));
  const kur = Cp.kurikulumCache.find(
    (k) => String(k.id) === String(Cp.kurikulumId),
  );

  if (!mk) return;

  $("#cpInfoTahun").text(kur ? kur.tahun : "-");
  $("#cpInfoKode").text(mk.code || "-");
  $("#cpInfoNama").text(mk.name);

  const totalSks =
    parseInt(mk.sks_tatap_muka || 0) +
    parseInt(mk.sks_praktikum || 0) +
    parseInt(mk.sks_praktek_lapangan || 0) +
    parseInt(mk.sks_simulasi || 0);
  $("#cpInfoSks").text(totalSks);
}

function loadCpmkCache() {
  $.getJSON(
    Cp.api,
    { action: "cpmk_list", mata_kuliah_id: Cp.mataKuliahId },
    function (res) {
      if (res.success) Cp.cpmkCache = res.data;
    },
  );
}

function fillCpmkOptions() {
  let html = '<option value="">-- Pilih CPMK --</option>';
  Cp.cpmkCache.forEach(function (c) {
    html += `<option value="${c.id}">${escapeHtml(c.code)}</option>`;
  });
  $("#cp_cpmk_id").html(html);
}

function loadSubCpmkOptions(cpmkId, selectedId) {
  if (!cpmkId) {
    $("#cp_sub_cpmk_id").html(
      '<option value="">-- Pilih CPMK dulu --</option>',
    );
    return;
  }

  $.getJSON(
    Cp.api,
    { action: "sub_cpmk_list", cpmk_id: cpmkId },
    function (res) {
      let html = '<option value="">-- Tanpa Sub-CPMK --</option>';

      if (res.success) {
        res.data.forEach(function (s) {
          const sel =
            selectedId && String(selectedId) === String(s.id) ? "selected" : "";
          html += `<option value="${s.id}" data-bobot="${s.bobot}" ${sel}>${escapeHtml(s.code)}</option>`;
        });
      }

      $("#cp_sub_cpmk_id").html(html);

      if (selectedId) {
        $("#cp_bobot_per_evaluasi").val(
          $("#cp_sub_cpmk_id option:selected").data("bobot") || 0,
        );
      }
    },
  );
}

function loadTable() {
  $("#cpTableBody").html(
    '<tr><td colspan="8" class="text-center text-muted">Memuat data...</td></tr>',
  );

  $.getJSON(
    Cp.api,
    { action: "list", mata_kuliah_id: Cp.mataKuliahId },
    function (res) {
      if (!res.success) {
        $("#cpTableBody").html(
          '<tr><td colspan="8" class="text-center text-danger">' +
            res.message +
            "</td></tr>",
        );
        return;
      }

      if (!res.data.length) {
        $("#cpTableBody").html(
          '<tr><td colspan="8" class="text-center text-muted">Belum ada data.</td></tr>',
        );
        return;
      }

      let html = "";

      res.data.forEach(function (row) {
        html += `
        <tr>
          <td class="text-center"><span class="badge-pertemuan">${row.pertemuan}</span></td>
          <td>${escapeHtml(row.cpmk_code)}</td>
          <td>${row.sub_cpmk_code ? escapeHtml(row.sub_cpmk_code) : '<span class="text-muted">-</span>'}</td>
          <td>${escapeHtml(row.indikator_penilaian || "-")}</td>
          <td>${escapeHtml(row.bentuk_evaluasi || "-")}</td>
          <td class="text-center">${parseFloat(row.bobot_per_evaluasi).toFixed(2)}%</td>
          <td class="text-center">${parseFloat(row.bobot_penilaian).toFixed(2)}%</td>
          <td>
            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-cp" data-id="${row.id}">
              <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-cp" data-id="${row.id}">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
      `;
      });

      $("#cpTableBody").html(html);
    },
  );
}

$(document).on("click", ".btn-edit-cp", function () {
  const id = $(this).data("id");

  $.getJSON(Cp.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    $("#cp_id").val(res.data.id);
    $("#cp_mata_kuliah_id").val(res.data.mata_kuliah_id);
    $("#cp_pertemuan").val(res.data.pertemuan);
    $("#cp_indikator").val(res.data.indikator_penilaian);
    $("#cp_bentuk_evaluasi").val(res.data.bentuk_evaluasi);
    $("#cp_bobot_penilaian").val(res.data.bobot_penilaian);
    $("#cpModalTitle").text("Edit Capaian Pembelajaran");

    fillCpmkOptions();
    $("#cp_cpmk_id").val(res.data.cpmk_id);
    loadSubCpmkOptions(res.data.cpmk_id, res.data.sub_cpmk_id);

    Cp.modal.show();
  });
});

$(document).on("click", ".btn-delete-cp", function () {
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

    $.post(Cp.api + "?action=delete", { id: id }, function (response) {
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
  const id = $("#cp_id").val();
  const action = id ? "update" : "create";

  const formData = $("#cpForm").serialize();

  $.post(Cp.api + "?action=" + action, formData, function (response) {
    if (!response.success) {
      Swal.fire("Gagal", response.message, "error");
      return;
    }

    Cp.modal.hide();
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
