const Mahasiswa = {
  api: SIQUA.BASE_URL + "obe/mahasiswa/api.php",
  unitId: null,
  kurikulumId: null,
  kurikulumCache: [],
  modal: null,
  importModal: null,
  importRows: [],
};

function loadDosenPaOptions(selectedId) {
  $.getJSON(
    SIQUA.BASE_URL + "obe/dosen/api.php",
    { action: "list", unit_id: Mahasiswa.unitId || 0 },
    function (res) {
      let html = '<option value="">-- Belum ditentukan --</option>';
      if (res.success) {
        res.data.forEach(function (d) {
          const label = [d.gelar_depan, d.name, d.gelar_belakang]
            .filter(Boolean)
            .join(" ");
          html += `<option value="${d.id}">${label}</option>`;
        });
      }
      $("#mhs_pa_dosen_id").html(html);
      if (selectedId) {
        $("#mhs_pa_dosen_id").val(selectedId);
      }
    },
  );
}

$(document).ready(function () {
  Mahasiswa.modal = new bootstrap.Modal(document.getElementById("mhsModal"));
  Mahasiswa.importModal = new bootstrap.Modal(
    document.getElementById("mhsImportModal"),
  );

  if (MHS_IS_AUDITEE) {
    loadKurikulumTabs();
  }

  $(document).on("change", "#mhsUnitSelector", function () {
    Mahasiswa.unitId = $(this).val();
    $("#mhsKurikulumTabsWrap").hide();
    $("#mhsListCard").hide();
    if (Mahasiswa.unitId) {
      loadKurikulumTabs();
    }
  });

  $("#btnAddMhs").on("click", function () {
    $("#mhsForm")[0].reset();
    $("#mhs_id").val("");
    $("#mhs_kurikulum_id").val(Mahasiswa.kurikulumId);
    $("#mhsModalTitle").text("Tambah Mahasiswa");
    loadDosenPaOptions();
    Mahasiswa.modal.show();
  });

  $("#mhsForm").on("submit", function (e) {
    e.preventDefault();
    saveData();
  });

  $("#btnImportExcel").on("click", function () {
    $("#mhsImportFile").val("");
    $("#mhsImportPreview").html("");
    $("#btnConfirmImport").hide();
    Mahasiswa.importRows = [];
    Mahasiswa.importModal.show();
  });

  $("#mhsImportFile").on("change", function (e) {
    handleImportFile(e.target.files[0]);
  });

  $("#btnConfirmImport").on("click", function () {
    confirmImport();
  });
});

function loadKurikulumTabs() {
  $.getJSON(
    Mahasiswa.api,
    { action: "kurikulum_list", unit_id: Mahasiswa.unitId || 0 },
    function (res) {
      if (!res.success) return;

      Mahasiswa.kurikulumCache = (res.data || []).filter(
        (k) => k.is_active == 1,
      );

      if (!Mahasiswa.kurikulumCache.length) {
        $("#mhsKurikulumTabsWrap").hide();
        $("#mhsListCard").hide();
        return;
      }

      let html = "";

      Mahasiswa.kurikulumCache.forEach(function (k, idx) {
        html += `
        <li class="nav-item">
          <button type="button" class="nav-link mhs-kurikulum-tab ${idx === 0 ? "active" : ""}" data-kurikulum-id="${k.id}">
            ${k.tahun} - ${escapeHtml(k.nama)}
          </button>
        </li>
      `;
      });

      $("#mhsKurikulumTabs").html(html);
      $("#mhsKurikulumTabsWrap").show();

      selectKurikulumTab(Mahasiswa.kurikulumCache[0].id);
    },
  );
}

$(document).on("click", ".mhs-kurikulum-tab", function () {
  $(".mhs-kurikulum-tab").removeClass("active");
  $(this).addClass("active");
  selectKurikulumTab($(this).data("kurikulum-id"));
});

function selectKurikulumTab(kurikulumId) {
  Mahasiswa.kurikulumId = kurikulumId;
  $("#mhsListCard").show();
  loadTable();
}

function loadTable() {
  $("#mhsTableBody").html(
    '<tr><td colspan="6" class="text-center text-muted">Memuat data...</td></tr>',
  );

  $.getJSON(
    Mahasiswa.api,
    { action: "list", kurikulum_id: Mahasiswa.kurikulumId || 0 },
    function (res) {
      if (!res.success) {
        $("#mhsTableBody").html(
          '<tr><td colspan="6" class="text-center text-danger">' +
            res.message +
            "</td></tr>",
        );
        return;
      }

      $("#mhsCountBadge").text(res.data.length);

      if (!res.data.length) {
        $("#mhsTableBody").html(
          '<tr><td colspan="6" class="text-center text-muted">Belum ada data.</td></tr>',
        );
        return;
      }

      const statusColor = {
        Aktif: "#059669",
        Cuti: "#d97706",
        Lulus: "#2563eb",
        DO: "#dc2626",
        "Non-Aktif": "#6b7280",
      };

      let html = "";

      res.data.forEach(function (row, idx) {
        html += `
        <tr>
          <td>${idx + 1}</td>
          <td>${escapeHtml(row.nim)}</td>
          <td>${escapeHtml(row.nama)}</td>
          <td>${row.angkatan}</td>
          <td><span class="status-badge" style="background:${statusColor[row.status] || "#6b7280"}22; color:${statusColor[row.status] || "#6b7280"};">${escapeHtml(row.status)}</span></td>
          <td>
            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-mhs" data-id="${row.id}">
              <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-mhs" data-id="${row.id}">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
      `;
      });

      $("#mhsTableBody").html(html);
    },
  );
}

