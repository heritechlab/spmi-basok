const BobotKontribusi = {
  api: SIQUA.BASE_URL + "obe/bobot_kontribusi/api.php",
  unitId: null,
  kurikulumId: null,
  kurikulumCache: [],
  saveQueue: [],
  saveTimer: null,
};

$(document).ready(function () {
  if (BK_IS_AUDITEE) {
    loadKurikulumTabs();
  }

  $(document).on("change", "#bkUnitSelector", function () {
    BobotKontribusi.unitId = $(this).val();
    $("#bkKurikulumTabsWrap").hide();
    $("#bkMatriksCard").hide();
    if (BobotKontribusi.unitId) {
      loadKurikulumTabs();
    }
  });

  $(document).on("blur", ".bobot-input", function () {
    handleBobotChange($(this));
  });

  $(document).on("keydown", ".bobot-input", function (e) {
    if (e.key === "Enter") {
      $(this).blur();
    }
  });

  $("#btnHitungOtomatisBK").on("click", function () {
    hitungOtomatis();
  });

  $("#btnLihatDasarBK").on("click", function () {
    loadDasarPerhitungan();
    new bootstrap.Modal(document.getElementById("bkDasarModal")).show();
  });
});

function loadDasarPerhitungan() {
  $("#bkDasarModalBody").html(
    '<p class="text-center text-muted">Memuat...</p>',
  );

  $.getJSON(
    BobotKontribusi.api,
    { action: "tingkat1_detail", kurikulum_id: BobotKontribusi.kurikulumId },
    function (res) {
      if (!res.success || !res.data.length) {
        $("#bkDasarModalBody").html(
          '<p class="text-center text-muted">Belum ada data RPS untuk Kurikulum ini.</p>',
        );
        return;
      }

      const mkList = [];
      const mkSeen = {};
      const cplList = [];
      const cplSeen = {};

      res.data.forEach(function (row) {
        if (!mkSeen[row.mata_kuliah_id]) {
          mkSeen[row.mata_kuliah_id] = true;
          mkList.push({
            id: row.mata_kuliah_id,
            code: row.mk_code,
            name: row.mk_name,
          });
        }
        if (!cplSeen[row.cpl_id]) {
          cplSeen[row.cpl_id] = true;
          cplList.push({ id: row.cpl_id, code: row.cpl_code });
        }
      });

      const rawMap = {};
      const normMap = {};
      res.data.forEach(function (row) {
        const key = row.mata_kuliah_id + "_" + row.cpl_id;
        rawMap[key] = parseFloat(row.total_bobot);
        normMap[key] = parseFloat(row.persen_normalisasi);
      });

      function buildMatrixTable(valueMap, totalMode) {
        let html =
          '<table class="table table-bordered table-sm" style="font-size:11px;">';
        html += "<thead><tr><th>No</th><th>Mata Kuliah</th>";
        cplList.forEach(function (c) {
          html += `<th class="text-center">${escapeHtml(c.code)}</th>`;
        });
        html +=
          totalMode === "row"
            ? '<th class="text-center">%Total</th></tr></thead><tbody>'
            : '<th class="text-center">Rata-rata</th></tr></thead><tbody>';

        const colTotals = {};
        cplList.forEach((c) => (colTotals[c.id] = 0));

        mkList.forEach(function (mk, idx) {
          html += `<tr><td>${idx + 1}</td><td>${escapeHtml(mk.code)} - ${escapeHtml(mk.name)}</td>`;
          let rowSum = 0;
          let rowCount = 0;

          cplList.forEach(function (c) {
            const key = mk.id + "_" + c.id;
            const val = valueMap[key];
            if (val !== undefined) {
              html += `<td class="text-center">${val.toFixed(1)}</td>`;
              rowSum += val;
              rowCount++;
              colTotals[c.id] += val;
            } else {
              html += `<td class="text-center text-muted">-</td>`;
            }
          });

          if (totalMode === "row") {
            html += `<td class="text-center fw-bold">${rowSum.toFixed(1)}</td></tr>`;
          } else {
            const rata = rowCount > 0 ? rowSum / rowCount : 0;
            html += `<td class="text-center fw-bold">${rata.toFixed(1)}</td></tr>`;
          }
        });

        html += `<tr class="table-light"><td colspan="2" class="text-end fw-bold">TOTAL</td>`;
        let grandTotal = 0;
        cplList.forEach(function (c) {
          html += `<td class="text-center fw-bold">${colTotals[c.id].toFixed(1)}</td>`;
          grandTotal += colTotals[c.id];
        });
        html += `<td class="text-center fw-bold">${grandTotal.toFixed(1)}</td></tr>`;
        html += "</tbody></table>";

        return html;
      }

      let finalHtml = `
        <div class="mb-4">
          <div class="fw-bold mb-1">Tabel 1. Rekapitulasi dari Semua Mata Kuliah sesuai RPS</div>
          <div class="text-muted small mb-2">% Kontribusi CPL terhadap Nilai Akhir Mata Kuliah (total per BARIS = 100%, mentah dari bobot RPS)</div>
          <div class="table-responsive">${buildMatrixTable(rawMap, "row")}</div>
        </div>
        <div>
          <div class="fw-bold mb-1">Tabel 2. Penentuan Persentase Kontribusi terhadap Capaian OBE Prodi</div>
          <div class="text-muted small mb-2">Hasil normalisasi (total per KOLOM/CPL = 100%) &mdash; inilah nilai yang diisi otomatis ke Matriks Bobot</div>
          <div class="table-responsive">${buildMatrixTable(normMap, "col")}</div>
        </div>
      `;

      $("#bkDasarModalBody").html(finalHtml);
    },
  );
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

function hitungOtomatis() {
  Swal.fire({
    icon: "question",
    title: "Hitung Otomatis?",
    text: "Ini akan MENGHAPUS SEMUA data Bobot yang ada untuk Kurikulum ini, lalu menghitung ulang dari nol (dinormalisasi supaya tiap kolom CPL pas 100%). Lanjutkan?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hitung Ulang",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $("#bkSaveStatus").text("Menghitung ulang...");

    $.post(
      BobotKontribusi.api + "?action=hitung_otomatis",
      {
        unit_id: BobotKontribusi.unitId || 0,
        kurikulum_id: BobotKontribusi.kurikulumId,
      },
      function (response) {
        if (response.success) {
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: response.message,
            timer: 1800,
            showConfirmButton: false,
          });
          loadMatriks();
        } else {
          Swal.fire("Gagal", response.message, "error");
        }
      },
    );
  });
}

