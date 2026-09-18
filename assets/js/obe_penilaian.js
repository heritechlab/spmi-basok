const Penilaian = {
  api: SIQUA.BASE_URL + "obe/penilaian/api.php",
  groups: null,
  unitId: null,
  kurikulumId: null,
  kurikulumCache: [],
  mataKuliahId: null,
  mkCache: [],
  saveQueue: [],
  saveTimer: null,
};

$(document).ready(function () {
  if (!(typeof PNL_EMBED !== "undefined" && PNL_EMBED)) {
    $("#pnlUnitSelector").select2({
      placeholder: "-- Pilih Program Studi --",
      width: "100%",
    });
  }

  if (PNL_IS_AUDITEE) {
    loadKurikulumTabs();
  }

  if (typeof PNL_EMBED !== "undefined" && PNL_EMBED) {
    Penilaian.unitId = PNL_PRESET_UNIT_ID;
    loadKurikulumTabs();
  }

  $(document).on("change", "#pnlUnitSelector", function () {
    Penilaian.unitId = $(this).val();
    resetView();
    if (Penilaian.unitId) {
      loadKurikulumTabs();
    }
  });

  $(document).on("change", "#pnlMkSelector", function () {
    Penilaian.mataKuliahId = $(this).val();
    if (Penilaian.mataKuliahId) {
      loadGrid();
      $("#pnlGridCard").show();
    } else {
      $("#pnlGridCard").hide();
    }
  });

  $(document).on("blur", ".nilai-input", function () {
    handleNilaiChange($(this));
    computeCapaianForRow($(this).closest("tr"));
  });

  $(document).on("keydown", ".nilai-input", function (e) {
    if (e.key === "Enter") {
      $(this).blur();
    }
  });

  $("#btnLihatLaporan").on("click", function () {
    loadLaporan();
    new bootstrap.Modal(document.getElementById("pnlLaporanModal")).show();
  });

  $("#btnLaporanDetail").on("click", function () {
    if (!Penilaian.mataKuliahId) return;
    const url =
      Penilaian.api.replace("api.php", "laporan_detail.php") +
      "?mata_kuliah_id=" +
      encodeURIComponent(Penilaian.mataKuliahId) +
      "&kurikulum_id=" +
      encodeURIComponent(Penilaian.kurikulumId);
    window.open(url, "_blank");
  });
});

function getKategoriKetercapaian(nilai) {
  if (nilai === null)
    return { label: "Belum Ada Data", color: "#6b7280", bgLight: "#f3f4f6" };
  if (nilai > 80)
    return { label: "Sangat Memuaskan", color: "#059669", bgLight: "#eef8f1" };
  if (nilai >= 70)
    return { label: "Memuaskan", color: "#2563eb", bgLight: "#eef4fe" };
  if (nilai >= 60)
    return { label: "Cukup Memuaskan", color: "#d97706", bgLight: "#fef7e8" };

  return { label: "Tidak Memuaskan", color: "#dc2626", bgLight: "#fdecec" };
}

