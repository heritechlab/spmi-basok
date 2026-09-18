const Rps = {
  api: SIQUA.BASE_URL + "obe/rps/api.php",
  mkApi: SIQUA.BASE_URL + "obe/mata_kuliah/api.php",
  jadwalApi: SIQUA.BASE_URL + "obe/jadwal_dosen/api.php",
  rubrikApi: SIQUA.BASE_URL + "obe/rubrik_penilaian/api.php",
  rencanaTugasApi: SIQUA.BASE_URL + "obe/rencana_tugas/api.php",
  dosenCache: [],
  unitId: null,
  kurikulumId: null,
  kurikulumCache: [],
  mataKuliahId: null,
  mkCache: [],
  subCpmkCache: [],
  bentukPembelajaranCache: [],
  metodeLuringCache: [],
  metodeDaringCache: [],
  indikatorCache: [],
  rencanaEvaluasiCache: [],
  currentMkSks: 0,
  modal: null,
};

const BANK_KKO_PENGETAHUAN = [
  "Menjelaskan",
  "Menguraikan",
  "Membandingkan",
  "Menyimpulkan",
  "Menerapkan",
  "Menghitung",
  "Menganalisis",
  "Membedakan",
  "Merinci",
  "Mengevaluasi",
  "Menilai",
  "Memahami",
  "Merancang",
  "Menyusun",
  "Merumuskan",
];

const BANK_KKO_SIKAP = [
  "Menunjukkan",
  "Menerapkan",
  "Menghayati",
  "Menaati",
  "Menjalankan",
  "Membiasakan",
  "Menerima",
  "Mematuhi",
];

const BANK_KKO_KETERAMPILAN = [
  "Melaksanakan",
  "Mendemonstrasikan",
  "Mempraktikkan",
  "Mengoperasikan",
  "Melakukan",
  "Menyusun",
  "Mengukur",
  "Merangkai",
];

function initBuilderKko() {
  let htmlP = '<option value="">-- Tidak dipakai --</option>';
  BANK_KKO_PENGETAHUAN.forEach(function (kko) {
    htmlP += `<option value="${kko}">${kko}</option>`;
  });
  $("#rps_builder_kko_pengetahuan").html(htmlP);

  let htmlS = '<option value="">-- Tidak dipakai --</option>';
  BANK_KKO_SIKAP.forEach(function (kko) {
    htmlS += `<option value="${kko}">${kko}</option>`;
  });
  $("#rps_builder_kko_sikap").html(htmlS);

  let htmlK = '<option value="">-- Tidak dipakai --</option>';
  BANK_KKO_KETERAMPILAN.forEach(function (kko) {
    htmlK += `<option value="${kko}">${kko}</option>`;
  });
  $("#rps_builder_kko_keterampilan").html(htmlK);
}

$(document).ready(function () {
  Rps.modal = new bootstrap.Modal(document.getElementById("rpsModal"));
  initBuilderKko();
  loadPeriodeAktifLabel();

  $("#rpsMetodeLuringSelect, #rpsMetodeDaringSelect").select2({
    placeholder: "-- Pilih Metode (bisa lebih dari 1) --",
    width: "100%",
    dropdownParent: $("#rpsModal"),
  });

  if (RPS_IS_AUDITEE) {
    loadKurikulumTabs();
  }

  loadBentukPembelajaranCache();
  loadMetodeCache();
  loadIndikatorCache();

  $(document).on("change", "#rpsUnitSelector", function () {
    Rps.unitId = $(this).val();
    resetView();
    if (Rps.unitId) {
      loadKurikulumTabs();
    }
  });

  $(document).on("change", "#rpsMkSelector", function () {
    Rps.mataKuliahId = $(this).val();
    if (Rps.mataKuliahId) {
      showMkInfo();
      loadSubCpmkCache();
      loadTable();
      $("#rpsListCard").show();
      $("#rpsMkInfoCard").show();
      $("#rpsWorkspaceTabs").show();
      switchRpsTab("isi-rps");
    } else {
      $("#rpsListCard").hide();
      $("#rpsMkInfoCard").hide();
      $("#rpsWorkspaceTabs").hide();
      $(".rps-tab-panel").hide();
    }
  });

  $(document).on("click", ".rps-tab-btn", function () {
    switchRpsTab($(this).data("tab"));
  });

  $("#btnAddRps").on("click", function () {
    $("#rpsForm")[0].reset();
    $("#rps_id").val("");
    $("#rps_mata_kuliah_id").val(Rps.mataKuliahId);
    $("#rpsModalTitle").text("Tambah Baris RPS");
    fillSubCpmkOptions();
    $("#rpsBahanKajianChecklist").html(
      '<p class="text-muted small mb-0">Pilih Sub-CPMK terlebih dahulu.</p>',
    );
    renderBentukLuringChecklist([]);
    $("#rpsRencanaEvaluasiKosong").hide();
    loadRencanaEvaluasiForPertemuan([]);
    updateBuilderTopikOptions();

    loadMetodeCache(function () {
      renderMetodeLuringChecklist([]);
      renderMetodeDaringChecklist([]);
      updateBuilderAktivitasOptions();
    });

    Rps.modal.show();
  });

  $(document).on("change", "#rps_pertemuan", function () {
    loadRencanaEvaluasiForPertemuan();
  });

  $(document).on("change", ".rps-reo-checkbox", function () {
    hitungTotalBobotRencanaEvaluasi();
  });

  $(document).on("change", ".rps-metode-checkbox", function () {
    updateBuilderAktivitasOptions();
  });

  $(document).on("change", ".rps-bk-checkbox", function () {
    updateBuilderTopikOptions();
  });

  $("#btnSusunIndikatorUmum").on("click", function () {
    const aktivitas = $("#rps_builder_aktivitas").val();
    const persen = $("#rps_builder_persen").val() || 0;
    const topik = $("#rps_builder_topik").val();

    const kkoPengetahuan = $("#rps_builder_kko_pengetahuan").val();
    const kkoSikap = $("#rps_builder_kko_sikap").val();
    const kkoKeterampilan = $("#rps_builder_kko_keterampilan").val();

    const kkoTerpilih = [kkoPengetahuan, kkoSikap, kkoKeterampilan].filter(
      (v) => v && v !== "",
    );

    if (!aktivitas || !topik || !kkoTerpilih.length) {
      Swal.fire(
        "Belum Lengkap",
        "Pilih Aktivitas, Topik, dan minimal 1 Kata Kerja (Pengetahuan/Sikap/Keterampilan) dulu.",
        "warning",
      );
      return;
    }

    // Gabungkan kata kerja yang dipilih, contoh: "menjelaskan, menunjukkan, dan melaksanakan"
    const kkoLower = kkoTerpilih.map((k) => k.toLowerCase());
    let kkoGabung;

    if (kkoLower.length === 1) {
      kkoGabung = kkoLower[0];
    } else {
      kkoGabung =
        kkoLower.slice(0, -1).join(", ") +
        ", dan " +
        kkoLower[kkoLower.length - 1];
    }

    const kalimat = `Setelah ${aktivitas}, mahasiswa mampu (${persen}%) ${kkoGabung} ${topik}.`;
    $("#rps_indikator_umum").val(kalimat);
  });

  $("#btnSusunIndikatorKhusus").on("click", function () {
    const aktivitas = $("#rps_builder_aktivitas").val();
    const persen = $("#rps_builder_persen").val() || 0;
    const kko = $("#rps_builder_kko").val();
    const topik = $("#rps_builder_topik").val();
    const kko2 = $("#rps_builder_kko2").val();
    const topik2 = $("#rps_builder_topik2").val();

    const kodeTakson = $(".rps-kode-takson:checked")
      .map(function () {
        return $(this).val();
      })
      .get();

    if (!aktivitas || !kko || !topik) {
      Swal.fire(
        "Belum Lengkap",
        "Pilih Aktivitas, Kata Kerja, dan Topik dulu.",
        "warning",
      );
      return;
    }

    let kalimat = `Setelah ${aktivitas}, mahasiswa mampu (${persen}%) ${kko.toLowerCase()} ${topik}`;

    if (kko2 && topik2) {
      const kodeText = kodeTakson.length ? ` (${kodeTakson.join(", ")})` : "";
      kalimat += ` dan juga Mampu ${kko2.toLowerCase()}${kodeText} ${topik2}`;
    }

    kalimat += ".";

    $("#rps_indikator_khusus").val(kalimat);
  });

  $(document).on("change", "#rps_sub_cpmk", function () {
    loadBahanKajianChecklist($(this).val(), []);
  });

  $("#btnBuildMateri").on("click", function () {
    const names = $(".rps-bk-checkbox:checked")
      .map(function () {
        return $(this).data("nama");
      })
      .get();

    if (!names.length) {
      Swal.fire(
        "Belum ada Bahan Kajian",
        "Centang minimal 1 Bahan Kajian dulu.",
        "warning",
      );
      return;
    }

    const text =
      names.length === 1
        ? names[0]
        : names.slice(0, -1).join(", ") + ", dan " + names[names.length - 1];
    $("#rps_materi").val(text);
  });

  $("#btnBuildPengalaman").on("click", function () {
    const aktivitas = $(".rps-metode-checkbox:checked")
      .map(function () {
        return $(this).data("aktivitas");
      })
      .get()
      .filter((a) => a && a.trim() !== "");

    if (!aktivitas.length) {
      Swal.fire(
        "Belum ada Metode",
        "Pilih minimal 1 Metode Pembelajaran yang punya Aktivitas Mahasiswa.",
        "warning",
      );
      return;
    }

    $("#rps_pengalaman").val(aktivitas.join("\n\n"));
  });

  $("#btnHitungWaktu").on("click", function () {
    const kategoriSet = new Set();

    $(".rps-bentuk-luring-checkbox:checked").each(function () {
      kategoriSet.add($(this).data("kategori"));
    });

    if (!kategoriSet.size) {
      Swal.fire(
        "Belum ada Bentuk Pembelajaran",
        "Centang minimal 1 Bentuk Pembelajaran (Luring) dulu.",
        "warning",
      );
      return;
    }

    const singkatan = {
      "Belajar Terbimbing": "TM",
      "Penugasan Terstruktur": "BT",
      "Belajar Mandiri": "BM",
      "Kegiatan Praktik": "Kegiatan",
      "Kegiatan MBKM": "Kegiatan",
    };

    const kategoriList = Array.from(kategoriSet);
    let totalMenit = 0;
    let rincianParts = [];
    let doneCount = 0;

    kategoriList.forEach(function (kategori) {
      $.getJSON(
        Rps.api,
        { action: "waktu_komponen", kategori: kategori },
        function (res) {
          doneCount++;

          if (res.success) {
            totalMenit += (res.data.total_menit || 0) * Rps.currentMkSks;

            (res.data.komponen || []).forEach(function (k) {
              const label = singkatan[k.nama_komponen] || k.nama_komponen;
              rincianParts.push(`${label} = ${Rps.currentMkSks} x ${k.menit}"`);
            });
          }

          if (doneCount === kategoriList.length) {
            const rincianText = rincianParts.join(" + "); // disimpan tetap 1 baris untuk database
            $("#rps_alokasi_waktu").val(totalMenit);
            $("#rps_alokasi_waktu_rincian").html(
              formatRincianWaktu(rincianText),
            );
            $("#rps_alokasi_waktu_rincian_input").val(rincianText);
          }
        },
      );
    });
  });

  $("#rpsForm").on("submit", function (e) {
    e.preventDefault();
    saveData();
  });
});

