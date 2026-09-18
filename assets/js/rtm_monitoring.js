const RtmMonitoring = {
  api: SIQUA.BASE_URL + "rtl/monitoring/api.php",
};

$(document).ready(function () {
  loadOverall();
  loadByUnit();

  $("#filterPeriod").on("change", function () {
    loadOverall();
    loadByUnit();
  });
});

let overallChartInstance = null;

function loadOverall() {
  const periodId = $("#filterPeriod").val();

  $.getJSON(
    RtmMonitoring.api,
    { action: "overall", period_id: periodId },
    function (res) {
      if (!res.success) return;

      const d = res.data;
      const total = d.total || 0;

      $("#overallTotal").text(total);

      if (overallChartInstance) {
        overallChartInstance.destroy();
      }

      overallChartInstance = new Chart(
        document.getElementById("chartOverallStatus"),
        {
          type: "bar",
          data: {
            labels: ["Belum", "Proses", "Selesai"],
            datasets: [
              {
                label: "Jumlah RTL",
                data: [d.belum || 0, d.proses || 0, d.selesai || 0],
                backgroundColor: ["#a78bfa", "#f59e0b", "#22c55e"],
                borderRadius: 6,
                maxBarThickness: 48,
              },
            ],
          },
          options: {
            responsive: false,
            plugins: {
              legend: { display: false },
            },
            scales: {
              y: { beginAtZero: true, ticks: { precision: 0 } },
            },
          },
        },
      );
    },
  );
}

function loadByUnit() {
  const periodId = $("#filterPeriod").val();

  $("#unitTableBody").html(
    '<tr><td colspan="4" class="text-muted">Memuat data...</td></tr>',
  );

  $.getJSON(
    RtmMonitoring.api,
    { action: "by_unit", period_id: periodId },
    function (res) {
      if (!res.success || !res.data.length) {
        $("#unitTableBody").html(
          '<tr><td colspan="4" class="text-muted">Belum ada data RTL untuk ditampilkan.</td></tr>',
        );
        return;
      }

      let html = "";

      res.data.forEach(function (row) {
        const total = parseInt(row.total, 10) || 0;
        const selesai = parseInt(row.selesai, 10) || 0;
        const proses = parseInt(row.proses, 10) || 0;
        const belum = parseInt(row.belum, 10) || 0;

        const verifSesuai = parseInt(row.verif_sesuai, 10) || 0;
        const verifRevisi = parseInt(row.verif_revisi, 10) || 0;
        const verifBelum = parseInt(row.verif_belum, 10) || 0;

        const pctSelesai = total > 0 ? Math.round((selesai / total) * 100) : 0;
        const pctVerifSesuai =
          total > 0 ? Math.round((verifSesuai / total) * 100) : 0;

        const pctProses = total > 0 ? Math.round((proses / total) * 100) : 0;
        const pctBelum = 100 - pctSelesai - pctProses;

        const pctVerifRevisi =
          total > 0 ? Math.round((verifRevisi / total) * 100) : 0;
        const pctVerifBelum = 100 - pctVerifSesuai - pctVerifRevisi;

        html += `<tr>
        <td><strong>${row.unit_name}</strong></td>
        <td class="text-center">${total}</td>
        <td>
          <div class="segbar">
            <div class="segbar-fill" style="width:${pctSelesai}%; background:#059669;"></div>
            <div class="segbar-fill" style="width:${pctProses}%; background:#d97706;"></div>
            <div class="segbar-fill" style="width:${pctBelum}%; background:#e2dff2;"></div>
          </div>
          <div class="segbar-legend">
            <span><i style="background:#059669;"></i>Selesai ${selesai}</span>
            <span><i style="background:#d97706;"></i>Proses ${proses}</span>
            <span><i style="background:#cbc4e8;"></i>Belum ${belum}</span>
          </div>
        </td>
        <td>
          <div class="segbar">
            <div class="segbar-fill" style="width:${pctVerifSesuai}%; background:#2563eb;"></div>
            <div class="segbar-fill" style="width:${pctVerifRevisi}%; background:#dc2626;"></div>
            <div class="segbar-fill" style="width:${pctVerifBelum}%; background:#e2dff2;"></div>
          </div>
          <div class="segbar-legend">
            <span><i style="background:#2563eb;"></i>Sesuai ${verifSesuai}</span>
            <span><i style="background:#dc2626;"></i>Revisi ${verifRevisi}</span>
            <span><i style="background:#cbc4e8;"></i>Belum ${verifBelum}</span>
          </div>
          <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-primary" style="width: ${pctVerifSesuai}%"></div>
          </div>
        </td>
      </tr>`;
      });

      $("#unitTableBody").html(html);
    },
  );
}
