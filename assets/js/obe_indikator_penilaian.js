const IndikatorPenilaian = {
  api: SIQUA.BASE_URL + "obe/indikator_penilaian/api.php",
  modal: null,
  rubrikModal: null,
  draftRubrik: [],
};

$(document).ready(function () {
  IndikatorPenilaian.modal = new bootstrap.Modal(
    document.getElementById("ipModal"),
  );
  IndikatorPenilaian.rubrikModal = new bootstrap.Modal(
    document.getElementById("rubrikModal"),
  );

  loadBentukPenilaianOptions();
  loadFilterBentukOptions();
  loadListing();

  $("#ipFilterBentuk, #ipFilterRanah, #ipFilterJenjang").on(
    "change",
    function () {
      loadListing();
    },
  );

  $("#btnAddIndikator").on("click", function () {
    $("#ipForm")[0].reset();
    $("#ip_id").val("");
    $("#ipModalTitle").text("Tambah Indikator Penilaian");
    IndikatorPenilaian.draftRubrik = [];
    renderDraftRubrik();
    $("#ipDraftRubrikWrap").show();
    IndikatorPenilaian.modal.show();
  });

  $("#btnAddDraftRubrikRow").on("click", function () {
    IndikatorPenilaian.draftRubrik.push({ nama_kriteria: "" });
    renderDraftRubrik();
  });

  $(document).on("input", ".draft-rubrik-nama", function () {
    const idx = $(this).data("idx");
    IndikatorPenilaian.draftRubrik[idx].nama_kriteria = $(this).val();
  });

  $(document).on("click", ".btn-remove-draft-rubrik", function () {
    const idx = $(this).data("idx");
    IndikatorPenilaian.draftRubrik.splice(idx, 1);
    renderDraftRubrik();
  });

  $("#btnBuildIndikator").on("click", function () {
    const names = IndikatorPenilaian.draftRubrik
      .map((r) => r.nama_kriteria.trim())
      .filter((n) => n !== "");

    if (!names.length) {
      Swal.fire(
        "Belum ada Kriteria",
        "Isi minimal 1 baris Kriteria Rubrik dulu.",
        "warning",
      );
      return;
    }

    let text;
    if (names.length === 1) {
      text = names[0];
    } else {
      text = names.slice(0, -1).join(", ") + ", dan " + names[names.length - 1];
    }

    $("#ip_indikator").val(text);
  });

  $("#ipForm").on("submit", function (e) {
    e.preventDefault();
    saveIndikator();
  });

  $("#rubrikForm").on("submit", function (e) {
    e.preventDefault();
    saveRubrik();
  });
});

function loadBentukPenilaianOptions() {
  $.getJSON(
    IndikatorPenilaian.api,
    { action: "bentuk_penilaian_list" },
    function (res) {
      if (!res.success) return;

      let html = '<option value="">-- Pilih Bentuk Penilaian --</option>';

      res.data.forEach(function (b) {
        html += `<option value="${escapeAttr(b.nama_bentuk)}">${escapeHtml(b.nama_bentuk)}</option>`;
      });

      $("#ip_teknik").html(html);
    },
  );
}

function loadFilterBentukOptions() {
  $.getJSON(
    IndikatorPenilaian.api,
    { action: "bentuk_penilaian_list" },
    function (res) {
      if (!res.success) return;

      let html = '<option value="">Semua Bentuk Penilaian</option>';

      res.data.forEach(function (b) {
        html += `<option value="${escapeAttr(b.nama_bentuk)}">${escapeHtml(b.nama_bentuk)}</option>`;
      });

      $("#ipFilterBentuk").html(html);
    },
  );
}

function renderDraftRubrik() {
  let html = "";

  IndikatorPenilaian.draftRubrik.forEach(function (r, idx) {
    html += `
      <tr>
        <td><input type="text" class="form-control form-control-sm draft-rubrik-nama" data-idx="${idx}" value="${escapeAttr(r.nama_kriteria)}" placeholder="Contoh: Ketajaman Analisis"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-draft-rubrik" data-idx="${idx}"><i class="bi bi-x"></i></button></td>
      </tr>
    `;
  });

  $("#ipDraftRubrikBody").html(
    html ||
      '<tr><td colspan="2" class="text-center text-muted">Belum ada baris.</td></tr>',
  );
}

