const Assignment = {
  api: SIQUA.BASE_URL + "audit/assignments/api.php",
  table: null,
  modal: null,
  form: null,
  isEdit: false,
  currentId: null,
};

$(document).ready(function () {
  Assignment.modal = new bootstrap.Modal(
    document.getElementById("assignmentModal"),
  );
  Assignment.form = $("#assignmentForm");

  initializeTable();
  loadDropdowns();
  registerEvents();
});

function initializeTable() {
  Assignment.table = $("#tableAssignment").DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    autoWidth: false,
    ordering: false,
    searching: true,
    pageLength: 10,
    lengthMenu: [
      [10, 25, 50, 100],
      [10, 25, 50, 100],
    ],

    ajax: {
      url: Assignment.api,
      type: "GET",
      data: function (d) {
        d.action = "list";
        d.period_id = $("#filterPeriod").val();
        d.status = $("#filterStatus").val();
      },
    },

    columnDefs: [
      {
        targets: [6, 7, 8, 9],
        className: "text-center",
        orderable: false,
        searchable: false,
      },
    ],

    language: {
      processing: "Memuat data...",
      search: "Cari :",
      lengthMenu: "Tampilkan _MENU_ data",
      info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
      infoEmpty: "Tidak ada data",
      zeroRecords: "Data tidak ditemukan",
      paginate: {
        first: "Awal",
        last: "Akhir",
        next: "\u203a",
        previous: "\u2039",
      },
    },
  });
}

function reloadTable() {
  Assignment.table.ajax.reload(null, false);
}

function refreshStatistics() {
  $.getJSON(Assignment.api, { action: "statistics" }, function (res) {
    if (!res.success) return;

    $("#statTotal").text(res.data.total ?? 0);
    $("#statDraft").text(res.data.draft ?? 0);
    $("#statDijadwalkan").text(res.data.dijadwalkan ?? 0);
    $("#statBerlangsung").text(res.data.berlangsung ?? 0);
    $("#statSelesai").text(res.data.selesai ?? 0);
  });
}

function loadDropdowns() {
  $.getJSON(Assignment.api, { action: "periods" }, function (res) {
    if (!res.success) return;
    const $period = $("#period_id, #filterPeriod");
    res.data.forEach(function (row) {
      $("#period_id").append(
        `<option value="${row.id}">${row.period_name}</option>`,
      );
    });
  });

  $.getJSON(Assignment.api, { action: "units" }, function (res) {
    if (!res.success) return;
    const $unit = $("#auditee_id");
    res.data.forEach(function (row) {
      $unit.append(
        `<option value="${row.id}">${row.code} - ${row.name}</option>`,
      );
    });
  });

  $.getJSON(Assignment.api, { action: "auditors" }, function (res) {
    if (!res.success) return;
    const $auditor = $("#lead_auditor");
    res.data.forEach(function (row) {
      $auditor.append(`<option value="${row.id}">${row.full_name}</option>`);
    });
  });
  $.getJSON(Assignment.api, { action: "standards" }, function (res) {
    if (!res.success) return;
    let html = "";
    res.data.forEach(function (row) {
      html += `
        <div class="form-check">
          <input class="form-check-input standard-checkbox" type="checkbox" value="${row.id}" id="std_${row.id}">
          <label class="form-check-label" for="std_${row.id}">${row.code} - ${row.name}</label>
        </div>
      `;
    });
    $("#standardCheckboxList").html(html);
  });
  $.getJSON(Assignment.api, { action: "team_candidates" }, function (res) {
    if (!res.success) return;
    let html = "";
    res.data.forEach(function (row) {
      html += `
        <div class="form-check">
          <input class="form-check-input team-member-checkbox" type="checkbox" value="${row.id}" id="team_${row.id}">
          <label class="form-check-label" for="team_${row.id}">${row.full_name}</label>
        </div>
      `;
    });
    $("#teamMemberCheckboxList").html(html);
  });
}

function resetForm() {
  document.getElementById("assignmentForm").reset();
  $("#id").val("");
  $("#status").val("Draft");
  Assignment.isEdit = false;
  Assignment.currentId = null;
  $(".standard-checkbox").prop("checked", false);
  $(".team-member-checkbox").prop("checked", false);
}