function resetView() {
  $("#rpsKurikulumTabsWrap").hide();
  $("#rpsMkSelectorCard").hide();
  $("#rpsMkInfoCard").hide();
  $("#rpsListCard").hide();
}

function loadPeriodeAktifLabel() {
  $.getJSON(
    SIQUA.BASE_URL + "obe/periode_akademik/api.php",
    { action: "active" },
    function (res) {
      if (res.success) {
        $("#rpsPeriodeAktifLabel").text(
          res.data.tahun_ajaran + " - " + res.data.jenis_semester,
        );
      } else {
        $("#rpsPeriodeAktifLabel")
          .text("Belum ada Periode aktif")
          .removeClass("text-primary")
          .addClass("text-danger");
      }
    },
  );
}

function switchRpsTab(tabName) {
  $(".rps-tab-btn").removeClass("active");
  $(`.rps-tab-btn[data-tab="${tabName}"]`).addClass("active");

  $(".rps-tab-panel").hide();

  const panelMap = {
    detail: "#rpsTabPanelDetail",
    "isi-rps": "#rpsTabPanelIsiRps",
    "rencana-evaluasi": "#rpsTabPanelRencanaEvaluasi",
    "jadwal-dosen": "#rpsTabPanelJadwalDosen",
    "rubrik-penilaian": "#rpsTabPanelRubrikPenilaian",
    "rencana-tugas": "#rpsTabPanelRencanaTugas",
    "cetak-rps": "#rpsTabPanelCetakRps",
  };

  $(panelMap[tabName]).show();

  if (tabName === "detail") {
    renderDetailMataKuliah();
  } else if (tabName === "rencana-evaluasi") {
    renderTabRencanaEvaluasi();
  } else if (tabName === "jadwal-dosen") {
    renderTabJadwalDosen();
  } else if (tabName === "rubrik-penilaian") {
    renderTabRubrikPenilaianFrame();
  } else if (tabName === "rencana-tugas") {
    renderTabRencanaTugas();
  } else if (tabName === "cetak-rps") {
    renderTabCetakRps();
  }
}

function renderTabCetakRps() {
  $("#btnBukaCetakRps")
    .off("click")
    .on("click", function () {
      const url =
        SIQUA.BASE_URL +
        "obe/cetak_rps/index.php?mata_kuliah_id=" +
        encodeURIComponent(Rps.mataKuliahId);
      window.open(url, "_blank");
    });
}

function loadDosenCacheForDetail(unitId, callback) {
  $.getJSON(
    Rps.mkApi,
    { action: "dosen_list", unit_id: unitId || 0 },
    function (res) {
      if (res.success) Rps.dosenCache = res.data;
      if (typeof callback === "function") callback();
    },
  );
}