function loadListing() {
  $("#ipListArea").html('<p class="text-center text-muted">Memuat data...</p>');

  const filterBentuk = $("#ipFilterBentuk").val();
  const filterRanah = $("#ipFilterRanah").val();
  const filterJenjang = $("#ipFilterJenjang").val();

  $.getJSON(IndikatorPenilaian.api, { action: "list" }, function (res) {
    if (res.success) {
      res.data = res.data.filter(function (item) {
        if (filterBentuk && item.teknik_penilaian !== filterBentuk)
          return false;
        if (filterRanah && item.taksonomi_ranah !== filterRanah) return false;
        if (filterJenjang && item.taksonomi_jenjang !== filterJenjang)
          return false;
        return true;
      });
    }
    if (!res.success) {
      $("#ipListArea").html(
        '<p class="text-center text-danger">' + res.message + "</p>",
      );
      return;
    }

    if (!res.data.length) {
      $("#ipListArea").html(
        '<p class="text-center text-muted">Tidak ada Indikator Penilaian yang cocok dengan filter.</p>',
      );
      return;
    }

    let html = "";

    res.data.forEach(function (item) {
      html += `
        <div class="indikator-item">
          <div class="indikator-head">
            <div class="indikator-text">
              <span class="teknik-badge">${escapeHtml(item.teknik_penilaian)}</span>
              <span class="teknik-badge" style="background:#eef8f1; color:#059669;">${escapeHtml(item.taksonomi_ranah)}</span>
              <span class="teknik-badge" style="background:#fef3e2; color:#d97706;">${escapeHtml(item.taksonomi_jenjang)}</span>
              ${escapeHtml(item.indikator)}
            </div>
            ${
              IP_CAN_MANAGE
                ? `
            <div class="text-nowrap ms-2">
              <button type="button" class="btn btn-sm btn-outline-primary btn-edit-ip" data-id="${item.id}">
                <i class="bi bi-pencil"></i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger btn-delete-ip" data-id="${item.id}">
                <i class="bi bi-trash"></i>
              </button>
            </div>`
                : ""
            }
          </div>
          <div class="d-flex justify-content-end align-items-center">
            ${
              IP_CAN_MANAGE
                ? `
            <button type="button" class="btn btn-sm btn-outline-secondary btn-add-rubrik" data-indikator-id="${item.id}">
              <i class="bi bi-plus-circle"></i> Tambah Kriteria
            </button>`
                : ""
            }
          </div>
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Nama Kriteria</th>
                <th>Deskripsi</th>
                ${IP_CAN_MANAGE ? '<th width="80">Aksi</th>' : ""}
              </tr>
            </thead>
            <tbody>
      `;

      if (!item.rubrik || !item.rubrik.length) {
        html += `<tr><td colspan="3" class="text-center text-muted">Belum ada Kriteria Rubrik.</td></tr>`;
      } else {
        item.rubrik.forEach(function (r) {
          html += `
            <tr>
              <td>${escapeHtml(r.nama_kriteria)}</td>
              <td>${r.deskripsi ? escapeHtml(r.deskripsi) : '<span class="text-muted">-</span>'}</td>
              ${
                IP_CAN_MANAGE
                  ? `
              <td>
                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-rubrik" data-id="${r.id}" data-indikator-id="${item.id}">
                  <i class="bi bi-pencil"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-rubrik" data-id="${r.id}">
                  <i class="bi bi-trash"></i>
                </button>
              </td>`
                  : ""
              }
            </tr>
          `;
        });
      }

      html += `</tbody></table></div>`;
    });

    $("#ipListArea").html(html);
  });
}

