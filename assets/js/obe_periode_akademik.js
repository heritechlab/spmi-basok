const Periode = {
  api: SIQUA.BASE_URL + "obe/periode_akademik/api.php",
  modal: null,
};

$(document).ready(function () {
  Periode.modal = new bootstrap.Modal(document.getElementById("padModal"));

  loadTable();

  $("#btnAddPeriode").on("click", function () {
    $("#padForm")[0].reset();
    Periode.modal.show();
  });

  $("#padForm").on("submit", function (e) {
    e.preventDefault();

    $.post(Periode.api + "?action=create", $(this).serialize(), function (res) {
      if (!res.success) {
        Swal.fire("Gagal", res.message, "error");
        return;
      }
      Periode.modal.hide();
      Swal.fire({
        icon: "success",
        title: "Tersimpan",
        timer: 1200,
        showConfirmButton: false,
      });
      loadTable();
    });
  });
});

function loadTable() {
  $.getJSON(Periode.api, { action: "list" }, function (res) {
    populateCopySelectors(res.success ? res.data : []);
    if (!res.success) return;

    if (!res.data.length) {
      $("#padTableBody").html(
        '<tr><td colspan="6" class="text-center text-muted">Belum ada Periode Akademik.</td></tr>',
      );
      return;
    }

    let html = "";
    res.data.forEach(function (p) {
      const statusBadge =
        p.is_active == 1
          ? '<span class="badge-aktif">AKTIF</span>'
          : '<span class="badge-nonaktif">Nonaktif</span>';

      const aksiBtn =
        p.is_active == 1
          ? '<span class="text-muted small">Sedang berjalan</span>'
          : `<button class="btn btn-sm btn-outline-success btn-set-active" data-id="${p.id}"><i class="bi bi-check-circle"></i> Jadikan Aktif</button>
           <button class="btn btn-sm btn-outline-danger btn-delete-periode" data-id="${p.id}"><i class="bi bi-trash"></i></button>`;

      html += `
        <tr>
          <td><strong>${escapeHtml(p.tahun_ajaran)}</strong></td>
          <td>${escapeHtml(p.jenis_semester)}</td>
          <td>${p.tanggal_mulai || "-"}</td>
          <td>${p.tanggal_selesai || "-"}</td>
          <td class="text-center">${statusBadge}</td>
          <td>${aksiBtn}</td>
        </tr>
      `;
    });

    $("#padTableBody").html(html);
  });
}

$(document).on("click", ".btn-set-active", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Jadikan Periode ini aktif?",
    text: "Periode aktif sebelumnya akan otomatis nonaktif.",
    showCancelButton: true,
    confirmButtonText: "Ya, Aktifkan",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(Periode.api + "?action=set_active", { id: id }, function (res) {
      if (!res.success) {
        Swal.fire("Gagal", res.message, "error");
        return;
      }
      Swal.fire({
        icon: "success",
        title: "Diperbarui",
        timer: 1200,
        showConfirmButton: false,
      });
      loadTable();
    });
  });
});

$(document).on("click", ".btn-delete-periode", function () {
  const id = $(this).data("id");

  Swal.fire({
    icon: "warning",
    title: "Hapus Periode ini?",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    confirmButtonColor: "#dc2626",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.post(Periode.api + "?action=delete", { id: id }, function (res) {
      if (!res.success) {
        Swal.fire("Gagal", res.message, "error");
        return;
      }
      Swal.fire({
        icon: "success",
        title: "Terhapus",
        timer: 1200,
        showConfirmButton: false,
      });
      loadTable();
    });
  });
});

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

function populateCopySelectors(periodeList) {
  let options = '<option value="">-- Pilih Periode --</option>';
  periodeList.forEach(function (p) {
    options += `<option value="${p.id}">${escapeHtml(p.tahun_ajaran)} - ${escapeHtml(p.jenis_semester)}${p.is_active == 1 ? " (Aktif)" : ""}</option>`;
  });
  $("#padCopySource, #padCopyTarget").html(options);
}

$(document).on("click", "#btnCopyPeriode", function () {
  const sourceId = $("#padCopySource").val();
  const targetId = $("#padCopyTarget").val();

  if (!sourceId || !targetId) {
    Swal.fire(
      "Belum Lengkap",
      "Pilih Periode sumber dan tujuan dulu.",
      "warning",
    );
    return;
  }

  if (sourceId === targetId) {
    Swal.fire(
      "Tidak Valid",
      "Periode sumber dan tujuan tidak boleh sama.",
      "warning",
    );
    return;
  }

  Swal.fire({
    icon: "warning",
    title: "Salin data ke Periode ini?",
    text: "RPS, Rencana Evaluasi, Tim Teaching, dan Rubrik Penilaian dari Periode sumber akan disalin ke Periode tujuan. Proses ini bisa memakan waktu beberapa saat.",
    showCancelButton: true,
    confirmButtonText: "Ya, Salin",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    Swal.fire({
      title: "Menyalin data...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    $.post(
      Periode.api + "?action=copy",
      { source_periode_id: sourceId, target_periode_id: targetId },
      function (res) {
        if (!res.success) {
          Swal.fire("Gagal", res.message, "error");
          return;
        }
        Swal.fire({ icon: "success", title: "Berhasil", text: res.message });
      },
    ).fail(function () {
      Swal.fire("Gagal", "Tidak dapat menghubungi server.", "error");
    });
  });
});