function renderDetailMataKuliah() {
  const mk = Rps.mkCache.find((m) => String(m.id) === String(Rps.mataKuliahId));
  const kur = Rps.kurikulumCache.find(
    (k) => String(k.id) === String(Rps.kurikulumId),
  );

  if (!mk) {
    $("#rpsDetailMkContent").html(
      '<p class="text-muted">Mata Kuliah tidak ditemukan.</p>',
    );
    return;
  }

  if (!Rps.dosenCache.length) {
    loadDosenCacheForDetail(mk.unit_id, renderDetailMataKuliah);
    $("#rpsDetailMkContent").html(
      '<p class="text-muted">Memuat data dosen...</p>',
    );
    return;
  }

  const sksTatapMuka = parseInt(mk.sks_tatap_muka || 0);
  const sksPraktikum = parseInt(mk.sks_praktikum || 0);
  const sksPraktekLapangan = parseInt(mk.sks_praktek_lapangan || 0);
  const sksSimulasi = parseInt(mk.sks_simulasi || 0);

  const dosenOptions = Rps.dosenCache
    .map((d) => {
      const label = [d.gelar_depan, d.name, d.gelar_belakang]
        .filter(Boolean)
        .join(" ");
      return `<option value="${d.id}">${escapeHtml(label)}</option>`;
    })
    .join("");

  const selectedDosenIds = (mk.dosen_list || []).map((d) => String(d.id));
  const koordinator = (mk.dosen_list || []).find(
    (d) => d.peran === "Koordinator",
  );
  const koordinatorId = koordinator ? koordinator.id : "";

  const cplHidden = (mk.cpl_ids || [])
    .map((cid) => `<input type="hidden" name="cpl_ids[]" value="${cid}">`)
    .join("");

  const html = `
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <div class="text-muted small text-uppercase" style="font-size:10.5px; letter-spacing:.3px;">Kurikulum</div>
        <div class="fw-semibold">${kur ? escapeHtml(kur.tahun + " - " + kur.nama) : "-"}</div>
      </div>
      <div class="col-md-6">
        <div class="text-muted small text-uppercase" style="font-size:10.5px; letter-spacing:.3px;">Ka. Program Studi</div>
        <div class="fw-semibold">${escapeHtml(mk.ka_prodi_name || "-")}</div>
      </div>
    </div>

    <hr>

    <form id="rpsMkDetailForm">
      <input type="hidden" name="id" value="${mk.id}">
      <input type="hidden" name="unit_id" value="${mk.unit_id}">
      <input type="hidden" name="kurikulum_id" value="${mk.kurikulum_id}">
      ${cplHidden}

      <div class="section-label text-muted small text-uppercase mb-2" style="font-size:10.5px; letter-spacing:.3px;">Identitas Mata Kuliah</div>
      <div class="row g-3 mb-3">
        <div class="col-md-3">
          <label class="form-label small">Kode MK</label>
          <input type="text" class="form-control form-control-sm" name="code" value="${escapeHtml(mk.code || "")}">
        </div>
        <div class="col-md-9">
          <label class="form-label small">Nama MK</label>
          <input type="text" class="form-control form-control-sm" name="name" value="${escapeHtml(mk.name || "")}" required>
        </div>
        <div class="col-md-4">
          <label class="form-label small">Kelompok Bidang Ilmu</label>
          <input type="text" class="form-control form-control-sm" name="rumpun_mk" value="${escapeHtml(mk.rumpun_mk || "")}">
        </div>
        <div class="col-md-4">
          <label class="form-label small">Semester</label>
          <input type="number" min="1" max="14" class="form-control form-control-sm" name="semester" value="${mk.semester || 1}">
        </div>
        <div class="col-md-4">
          <label class="form-label small">Tahun Ajaran</label>
          <input type="text" class="form-control form-control-sm" name="tahun_ajaran" placeholder="mis. 2025/2026 Ganjil" value="${escapeHtml(mk.tahun_ajaran || "")}">
        </div>
        <div class="col-md-4">
          <label class="form-label small">RPS Direvisi (tanggal)</label>
          <input type="date" class="form-control form-control-sm" name="tanggal_revisi_rps" value="${mk.tanggal_revisi_rps || ""}">
        </div>
        <div class="col-md-4">
          <label class="form-label small">Jenis MK</label>
          <select class="form-select form-select-sm" name="jenis_mk">
            ${[
              "Wajib Nasional",
              "Wajib Institusi",
              "Wajib Prodi",
              "Pilihan Prodi",
            ]
              .map(
                (j) =>
                  `<option value="${j}" ${mk.jenis_mk === j ? "selected" : ""}>${j}</option>`,
              )
              .join("")}
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small">Kelompok MK</label>
          <input type="text" class="form-control form-control-sm" name="kelompok_mk" value="${escapeHtml(mk.kelompok_mk || "")}">
        </div>
      </div>

      <div class="section-label text-muted small text-uppercase mb-2" style="font-size:10.5px; letter-spacing:.3px;">Bobot SKS</div>
      <div class="row g-2 mb-3">
        <div class="col-md-3 col-6">
          <label class="form-label small">Tatap Muka</label>
          <input type="number" min="0" class="form-control form-control-sm" name="sks_tatap_muka" value="${sksTatapMuka}">
        </div>
        <div class="col-md-3 col-6">
          <label class="form-label small">Praktikum</label>
          <input type="number" min="0" class="form-control form-control-sm" name="sks_praktikum" value="${sksPraktikum}">
        </div>
        <div class="col-md-3 col-6">
          <label class="form-label small">Praktek Lapangan</label>
          <input type="number" min="0" class="form-control form-control-sm" name="sks_praktek_lapangan" value="${sksPraktekLapangan}">
        </div>
        <div class="col-md-3 col-6">
          <label class="form-label small">Simulasi</label>
          <input type="number" min="0" class="form-control form-control-sm" name="sks_simulasi" value="${sksSimulasi}">
        </div>
      </div>

      <div class="section-label text-muted small text-uppercase mb-2" style="font-size:10.5px; letter-spacing:.3px;">Penanggung Jawab</div>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label small">Pengembang RPS MK</label>
          <select class="form-select form-select-sm rps-mk-select2" name="dosen_pengembang_rps_id" style="width:100%;">
            <option value="">- pilih dosen -</option>
            ${Rps.dosenCache
              .map((d) => {
                const label = [d.gelar_depan, d.name, d.gelar_belakang]
                  .filter(Boolean)
                  .join(" ");
                return `<option value="${d.id}" ${String(mk.dosen_pengembang_rps_id) === String(d.id) ? "selected" : ""}>${escapeHtml(label)}</option>`;
              })
              .join("")}
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label small">Gugus Kendali Mutu</label>
          <select class="form-select form-select-sm rps-mk-select2" name="gkm_dosen_id" style="width:100%;">
            <option value="">- pilih dosen -</option>
            ${Rps.dosenCache
              .map((d) => {
                const label = [d.gelar_depan, d.name, d.gelar_belakang]
                  .filter(Boolean)
                  .join(" ");
                return `<option value="${d.id}" ${String(mk.gkm_dosen_id) === String(d.id) ? "selected" : ""}>${escapeHtml(label)}</option>`;
              })
              .join("")}
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label small">Tim Teaching / Pengampu</label>
          <select class="form-select form-select-sm rps-mk-select2" name="dosen_ids[]" id="rpsMkDosenIds" multiple style="width:100%;">
            ${Rps.dosenCache
              .map((d) => {
                const label = [d.gelar_depan, d.name, d.gelar_belakang]
                  .filter(Boolean)
                  .join(" ");
                return `<option value="${d.id}" ${selectedDosenIds.includes(String(d.id)) ? "selected" : ""}>${escapeHtml(label)}</option>`;
              })
              .join("")}
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label small">Koordinator MK</label>
          <select class="form-select form-select-sm" name="koordinator_id" id="rpsMkKoordinatorId">
            <option value="">- pilih dari Tim Teaching -</option>
          </select>
        </div>
        <div class="col-md-12">
          <label class="form-label small">Prasyarat Mata Kuliah</label>
          <input type="text" class="form-control form-control-sm" name="prasyarat_mk" placeholder="mis. Anatomi Fisiologi, Biokimia" value="${escapeHtml(mk.prasyarat_mk || "")}">
        </div>
      </div>

      <div class="section-label text-muted small text-uppercase mb-2" style="font-size:10.5px; letter-spacing:.3px;">Deskripsi &amp; Media</div>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label small">Deskripsi Mata Kuliah</label>
          <textarea class="form-control form-control-sm" name="deskripsi" rows="3">${escapeHtml(mk.deskripsi || "")}</textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label small">Media Pembelajaran MK</label>
          <textarea class="form-control form-control-sm" name="media_pembelajaran" rows="3">${escapeHtml(mk.media_pembelajaran || "")}</textarea>
        </div>
      </div>

      <div class="section-label text-muted small text-uppercase mb-2" style="font-size:10.5px; letter-spacing:.3px;">Bahan Kajian</div>
      <div class="alert alert-light border small mb-3">
        Bahan Kajian dikelola otomatis per pertemuan pada tab <strong>Isi RPS</strong>.
      </div>

      <div class="section-label text-muted small text-uppercase mb-2" style="font-size:10.5px; letter-spacing:.3px;">Daftar Pustaka</div>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label small">Pustaka Utama</label>
          <textarea class="form-control form-control-sm" name="pustaka_utama" rows="3">${escapeHtml(mk.pustaka_utama || "")}</textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label small">Pustaka Pendukung</label>
          <textarea class="form-control form-control-sm" name="pustaka_pendukung" rows="3">${escapeHtml(mk.pustaka_pendukung || "")}</textarea>
        </div>
      </div>

      <input type="hidden" name="jenis_mk_hidden_keep" value="1">
      <div class="text-end">
        <button type="submit" class="btn btn-primary btn-sm" id="btnSaveMkDetail">
          <i class="bi bi-save"></i> Simpan Detail Mata Kuliah
        </button>
      </div>
    </form>
  `;

  $("#rpsDetailMkContent").html(html);

  $(".rps-mk-select2").select2({ width: "100%" });

  function refreshKoordinatorOptions() {
    const selectedIds = $("#rpsMkDosenIds").val() || [];
    const $koordSelect = $("#rpsMkKoordinatorId");
    const currentVal = $koordSelect.val();
    $koordSelect.html('<option value="">- pilih dari Tim Teaching -</option>');
    selectedIds.forEach((id) => {
      const d = Rps.dosenCache.find((x) => String(x.id) === String(id));
      if (d) {
        const label = [d.gelar_depan, d.name, d.gelar_belakang]
          .filter(Boolean)
          .join(" ");
        $koordSelect.append(
          `<option value="${d.id}">${escapeHtml(label)}</option>`,
        );
      }
    });
    if (selectedIds.includes(String(currentVal))) {
      $koordSelect.val(currentVal);
    } else if (selectedIds.includes(String(koordinatorId))) {
      $koordSelect.val(koordinatorId);
    }
  }

  $("#rpsMkDosenIds").on("change", refreshKoordinatorOptions);
  refreshKoordinatorOptions();
  if (selectedDosenIds.includes(String(koordinatorId))) {
    $("#rpsMkKoordinatorId").val(koordinatorId);
  }

  $("#rpsMkDetailForm")
    .off("submit")
    .on("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      formData.append("action", "update");
      formData.append("validasi_rps", mk.validasi_rps || "Belum");
      formData.append("minimal_nilai_lulus", mk.minimal_nilai_lulus || "C");
      if (mk.ada_diktat) formData.append("ada_diktat", "1");
      if (mk.ada_silabus) formData.append("ada_silabus", "1");
      formData.append("konsentrasi", mk.konsentrasi || "");

      $("#btnSaveMkDetail")
        .prop("disabled", true)
        .html(
          '<span class="spinner-border spinner-border-sm"></span> Menyimpan...',
        );

      $.ajax({
        url: Rps.mkApi,
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
      })
        .done(function (res) {
          if (res.success) {
            Swal.fire({
              icon: "success",
              title: "Tersimpan",
              text: res.message || "Detail Mata Kuliah berhasil diperbarui.",
              timer: 1600,
              showConfirmButton: false,
            });
            reloadMkCacheSilent(function () {
              renderDetailMataKuliah();
            });
          } else {
            Swal.fire({
              icon: "error",
              title: "Gagal",
              text: res.message || "Terjadi kesalahan.",
            });
          }
        })
        .fail(function () {
          Swal.fire({
            icon: "error",
            title: "Gagal",
            text: "Tidak dapat menghubungi server.",
          });
        })
        .always(function () {
          $("#btnSaveMkDetail")
            .prop("disabled", false)
            .html('<i class="bi bi-save"></i> Simpan Detail Mata Kuliah');
        });
    });
}