$(document).on("click", ".btn-edit-ip", function () {
  const id = $(this).data("id");

  $.getJSON(IndikatorPenilaian.api, { action: "get", id: id }, function (res) {
    if (!res.success) {
      Swal.fire("Gagal", res.message, "error");
      return;
    }

    $("#ip_id").val(res.data.id);
    $("#ip_teknik").val(res.data.teknik_penilaian);
    $("#ip_ranah").val(res.data.taksonomi_ranah);
    $("#ip_jenjang").val(res.data.taksonomi_jenjang);
    $("#ip_indikator").val(res.data.indikator);
    $("#ip_sort_order").val(res.data.sort_order);
    $("#ipModalTitle").text("Edit Indikator Penilaian");

    IndikatorPenilaian.modal.show();
  });
});

$(document).on("click", ".btn-delete-ip", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Hapus Indikator ini?",
    text: "Seluruh Kriteria Rubrik di dalamnya juga akan ikut terhapus.",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(
      IndikatorPenilaian.api + "?action=delete",
      { id: id },
      function (response) {
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
        loadListing();
      },
    );
  });
});

function saveIndikator() {
  const id = $("#ip_id").val();
  const action = id ? "update" : "create";

  const formData = $("#ipForm").serialize();

  $.post(
    IndikatorPenilaian.api + "?action=" + action,
    formData,
    function (response) {
      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      if (!id && IndikatorPenilaian.draftRubrik.length) {
        const newIndikatorId = response.data.id;
        const validRubrik = IndikatorPenilaian.draftRubrik.filter(
          (r) => r.nama_kriteria.trim() !== "",
        );
        let doneCount = 0;

        if (!validRubrik.length) {
          finishSaveIndikator(response.message);
          return;
        }

        validRubrik.forEach(function (r) {
          $.post(
            IndikatorPenilaian.api + "?action=rubrik_create",
            {
              indikator_penilaian_id: newIndikatorId,
              nama_kriteria: r.nama_kriteria,
              deskripsi: "",
            },
            function () {
              doneCount++;
              if (doneCount === validRubrik.length) {
                finishSaveIndikator(response.message);
              }
            },
          );
        });
      } else {
        finishSaveIndikator(response.message);
      }
    },
  );
}

function finishSaveIndikator(message) {
  IndikatorPenilaian.modal.hide();
  Swal.fire({
    icon: "success",
    title: "Berhasil",
    text: message,
    timer: 1200,
    showConfirmButton: false,
  });
  loadListing();
}

$(document).on("click", ".btn-add-rubrik", function () {
  const indikatorId = $(this).data("indikator-id");

  $("#rubrikForm")[0].reset();
  $("#rb_id").val("");
  $("#rb_indikator_penilaian_id").val(indikatorId);
  $("#rubrikModalTitle").text("Tambah Kriteria Rubrik");
  IndikatorPenilaian.rubrikModal.show();
});

$(document).on("click", ".btn-edit-rubrik", function () {
  const id = $(this).data("id");
  const indikatorId = $(this).data("indikator-id");

  $.getJSON(
    IndikatorPenilaian.api,
    { action: "rubrik_get", id: id },
    function (res) {
      if (!res.success) {
        Swal.fire("Gagal", res.message, "error");
        return;
      }

      $("#rb_id").val(res.data.id);
      $("#rb_indikator_penilaian_id").val(indikatorId);
      $("#rb_nama").val(res.data.nama_kriteria);
      $("#rb_deskripsi").val(res.data.deskripsi);
      $("#rubrikModalTitle").text("Edit Kriteria Rubrik");

      IndikatorPenilaian.rubrikModal.show();
    },
  );
});

$(document).on("click", ".btn-delete-rubrik", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Hapus Kriteria Rubrik ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(
      IndikatorPenilaian.api + "?action=rubrik_delete",
      { id: id },
      function (response) {
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
        loadListing();
      },
    );
  });
});

function saveRubrik() {
  const id = $("#rb_id").val();
  const action = id ? "rubrik_update" : "rubrik_create";

  const formData = $("#rubrikForm").serialize();

  $.post(
    IndikatorPenilaian.api + "?action=" + action,
    formData,
    function (response) {
      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }

      IndikatorPenilaian.rubrikModal.hide();
      Swal.fire({
        icon: "success",
        title: "Berhasil",
        text: response.message,
        timer: 1200,
        showConfirmButton: false,
      });
      loadListing();
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
