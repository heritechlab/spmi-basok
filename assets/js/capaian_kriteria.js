const CK = {
  api: SIQUA.BASE_URL + "capaian_kriteria/api.php",
  level: "prodi",
  unitId: null,
  periodId: null,
};

$(document).ready(function () {
  registerCkEvents();
});

function registerCkEvents() {
  $("#ckLevelTabs a").on("click", function (e) {
    e.preventDefault();
    $("#ckLevelTabs a").removeClass("active");
    $(this).addClass("active");
    CK.level = $(this).data("level");

    if (CK.level === "institusi") {
      $("#ckUnitWrapper").hide();
    } else {
      $("#ckUnitWrapper").show();
    }

    $("#ckContainer").html("");
    loadCkData();
  });

  if (!CK_IS_AUDITEE) {
    $(document).on("change", "#ckUnitSelector", function () {
      CK.unitId = $(this).val();
      loadCkData();
    });
  }

  $(document).on("change", "#ckPeriodSelector", function () {
    CK.periodId = $(this).val();
    loadCkData();
  });

  $(document).on("click", ".ck-toggle-detail", function () {
    $("#" + $(this).data("target")).slideToggle(150);
  });
}

function loadCkData() {
  if (!CK.periodId) {
    $("#ckContainer").html(
      '<p class="text-muted">Silakan pilih Periode terlebih dahulu.</p>',
    );
    return;
  }

  if (CK.level === "prodi" && !CK_IS_AUDITEE && !CK.unitId) {
    $("#ckContainer").html(
      '<p class="text-muted">Silakan pilih Program Studi terlebih dahulu.</p>',
    );
    return;
  }

  $("#ckContainer").html('<p class="text-muted">Memuat data...</p>');

  if (CK.level === "prodi") {
    $.getJSON(
      CK.api,
      {
        action: "prodi_scores",
        unit_id: CK.unitId || 0,
        period_id: CK.periodId,
      },
      function (res) {
        if (!res.success) {
          $("#ckContainer").html(
            '<p class="text-danger">' + res.message + "</p>",
          );
          return;
        }

        renderProdiCriteria(res.data);
      },
    );
  } else {
    $.getJSON(
      CK.api,
      { action: "institution_scores", period_id: CK.periodId },
      function (res) {
        if (!res.success) {
          $("#ckContainer").html(
            '<p class="text-danger">' + res.message + "</p>",
          );
          return;
        }

        renderInstitutionCriteria(res.data);
      },
    );
  }
}

function ckColorFor(score) {
  if (score === null) return "#d1d5db";
  if (score >= 4) return "#22c55e";
  if (score >= 3) return "#84cc16";
  if (score >= 2) return "#f59e0b";
  return "#ef4444";
}

function ckPolarPoint(cx, cy, r, angleDeg) {
  const rad = (angleDeg * Math.PI) / 180;
  return { x: cx + r * Math.cos(rad), y: cy - r * Math.sin(rad) };
}

function ckArcPath(cx, cy, r, startAngle, endAngle) {
  const start = ckPolarPoint(cx, cy, r, startAngle);
  const end = ckPolarPoint(cx, cy, r, endAngle);
  const largeArc = Math.abs(startAngle - endAngle) > 180 ? 1 : 0;
  return `M ${start.x} ${start.y} A ${r} ${r} 0 ${largeArc} 1 ${end.x} ${end.y}`;
}

function ckZoneInfo(score) {
  if (score === null) return { label: "-", color: "#9ca3af" };

  if (score >= 4) return { label: "Sangat Baik", color: "#22c55e" };
  if (score >= 3) return { label: "Baik", color: "#84cc16" };
  if (score >= 2) return { label: "Kurang Baik", color: "#f59e0b" };
  return { label: "Sangat Kurang Baik", color: "#ef4444" };
}