function renderTabRencanaEvaluasi() {
  const mk = Rps.mkCache.find((m) => String(m.id) === String(Rps.mataKuliahId));
  if (!mk) return;

  const url =
    SIQUA.BASE_URL +
    "obe/rencana_evaluasi/index.php?embed=1" +
    "&unit_id=" +
    encodeURIComponent(mk.unit_id) +
    "&kurikulum_id=" +
    encodeURIComponent(Rps.kurikulumId) +
    "&mata_kuliah_id=" +
    encodeURIComponent(Rps.mataKuliahId);

  $("#rpsRencanaEvaluasiFrame").attr("src", url);
}

function renderTabJadwalDosen() {
  const mk = Rps.mkCache.find((m) => String(m.id) === String(Rps.mataKuliahId));
  if (!mk) return;

  $("#rpsJadwalDosenContent").html(
    '<p class="text-muted">Memuat Jadwal Dosen...</p>',
  );

  $.getJSON(
    Rps.api,
    { action: "list", mata_kuliah_id: Rps.mataKuliahId },
    function (rpsRes) {
      const pertemuanList = (rpsRes.success ? rpsRes.data : [])
        .map((r) => parseInt(r.pertemuan))
        .filter((p, idx, arr) => p > 0 && arr.indexOf(p) === idx)
        .sort((a, b) => a - b);

      if (!pertemuanList.length) {
        $("#rpsJadwalDosenContent").html(
          '<p class="text-muted">Belum ada data Pertemuan. Tambahkan baris di tab <strong>Isi RPS</strong> terlebih dahulu.</p>',
        );
        return;
      }

      $.getJSON(
        Rps.jadwalApi,
        { action: "list", mata_kuliah_id: Rps.mataKuliahId },
        function (jadwalRes) {
          const jadwalMap = {};
          (jadwalRes.success ? jadwalRes.data : []).forEach((j) => {
            jadwalMap[j.pertemuan] = j;
          });

          const hariOptions = [
            "Senin",
            "Selasa",
            "Rabu",
            "Kamis",
            "Jumat",
            "Sabtu",
            "Minggu",
          ];
          const dosenList = mk.dosen_list || [];

          let rowsHtml = "";
          pertemuanList.forEach((p) => {
            const j = jadwalMap[p] || {};

            const hariSelect = hariOptions
              .map(
                (h) =>
                  `<option value="${h}" ${j.hari === h ? "selected" : ""}>${h}</option>`,
              )
              .join("");

            const dosenSelect = dosenList
              .map((d) => {
                const label = [d.gelar_depan, d.name, d.gelar_belakang]
                  .filter(Boolean)
                  .join(" ");
                return `<option value="${escapeHtml(label)}" ${j.dosen_pengampu === label ? "selected" : ""}>${escapeHtml(label)}</option>`;
              })
              .join("");

            rowsHtml += `
          <tr data-pertemuan="${p}">
            <td class="text-center"><span class="badge-pertemuan">${p}</span></td>
            <td>
              <select class="form-select form-select-sm jd-hari">
                <option value="">- pilih -</option>
                ${hariSelect}
              </select>
            </td>
            <td><input type="time" class="form-control form-control-sm jd-jam-mulai" value="${j.jam_mulai ? j.jam_mulai.substring(0, 5) : ""}"></td>
            <td><input type="time" class="form-control form-control-sm jd-jam-selesai" value="${j.jam_selesai ? j.jam_selesai.substring(0, 5) : ""}"></td>
            <td><input type="text" class="form-control form-control-sm jd-ruang" value="${escapeHtml(j.ruang || "")}"></td>
            <td>
              <select class="form-select form-select-sm jd-dosen">
                <option value="">- pilih dosen -</option>
                ${dosenSelect}
              </select>
            </td>
          </tr>
        `;
          });

          const html = `
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead>
              <tr>
                <th width="60" class="text-center">Mg</th>
                <th width="120">Hari</th>
                <th width="110">Jam Mulai</th>
                <th width="110">Jam Selesai</th>
                <th width="140">Ruang</th>
                <th>Dosen Pengampu</th>
              </tr>
            </thead>
            <tbody>${rowsHtml}</tbody>
          </table>
        </div>
        <div class="text-end">
          <button type="button" class="btn btn-primary btn-sm" id="btnSaveJadwalDosen">
            <i class="bi bi-save"></i> Simpan Jadwal Dosen
          </button>
        </div>
      `;

          $("#rpsJadwalDosenContent").html(html);

          $("#btnSaveJadwalDosen").on("click", function () {
            const rows = [];
            $("#rpsJadwalDosenContent tbody tr").each(function () {
              rows.push({
                pertemuan: $(this).data("pertemuan"),
                hari: $(this).find(".jd-hari").val(),
                jam_mulai: $(this).find(".jd-jam-mulai").val(),
                jam_selesai: $(this).find(".jd-jam-selesai").val(),
                ruang: $(this).find(".jd-ruang").val(),
                dosen_pengampu: $(this).find(".jd-dosen").val(),
              });
            });

            $("#btnSaveJadwalDosen")
              .prop("disabled", true)
              .html(
                '<span class="spinner-border spinner-border-sm"></span> Menyimpan...',
              );

            $.post(
              Rps.jadwalApi,
              {
                action: "save",
                mata_kuliah_id: Rps.mataKuliahId,
                rows: JSON.stringify(rows),
              },
              function (res) {
                if (res.success) {
                  Swal.fire({
                    icon: "success",
                    title: "Tersimpan",
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false,
                  });
                } else {
                  Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: res.message,
                  });
                }
              },
              "json",
            ).always(function () {
              $("#btnSaveJadwalDosen")
                .prop("disabled", false)
                .html('<i class="bi bi-save"></i> Simpan Jadwal Dosen');
            });
          });
        },
      );
    },
  );
}

