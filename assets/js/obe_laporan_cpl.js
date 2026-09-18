const LaporanCpl = {
  api: SIQUA.BASE_URL + "obe/laporan_cpl/api.php",
  unitId: null,
  chartInstance: null,
};

$(document).ready(function () {
  $("#lcplUnitSelector").select2({
    placeholder: "-- Pilih Program Studi --",
    width: "100%",
  });

  $("#btnLihatMatriks").on("click", function () {
    if (!LaporanCpl.unitId) {
      Swal.fire(
        "Belum Lengkap",
        "Pilih Program Studi terlebih dahulu.",
        "warning",
      );
      return;
    }
    const url =
      SIQUA.BASE_URL +
      "obe/laporan_cpl/matriks.php" +
      "?unit_id=" +
      encodeURIComponent(LaporanCpl.unitId);
    window.open(url, "_blank");
  });

  $(document).on("change", "#lcplUnitSelector", function () {
    LaporanCpl.unitId = $(this).val();
    $("#lcplContent").hide();
    if (LaporanCpl.unitId) {
      loadLaporan();
    }
  });

  $(document).on("click", ".cpl-detail-toggle", function () {
    const target = $(this).data("target");
    $(this).toggleClass("open");
    $("#" + target).slideToggle(180);
  });
});

function loadLaporan() {
  $("#lcplContent").hide();

  $.getJSON(
    LaporanCpl.api,
    {
      action: "laporan",
      unit_id: LaporanCpl.unitId || 0,
    },
    function (res) {
      if (!res.success) {
        $("#lcplCplList").html(
          '<p class="text-danger">' + res.message + "</p>",
        );
        $("#lcplContent").show();
        return;
      }

      const { cpl_hasil, jumlah_mk, jumlah_mahasiswa, periode_label } =
        res.data;

      $("#lcplHeroTitle").text(periode_label || "Program Studi");

      if (!cpl_hasil.length) {
        $("#lcplCplList").html(
          '<p class="text-muted">Belum ada data CPL untuk Program Studi ini.</p>',
        );
        $("#lcplContent").show();
        return;
      }

      cpl_hasil.forEach(function (item) {
        item.status =
          item.ketercapaian === null
            ? null
            : item.ketercapaian >= 65
              ? "Memenuhi"
              : "Belum Memenuhi";
      });

      renderRadarChart(cpl_hasil);
      renderKpi(cpl_hasil, jumlah_mk, jumlah_mahasiswa);
      renderDetailList(cpl_hasil);

      $("#lcplContent").show();
    },
  );
}

function renderRadarChart(cplHasil) {
  const labels = cplHasil.map((c) => c.cpl_code);
  const data = cplHasil.map((c) =>
    c.ketercapaian !== null ? c.ketercapaian : 0,
  );

  if (LaporanCpl.chartInstance) {
    LaporanCpl.chartInstance.destroy();
  }

  const ctx = document.getElementById("lcplRadarChart").getContext("2d");

  LaporanCpl.chartInstance = new Chart(ctx, {
    type: "radar",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Ketercapaian (%)",
          data: data,
          backgroundColor: "rgba(167, 139, 250, 0.25)",
          borderColor: "#a78bfa",
          borderWidth: 2,
          pointBackgroundColor: "#a78bfa",
          pointBorderColor: "#fff",
          pointBorderWidth: 1.5,
          pointRadius: 4,
          pointHoverRadius: 6,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      animation: { duration: 900, easing: "easeOutQuart" },
      scales: {
        r: {
          min: 0,
          max: 100,
          ticks: {
            stepSize: 25,
            color: "rgba(255,255,255,0.35)",
            backdropColor: "transparent",
            font: { size: 9 },
          },
          grid: { color: "rgba(255,255,255,0.12)" },
          angleLines: { color: "rgba(255,255,255,0.12)" },
          pointLabels: {
            color: "rgba(255,255,255,0.85)",
            font: { size: 12, weight: "600" },
          },
        },
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: "#1e1b3a",
          padding: 10,
          titleFont: { size: 12 },
          bodyFont: { size: 12 },
          callbacks: {
            label: function (ctx) {
              return ctx.raw.toFixed(2) + "%";
            },
          },
        },
      },
    },
  });
}

