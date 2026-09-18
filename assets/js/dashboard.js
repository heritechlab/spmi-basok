const dropdown = document.getElementsByClassName("dropdown-btn");

for (let i = 0; i < dropdown.length; i++) {
  dropdown[i].addEventListener("click", function () {
    this.classList.toggle("active");

    let menu = this.nextElementSibling;

    if (menu.style.display === "block") {
      menu.style.display = "none";
    } else {
      menu.style.display = "block";
    }
  });
}

document.addEventListener("DOMContentLoaded", function () {
  if (typeof Chart === "undefined") {
    console.error("Chart.js belum dimuat");
    return;
  }

  const auditCanvas = document.getElementById("auditChart");

  if (auditCanvas) {
    console.log("Chart siap dibuat");
    const auditCanvas = document.getElementById("auditChart");

    if (auditCanvas) {
      const standar = Number(auditCanvas.dataset.standar);
      const audit = Number(auditCanvas.dataset.audit);
      const temuan = Number(auditCanvas.dataset.temuan);
      const rtl = Number(auditCanvas.dataset.rtl);

      console.log({
        standar,
        audit,
        temuan,
        rtl,
      });
      new Chart(auditCanvas, {
        type: "bar",

        data: {
          labels: ["Standar", "Audit", "Temuan", "RTL"],

          datasets: [
            {
              label: "Jumlah",

              data: [standar, audit, temuan, rtl],

              backgroundColor: ["#2563EB", "#16A34A", "#DC2626", "#F59E0B"],

              hoverBackgroundColor: [
                "#1D4ED8",
                "#15803D",
                "#B91C1C",
                "#D97706",
              ],

              borderRadius: 12,

              borderSkipped: false,

              maxBarThickness: 45,
            },
          ],
        },

        options: {
          responsive: true,

          animation: {
            duration: 1800,

            easing: "easeOutQuart",
          },

          plugins: {
            legend: {
              display: false,
            },

            datalabels: {
              color: "#111827",

              anchor: "end",

              align: "top",

              font: {
                size: 14,

                weight: "bold",
              },

              formatter: function (value) {
                return value;
              },
            },

            tooltip: {
              backgroundColor: "#1E3A8A",

              titleColor: "#ffffff",

              bodyColor: "#ffffff",

              cornerRadius: 8,

              padding: 12,

              displayColors: false,

              callbacks: {
                label: function (context) {
                  return "Jumlah : " + context.raw;
                },
              },
            },
          },

          scales: {
            x: {
              grid: {
                display: false,
              },

              ticks: {
                color: "#374151",

                font: {
                  size: 13,

                  weight: "600",
                },
              },
            },

            y: {
              beginAtZero: true,

              ticks: {
                stepSize: 1,

                color: "#6B7280",
              },

              grid: {
                color: "#EEF2F7",

                drawBorder: false,
              },
            },
          },
        },

        plugins: [ChartDataLabels],
      });
    }
  }

  const gaugeCanvas = document.getElementById("statusGauge");

  if (gaugeCanvas) {
    const draft = parseInt(gaugeCanvas.dataset.draft, 10) || 0;
    const berjalan = parseInt(gaugeCanvas.dataset.berjalan, 10) || 0;
    const selesai = parseInt(gaugeCanvas.dataset.selesai, 10) || 0;
    const total = draft + berjalan + selesai;
    const percent = total > 0 ? Math.round((selesai / total) * 100) : 0;

    const ctx = gaugeCanvas.getContext("2d");
    const cx = gaugeCanvas.width / 2;
    const cy = gaugeCanvas.height - 20;
    const radius = 130;

    function drawGauge(animatedPercent) {
      ctx.clearRect(0, 0, gaugeCanvas.width, gaugeCanvas.height);

      const startAngle = Math.PI;
      const endAngle = 2 * Math.PI;

      const zones = [
        { from: 0, to: 0.4, color: "#ef4444" },
        { from: 0.4, to: 0.7, color: "#f59e0b" },
        { from: 0.7, to: 1, color: "#22c55e" },
      ];

      zones.forEach(function (zone) {
        ctx.beginPath();
        ctx.arc(
          cx,
          cy,
          radius,
          startAngle + zone.from * Math.PI,
          startAngle + zone.to * Math.PI,
          false,
        );
        ctx.lineWidth = 24;
        ctx.strokeStyle = zone.color;
        ctx.lineCap = "butt";
        ctx.stroke();
      });

      const needleAngle = startAngle + (animatedPercent / 100) * Math.PI;

      ctx.save();
      ctx.translate(cx, cy);
      ctx.rotate(needleAngle);
      ctx.beginPath();
      ctx.moveTo(-6, 0);
      ctx.lineTo(0, -radius + 30);
      ctx.lineTo(6, 0);
      ctx.closePath();
      ctx.fillStyle = "#1f2937";
      ctx.fill();
      ctx.restore();

      ctx.beginPath();
      ctx.arc(cx, cy, 10, 0, 2 * Math.PI);
      ctx.fillStyle = "#1f2937";
      ctx.fill();

      ctx.font = "bold 26px Arial";
      ctx.fillStyle = "#1f2937";
      ctx.textAlign = "center";
      ctx.fillText(animatedPercent + "%", cx, cy - 50);

      ctx.font = "11px Arial";
      ctx.fillStyle = "#9ca3af";
      ctx.fillText("Periode Selesai", cx, cy - 30);
    }

    let current = 0;
    const step = Math.max(1, Math.ceil(percent / 30));
    const timer = setInterval(function () {
      current += step;
      if (current >= percent) {
        current = percent;
        clearInterval(timer);
      }
      drawGauge(current);
    }, 20);
  }

  const trendCanvas = document.getElementById("trendChart");

  if (trendCanvas) {
    const labels = JSON.parse(trendCanvas.dataset.labels || "[]");
    const scores = JSON.parse(trendCanvas.dataset.scores || "[]");
    const findings = JSON.parse(trendCanvas.dataset.findings || "[]");

    const ctxTrend = trendCanvas.getContext("2d");
    const trendGradient = ctxTrend.createLinearGradient(0, 0, 0, 260);
    trendGradient.addColorStop(0, "rgba(91, 33, 182, 0.30)");
    trendGradient.addColorStop(0.4, "rgba(91, 33, 182, 0.12)");
    trendGradient.addColorStop(1, "rgba(91, 33, 182, 0.00)");

    const trendGlowPlugin = {
      id: "trendGlowPlugin",
      beforeDatasetsDraw(chart) {
        const { ctx } = chart;
        ctx.save();
        ctx.shadowColor = "rgba(91, 33, 182, 0.35)";
        ctx.shadowBlur = 10;
        ctx.shadowOffsetY = 3;
      },
      afterDatasetsDraw(chart) {
        chart.ctx.restore();
      },
    };

    new Chart(trendCanvas, {
      type: "line",
      data: {
        labels: labels,
        datasets: [
          {
            label: "Skor Capaian AMI",
            data: scores,
            borderColor: "#5b21b6",
            backgroundColor: trendGradient,
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            cubicInterpolationMode: "monotone",
            pointBackgroundColor: "#5b21b6",
            pointBorderColor: "#fff",
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8,
            yAxisID: "y",
          },
          {
            label: "Jumlah Temuan",
            data: findings,
            borderColor: "#dc2626",
            backgroundColor: "rgba(220, 38, 38, 0.06)",
            borderWidth: 2,
            borderDash: [5, 4],
            fill: false,
            tension: 0.4,
            pointBackgroundColor: "#dc2626",
            pointBorderColor: "#fff",
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            yAxisID: "y1",
          },
        ],
      },
      options: {
        responsive: true,
        interaction: { mode: "index", intersect: false },
        layout: { padding: { top: 16 } },
        plugins: {
          legend: {
            position: "bottom",
            labels: { usePointStyle: true, boxWidth: 8, font: { size: 11 } },
          },
          tooltip: {
            backgroundColor: "#1f2937",
            padding: 10,
            cornerRadius: 8,
            titleFont: { size: 12, weight: "600" },
            bodyFont: { size: 12 },
          },
        },
        scales: {
          y: {
            type: "linear",
            position: "left",
            min: 0,
            max: 4,
            ticks: { stepSize: 1, font: { size: 11 } },
            title: {
              display: true,
              text: "Skor Capaian (1-4)",
              font: { size: 11 },
            },
            grid: { color: "rgba(0,0,0,0.04)" },
          },
          y1: {
            type: "linear",
            position: "right",
            beginAtZero: true,
            title: { display: true, text: "Jumlah Temuan", font: { size: 11 } },
            grid: { drawOnChartArea: false },
            ticks: { stepSize: 1, font: { size: 11 } },
          },
          x: {
            grid: { display: false },
            ticks: { font: { size: 11 } },
          },
        },
      },
      plugins: [trendGlowPlugin],
    });
  }

  const radarCanvas = document.getElementById("standardRadarChart");

  if (radarCanvas) {
    const labels = JSON.parse(radarCanvas.dataset.labels || "[]");
    const scores = JSON.parse(radarCanvas.dataset.scores || "[]");

    function wrapLabel(text, maxCharsPerLine) {
      const words = text.split(" ");
      const lines = [];
      let currentLine = "";

      words.forEach(function (word) {
        const testLine = currentLine ? currentLine + " " + word : word;
        if (testLine.length > maxCharsPerLine && currentLine) {
          lines.push(currentLine);
          currentLine = word;
        } else {
          currentLine = testLine;
        }
      });

      if (currentLine) {
        lines.push(currentLine);
      }

      return lines;
    }

    const ikuStatusCanvas = document.getElementById("ikuStatusChart");

    if (ikuStatusCanvas) {
      const labels = JSON.parse(ikuStatusCanvas.dataset.labels || "[]");
      const values = JSON.parse(ikuStatusCanvas.dataset.values || "{}");

      const ctx = ikuStatusCanvas.getContext("2d");

      function makeGradient(colorStart, colorEnd) {
        const g = ctx.createLinearGradient(0, 0, 600, 0);
        g.addColorStop(0, colorStart);
        g.addColorStop(1, colorEnd);
        return g;
      }

      new Chart(ikuStatusCanvas, {
        type: "bar",
        data: {
          labels: labels,
          datasets: [
            {
              label: "Menyimpang",
              data: values["Menyimpang"] || [],
              backgroundColor: makeGradient("#f87171", "#dc2626"),
              borderRadius: 6,
              borderSkipped: false,
            },
            {
              label: "Belum Mencapai",
              data: values["Belum Mencapai"] || [],
              backgroundColor: makeGradient("#fbbf24", "#f59e0b"),
              borderRadius: 6,
              borderSkipped: false,
            },
            {
              label: "Mencapai",
              data: values["Mencapai"] || [],
              backgroundColor: makeGradient("#4ade80", "#22c55e"),
              borderRadius: 6,
              borderSkipped: false,
            },
            {
              label: "Melampaui",
              data: values["Melampaui"] || [],
              backgroundColor: makeGradient("#a78bfa", "#7c3aed"),
              borderRadius: 6,
              borderSkipped: false,
            },
          ],
        },
        options: {
          indexAxis: "y",
          responsive: true,
          maintainAspectRatio: false,
          barThickness: 26,
          plugins: {
            legend: {
              position: "bottom",
              labels: {
                boxWidth: 10,
                boxHeight: 10,
                usePointStyle: true,
                pointStyle: "circle",
                font: { size: 11 },
              },
            },
            tooltip: {
              backgroundColor: "#1f2937",
              padding: 10,
              cornerRadius: 8,
              titleFont: { size: 12, weight: "600" },
              bodyFont: { size: 11 },
            },
          },
          scales: {
            x: {
              stacked: true,
              beginAtZero: true,
              ticks: { stepSize: 1, font: { size: 10 }, color: "#9ca3af" },
              grid: { color: "#f1f0f7" },
            },
            y: {
              stacked: true,
              ticks: { font: { size: 12, weight: "600" }, color: "#374151" },
              grid: { display: false },
            },
          },
        },
      });
    }

    const wrappedLabels = labels.map(function (label) {
      return wrapLabel(label, 14);
    });

    const radarPointColors = scores.map(function (s) {
      if (s === null) return "#9ca3af";
      if (s >= 4) return "#22c55e";
      if (s >= 3) return "#84cc16";
      if (s >= 2) return "#f59e0b";
      return "#ef4444";
    });

    const radarGlowPlugin = {
      id: "radarGlowPlugin",
      beforeDatasetsDraw(chart) {
        const { ctx } = chart;
        ctx.save();
        ctx.shadowColor = "rgba(91, 33, 182, 0.35)";
        ctx.shadowBlur = 8;
        ctx.shadowOffsetY = 2;
      },
      afterDatasetsDraw(chart) {
        chart.ctx.restore();
      },
    };

    new Chart(radarCanvas, {
      type: "radar",
      data: {
        labels: wrappedLabels,
        datasets: [
          {
            label: "Skor Capaian",
            data: scores,
            backgroundColor: "rgba(91, 33, 182, 0.10)",
            borderColor: "#5b21b6",
            borderWidth: 2.5,
            pointBackgroundColor: radarPointColors,
            pointBorderColor: "#fff",
            pointBorderWidth: 1.5,
            pointRadius: 5,
            pointHoverRadius: 7,
            spanGaps: true,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 1200, easing: "easeOutQuart" },
        scales: {
          r: {
            min: 0,
            max: 4,
            ticks: {
              stepSize: 1,
              backdropColor: "rgba(255,255,255,0.85)",
              backdropPadding: 3,
              font: { size: 10 },
            },
            grid: { color: "rgba(91, 33, 182, 0.10)" },
            angleLines: { color: "rgba(91, 33, 182, 0.12)" },
            pointLabels: {
              font: { size: 10, weight: "600" },
              color: "#374151",
            },
          },
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: "#1f2937",
            padding: 10,
            cornerRadius: 8,
            titleFont: { size: 12, weight: "600" },
            bodyFont: { size: 12 },
            callbacks: {
              label: function (ctx) {
                return ctx.raw === null ? "Belum ada data" : "Skor: " + ctx.raw;
              },
            },
          },
        },
      },
      plugins: [radarGlowPlugin],
    });
  }
});