function loadLaporan() {
  $("#pnlLaporanTable thead").html("<tr><td>Memuat...</td></tr>");
  $("#pnlLaporanTable tbody").html("");
  $("#pnlCplSummary").html("");

  $.getJSON(
    Penilaian.api,
    {
      action: "laporan",
      mata_kuliah_id: Penilaian.mataKuliahId,
      kurikulum_id: Penilaian.kurikulumId,
    },
    function (res) {
      if (!res.success) {
        $("#pnlLaporanTable thead").html(
          '<tr><td class="text-danger">' + res.message + "</td></tr>",
        );
        return;
      }

      const { cpl_list, mahasiswa } = res.data;
      const cplIds = Object.keys(cpl_list);

      let summaryHtml = "";

      cplIds.forEach(function (cplId) {
        const scores = mahasiswa
          .map((m) => m.ketercapaian_cpl[cplId])
          .filter((v) => v !== null && v !== undefined);

        const rata = scores.length
          ? scores.reduce((a, b) => a + b, 0) / scores.length
          : null;
        const kategori = getKategoriKetercapaian(rata);

        summaryHtml += `
          <div style="background:${kategori.bgLight}; border-radius:12px; padding:12px 16px; min-width:140px; flex:1;">
            <div style="font-size:11px; font-weight:700; color:#6b6785; text-transform:uppercase; letter-spacing:.3px;">${escapeHtml(cpl_list[cplId])}</div>
            <div style="font-size:22px; font-weight:800; color:${kategori.color};">${rata !== null ? rata.toFixed(1) + "%" : "-"}</div>
            <div style="font-size:10.5px; color:${kategori.color}; font-weight:600;">${kategori.label}</div>
          </div>
        `;
      });

      $("#pnlCplSummary").html(summaryHtml);
      $("#pnlLaporanSubtitle").text(
        mahasiswa.length + " Mahasiswa · " + cplIds.length + " CPL",
      );

      let theadHtml =
        '<tr><th>No.</th><th>NIM</th><th style="text-align:left;">Nama</th>';
      cplIds.forEach(function (cplId) {
        theadHtml += `<th>${escapeHtml(cpl_list[cplId])}</th>`;
      });
      theadHtml += "<th>Nilai Akhir MK</th></tr>";

      $("#pnlLaporanTable thead").html(theadHtml);

      if (!mahasiswa.length) {
        $("#pnlLaporanTable tbody").html(
          `<tr><td colspan="${3 + cplIds.length + 1}" class="text-muted">Belum ada data.</td></tr>`,
        );
        return;
      }

      let tbodyHtml = "";

      mahasiswa.forEach(function (m, idx) {
        tbodyHtml += `<tr><td>${idx + 1}</td><td>${escapeHtml(m.nim)}</td><td style="text-align:left;">${escapeHtml(m.nama)}</td>`;

        cplIds.forEach(function (cplId) {
          const val = m.ketercapaian_cpl[cplId];

          if (val === null || val === undefined) {
            tbodyHtml += `<td><span class="text-muted">-</span></td>`;
          } else {
            const kategori = getKategoriKetercapaian(val);
            tbodyHtml += `<td><span style="color:${kategori.color}; font-weight:700;">${val.toFixed(2)}</span></td>`;
          }
        });

        tbodyHtml += `<td><strong>${m.nilai_akhir_mk.toFixed(2)}</strong></td></tr>`;
      });

      $("#pnlLaporanTable tbody").html(tbodyHtml);
    },
  );
}

function resetView() {
  $("#pnlKurikulumTabsWrap").hide();
  $("#pnlMkSelectorCard").hide();
  $("#pnlGridCard").hide();
}

function loadKurikulumTabs() {
  $.getJSON(
    Penilaian.api,
    { action: "kurikulum_list", unit_id: Penilaian.unitId || 0 },
    function (res) {
      if (!res.success) return;

      Penilaian.kurikulumCache = (res.data || []).filter(
        (k) => k.is_active == 1,
      );

      if (!Penilaian.kurikulumCache.length) {
        $("#pnlKurikulumTabsWrap").hide();
        $("#pnlMkSelectorCard").hide();
        return;
      }

      let html = "";

      Penilaian.kurikulumCache.forEach(function (k, idx) {
        html += `
        <li class="nav-item">
          <button type="button" class="nav-link pnl-kurikulum-tab ${idx === 0 ? "active" : ""}" data-kurikulum-id="${k.id}">
            ${k.tahun} - ${escapeHtml(k.nama)}
          </button>
        </li>
      `;
      });

      $("#pnlKurikulumTabs").html(html);
      $("#pnlKurikulumTabsWrap")
        .toggle(Penilaian.kurikulumCache.length > 1)
        .show();

      const presetKurId =
        typeof PNL_PRESET_KURIKULUM_ID !== "undefined" &&
        PNL_PRESET_KURIKULUM_ID
          ? String(PNL_PRESET_KURIKULUM_ID)
          : null;
      const matchKur = presetKurId
        ? Penilaian.kurikulumCache.find((k) => String(k.id) === presetKurId)
        : null;

      selectKurikulumTab(
        matchKur ? matchKur.id : Penilaian.kurikulumCache[0].id,
      );
    },
  );
}

$(document).on("click", ".pnl-kurikulum-tab", function () {
  $(".pnl-kurikulum-tab").removeClass("active");
  $(this).addClass("active");
  selectKurikulumTab($(this).data("kurikulum-id"));
});