function renderKpi(cplHasil, jumlahMk, jumlahMahasiswa) {
  const valid = cplHasil.filter((c) => c.ketercapaian !== null);

  const rata = valid.length
    ? valid.reduce((s, c) => s + c.ketercapaian, 0) / valid.length
    : null;
  $("#kpiRataRata").text(rata !== null ? rata.toFixed(1) + "%" : "-");

  if (valid.length) {
    const tertinggi = valid.reduce((a, b) =>
      a.ketercapaian > b.ketercapaian ? a : b,
    );
    const terendah = valid.reduce((a, b) =>
      a.ketercapaian < b.ketercapaian ? a : b,
    );

    $("#kpiTertinggi").text(tertinggi.ketercapaian.toFixed(1) + "%");
    $("#kpiTertinggiCode").text(tertinggi.cpl_code);

    $("#kpiTerendah").text(terendah.ketercapaian.toFixed(1) + "%");
    $("#kpiTerendahCode").text(terendah.cpl_code);
  } else {
    $("#kpiTertinggi, #kpiTerendah").text("-");
    $("#kpiTertinggiCode, #kpiTerendahCode").text("");
  }

  const belumMemenuhi = cplHasil.filter(
    (c) => c.status === "Belum Memenuhi",
  ).length;
  $("#kpiBelumMemenuhi").text(belumMemenuhi);
  $("#kpiTotalCpl").text(cplHasil.length);
}

function renderDetailList(cplHasil) {
  let html = "";

  cplHasil.forEach(function (item, idx) {
    const kategori = getKategoriKetercapaian(item.ketercapaian);
    const detailId = "lcpl-detail-" + idx;
    const isWarning = item.status === "Belum Memenuhi";
    const pct =
      item.ketercapaian !== null ? Math.min(item.ketercapaian, 100) : 0;

    html += `
      <div class="cpl-row-card" style="border-left-color:${kategori.color};">
        ${isWarning ? `<div class="warning-strip"><i class="bi bi-exclamation-triangle-fill"></i> Belum mencapai batas minimal 65% (BC)</div>` : ""}
        <div class="cpl-row-main">
          <div class="cpl-row-code">${escapeHtml(item.cpl_code)}</div>
          <div class="cpl-row-bar-wrap">
            <div class="cpl-row-bar-track">
              <div class="cpl-row-bar-fill" id="bar-${idx}" style="background:linear-gradient(90deg, ${kategori.color}99, ${kategori.color});"></div>
            </div>
          </div>
          <div class="cpl-row-score-wrap">
            <div class="cpl-row-score" style="color:${kategori.color};">${item.ketercapaian !== null ? item.ketercapaian.toFixed(1) + "%" : "-"}</div>
            <div class="cpl-row-score-label">Ketercapaian</div>
          </div>
          <div class="cpl-row-status" style="color:${kategori.color}; background:${kategori.color}18;">${kategori.label}</div>
        </div>
        <span class="cpl-detail-toggle" data-target="${detailId}">
          Rincian per Mata Kuliah <span class="chev">▾</span>
        </span>
        <table class="table table-bordered cpl-detail-table" id="${detailId}">
          <thead><tr><th>Mata Kuliah</th><th class="text-center">Nilai Capaian</th><th class="text-center">Bobot Kontribusi</th></tr></thead>
          <tbody>
    `;

    if (!item.detail_mk.length) {
      html += `<tr><td colspan="3" class="text-center text-muted">Belum ada Mata Kuliah dengan data lengkap.</td></tr>`;
    } else {
      item.detail_mk.forEach(function (d) {
        html += `<tr><td>${escapeHtml(d.mata_kuliah)}</td><td class="text-center">${d.skor.toFixed(2)}</td><td class="text-center">${d.bobot.toFixed(2)}%</td></tr>`;
      });
    }

    html += `</tbody></table></div>`;
  });

  $("#lcplCplList").html(html);

  cplHasil.forEach(function (item, idx) {
    const pct =
      item.ketercapaian !== null ? Math.min(item.ketercapaian, 100) : 0;
    requestAnimationFrame(function () {
      setTimeout(function () {
        $("#bar-" + idx).css("width", pct + "%");
      }, 60 * idx);
    });
  });
}

function getKategoriKetercapaian(nilai) {
  if (nilai === null) return { label: "Belum Ada Data", color: "#6b7280" };
  if (nilai > 80)
    return { label: "A - Sangat Baik / Istimewa", color: "#059669" };
  if (nilai >= 75) return { label: "AB - Baik Sekali", color: "#0d9488" };
  if (nilai >= 70) return { label: "B - Baik", color: "#2563eb" };
  if (nilai >= 65) return { label: "BC - Cukup Baik", color: "#d97706" };
  if (nilai >= 56) return { label: "C - Cukup", color: "#ea580c" };
  if (nilai >= 40) return { label: "D - Kurang", color: "#dc2626" };

  return { label: "E - Gagal / Tidak Lulus", color: "#991b1b" };
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}