function ckSpeedometerGauge(score, size) {
  size = size || 220;

  const cx = size / 2;
  const cy = size / 2 + 10;
  const r = size / 2 - 32;
  const strokeWidth = 22;

  const zones = [
    { from: 180, to: 180 - (1 / 3) * 180, color: "#ef4444" },
    { from: 180 - (1 / 3) * 180, to: 180 - (2 / 3) * 180, color: "#f59e0b" },
    { from: 180 - (2 / 3) * 180, to: 0, color: "#84cc16" },
  ];

  let zonesHtml = "";
  let labelsHtml = "";

  zones.forEach(function (z) {
    zonesHtml += `<path d="${ckArcPath(cx, cy, r, z.from, z.to)}" fill="none" stroke="${z.color}" stroke-width="${strokeWidth}" />`;
  });

  const tickValues = [1, 2, 3, 4];
  let ticksHtml = "";

  tickValues.forEach(function (v) {
    const angle = 180 - ((v - 1) / 3) * 180;
    const labelPos = ckPolarPoint(cx, cy, r - strokeWidth / 2 - 14, angle);
    labelsHtml += `<text x="${labelPos.x}" y="${labelPos.y}" text-anchor="middle" dominant-baseline="middle" style="font-size:9px; fill:#9ca3af; font-weight:600;">${v}</text>`;
  });

  const pct = score === null ? 1 : score;
  const needleAngle = 180 - ((pct - 1) / 3) * 180;
  const needleTip = ckPolarPoint(cx, cy, r - strokeWidth / 2 - 6, needleAngle);
  const needleBack = ckPolarPoint(cx, cy, 14, needleAngle + 180);

  const zoneInfo = ckZoneInfo(score);
  const display = score === null ? "-" : score;

  const svgW = size;
  const svgH = size / 2 + 50;

  return `
    <div class="d-flex flex-column align-items-center">
      <svg width="${svgW}" height="${svgH}" viewBox="0 0 ${svgW} ${svgH}">
        ${zonesHtml}
        ${labelsHtml}

        <line x1="${needleBack.x}" y1="${needleBack.y}" x2="${needleTip.x}" y2="${needleTip.y}"
          stroke="${zoneInfo.color}" stroke-width="3.5" stroke-linecap="round"
          style="transition: all 0.8s cubic-bezier(0.4,0,0.2,1); filter: drop-shadow(0 1px 2px rgba(0,0,0,0.25));" />
        <circle cx="${cx}" cy="${cy}" r="7" fill="${zoneInfo.color}" stroke="#fff" stroke-width="2" />
      </svg>
      <div class="mt-1 text-center">
        <div style="font-size:${size * 0.13}px; font-weight:800; color:#3a3348; line-height:1.1;">${display}</div>
        <div style="font-size:11.5px; color:${zoneInfo.color}; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">${zoneInfo.label}</div>
      </div>
    </div>
  `;
}

let ckChartInstance = null;

function renderSpiderAndWarning(labels, scores) {
  $("#ckSpiderWrapper").show();

  const ctx = document.getElementById("ckSpiderChart");

  if (ckChartInstance) {
    ckChartInstance.destroy();
  }

  const pointColors = scores.map((s) => ckColorFor(s));

  ckChartInstance = new Chart(ctx, {
    type: "radar",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Skor Capaian",
          data: scores.map((s) => (s === null ? 0 : s)),
          backgroundColor: "rgba(124, 58, 237, 0.15)",
          borderColor: "#7c3aed",
          borderWidth: 2,
          pointBackgroundColor: pointColors,
          pointBorderColor: "#fff",
          pointBorderWidth: 1.5,
          pointRadius: 5,
          pointHoverRadius: 7,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        r: {
          min: 0,
          max: 4,
          ticks: { stepSize: 1, font: { size: 9 } },
          pointLabels: { font: { size: 10, weight: "600" } },
        },
      },
      plugins: { legend: { display: false } },
    },
  });

  const validScores = [];
  const weakPoints = [];

  labels.forEach(function (label, i) {
    const s = scores[i];
    if (s !== null) {
      validScores.push(s);
      if (s < 2) {
        weakPoints.push({ label, s });
      }
    }
  });

  const avg = validScores.length
    ? Math.round(
        (validScores.reduce((a, b) => a + b, 0) / validScores.length) * 100,
      ) / 100
    : null;

  let warnColor = "#8b8398";
  let warnIcon = "bi-dash-circle";
  let warnText = "Belum ada data capaian yang cukup untuk dianalisis.";

  if (avg !== null) {
    if (weakPoints.length > 0) {
      const names = weakPoints
        .map((w) => w.label + " (" + w.s + ")")
        .join(", ");
      warnColor = "#ef4444";
      warnIcon = "bi-exclamation-triangle-fill";
      warnText = `Rata-rata capaian keseluruhan ${avg}. Perhatian khusus diperlukan pada: ${names} — skor di bawah 2 (Kurang Baik) menandakan area yang belum tercapai dan perlu segera ditindaklanjuti.`;
    } else if (avg < 3) {
      warnColor = "#f59e0b";
      warnIcon = "bi-exclamation-circle-fill";
      warnText = `Rata-rata capaian keseluruhan ${avg}. Cukup baik, namun masih ada ruang peningkatan pada beberapa Kriteria.`;
    } else {
      warnColor = "#22c55e";
      warnIcon = "bi-check-circle-fill";
      warnText = `Rata-rata capaian keseluruhan ${avg}. Sangat baik — seluruh Kriteria berada di atas ambang batas aman.`;
    }
  }

  $("#ckWarningBox").html(`
    <div class="p-3 rounded d-flex align-items-start gap-2" style="background:${warnColor}15; border:1px solid ${warnColor}40;">
      <i class="bi ${warnIcon}" style="color:${warnColor}; font-size:18px; margin-top:1px;"></i>
      <span style="color:#3a3348; font-size:13px;">${warnText}</span>
    </div>
  `);
}