function selectKurikulumTab(kurikulumId) {
  Penilaian.kurikulumId = kurikulumId;
  Penilaian.mataKuliahId = null;
  $("#pnlGridCard").hide();

  $.getJSON(
    Penilaian.api,
    { action: "mata_kuliah_list", kurikulum_id: kurikulumId },
    function (res) {
      if (!res.success) return;

      Penilaian.mkCache = res.data;

      let html = '<option value="">-- Pilih Mata Kuliah --</option>';
      Penilaian.mkCache.forEach(function (mk) {
        html += `<option value="${mk.id}">${mk.code ? mk.code + " - " : ""}${escapeHtml(mk.name)}</option>`;
      });

      $("#pnlMkSelector").html(html).val("");

      if (!(typeof PNL_EMBED !== "undefined" && PNL_EMBED)) {
        if ($("#pnlMkSelector").hasClass("select2-hidden-accessible")) {
          $("#pnlMkSelector").select2("destroy");
        }

        $("#pnlMkSelector").select2({
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
      }

      $("#pnlMkSelectorCard").show();

      if (typeof PNL_PRESET_MK_ID !== "undefined" && PNL_PRESET_MK_ID) {
        const matchMk = Penilaian.mkCache.find(
          (m) => String(m.id) === String(PNL_PRESET_MK_ID),
        );
        if (matchMk) {
          $("#pnlMkSelector").val(matchMk.id).trigger("change");
        }
      }
    },
  );
}

function groupKomponenHierarchy(komponenList) {
  const cplOrder = [];
  const cplMap = {};

  komponenList.forEach(function (k) {
    const cplKey = k.cpl_id ? "cpl_" + k.cpl_id : "cpl_none";
    const cplLabel = k.cpl_code || "Tanpa CPL";
    const cpmkKey = k.cpmk_id
      ? "cpmk_" + k.cpmk_id
      : "cpmk_" + (k.cpmk_code || "none");
    const cpmkLabel = k.cpmk_code || "-";
    const subKey = k.sub_cpmk_id
      ? "sub_" + k.sub_cpmk_id
      : "sub_" + (k.sub_cpmk_code || "none");
    const subLabel = k.sub_cpmk_code || "-";

    if (!cplMap[cplKey]) {
      cplMap[cplKey] = {
        key: cplKey,
        label: cplLabel,
        cpmkOrder: [],
        cpmkMap: {},
      };
      cplOrder.push(cplKey);
    }
    const cplEntry = cplMap[cplKey];

    if (!cplEntry.cpmkMap[cpmkKey]) {
      cplEntry.cpmkMap[cpmkKey] = {
        key: cpmkKey,
        label: cpmkLabel,
        subOrder: [],
        subMap: {},
      };
      cplEntry.cpmkOrder.push(cpmkKey);
    }
    const cpmkEntry = cplEntry.cpmkMap[cpmkKey];

    if (!cpmkEntry.subMap[subKey]) {
      cpmkEntry.subMap[subKey] = { key: subKey, label: subLabel, items: [] };
      cpmkEntry.subOrder.push(subKey);
    }
    cpmkEntry.subMap[subKey].items.push(k);
  });

  return cplOrder.map(function (k) {
    return cplMap[k];
  });
}

function loadGrid() {
  $("#pnlGridTable thead").html("<tr><td>Memuat...</td></tr>");
  $("#pnlGridTable tbody").html("");

  $.getJSON(
    Penilaian.api,
    {
      action: "grid",
      mata_kuliah_id: Penilaian.mataKuliahId,
      kurikulum_id: Penilaian.kurikulumId,
    },
    function (res) {
      if (!res.success) {
        $("#pnlGridTable thead").html(
          '<tr><td class="text-danger">' + res.message + "</td></tr>",
        );
        return;
      }

      const { komponen_list, mahasiswa_list, nilai_map } = res.data;

      if (!komponen_list.length) {
        $("#pnlGridTable thead").html(
          "<tr><td>Belum ada RPS untuk Mata Kuliah ini. Silakan susun RPS dulu.</td></tr>",
        );
        return;
      }

      if (!mahasiswa_list.length) {
        $("#pnlGridTable thead").html(
          "<tr><td>Belum ada Mahasiswa aktif untuk Kurikulum ini. Silakan tambahkan dulu di Master Mahasiswa.</td></tr>",
        );
        return;
      }

      Penilaian.groups = groupKomponenHierarchy(komponen_list);

      // ===== Header 4 tingkat: CPL -> CPMK -> Sub-CPMK -> TM/Komponen =====
      let row1 =
        '<tr><th rowspan="4" style="min-width:36px;">No.</th><th rowspan="4" class="col-nim">NIM</th><th rowspan="4" class="col-nama">Nama</th>';
      let row2 = "<tr>";
      let row3 = "<tr>";
      let row4 = "<tr>";

      Penilaian.groups.forEach(function (cpl) {
        let cplColspan = 0;

        cpl.cpmkOrder.forEach(function (cpmkKey) {
          const cpmk = cpl.cpmkMap[cpmkKey];
          let cpmkColspan = 0;

          cpmk.subOrder.forEach(function (subKey) {
            const sub = cpmk.subMap[subKey];
            const leafCount = sub.items.length;
            cpmkColspan += leafCount + 1;

            row3 += `<th colspan="${leafCount + 1}" style="background:#eef4fe;">${escapeHtml(sub.label)}</th>`;

            sub.items.forEach(function (k) {
              row4 += `<th title="${escapeAttr((k.cpmk_code || "") + " - " + (k.sub_cpmk_code || ""))}">${escapeHtml(k.label)}<br><span style="font-weight:400; font-size:9px;">TM ${k.pertemuan} &middot; ${parseFloat(k.bobot).toFixed(1)}%</span></th>`;
            });
            row4 += `<th style="background:#eef4fe;">Capaian<br>${escapeHtml(sub.label)}</th>`;
          });

          cplColspan += cpmkColspan + 1;
          row2 += `<th colspan="${cpmkColspan + 1}" style="background:#f1edfc;">${escapeHtml(cpmk.label)}</th>`;
          row3 += `<th rowspan="2" style="background:#ecfdf9;">Capaian<br>${escapeHtml(cpmk.label)}</th>`;
        });

        cplColspan += 1;
        row1 += `<th colspan="${cplColspan}" style="background:#e5dcfb;">${escapeHtml(cpl.label)}</th>`;
        row2 += `<th rowspan="3" style="background:#ddebff;">Capaian<br>${escapeHtml(cpl.label)}</th>`;
      });

      row1 += "</tr>";
      row2 += "</tr>";
      row3 += "</tr>";
      row4 += "</tr>";

      $("#pnlGridTable thead").html(row1 + row2 + row3 + row4);

      let tbodyHtml = "";

      mahasiswa_list.forEach(function (m, idx) {
        tbodyHtml += `<tr data-mahasiswa-id="${m.id}"><td class="text-center">${idx + 1}</td><td class="col-nim">${escapeHtml(m.nim)}</td><td class="col-nama">${escapeHtml(m.nama)}</td>`;

        Penilaian.groups.forEach(function (cpl) {
          cpl.cpmkOrder.forEach(function (cpmkKey) {
            const cpmk = cpl.cpmkMap[cpmkKey];

            cpmk.subOrder.forEach(function (subKey) {
              const sub = cpmk.subMap[subKey];

              sub.items.forEach(function (k) {
                const key = k.rps_id + "_" + k.rencana_evaluasi_id + "_" + m.id;
                const nilai = nilai_map.hasOwnProperty(key)
                  ? nilai_map[key]
                  : "";
                tbodyHtml += `<td><input type="number" class="nilai-input ${nilai !== "" ? "saved" : ""}" min="0" max="100" step="0.01" value="${nilai}" data-rps-id="${k.rps_id}" data-re-id="${k.rencana_evaluasi_id}" data-mahasiswa-id="${m.id}" data-cpl-key="${cpl.key}" data-cpmk-key="${cpmk.key}" data-sub-key="${sub.key}"></td>`;
              });

              tbodyHtml += `<td class="capaian-sub text-center fw-bold" data-sub-key="${sub.key}">-</td>`;
            });

            tbodyHtml += `<td class="capaian-cpmk text-center fw-bold" data-cpmk-key="${cpmk.key}">-</td>`;
          });

          tbodyHtml += `<td class="capaian-cpl text-center fw-bold" data-cpl-key="${cpl.key}">-</td>`;
        });

        tbodyHtml += "</tr>";
      });

      $("#pnlGridTable tbody").html(tbodyHtml);

      $("#pnlGridTable tbody tr").each(function () {
        computeCapaianForRow($(this));
      });
    },
  );
}

function computeCapaianForRow($row) {
  if (!Penilaian.groups) return;

  Penilaian.groups.forEach(function (cpl) {
    let cplSumNilaiBobot = 0;
    let cplSumBobot = 0;

    cpl.cpmkOrder.forEach(function (cpmkKey) {
      const cpmk = cpl.cpmkMap[cpmkKey];
      let cpmkSumNilaiBobot = 0;
      let cpmkSumBobot = 0;

      cpmk.subOrder.forEach(function (subKey) {
        const sub = cpmk.subMap[subKey];
        let sumNilaiBobot = 0;
        let sumBobot = 0;

        sub.items.forEach(function (item) {
          const $input = $row.find(
            `.nilai-input[data-rps-id="${item.rps_id}"][data-re-id="${item.rencana_evaluasi_id}"]`,
          );
          const val = parseFloat($input.val());
          const bobot = parseFloat(item.bobot) || 0;

          if (!isNaN(val)) {
            sumNilaiBobot += val * bobot;
            sumBobot += bobot;
          }
        });

        const subScore = sumBobot > 0 ? sumNilaiBobot / sumBobot : null;
        $row
          .find(`.capaian-sub[data-sub-key="${sub.key}"]`)
          .text(subScore !== null ? subScore.toFixed(2) : "-");

        if (subScore !== null) {
          cpmkSumNilaiBobot += subScore * sumBobot;
          cpmkSumBobot += sumBobot;
        }
      });

      const cpmkScore =
        cpmkSumBobot > 0 ? cpmkSumNilaiBobot / cpmkSumBobot : null;
      $row
        .find(`.capaian-cpmk[data-cpmk-key="${cpmk.key}"]`)
        .text(cpmkScore !== null ? cpmkScore.toFixed(2) : "-");

      if (cpmkScore !== null) {
        cplSumNilaiBobot += cpmkScore * cpmkSumBobot;
        cplSumBobot += cpmkSumBobot;
      }
    });

    const cplScore = cplSumBobot > 0 ? cplSumNilaiBobot / cplSumBobot : null;
    $row
      .find(`.capaian-cpl[data-cpl-key="${cpl.key}"]`)
      .text(cplScore !== null ? cplScore.toFixed(2) : "-");
  });
}

function handleNilaiChange($input) {
  const val = $input.val().trim();

  if (val === "") return;

  const nilai = parseFloat(val);

  if (isNaN(nilai) || nilai < 0 || nilai > 100) {
    $input.addClass("is-invalid");
    Swal.fire({
      icon: "warning",
      title: "Nilai tidak valid",
      text: "Nilai harus antara 0 - 100.",
      timer: 1500,
      showConfirmButton: false,
    });
    return;
  }

  $input.removeClass("is-invalid saved").addClass("saving");

  Penilaian.saveQueue.push({
    rps_id: $input.data("rps-id"),
    rencana_evaluasi_id: $input.data("re-id"),
    mahasiswa_id: $input.data("mahasiswa-id"),
    nilai: nilai,
    element: $input,
  });

  clearTimeout(Penilaian.saveTimer);
  Penilaian.saveTimer = setTimeout(flushSaveQueue, 500);
}

function flushSaveQueue() {
  if (!Penilaian.saveQueue.length) return;

  const batch = Penilaian.saveQueue.splice(0, Penilaian.saveQueue.length);
  const items = batch.map((b) => ({
    rps_id: b.rps_id,
    rencana_evaluasi_id: b.rencana_evaluasi_id,
    mahasiswa_id: b.mahasiswa_id,
    nilai: b.nilai,
  }));

  $("#pnlSaveStatus").text("Menyimpan...");

  $.post(
    Penilaian.api + "?action=save",
    { items: JSON.stringify(items) },
    function (response) {
      batch.forEach(function (b) {
        b.element.removeClass("saving").addClass("saved");
      });

      if (response.success) {
        $("#pnlSaveStatus").text(
          "Tersimpan " + new Date().toLocaleTimeString("id-ID"),
        );
      } else {
        $("#pnlSaveStatus").text("Gagal menyimpan");
      }
    },
  ).fail(function () {
    $("#pnlSaveStatus").text("Gagal menyimpan (koneksi)");
  });
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

function escapeAttr(text) {
  return (text || "").replace(/"/g, "&quot;");
}