function renderTabRubrikPenilaianFrame() {
  const mk = Rps.mkCache.find((m) => String(m.id) === String(Rps.mataKuliahId));
  if (!mk) return;

  const url =
    SIQUA.BASE_URL +
    "obe/penilaian/index.php?embed=1" +
    "&unit_id=" +
    encodeURIComponent(mk.unit_id) +
    "&kurikulum_id=" +
    encodeURIComponent(Rps.kurikulumId) +
    "&mata_kuliah_id=" +
    encodeURIComponent(Rps.mataKuliahId);

  $("#rpsRubrikPenilaianFrame").attr("src", url);
}

function renderTabRencanaTugas() {
  if (!Rps.mataKuliahId) return;

  $("#rpsRencanaTugasContent").html(
    '<p class="text-muted">Memuat Rencana Tugas...</p>',
  );

  $.getJSON(
    Rps.rencanaTugasApi,
    { action: "list", mata_kuliah_id: Rps.mataKuliahId },
    function (res) {
      if (!res.success || !res.data.length) {
        $("#rpsRencanaTugasContent").html(
          '<div class="alert alert-light border">Belum ada Basis Evaluasi bertipe Tugas (Aktivitas Partisipatif / Hasil Proyek / Tugas) di tab <strong>Rencana Evaluasi</strong> untuk Mata Kuliah ini.</div>',
        );
        return;
      }

      let html = "";

      res.data.forEach(function (t) {
        const subCpmkText =
          (t.sub_cpmk_list || []).map((s) => s.code).join(", ") || "-";
        const indikatorText =
          (t.indikator_list || []).map((i) => i.indikator).join("; ") || "-";

        html += `
        <div class="card shadow-sm mb-3 rencana-tugas-card" data-reo-id="${t.rencana_evaluasi_id}">
          <div class="card-header d-flex justify-content-between align-items-center" style="background:#f1edfc;">
            <span><strong>Tugas ke-${t.tugas_ke}</strong> &mdash; ${escapeHtml(t.basis_evaluasi)}</span>
            <span class="badge bg-primary">${parseFloat(t.bobot_persen).toFixed(1)}%</span>
          </div>
          <div class="card-body">
            <div class="row g-2 mb-3">
              <div class="col-md-3">
                <div class="text-muted small text-uppercase" style="font-size:10.5px;">Minggu Ke (asal)</div>
                <div class="fw-semibold">${t.pertemuan || "-"}</div>
              </div>
              <div class="col-md-5">
                <div class="text-muted small text-uppercase" style="font-size:10.5px;">Sub-CPMK Terkait</div>
                <div class="fw-semibold">${escapeHtml(subCpmkText)}</div>
              </div>
              <div class="col-md-4">
                <div class="text-muted small text-uppercase" style="font-size:10.5px;">Indikator (dari Rencana Evaluasi)</div>
                <div class="fw-semibold small">${escapeHtml(indikatorText)}</div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small">Bentuk Tugas</label>
                <input type="text" class="form-control form-control-sm rt-bentuk-tugas" placeholder="mis. Makalah, Video, Proyek Kelompok" value="${escapeHtml(t.bentuk_tugas)}">
              </div>
              <div class="col-md-6">
                <label class="form-label small">Judul Tugas</label>
                <input type="text" class="form-control form-control-sm rt-judul" value="${escapeHtml(t.judul)}">
              </div>
              <div class="col-md-6">
                <label class="form-label small">Minggu Mulai</label>
                <input type="number" min="1" max="16" class="form-control form-control-sm rt-minggu-mulai" value="${t.minggu_mulai || ""}">
              </div>
              <div class="col-md-6">
                <label class="form-label small">Minggu Selesai (dikumpulkan)</label>
                <input type="number" min="1" max="16" class="form-control form-control-sm rt-minggu-selesai" value="${t.minggu_selesai || ""}">
              </div>
              <div class="col-md-12">
                <label class="form-label small">Deskripsi Tugas</label>
                <textarea class="form-control form-control-sm rt-deskripsi" rows="3">${escapeHtml(t.deskripsi_tugas)}</textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label small">Metode Pengerjaan</label>
                <textarea class="form-control form-control-sm rt-metode" rows="2" placeholder="mis. Individu / Kelompok, langkah pengerjaan">${escapeHtml(t.metode_pengerjaan)}</textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label small">Bentuk &amp; Format Luaran</label>
                <textarea class="form-control form-control-sm rt-luaran" rows="2" placeholder="mis. Dokumen PDF, maksimal 10 halaman">${escapeHtml(t.bentuk_luaran)}</textarea>
              </div>
              <div class="col-md-12">
                <label class="form-label small">Kriteria Penilaian</label>
                <textarea class="form-control form-control-sm rt-kriteria" rows="2">${escapeHtml(t.kriteria_penilaian)}</textarea>
              </div>
            </div>

            <div class="text-end mt-3">
              <button type="button" class="btn btn-primary btn-sm btn-save-rencana-tugas">
                <i class="bi bi-save"></i> Simpan Tugas ke-${t.tugas_ke}
              </button>
            </div>
          </div>
        </div>
      `;
      });

      $("#rpsRencanaTugasContent").html(html);

      $(".btn-save-rencana-tugas").on("click", function () {
        const $card = $(this).closest(".rencana-tugas-card");
        const reoId = $card.data("reo-id");
        const $btn = $(this);

        $btn
          .prop("disabled", true)
          .html(
            '<span class="spinner-border spinner-border-sm"></span> Menyimpan...',
          );

        $.post(
          Rps.rencanaTugasApi,
          {
            action: "save",
            mata_kuliah_id: Rps.mataKuliahId,
            rencana_evaluasi_id: reoId,
            bentuk_tugas: $card.find(".rt-bentuk-tugas").val(),
            judul: $card.find(".rt-judul").val(),
            deskripsi_tugas: $card.find(".rt-deskripsi").val(),
            metode_pengerjaan: $card.find(".rt-metode").val(),
            bentuk_luaran: $card.find(".rt-luaran").val(),
            kriteria_penilaian: $card.find(".rt-kriteria").val(),
            minggu_mulai: $card.find(".rt-minggu-mulai").val(),
            minggu_selesai: $card.find(".rt-minggu-selesai").val(),
          },
          function (res) {
            if (res.success) {
              Swal.fire({
                icon: "success",
                title: "Tersimpan",
                timer: 1200,
                showConfirmButton: false,
              });
            } else {
              Swal.fire({ icon: "error", title: "Gagal", text: res.message });
            }
          },
          "json",
        ).always(function () {
          $btn
            .prop("disabled", false)
            .html(`<i class="bi bi-save"></i> Simpan`);
        });
      });
    },
  );
}

