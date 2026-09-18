const PelaporanSiakad = {
  api: SIQUA.BASE_URL + "obe/pelaporan_siakad/api.php",
  unitId: null,
  kurikulumId: null,
  kurikulumCache: [],
  mataKuliahId: null,
  mkCache: [],
  lastData: [],
  lastMkName: "",
};

$(document).ready(function () {
  $("#sikUnitSelector").select2({
    placeholder: "-- Pilih Program Studi --",
    width: "100%",
  });

  loadKurikulumTabs();

  $(document).on("change", "#sikUnitSelector", function () {
    PelaporanSiakad.unitId = $(this).val();
    $("#sikKurikulumTabsWrap").hide();
    $("#sikMkSelectorCard").hide();
    $("#sikTableCard").hide();
    if (PelaporanSiakad.unitId) {
      loadKurikulumTabs();
    }
  });

  $(document).on("change", "#sikMkSelector", function () {
    PelaporanSiakad.mataKuliahId = $(this).val();
    if (PelaporanSiakad.mataKuliahId) {
      loadLaporan();
    } else {
      $("#sikTableCard").hide();
    }
  });

  $("#btnExportSiakad").on("click", function () {
    exportExcel();
  });
});

function loadKurikulumTabs() {
  $.getJSON(
    PelaporanSiakad.api,
    { action: "kurikulum_list", unit_id: PelaporanSiakad.unitId || 0 },
    function (res) {
      if (!res.success) return;

      PelaporanSiakad.kurikulumCache = (res.data || []).filter(
        (k) => k.is_active == 1,
      );

      if (!PelaporanSiakad.kurikulumCache.length) {
        $("#sikKurikulumTabsWrap").hide();
        return;
      }

      let html = "";

      PelaporanSiakad.kurikulumCache.forEach(function (k, idx) {
        html += `
        <li class="nav-item">
          <button type="button" class="nav-link sik-kurikulum-tab ${idx === 0 ? "active" : ""}" data-kurikulum-id="${k.id}">
            ${k.tahun} - ${escapeHtml(k.nama)}
          </button>
        </li>
      `;
      });

      $("#sikKurikulumTabs").html(html);
      $("#sikKurikulumTabsWrap").show();

      selectKurikulumTab(PelaporanSiakad.kurikulumCache[0].id);
    },
  );
}

$(document).on("click", ".sik-kurikulum-tab", function () {
  $(".sik-kurikulum-tab").removeClass("active");
  $(this).addClass("active");
  selectKurikulumTab($(this).data("kurikulum-id"));
});

function selectKurikulumTab(kurikulumId) {
  PelaporanSiakad.kurikulumId = kurikulumId;
  PelaporanSiakad.mataKuliahId = null;
  $("#sikTableCard").hide();

  $.getJSON(
    PelaporanSiakad.api,
    { action: "mata_kuliah_list", kurikulum_id: kurikulumId },
    function (res) {
      if (!res.success) return;

      PelaporanSiakad.mkCache = res.data;

      let html = '<option value="">-- Pilih Mata Kuliah --</option>';
      PelaporanSiakad.mkCache.forEach(function (mk) {
        html += `<option value="${mk.id}">${mk.code ? mk.code + " - " : ""}${escapeHtml(mk.name)}</option>`;
      });

      $("#sikMkSelector").html(html).val("");

      if ($("#sikMkSelector").hasClass("select2-hidden-accessible")) {
        $("#sikMkSelector").select2("destroy");
      }

      $("#sikMkSelector").select2({
        placeholder: "-- Pilih Mata Kuliah --",
        width: "100%",
        language: {
          noResults: function () {
            return "Tidak ditemukan";
          },
          searching: function () {
            return "Mencari...";
          },
        },
      });

      $("#sikMkSelectorCard").show();
    },
  );
}

function warnaNilai(nilai) {
  if (nilai === null) return "#9ca3af";
  if (nilai >= 80) return "#059669";
  if (nilai >= 70) return "#2563eb";
  if (nilai >= 60) return "#d97706";
  return "#dc2626";
}

function loadLaporan() {
  $("#sikTableBody").html(
    '<tr><td colspan="8" class="text-center text-muted">Memuat...</td></tr>',
  );
  $("#sikTableCard").show();

  PelaporanSiakad.lastMkName = $("#sikMkSelector option:selected").text();

  $.getJSON(
    PelaporanSiakad.api,
    {
      action: "laporan",
      mata_kuliah_id: PelaporanSiakad.mataKuliahId,
      kurikulum_id: PelaporanSiakad.kurikulumId,
    },
    function (res) {
      if (!res.success) {
        $("#sikTableBody").html(
          '<tr><td colspan="8" class="text-center text-danger">' +
            res.message +
            "</td></tr>",
        );
        return;
      }

      PelaporanSiakad.lastData = res.data;

      if (!res.data.length) {
        $("#sikTableBody").html(
          '<tr><td colspan="8" class="text-center text-muted">Belum ada data Mahasiswa/Nilai.</td></tr>',
        );
        return;
      }

      const fmt = (v) =>
        v === null
          ? '<span class="text-muted">-</span>'
          : `<span class="nilai-cell" style="color:${warnaNilai(v)};">${v.toFixed(2)}</span>`;

      let html = "";

      res.data.forEach(function (row, idx) {
        html += `
        <tr>
          <td>${idx + 1}</td>
          <td class="col-nim">${escapeHtml(row.nim)}</td>
          <td class="col-nama">${escapeHtml(row.nama)}</td>
          <td>${fmt(row.sikap)}</td>
          <td>${fmt(row.pengetahuan)}</td>
          <td>${fmt(row.ku)}</td>
          <td>${fmt(row.kk)}</td>
          <td class="nilai-total" style="color:${warnaNilai(row.total)};">${row.total !== null ? row.total.toFixed(2) : '<span class="text-muted">-</span>'}</td>
        </tr>
      `;
      });

      $("#sikTableBody").html(html);
    },
  );
}

function exportExcel() {
  if (!PelaporanSiakad.lastData.length) {
    Swal.fire(
      "Belum Ada Data",
      "Pilih Mata Kuliah dengan data terlebih dahulu.",
      "warning",
    );
    return;
  }

  const rows = PelaporanSiakad.lastData.map(function (r, idx) {
    return {
      "No.": idx + 1,
      NIM: r.nim,
      Nama: r.nama,
      Sikap: r.sikap,
      Pengetahuan: r.pengetahuan,
      "Keterampilan Umum": r.ku,
      "Keterampilan Khusus": r.kk,
      "Nilai Akhir": r.total,
    };
  });

  const ws = XLSX.utils.json_to_sheet(rows);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Nilai SIAKAD");
  XLSX.writeFile(
    wb,
    "Nilai_SIAKAD_" +
      PelaporanSiakad.lastMkName.replace(/[^a-z0-9]/gi, "_") +
      ".xlsx",
  );
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}
