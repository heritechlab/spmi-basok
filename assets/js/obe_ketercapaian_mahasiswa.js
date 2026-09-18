const KetMhs = {
  api: SIQUA.BASE_URL + "obe/ketercapaian_mahasiswa/api.php",
  unitId: null,
  kurikulumId: null,
  kurikulumCache: [],
  currentMhsId: null,
  detailModal: null,
};

$(document).ready(function () {
  KetMhs.detailModal = new bootstrap.Modal(
    document.getElementById("kmDetailModal"),
  );

  $("#kmUnitSelector").select2({
    placeholder: "-- Pilih Program Studi --",
    width: "100%",
  });

  loadKurikulumTabs();

  $(document).on("click", ".btn-detail-pertemuan", function () {
    const mkId = $(this).data("mk-id");
    const mkName = $(this).data("mk-name");
    loadDetailPertemuan(mkId, mkName);
  });

  $(document).on("change", "#kmUnitSelector", function () {
    KetMhs.unitId = $(this).val();
    $("#kmKurikulumTabsWrap").hide();
    $("#kmMhsSelectorCard").hide();
    $("#kmContent").hide();
    if (KetMhs.unitId) {
      loadKurikulumTabs();
    }
  });

  $(document).on("change", "#kmMhsSelector", function () {
    const mhsId = $(this).val();
    KetMhs.currentMhsId = mhsId;
    if (mhsId) {
      loadLaporan(mhsId);
    } else {
      $("#kmContent").hide();
    }
  });

  $(document).on("click", ".cpl-detail-toggle", function () {
    const target = $(this).data("target");
    $(this).toggleClass("open");
    $("#" + target).slideToggle(180);
  });
});

function loadKurikulumTabs() {
  $.getJSON(
    KetMhs.api,
    { action: "kurikulum_list", unit_id: KetMhs.unitId || 0 },
    function (res) {
      if (!res.success) return;

      KetMhs.kurikulumCache = (res.data || []).filter((k) => k.is_active == 1);

      if (!KetMhs.kurikulumCache.length) {
        $("#kmKurikulumTabsWrap").hide();
        return;
      }

      let html = "";

      KetMhs.kurikulumCache.forEach(function (k, idx) {
        html += `
        <li class="nav-item">
          <button type="button" class="nav-link km-kurikulum-tab ${idx === 0 ? "active" : ""}" data-kurikulum-id="${k.id}">
            ${k.tahun} - ${escapeHtml(k.nama)}
          </button>
        </li>
      `;
      });

      $("#kmKurikulumTabs").html(html);
      $("#kmKurikulumTabsWrap").show();

      selectKurikulumTab(KetMhs.kurikulumCache[0].id);
    },
  );
}

$(document).on("click", ".km-kurikulum-tab", function () {
  $(".km-kurikulum-tab").removeClass("active");
  $(this).addClass("active");
  selectKurikulumTab($(this).data("kurikulum-id"));
});