function loadKurikulumTabs() {
  $.getJSON(
    Rps.api,
    { action: "kurikulum_list", unit_id: Rps.unitId || 0 },
    function (res) {
      if (!res.success) return;

      Rps.kurikulumCache = (res.data || []).filter((k) => k.is_active == 1);

      if (!Rps.kurikulumCache.length) {
        $("#rpsKurikulumTabsWrap").hide();
        $("#rpsMkSelectorCard").hide();
        return;
      }

      let html = "";

      Rps.kurikulumCache.forEach(function (k, idx) {
        html += `
        <li class="nav-item">
          <button type="button" class="nav-link rps-kurikulum-tab ${idx === 0 ? "active" : ""}" data-kurikulum-id="${k.id}">
            ${k.tahun} - ${escapeHtml(k.nama)}
          </button>
        </li>
      `;
      });

      $("#rpsKurikulumTabs").html(html);
      $("#rpsKurikulumTabsWrap").toggle(Rps.kurikulumCache.length > 1);

      selectKurikulumTab(Rps.kurikulumCache[0].id);
    },
  );
}

$(document).on("click", ".rps-kurikulum-tab", function () {
  $(".rps-kurikulum-tab").removeClass("active");
  $(this).addClass("active");
  selectKurikulumTab($(this).data("kurikulum-id"));
});

function selectKurikulumTab(kurikulumId) {
  Rps.kurikulumId = kurikulumId;
  Rps.mataKuliahId = null;
  $("#rpsMkInfoCard").hide();
  $("#rpsListCard").hide();

  $.getJSON(
    Rps.api,
    { action: "mata_kuliah_list", kurikulum_id: kurikulumId },
    function (res) {
      if (!res.success) return;

      Rps.mkCache = res.data;

      let html = '<option value="">-- Pilih Mata Kuliah --</option>';
      Rps.mkCache.forEach(function (mk) {
        html += `<option value="${mk.id}">${mk.code ? mk.code + " - " : ""}${escapeHtml(mk.name)}</option>`;
      });

      $("#rpsMkSelector").html(html).val("");
      $("#rpsMkSelectorCard").show();
    },
  );
}

function reloadMkCacheSilent(callback) {
  $.getJSON(
    Rps.api,
    { action: "mata_kuliah_list", kurikulum_id: Rps.kurikulumId },
    function (res) {
      if (res.success) {
        Rps.mkCache = res.data;
      }
      if (typeof callback === "function") callback();
    },
  );
}

function showMkInfo() {
  const mk = Rps.mkCache.find((m) => String(m.id) === String(Rps.mataKuliahId));
  const kur = Rps.kurikulumCache.find(
    (k) => String(k.id) === String(Rps.kurikulumId),
  );

  if (!mk) return;

  $("#rpsInfoTahun").text(kur ? kur.tahun : "-");
  $("#rpsInfoKode").text(mk.code || "-");
  $("#rpsInfoNama").text(mk.name);

  const totalSks =
    parseInt(mk.sks_tatap_muka || 0) +
    parseInt(mk.sks_praktikum || 0) +
    parseInt(mk.sks_praktek_lapangan || 0) +
    parseInt(mk.sks_simulasi || 0);
  Rps.currentMkSks = totalSks || 1;
  $("#rpsInfoSks").text(totalSks);
}

function loadSubCpmkCache() {
  $.getJSON(
    Rps.api,
    { action: "sub_cpmk_list", mata_kuliah_id: Rps.mataKuliahId },
    function (res) {
      if (res.success) Rps.subCpmkCache = res.data;
    },
  );
}

function fillSubCpmkOptions() {
  let html = '<option value="">-- Pilih Sub-CPMK --</option>';
  Rps.subCpmkCache.forEach(function (s) {
    html += `<option value="${s.id}">${escapeHtml(s.cpmk_code)} - ${escapeHtml(s.code)}</option>`;
  });
  $("#rps_sub_cpmk").html(html);
}

function loadBahanKajianChecklist(subCpmkId, selectedIds) {
  if (!subCpmkId) {
    $("#rpsBahanKajianChecklist").html(
      '<p class="text-muted small mb-0">Pilih Sub-CPMK terlebih dahulu.</p>',
    );
    return;
  }

  $.getJSON(
    Rps.api,
    { action: "bahan_kajian_list", sub_cpmk_id: subCpmkId },
    function (res) {
      if (!res.success || !res.data.length) {
        $("#rpsBahanKajianChecklist").html(
          '<p class="text-muted small mb-0">Belum ada Bahan Kajian untuk Sub-CPMK ini.</p>',
        );
        return;
      }

      let html = "";

      res.data.forEach(function (bk) {
        const checked =
          selectedIds.includes(bk.id) || selectedIds.includes(String(bk.id))
            ? "checked"
            : "";
        html += `
        <div class="form-check">
          <input class="form-check-input rps-bk-checkbox" type="checkbox" value="${bk.id}" data-nama="${escapeAttr(bk.nama_bahan_kajian)}" id="rps_bk_${bk.id}" ${checked}>
          <label class="form-check-label small" for="rps_bk_${bk.id}">${escapeHtml(bk.nama_bahan_kajian)}</label>
        </div>
      `;
      });

      $("#rpsBahanKajianChecklist").html(html);
    },
  );
}

function loadBentukPembelajaranCache() {
  $.getJSON(Rps.api, { action: "bentuk_pembelajaran_list" }, function (res) {
    if (res.success) Rps.bentukPembelajaranCache = res.data;
  });
}

function loadMetodeCache(callback) {
  $.getJSON(Rps.api, { action: "metode_pembelajaran_list" }, function (res) {
    if (!res.success) return;

    Rps.metodeLuringCache = res.data.filter((m) => m.kategori !== "Daring");
    Rps.metodeDaringCache = res.data.filter((m) => m.kategori === "Daring");

    if (typeof callback === "function") {
      callback();
    }
  });
}

function loadIndikatorCache() {
  $.getJSON(Rps.api, { action: "indikator_list" }, function (res) {
    if (res.success) Rps.indikatorCache = res.data;
  });
}

function renderBentukLuringChecklist(selectedIds) {
  let html = "";

  Rps.bentukPembelajaranCache.forEach(function (b) {
    const checked =
      selectedIds.includes(b.id) || selectedIds.includes(String(b.id))
        ? "checked"
        : "";
    html += `
      <div class="form-check">
        <input class="form-check-input rps-bentuk-luring-checkbox" type="checkbox" value="${b.id}" data-kategori="${escapeAttr(b.kategori)}" id="rps_bl_${b.id}" ${checked}>
        <label class="form-check-label small" for="rps_bl_${b.id}">${escapeHtml(b.nama_bentuk)}</label>
      </div>
    `;
  });

  $("#rpsBentukLuringChecklist").html(html);
}

function renderMetodeLuringChecklist(selectedIds) {
  let html = "";

  Rps.metodeLuringCache.forEach(function (m) {
    const checked =
      selectedIds.includes(m.id) || selectedIds.includes(String(m.id))
        ? "checked"
        : "";
    html += `
      <div class="form-check">
        <input class="form-check-input rps-metode-checkbox rps-metode-luring-checkbox" type="checkbox" value="${m.id}" data-aktivitas="${escapeAttr(m.aktivitas_mahasiswa || "")}" id="rps_ml_${m.id}" ${checked}>
        <label class="form-check-label small" for="rps_ml_${m.id}">${escapeHtml(m.nama_metode)}</label>
      </div>
    `;
  });

  $("#rpsMetodeLuringChecklist").html(html);
}