function loadKurikulumTabs() {
  $.getJSON(
    BobotKontribusi.api,
    { action: "kurikulum_list", unit_id: BobotKontribusi.unitId || 0 },
    function (res) {
      if (!res.success) return;

      BobotKontribusi.kurikulumCache = (res.data || []).filter(
        (k) => k.is_active == 1,
      );

      if (!BobotKontribusi.kurikulumCache.length) {
        $("#bkKurikulumTabsWrap").hide();
        $("#bkMatriksCard").hide();
        return;
      }

      let html = "";

      BobotKontribusi.kurikulumCache.forEach(function (k, idx) {
        html += `
        <li class="nav-item">
          <button type="button" class="nav-link bk-kurikulum-tab ${idx === 0 ? "active" : ""}" data-kurikulum-id="${k.id}">
            ${k.tahun} - ${escapeHtml(k.nama)}
          </button>
        </li>
      `;
      });

      $("#bkKurikulumTabs").html(html);
      $("#bkKurikulumTabsWrap").show();

      selectKurikulumTab(BobotKontribusi.kurikulumCache[0].id);
    },
  );
}

$(document).on("click", ".bk-kurikulum-tab", function () {
  $(".bk-kurikulum-tab").removeClass("active");
  $(this).addClass("active");
  selectKurikulumTab($(this).data("kurikulum-id"));
});

function selectKurikulumTab(kurikulumId) {
  BobotKontribusi.kurikulumId = kurikulumId;
  $("#bkMatriksCard").show();
  loadMatriks();
}

function loadMatriks() {
  $("#bkMatriksTable thead").html("<tr><td>Memuat...</td></tr>");
  $("#bkMatriksTable tbody").html("");

  $.getJSON(
    BobotKontribusi.api,
    {
      action: "matriks",
      unit_id: BobotKontribusi.unitId || 0,
      kurikulum_id: BobotKontribusi.kurikulumId,
    },
    function (res) {
      if (!res.success) {
        $("#bkMatriksTable thead").html(
          '<tr><td class="text-danger">' + res.message + "</td></tr>",
        );
        return;
      }

      const { mata_kuliah, cpl, bobot_map } = res.data;

      if (!mata_kuliah.length) {
        $("#bkMatriksTable thead").html(
          "<tr><td>Belum ada Mata Kuliah untuk Kurikulum ini.</td></tr>",
        );
        return;
      }

      if (!cpl.length) {
        $("#bkMatriksTable thead").html(
          "<tr><td>Belum ada CPL untuk Program Studi ini.</td></tr>",
        );
        return;
      }

      renderMatriks(mata_kuliah, cpl, bobot_map);
    },
  );
}