function registerEvents() {
  $("#filterPeriod, #filterStatus").on("change", function () {
    reloadTable();
  });

  $("#btnAddAssignment").on("click", function () {
    resetForm();
    Assignment.isEdit = false;
    Assignment.currentId = null;
    $("#modalTitle").text("Tambah Penugasan Audit");
    Assignment.modal.show();
  });

  $("#tableAssignment").on("click", ".btn-edit", function () {
    loadData($(this).data("id"));
  });

  Assignment.form.on("submit", function (e) {
    e.preventDefault();
    saveData();
  });

  $("#assignmentModal").on("hidden.bs.modal", function () {
    resetForm();
  });
  $("#tableAssignment").on("click", ".btn-delete", function () {
    deleteData($(this).data("id"));
  });
  $("#tableAssignment").on("click", ".btn-approve", function () {
    approveAssignment($(this).data("id"));
  });
}

function approveAssignment(id) {
  Swal.fire({
    icon: "question",
    title: "Setujui penugasan ini?",
    text: "Auditee akan dianggap menyetujui penugasan ini (fitur email otomatis menyusul).",
    showCancelButton: true,
    confirmButtonColor: "#198754",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Ya, Setujui",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: Assignment.api + "?action=approve",
      type: "POST",
      dataType: "json",
      data: { id: id },
      success: function (response) {
        if (response.success) {
          reloadTable();
          refreshStatistics();
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: response.message,
            timer: 1500,
            showConfirmButton: false,
          });
        } else {
          showError(response.message);
        }
      },
      error: function () {
        showError("Gagal memproses persetujuan.");
      },
    });
  });
}

function loadData(id) {
  $.ajax({
    url: Assignment.api,
    type: "GET",
    dataType: "json",
    data: { action: "get", id: id },
    beforeSend: function () {
      showLoading();
    },
    success: function (response) {
      Swal.close();

      if (!response.success) {
        showError(response.message);
        return;
      }

      const row = response.data;

      resetForm();

      $("#id").val(row.id);
      $("#assignment_number").val(row.assignment_number);
      $("#period_id").val(row.period_id);
      $("#auditee_id").val(row.auditee_id);
      $("#lead_auditor").val(row.lead_auditor);
      $("#audit_type").val(row.audit_type);
      $("#audit_date").val(row.audit_date);
      $("#status").val(row.status);
      $("#notes").val(row.notes);
      $(".standard-checkbox").prop("checked", false);
      (row.standard_ids || []).forEach(function (sid) {
        $("#std_" + sid).prop("checked", true);
      });
      $(".team-member-checkbox").prop("checked", false);
      (row.team_member_ids || []).forEach(function (uid) {
        $("#team_" + uid).prop("checked", true);
      });

      Assignment.isEdit = true;
      Assignment.currentId = row.id;

      $("#modalTitle").text("Edit Penugasan Audit");

      Assignment.modal.show();
    },
    error: function () {
      Swal.close();
      showError("Gagal mengambil data.");
    },
  });
}

function saveData() {
  const formData = new FormData(document.getElementById("assignmentForm"));

  $(".standard-checkbox:checked").each(function () {
    formData.append("standard_ids[]", $(this).val());
  });
  $(".team-member-checkbox:checked").each(function () {
    formData.append("team_member_ids[]", $(this).val());
  });

  const action = Assignment.isEdit ? "update" : "create";
  const idParam = Assignment.isEdit ? "&id=" + Assignment.currentId : "";

  $.ajax({
    url: Assignment.api + "?action=" + action + idParam,
    type: "POST",
    dataType: "json",
    data: formData,
    processData: false,
    contentType: false,

    beforeSend: function () {
      showLoading();
    },
    success: function (response) {
      Swal.close();

      if (!response.success) {
        showError(response.message);
        return;
      }

      Assignment.modal.hide();
      reloadTable();
      refreshStatistics();

      Swal.fire({
        icon: "success",
        title: "Berhasil",
        text: response.message,
        timer: 1500,
        showConfirmButton: false,
      });
    },
    error: function () {
      Swal.close();
      showError("Terjadi kesalahan pada server.");
    },
  });
}

function deleteData(id) {
  Swal.fire({
    icon: "warning",
    title: "Hapus penugasan ini?",
    text: "Data yang sudah dihapus tidak bisa dikembalikan.",
    showCancelButton: true,
    confirmButtonColor: "#dc3545",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (!result.isConfirmed) return;

    $.ajax({
      url: Assignment.api + "?action=delete",
      type: "POST",
      dataType: "json",
      data: { id: id },
      success: function (response) {
        if (response.success) {
          reloadTable();
          refreshStatistics();
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: response.message,
            timer: 1500,
            showConfirmButton: false,
          });
        } else {
          showError(response.message);
        }
      },
      error: function () {
        showError("Gagal menghapus data.");
      },
    });
  });
}

function showLoading() {
  Swal.fire({
    title: "Memproses...",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });
}

function showError(message) {
  Swal.fire({
    icon: "error",
    title: "Gagal",
    text: message,
  });
}