function renderMetodeDaringChecklist(selectedIds) {
  let html = "";

  Rps.metodeDaringCache.forEach(function (m) {
    const checked =
      selectedIds.includes(m.id) || selectedIds.includes(String(m.id))
        ? "checked"
        : "";
    html += `
      <div class="form-check">
        <input class="form-check-input rps-metode-checkbox rps-metode-daring-checkbox" type="checkbox" value="${m.id}" data-aktivitas="${escapeAttr(m.aktivitas_mahasiswa || "")}" id="rps_md_${m.id}" ${checked}>
        <label class="form-check-label small" for="rps_md_${m.id}">${escapeHtml(m.nama_metode)}</label>
      </div>
    `;
  });

  $("#rpsMetodeDaringChecklist").html(html);
}

function updateBuilderAktivitasOptions() {
  const items = [];

  $(".rps-metode-checkbox:checked").each(function () {
    const aktivitas = $(this).data("aktivitas");
    if (aktivitas && aktivitas.trim() !== "") {
      items.push(aktivitas);
    }
  });

  let html = "";

  if (!items.length) {
    html = '<option value="">-- Pilih Metode dulu --</option>';
  } else {
    items.forEach(function (aktivitas) {
      html += `<option value="${escapeAttr(aktivitas)}">${escapeHtml(aktivitas.substring(0, 70))}${aktivitas.length > 70 ? "..." : ""}</option>`;
    });
  }

  $("#rps_builder_aktivitas").html(html);
}

function updateBuilderTopikOptions() {
  const items = [];

  $(".rps-bk-checkbox:checked").each(function () {
    items.push($(this).data("nama"));
  });

  let html = "";

  if (!items.length) {
    html = '<option value="">-- Centang Bahan Kajian dulu --</option>';
  } else {
    items.forEach(function (nama) {
      html += `<option value="${escapeAttr(nama)}">${escapeHtml(nama)}</option>`;
    });
  }

  $("#rps_builder_topik, #rps_builder_topik2").html(html);
}

function loadRencanaEvaluasiForPertemuan(selectedIds) {
  selectedIds = selectedIds || [];
  const pertemuan = $("#rps_pertemuan").val();

  if (!pertemuan || !Rps.mataKuliahId) {
    $("#rpsRencanaEvaluasiChecklist").html("");
    $("#rpsRencanaEvaluasiKosong").hide();
    $("#rpsDetailBobotTotal").text("0%");
    return;
  }

  $.getJSON(
    Rps.api,
    {
      action: "rencana_evaluasi_by_pertemuan",
      mata_kuliah_id: Rps.mataKuliahId,
      pertemuan: pertemuan,
    },
    function (res) {
      if (!res.success || !res.data.length) {
        $("#rpsRencanaEvaluasiChecklist").html("");
        $("#rpsRencanaEvaluasiKosong").show();
        $("#rpsDetailBobotTotal").text("0%");
        return;
      }

      Rps.rencanaEvaluasiCache = res.data;
      $("#rpsRencanaEvaluasiKosong").hide();

      let html = "";

      res.data.forEach(function (item) {
        const checked =
          selectedIds.includes(item.id) || selectedIds.includes(String(item.id))
            ? "checked"
            : "";

        let indikatorHtml = "";
        if (item.indikator_kognitif) {
          indikatorHtml += `<div class="mb-1"><span class="text-primary fw-semibold">Kognitif:</span> ${escapeHtml(item.indikator_kognitif)}</div>`;
        }
        if (item.indikator_afektif) {
          indikatorHtml += `<div class="mb-1"><span class="text-success fw-semibold">Afektif:</span> ${escapeHtml(item.indikator_afektif)}</div>`;
        }
        if (item.indikator_psikomotorik) {
          indikatorHtml += `<div class="mb-1"><span class="text-warning fw-semibold">Psikomotorik:</span> ${escapeHtml(item.indikator_psikomotorik)}</div>`;
        }

        html += `
          <div class="form-check mb-2 pb-2" style="border-bottom:1px dashed #e2dff2;">
            <input class="form-check-input rps-reo-checkbox" type="checkbox" value="${item.id}" id="rps_reo_${item.id}" ${checked}>
            <label class="form-check-label small" for="rps_reo_${item.id}">
              <strong>${escapeHtml(item.basis_evaluasi)}</strong> (${parseFloat(item.bobot_persen).toFixed(2)}%)
              <div class="text-muted" style="font-size:10.5px;">Komponen: ${formatKomponenSiakad(item.komponen_siakad)}</div>
              <div style="font-size:10.5px;">${indikatorHtml || '<span class="text-muted">Belum ada Indikator terkait.</span>'}</div>
            </label>
          </div>
        `;
      });

      $("#rpsRencanaEvaluasiChecklist").html(html);
      hitungTotalBobotRencanaEvaluasi();
    },
  );
}

function hitungTotalBobotRencanaEvaluasi() {
  const rpsId = $("#rps_id").val();
  let total = 0;

  $(".rps-reo-checkbox:checked").each(function () {
    const reoId = $(this).val();
    const item = (Rps.rencanaEvaluasiCache || []).find(
      (r) => String(r.id) === String(reoId),
    );

    if (item) {
      const jumlahDipakai = countRpsUsingRencanaEvaluasi(reoId, rpsId);
      total += parseFloat(item.bobot_persen) / jumlahDipakai;
    }
  });

  $("#rpsDetailBobotTotal").text(total.toFixed(2) + "%");
}

function formatKomponenSiakad(text) {
  return (text || "-")
    .split(",")
    .map(function (part) {
      return part
        .trim()
        .toLowerCase()
        .replace(/\b\w/g, function (c) {
          return c.toUpperCase();
        });
    })
    .join(", ");
}

function countRpsUsingRencanaEvaluasi(rencanaEvaluasiId, excludeRpsId) {
  // Dihitung dari tabel yang sedang tampil di layar (perkiraan real-time, angka pasti akan disinkronkan ulang oleh server saat Simpan)
  let count = 1; // baris ini sendiri

  $(
    "#rpsTableBody tr[data-rencana-evaluasi-id='" + rencanaEvaluasiId + "']",
  ).each(function () {
    if (String($(this).data("rps-id")) !== String(excludeRpsId)) {
      count++;
    }
  });

  return count;
}