$(document).on("click", ".btn-edit-mhs", function () {
  const id = $(this).data("id");

  $.getJSON(Mahasiswa.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    $("#mhs_id").val(res.data.id);
    $("#mhs_kurikulum_id").val(res.data.kurikulum_id);
    $("#mhs_nim").val(res.data.nim);
    $("#mhs_nama").val(res.data.nama);
    $("#mhs_angkatan").val(res.data.angkatan);
    $("#mhs_status").val(res.data.status);
    $("#mhsModalTitle").text("Edit Mahasiswa");

    loadDosenPaOptions(res.data.pa_dosen_id);
    Mahasiswa.modal.show();
  });
});

$(document).on("click", ".btn-delete-mhs", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Hapus Mahasiswa ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(Mahasiswa.api + "?action=delete", { id: id }, function (response) {
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
  const id = $("#mhs_id").val();
  const action = id ? "update" : "create";

  const formData =
    $("#mhsForm").serialize() + "&unit_id=" + (Mahasiswa.unitId || 0);

  $.post(Mahasiswa.api + "?action=" + action, formData, function (response) {
    if (!response.success) {
      Swal.fire("Gagal", response.message, "error");
      return;
    }

    Mahasiswa.modal.hide();
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

function handleImportFile(file) {
  if (!file) return;

  const reader = new FileReader();

  reader.onload = function (e) {
    const data = new Uint8Array(e.target.result);
    const workbook = XLSX.read(data, { type: "array" });
    const sheetName = workbook.SheetNames[0];
    const sheet = workbook.Sheets[sheetName];
    const rows = XLSX.utils.sheet_to_json(sheet, { defval: "" });

    if (!rows.length) {
      $("#mhsImportPreview").html(
        '<p class="text-danger small">File kosong atau tidak terbaca.</p>',
      );
      return;
    }

    const normalizedRows = rows
      .map(function (r) {
        const keys = Object.keys(r);
        const findKey = (name) =>
          keys.find((k) => k.toLowerCase().trim() === name);

        return {
          nim: String(r[findKey("nim")] || "").trim(),
          nama: String(r[findKey("nama")] || "").trim(),
          angkatan: parseInt(r[findKey("angkatan")]) || 0,
          status: String(r[findKey("status")] || "Aktif").trim() || "Aktif",
        };
      })
      .filter((r) => r.nim !== "" || r.nama !== "");

    if (!normalizedRows.length) {
      $("#mhsImportPreview").html(
        '<p class="text-danger small">Tidak ditemukan kolom nim/nama/angkatan yang sesuai. Pastikan header kolom persis: nim, nama, angkatan.</p>',
      );
      return;
    }

    Mahasiswa.importRows = normalizedRows;

    let html = `<p class="small text-muted">Ditemukan <strong>${normalizedRows.length}</strong> baris data. Pratinjau 10 baris pertama:</p>`;
    html += `<table class="table table-bordered table-sm" style="font-size:11.5px;"><thead><tr><th>NIM</th><th>Nama</th><th>Angkatan</th><th>Status</th></tr></thead><tbody>`;

    normalizedRows.slice(0, 10).forEach(function (r) {
      html += `<tr><td>${escapeHtml(r.nim)}</td><td>${escapeHtml(r.nama)}</td><td>${r.angkatan}</td><td>${escapeHtml(r.status)}</td></tr>`;
    });

    html += `</tbody></table>`;

    $("#mhsImportPreview").html(html);
    $("#btnConfirmImport").show();
  };

  reader.readAsArrayBuffer(file);
}

function confirmImport() {
  if (!Mahasiswa.importRows.length) return;

  $("#btnConfirmImport").prop("disabled", true).text("Mengimpor...");

  $.post(
    Mahasiswa.api + "?action=import",
    {
      unit_id: Mahasiswa.unitId || 0,
      kurikulum_id: Mahasiswa.kurikulumId,
      rows: JSON.stringify(Mahasiswa.importRows),
    },
    function (response) {
      $("#btnConfirmImport")
        .prop("disabled", false)
        .html('<i class="bi bi-upload"></i> Import Sekarang');

      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      let msg = response.message;

      if (response.data.failed && response.data.failed.length) {
        msg += `<br><br><small class="text-danger">${response.data.failed.length} baris gagal:<br>${response.data.failed.slice(0, 5).join("<br>")}</small>`;
      }

      Swal.fire({ icon: "success", title: "Impor Selesai", html: msg });
      Mahasiswa.importModal.hide();
      loadTable();
    },
  );
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}