function selectKurikulumTab(kurikulumId) {
  KetMhs.kurikulumId = kurikulumId;
  $("#kmContent").hide();

  $.getJSON(
    KetMhs.api,
    { action: "mahasiswa_list", kurikulum_id: kurikulumId },
    function (res) {
      if (!res.success) return;

      let html = '<option value="">-- Pilih Mahasiswa --</option>';

      res.data.forEach(function (m) {
        html += `<option value="${m.id}">${escapeHtml(m.nim)} - ${escapeHtml(m.nama)}</option>`;
      });

      $("#kmMhsSelector").html(html).val("");

      if ($("#kmMhsSelector").hasClass("select2-hidden-accessible")) {
        $("#kmMhsSelector").select2("destroy");
      }

      $("#kmMhsSelector").select2({
        placeholder: "-- Pilih Mahasiswa --",
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

      $("#kmMhsSelectorCard").show();
    },
  );
}

function loadLaporan(mahasiswaId) {
  const selectedText = $("#kmMhsSelector option:selected").text();
  const [nimPart, ...namaParts] = selectedText.split(" - ");
  const namaMhs = namaParts.join(" - ") || selectedText;
  const inisial = namaMhs.trim().charAt(0).toUpperCase() || "?";

  $("#kmProfileBar").html(`
    <div class="mhs-avatar">${inisial}</div>
    <div>
      <div class="mhs-name">${escapeHtml(namaMhs)}</div>
      <div class="mhs-nim">NIM ${escapeHtml(nimPart)}</div>
    </div>
    <div class="dropdown" style="margin-left:auto;">
      <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
        <i class="bi bi-folder2-open"></i> Lihat Portofolio Mahasiswa
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="#" id="kmPortoKhs"><i class="bi bi-file-earmark-text"></i> KHS Cetak</a></li>
        <li><a class="dropdown-item" href="#" id="kmPortoTranskrip"><i class="bi bi-journal-text"></i> Transkrip</a></li>
        <li><a class="dropdown-item" href="#" id="kmPortoCpl"><i class="bi bi-bar-chart-fill"></i> Ketercapaian 5 CPL</a></li>
        <li><a class="dropdown-item" href="#" id="kmPortoCplPerMk"><i class="bi bi-table"></i> Ketercapaian CPL per MK</a></li>
      </ul>
    </div>
  `);

  $("#kmPortoKhs")
    .off("click")
    .on("click", function (e) {
      e.preventDefault();
      window.open(
        SIQUA.BASE_URL + "obe/khs/cetak.php?mahasiswa_id=" + mahasiswaId,
        "_blank",
      );
    });
  $("#kmPortoTranskrip")
    .off("click")
    .on("click", function (e) {
      e.preventDefault();
      window.open(
        SIQUA.BASE_URL + "obe/khs/transkrip.php?mahasiswa_id=" + mahasiswaId,
        "_blank",
      );
    });
  $("#kmPortoCpl")
    .off("click")
    .on("click", function (e) {
      e.preventDefault();
      const url =
        SIQUA.BASE_URL +
        "obe/ketercapaian_mahasiswa/cetak_cpl.php" +
        "?mahasiswa_id=" +
        mahasiswaId +
        "&unit_id=" +
        (KetMhs.unitId || 0) +
        "&kurikulum_id=" +
        KetMhs.kurikulumId;
      window.open(url, "_blank");
    });

  $("#kmPortoCplPerMk")
    .off("click")
    .on("click", function (e) {
      e.preventDefault();
      const url =
        SIQUA.BASE_URL +
        "obe/ketercapaian_mahasiswa/cetak_cpl_per_mk.php" +
        "?mahasiswa_id=" +
        mahasiswaId +
        "&unit_id=" +
        (KetMhs.unitId || 0) +
        "&kurikulum_id=" +
        KetMhs.kurikulumId;
      window.open(url, "_blank");
    });
  loadDiskusi(mahasiswaId);
  $.getJSON(
    KetMhs.api,
    {
      action: "laporan",
      unit_id: KetMhs.unitId || 0,
      kurikulum_id: KetMhs.kurikulumId,
      mahasiswa_id: mahasiswaId,
    },
    function (res) {
      if (!res.success) {
        $("#kmCplList").html('<p class="text-danger">' + res.message + "</p>");
        $("#kmContent").show();
        return;
      }

      const { cpl_hasil, mk_diambil } = res.data;

      let cplHtml = "";

      if (!cpl_hasil.length) {
        cplHtml =
          '<p class="text-muted">Belum ada data CPL untuk Program Studi ini.</p>';
      }

      cpl_hasil.forEach(function (item, idx) {
        const kategori = getKategoriKetercapaian(item.ketercapaian);
        const detailId = "km-detail-" + idx;
        const isWarning = item.status === "Belum Memenuhi";
        const pct =
          item.ketercapaian !== null ? Math.min(item.ketercapaian, 100) : 0;

        const ringId = "ring-" + idx;
        const circumference = 2 * Math.PI * 28;
        const offset = circumference - (pct / 100) * circumference;

        const warningBanner = isWarning
          ? `<div class="warning-strip"><i class="bi bi-exclamation-triangle-fill"></i> Belum mencapai batas minimal 60%</div>`
          : "";

        cplHtml += `
        <div class="cpl-card ${isWarning ? "is-warning" : ""}">
          ${warningBanner}
          <div class="cpl-card-top">
            <div class="ring-wrap">
              <svg width="68" height="68" viewBox="0 0 68 68">
                <circle class="ring-track" cx="34" cy="34" r="28"></circle>
                <circle class="ring-fill" id="${ringId}" cx="34" cy="34" r="28"
                  stroke="${kategori.color}"
                  stroke-dasharray="${circumference}"
                  stroke-dashoffset="${circumference}"></circle>
              </svg>
              <div class="ring-value">${item.ketercapaian !== null ? item.ketercapaian.toFixed(0) + "%" : "-"}</div>
            </div>
            <div>
              <div class="cpl-code">${escapeHtml(item.cpl_code)}</div>
              <div class="cpl-status-line" style="color:${kategori.color};">
                <span class="dot" style="background:${kategori.color};"></span>
                ${kategori.label}
              </div>
            </div>
          </div>
          <span class="cpl-detail-toggle" data-target="${detailId}">
            Rincian per Mata Kuliah <span class="chev">▾</span>
          </span>
          <table class="table table-bordered cpl-detail-table" id="${detailId}">
            <thead><tr><th>Mata Kuliah</th><th class="text-center">Skor</th><th class="text-center">Bobot</th></tr></thead>
            <tbody>
      `;

        if (!item.detail_mk.length) {
          cplHtml += `<tr><td colspan="3" class="text-center text-muted">Belum ada data lengkap.</td></tr>`;
        } else {
          item.detail_mk.forEach(function (d) {
            cplHtml += `<tr><td>${escapeHtml(d.mata_kuliah)}</td><td class="text-center">${d.skor.toFixed(2)}</td><td class="text-center">${d.bobot.toFixed(2)}%</td></tr>`;
          });
        }

        cplHtml += `</tbody></table></div>`;
      });

      $("#kmCplList").html(cplHtml);

      // Animasi cincin progres, dijalankan sesudah elemen tertanam di DOM
      cpl_hasil.forEach(function (item, idx) {
        const pct =
          item.ketercapaian !== null ? Math.min(item.ketercapaian, 100) : 0;
        const circumference = 2 * Math.PI * 28;
        const offset = circumference - (pct / 100) * circumference;
        requestAnimationFrame(function () {
          setTimeout(function () {
            $("#ring-" + idx).css("stroke-dashoffset", offset);
          }, 60 * idx);
        });
      });

      let mkHtml = "";

      if (!mk_diambil.length) {
        mkHtml =
          '<div class="text-center text-muted py-4" style="font-size:12px;">Belum ada nilai untuk Mahasiswa ini.</div>';
      } else {
        mk_diambil.forEach(function (mk) {
          mkHtml += `
          <div class="mk-row">
            <div>
              <div class="mk-row-name">${escapeHtml(mk.nama)}</div>
              <div class="mk-row-sem">Semester ${mk.semester}</div>
            </div>
            <div class="d-flex align-items-center">
              <div class="mk-row-score">${mk.nilai_akhir !== null ? mk.nilai_akhir.toFixed(2) : "-"}</div>
              <button type="button" class="btn-ghost-eye btn-detail-pertemuan" data-mk-id="${mk.id}" data-mk-name="${escapeAttr(mk.nama)}">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
        `;
        });
      }

      $("#kmMkTableBody").html(mkHtml);
      $("#kmContent").show();
    },
  );
}

function getKategoriKetercapaian(nilai) {
  if (nilai === null) {
    return { label: "Belum Ada Data", color: "#6b7280", bgLight: "#f3f4f6" };
  }

  if (nilai > 80)
    return { label: "Sangat Memuaskan", color: "#059669", bgLight: "#eef8f1" };
  if (nilai >= 70)
    return { label: "Memuaskan", color: "#2563eb", bgLight: "#eef4fe" };
  if (nilai >= 60)
    return { label: "Cukup Memuaskan", color: "#d97706", bgLight: "#fef7e8" };

  return { label: "Tidak Memuaskan", color: "#dc2626", bgLight: "#fdecec" };
}

function loadDetailPertemuan(mkId, mkName) {
  $("#kmDetailModalTitle").text("Detail Nilai per Pertemuan - " + mkName);
  $("#kmDetailTableBody").html(
    '<tr><td colspan="5" class="text-center text-muted">Memuat...</td></tr>',
  );
  KetMhs.detailModal.show();

  $.getJSON(
    KetMhs.api,
    {
      action: "detail_pertemuan",
      mahasiswa_id: KetMhs.currentMhsId,
      mata_kuliah_id: mkId,
    },
    function (res) {
      if (!res.success || !res.data.length) {
        $("#kmDetailTableBody").html(
          '<tr><td colspan="5" class="text-center text-muted">Belum ada data RPS untuk Mata Kuliah ini.</td></tr>',
        );
        return;
      }

      let html = "";

      res.data.forEach(function (row) {
        const nilaiDisplay =
          row.nilai !== null
            ? parseFloat(row.nilai).toFixed(2)
            : '<span class="text-muted">Belum dinilai</span>';
        const rowClass =
          row.nilai !== null && parseFloat(row.nilai) < 60
            ? 'style="background:#fdecec;"'
            : "";

        const reId =
          row.rencana_evaluasi_id !== null
            ? parseInt(row.rencana_evaluasi_id)
            : 0;
        const bobot =
          reId !== 0
            ? parseFloat(row.komponen_bobot)
            : parseFloat(row.rps_bobot_total);
        const komponenLabel = reId !== 0 ? row.basis_evaluasi : "Nilai TM";

        html += `
        <tr ${rowClass}>
          <td class="text-center">${row.pertemuan}</td>
          <td>${escapeHtml(komponenLabel)}</td>
          <td>${escapeHtml(row.cpmk_code)} - ${escapeHtml(row.sub_cpmk_code)}</td>
          <td class="text-center">${row.cpl_code ? escapeHtml(row.cpl_code) : '<span class="text-muted">-</span>'}</td>
          <td class="text-center">${bobot.toFixed(2)}%</td>
          <td class="text-center">${nilaiDisplay}</td>
        </tr>
      `;
      });

      $("#kmDetailTableBody").html(html);
    },
  );
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

function escapeAttr(text) {
  return (text || "").replace(/"/g, "&quot;");
}
function loadDiskusi(mahasiswaId) {
  const diskusiApi = SIQUA.BASE_URL + "obe/diskusi_cpl/api.php";

  $.getJSON(
    diskusiApi,
    { action: "list", mahasiswa_id: mahasiswaId },
    function (res) {
      if (!res.success || !res.data.length) {
        $("#kmDiskusiThread").html(
          '<p class="text-muted small">Belum ada diskusi. Mulai percakapan di bawah.</p>',
        );
        return;
      }

      let html = "";
      res.data.forEach(function (d) {
        const inisial = d.penulis_nama.trim().charAt(0).toUpperCase() || "?";
        const waktu = new Date(d.created_at.replace(" ", "T")).toLocaleString(
          "id-ID",
          {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
          },
        );
        html += `
        <div class="diskusi-item">
          <div class="diskusi-avatar">${inisial}</div>
          <div class="diskusi-bubble">
            <span class="diskusi-nama">${escapeHtml(d.penulis_nama)}</span>
            <span class="diskusi-waktu">${waktu}</span>
            <div class="diskusi-pesan">${escapeHtml(d.pesan)}</div>
          </div>
        </div>
      `;
      });

      $("#kmDiskusiThread").html(html);
      $("#kmDiskusiThread").scrollTop($("#kmDiskusiThread")[0].scrollHeight);
    },
  );
}

$(document).on("submit", "#kmDiskusiForm", function (e) {
  e.preventDefault();

  const pesan = $("#kmDiskusiPesan").val().trim();
  if (!pesan || !KetMhs.currentMhsId) return;

  $.post(
    SIQUA.BASE_URL + "obe/diskusi_cpl/api.php?action=create",
    { mahasiswa_id: KetMhs.currentMhsId, pesan: pesan },
    function (res) {
      if (res.success) {
        $("#kmDiskusiPesan").val("");
        loadDiskusi(KetMhs.currentMhsId);
      } else {
        Swal.fire("Gagal", res.message, "error");
      }
    },
    "json",
  );
});