function ckStandardBars(standards) {
  if (!standards || !standards.length) {
    return '<p class="text-muted small mb-0 text-center py-2">Belum ada Standar yang dipetakan ke Kriteria ini.</p>';
  }

  let html = '<div class="mt-2 w-100">';

  standards.forEach(function (s) {
    const score = s.skor;
    const pct = score === null ? 0 : ((score - 1) / 3) * 100;
    const color = ckColorFor(score);
    const zone = ckZoneInfo(score);
    const display = score === null ? "-" : score;

    html += `
      <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded" style="background:#fafafd; border:1px solid #f1f0f8;">
        <div class="flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center"
          style="width:34px; height:34px; background:${color}18; color:${color}; font-weight:800; font-size:11px;">
          ${score === null ? "-" : Math.round(score)}
        </div>
        <div class="flex-grow-1" style="min-width:0;">
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-truncate" style="font-size:12px; font-weight:600; color:#3a3348; max-width:75%;" title="${escapeHtml(s.standard_code + " - " + s.standard_name)}">
              ${escapeHtml(s.standard_code)} - ${escapeHtml(s.standard_name)}
            </span>
            <span class="fw-bold" style="font-size:11.5px; color:${color};">${display}</span>
          </div>
          <div class="mt-1" style="height:6px; background:#eee6fc; border-radius:99px; overflow:hidden;">
            <div style="width:${pct}%; height:100%; background:linear-gradient(90deg, ${color}99, ${color}); border-radius:99px; transition:width 0.6s ease;"></div>
          </div>
        </div>
      </div>
    `;
  });

  html += "</div>";

  return html;
}

function renderProdiCriteria(criteriaList) {
  renderSpiderAndWarning(
    criteriaList.map((c) => c.criteria_name.replace(/^Kriteria \d+\.\s*/, "")),
    criteriaList.map((c) => c.skor),
  );

  let html = "";

  criteriaList.forEach(function (c, idx) {
    html += `
      <div class="col-lg-6">
        <div class="card shadow-sm h-100 ck-criteria-card" style="animation-delay:${idx * 0.05}s;">
          <div class="card-body text-center d-flex flex-column align-items-center">
            <div class="mb-2" style="font-size:15px; font-weight:700; color:#3a3348; min-height:36px;">${escapeHtml(c.criteria_name)}</div>
            ${ckSpeedometerGauge(c.skor, 210)}
            <hr class="w-100">
            <div class="w-100 text-start" style="font-size:12px; font-weight:700; color:#8b8398; text-transform:uppercase; letter-spacing:0.4px;">Rincian Standar</div>
            ${ckStandardBars(c.standards)}
          </div>
        </div>
      </div>
    `;
  });

  $("#ckContainer").html(html);
}

function renderInstitutionCriteria(criteriaList) {
  renderSpiderAndWarning(
    criteriaList.map((c) =>
      c.criteria_name.replace(/^Kriteria [\d.]+\.?\s*/, ""),
    ),
    criteriaList.map((c) => c.skor),
  );

  let html = "";

  criteriaList.forEach(function (c, idx) {
    html += `
      <div class="col-lg-6">
        <div class="card shadow-sm h-100 ck-criteria-card" style="animation-delay:${idx * 0.05}s;">
          <div class="card-body text-center d-flex flex-column align-items-center">
            <div class="mb-2" style="font-size:15px; font-weight:700; color:#3a3348; min-height:36px;">${escapeHtml(c.criteria_name)}</div>
            ${ckSpeedometerGauge(c.skor, 210)}
            <hr class="w-100">
            <div class="w-100 text-start" style="font-size:12px; font-weight:700; color:#8b8398; text-transform:uppercase; letter-spacing:0.4px;">Rincian Standar</div>
            ${ckStandardBars(c.standards)}
          </div>
        </div>
      </div>
    `;
  });

  $("#ckContainer").html(html);
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}