function loadTable() {
  $("#rpsTableBody").html(
    '<tr><td colspan="9" class="text-center text-muted">Memuat data...</td></tr>',
  );

  $.getJSON(
    Rps.api,
    { action: "list", mata_kuliah_id: Rps.mataKuliahId },
    function (res) {
      if (!res.success) {
        $("#rpsTableBody").html(
          '<tr><td colspan="9" class="text-center text-danger">' +
            res.message +
            "</td></tr>",
        );
        return;
      }

      let totalBobot = 0;

      if (!res.data.length) {
        $("#rpsTableBody").html(
          '<tr><td colspan="9" class="text-center text-muted">Belum ada data.</td></tr>',
        );
      } else {
        let html = "";

        res.data.forEach(function (row) {
          totalBobot += parseFloat(row.bobot_penilaian || 0);

          const bentukLuringBadges = (row.bentuk_luring || [])
            .map(
              (b) =>
                `<span class="mini-badge">${escapeHtml(b.nama_bentuk)}</span>`,
            )
            .join("");
          const metodeLuringBadges = (row.metode_luring || [])
            .map(
              (m) =>
                `<span class="mini-badge">${escapeHtml(m.nama_metode)}</span>`,
            )
            .join("");
          const metodeDaringBadges = (row.metode_daring || [])
            .map(
              (m) =>
                `<span class="mini-badge">${escapeHtml(m.nama_metode)}</span>`,
            )
            .join("");

          const luringHtml =
            bentukLuringBadges || metodeLuringBadges
              ? `${bentukLuringBadges}${metodeLuringBadges}`
              : '<span class="text-muted small">-</span>';
          const daringHtml =
            metodeDaringBadges || '<span class="text-muted small">-</span>';

          let indikatorHtml = '<span class="text-muted small">-</span>';
          let kriteriaBentukHtml = '<span class="text-muted small">-</span>';

          const indikatorParts = [];
          if (row.indikator_umum) {
            indikatorParts.push(
              `<div class="small mb-1"><strong>Umum:</strong> ${escapeHtml(row.indikator_umum)}</div>`,
            );
          }
          if (row.indikator_khusus) {
            indikatorParts.push(
              `<div class="small mb-1"><strong>Khusus:</strong> ${escapeHtml(row.indikator_khusus)}</div>`,
            );
          }
          if (indikatorParts.length) {
            indikatorHtml = indikatorParts.join("");
          }

          if (row.rencana_evaluasi_list && row.rencana_evaluasi_list.length) {
            let kriteriaBentukParts = [];

            row.rencana_evaluasi_list.forEach(function (re) {
              let kriteriaHtml = "";

              if (re.indikator_kognitif) {
                kriteriaHtml += `<div class="small mb-1"><span class="text-primary fw-semibold">Kognitif:</span> ${escapeHtml(re.indikator_kognitif)}</div>`;
              }
              if (re.indikator_afektif) {
                kriteriaHtml += `<div class="small mb-1"><span class="text-success fw-semibold">Afektif:</span> ${escapeHtml(re.indikator_afektif)}</div>`;
              }
              if (re.indikator_psikomotorik) {
                kriteriaHtml += `<div class="small mb-1"><span class="text-warning fw-semibold">Psikomotorik:</span> ${escapeHtml(re.indikator_psikomotorik)}</div>`;
              }
              if (!kriteriaHtml) {
                kriteriaHtml = '<div class="small text-muted">-</div>';
              }

              let part = `<div class="small mb-1"><strong>${escapeHtml(re.basis_evaluasi)}</strong></div>`;
              part += kriteriaHtml;

              kriteriaBentukParts.push(part);
            });

            kriteriaBentukHtml = kriteriaBentukParts.join('<hr class="my-1">');
          }

          html += `
          <tr data-rps-id="${row.id}" data-rencana-evaluasi-id="${row.rencana_evaluasi_id || ""}">
            <td class="text-center"><span class="badge-pertemuan">${row.pertemuan}</span></td>
            <td>
              <strong>${escapeHtml(row.cpmk_code)} - ${escapeHtml(row.sub_cpmk_code)}</strong>
              ${row.sub_cpmk_description ? `<div class="small text-muted mt-1" style="text-align:left;">${escapeHtml(row.sub_cpmk_description)}</div>` : ""}
            </td>
            <td>${indikatorHtml}</td>
            <td>${kriteriaBentukHtml}</td>
            <td>${luringHtml}</td>
            <td>${daringHtml}</td>
            <td class="text-center">
              ${row.alokasi_waktu_menit} menit
              ${row.alokasi_waktu_rincian ? `<div class="text-muted" style="font-size:9.5px;">${formatRincianWaktu(row.alokasi_waktu_rincian)}</div>` : ""}
            </td>
            <td class="small">${row.materi_pembelajaran ? escapeHtml(row.materi_pembelajaran) : '<span class="text-muted">-</span>'}</td>
            <td class="small">${row.pengalaman_belajar ? escapeHtml(row.pengalaman_belajar) : '<span class="text-muted">-</span>'}</td>
            <td class="text-center">${parseFloat(row.bobot_penilaian).toFixed(2)}%</td>
            <td>
              <button type="button" class="btn btn-sm btn-outline-primary btn-edit-rps" data-id="${row.id}">
                <i class="bi bi-pencil"></i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger btn-delete-rps" data-id="${row.id}">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
        `;
        });

        $("#rpsTableBody").html(html);
      }

      const color =
        Math.abs(totalBobot - 100) < 0.01
          ? "#059669"
          : totalBobot > 100
            ? "#dc2626"
            : "#d97706";
      $("#rpsTotalBobotBadge")
        .css("background", color)
        .text("Total Bobot: " + totalBobot.toFixed(2) + "%");
    },
  );
}

$(document).on("click", ".btn-edit-rps", function () {
  const id = $(this).data("id");

  $.getJSON(Rps.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    $("#rps_id").val(res.data.id);
    $("#rps_mata_kuliah_id").val(res.data.mata_kuliah_id);
    $("#rps_pertemuan").val(res.data.pertemuan);
    $("#rps_materi").val(res.data.materi_pembelajaran);
    $("#rps_pustaka").val(res.data.pustaka);
    $("#rps_pengalaman").val(res.data.pengalaman_belajar);
    $("#rps_alokasi_waktu").val(res.data.alokasi_waktu_menit);
    $("#rps_bobot").val(res.data.bobot_penilaian);
    $("#rpsModalTitle").text("Edit Baris RPS");

    fillSubCpmkOptions();
    $("#rps_sub_cpmk").val(res.data.sub_cpmk_id);
    loadBahanKajianChecklist(
      res.data.sub_cpmk_id,
      res.data.bahan_kajian_ids || [],
    );

    renderBentukLuringChecklist(res.data.bentuk_luring_ids || []);
    loadRencanaEvaluasiForPertemuan(res.data.rencana_evaluasi_ids || []);
    $("#rps_indikator_umum").val(res.data.indikator_umum);
    $("#rps_indikator_khusus").val(res.data.indikator_khusus);
    $("#rps_alokasi_waktu_rincian").html(
      formatRincianWaktu(res.data.alokasi_waktu_rincian || ""),
    );
    $("#rps_alokasi_waktu_rincian_input").val(
      res.data.alokasi_waktu_rincian || "",
    );

    loadMetodeCache(function () {
      renderMetodeLuringChecklist(res.data.metode_luring_ids || []);
      renderMetodeDaringChecklist(res.data.metode_daring_ids || []);
      updateBuilderAktivitasOptions();
    });

    setTimeout(function () {
      updateBuilderTopikOptions();
    }, 300);

    Rps.modal.show();
  });
});

$(document).on("click", ".btn-delete-rps", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Hapus baris RPS ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(Rps.api + "?action=delete", { id: id }, function (response) {
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
  const id = $("#rps_id").val();
  const action = id ? "update" : "create";

  const bahanKajianIds = $(".rps-bk-checkbox:checked")
    .map(function () {
      return $(this).val();
    })
    .get();
  const bentukLuringIds = $(".rps-bentuk-luring-checkbox:checked")
    .map(function () {
      return $(this).val();
    })
    .get();
  const metodeLuringIds = $(".rps-metode-luring-checkbox:checked")
    .map(function () {
      return $(this).val();
    })
    .get();
  const metodeDaringIds = $(".rps-metode-daring-checkbox:checked")
    .map(function () {
      return $(this).val();
    })
    .get();
  const rencanaEvaluasiIds = $(".rps-reo-checkbox:checked")
    .map(function () {
      return $(this).val();
    })
    .get();

  let formData = $("#rpsForm").serialize();

  bahanKajianIds.forEach(function (v) {
    formData += "&bahan_kajian_ids[]=" + v;
  });
  bentukLuringIds.forEach(function (v) {
    formData += "&bentuk_luring_ids[]=" + v;
  });
  metodeLuringIds.forEach(function (v) {
    formData += "&metode_luring_ids[]=" + v;
  });
  metodeDaringIds.forEach(function (v) {
    formData += "&metode_daring_ids[]=" + v;
  });
  rencanaEvaluasiIds.forEach(function (v) {
    formData += "&rencana_evaluasi_ids[]=" + v;
  });

  $.post(Rps.api + "?action=" + action, formData, function (response) {
    if (!response.success) {
      Swal.fire("Gagal", response.message, "error");
      return;
    }

    Rps.modal.hide();
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

function formatRincianWaktu(text) {
  if (!text) return "";
  return text
    .split(" + ")
    .map(function (part) {
      return `<div style="text-align:left;">${escapeHtml(part.trim())}</div>`;
    })
    .join("");
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

function escapeAttr(text) {
  return (text || "").replace(/"/g, "&quot;");
}