function renderMatriks(mkList, cplList, bobotMap) {
  let theadHtml =
    '<tr><th style="min-width:36px;">No.</th><th class="col-mk">Mata Kuliah</th>';
  cplList.forEach(function (c) {
    theadHtml += `<th>${escapeHtml(c.code)}</th>`;
  });
  theadHtml += "<th>Total Baris</th></tr>";

  $("#bkMatriksTable thead").html(theadHtml);

  let tbodyHtml = "";

  mkList.forEach(function (mk, idx) {
    let rowTotal = 0;

    tbodyHtml += `<tr><td class="text-center">${idx + 1}</td><td class="col-mk">Sem ${mk.semester} - ${escapeHtml(mk.name)}</td>`;

    cplList.forEach(function (c) {
      const key = mk.id + "_" + c.id;
      const val = bobotMap.hasOwnProperty(key) ? bobotMap[key] : "";
      if (val !== "") rowTotal += parseFloat(val);
      tbodyHtml += `<td><input type="number" class="bobot-input" min="0" max="100" step="0.01" value="${val}" data-mk-id="${mk.id}" data-cpl-id="${c.id}"></td>`;
    });

    tbodyHtml += `<td class="row-total text-center">${rowTotal.toFixed(2)}%</td></tr>`;
  });

  // Baris Total per Kolom
  tbodyHtml += `<tr class="col-total-row"><td></td><td class="col-mk">Total per CPL</td>`;

  let grandTotal = 0;

  cplList.forEach(function (c) {
    let colTotal = 0;

    mkList.forEach(function (mk) {
      const key = mk.id + "_" + c.id;
      if (bobotMap.hasOwnProperty(key)) colTotal += parseFloat(bobotMap[key]);
    });

    grandTotal += colTotal;

    const color =
      Math.abs(colTotal - 100) < 0.01
        ? "#059669"
        : colTotal > 100
          ? "#dc2626"
          : "#d97706";
    tbodyHtml += `<td class="text-center" style="color:${color};" data-col-total="${c.id}">${colTotal.toFixed(2)}%</td>`;
  });

  tbodyHtml += `<td class="text-center" id="bkGrandTotal">${grandTotal.toFixed(2)}%</td></tr>`;

  $("#bkMatriksTable tbody").html(tbodyHtml);
}

function handleBobotChange($input) {
  const val = $input.val().trim();
  const bobot = val === "" ? 0 : parseFloat(val);

  if (isNaN(bobot) || bobot < 0 || bobot > 100) {
    Swal.fire({
      icon: "warning",
      title: "Bobot tidak valid",
      text: "Bobot harus antara 0 - 100.",
      timer: 1500,
      showConfirmButton: false,
    });
    return;
  }

  BobotKontribusi.saveQueue.push({
    mata_kuliah_id: $input.data("mk-id"),
    cpl_id: $input.data("cpl-id"),
    bobot: bobot,
  });

  clearTimeout(BobotKontribusi.saveTimer);
  BobotKontribusi.saveTimer = setTimeout(function () {
    flushSaveQueue();
  }, 500);
}

function flushSaveQueue() {
  if (!BobotKontribusi.saveQueue.length) return;

  const items = BobotKontribusi.saveQueue.splice(
    0,
    BobotKontribusi.saveQueue.length,
  );

  $("#bkSaveStatus").text("Menyimpan...");

  $.post(
    BobotKontribusi.api + "?action=save",
    { items: JSON.stringify(items) },
    function (response) {
      if (response.success) {
        $("#bkSaveStatus").text(
          "Tersimpan " + new Date().toLocaleTimeString("id-ID"),
        );
        loadMatriks();
      } else {
        $("#bkSaveStatus").text("Gagal menyimpan");
      }
    },
  ).fail(function () {
    $("#bkSaveStatus").text("Gagal menyimpan (koneksi)");
  });
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}
