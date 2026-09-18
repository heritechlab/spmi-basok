const SurveyManage = {
  api: SIQUA.BASE_URL + "survey/manage/api.php",
  currentTypeId: null,
  isLayananBased: false,
  layananModal: null,
  categoryModal: null,
  questionModal: null,
};

$(document).ready(function () {
  SurveyManage.layananModal = new bootstrap.Modal(
    document.getElementById("layananModal"),
  );
  SurveyManage.categoryModal = new bootstrap.Modal(
    document.getElementById("categoryModal"),
  );
  SurveyManage.questionModal = new bootstrap.Modal(
    document.getElementById("questionModal"),
  );

  registerEvents();
});

function registerEvents() {
  $("#typeSelector").on("change", function () {
    const typeId = $(this).val();

    if (!typeId) {
      $("#treeContainer").html(
        '<p class="text-muted">Silakan pilih jenis survey terlebih dahulu.</p>',
      );
      return;
    }

    SurveyManage.currentTypeId = typeId;
    loadTree();
  });

  $("#layananForm").on("submit", function (e) {
    e.preventDefault();
    saveLayanan();
  });

  $("#categoryForm").on("submit", function (e) {
    e.preventDefault();
    saveCategory();
  });

  $("#questionForm").on("submit", function (e) {
    e.preventDefault();
    saveQuestion();
  });

  $(document).on("click", ".btn-add-layanan", function () {
    openLayananModal(0, "");
  });

  $(document).on("change", ".unit-pj-select", function () {
    const categoryId = $(this).data("category-id");
    const unitId = $(this).val();
    saveCategoryUnit(categoryId, unitId);
  });

  $(document).on("click", ".btn-edit-layanan", function () {
    openLayananModal($(this).data("id"), $(this).data("name"));
  });

  $(document).on("click", ".btn-delete-layanan", function () {
    confirmDelete(
      "Layanan ini beserta seluruh Aspek dan Pertanyaan di dalamnya",
      function () {
        $.post(
          SurveyManage.api + "?action=delete_layanan",
          { id: $(this).data("id") },
          handleAfterDelete,
        ).fail(showAjaxError);
      }.bind(this),
    );
  });

  $(document).on("click", ".btn-add-category", function () {
    const layananId = $(this).data("layanan-id") || "";
    openCategoryModal(0, "", layananId);
  });

  $(document).on("click", ".btn-edit-category", function () {
    openCategoryModal(
      $(this).data("id"),
      $(this).data("name"),
      $(this).data("layanan-id") || "",
    );
  });

  $(document).on("click", ".btn-delete-category", function () {
    const btn = this;
    confirmDelete(
      "Kategori/Aspek ini beserta seluruh Pertanyaan di dalamnya",
      function () {
        $.post(
          SurveyManage.api + "?action=delete_category",
          { id: $(btn).data("id") },
          handleAfterDelete,
        ).fail(showAjaxError);
      },
    );
  });

  $(document).on("click", ".btn-add-question", function () {
    openQuestionModal(0, "", $(this).data("category-id"));
  });

  $(document).on("click", ".btn-edit-question", function () {
    openQuestionModal(
      $(this).data("id"),
      $(this).data("text"),
      $(this).data("category-id"),
    );
  });

  $(document).on("click", ".btn-delete-question", function () {
    const btn = this;
    confirmDelete("Pertanyaan ini", function () {
      $.post(
        SurveyManage.api + "?action=delete_question",
        { id: $(btn).data("id") },
        handleAfterDelete,
      ).fail(showAjaxError);
    });
  });
}

function confirmDelete(label, onConfirm) {
  Swal.fire({
    icon: "warning",
    title: "Hapus " + label + "?",
    text: "Data ini akan dinonaktifkan dan tidak akan muncul lagi di form survey.",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
  }).then(function (result) {
    if (result.isConfirmed) {
      onConfirm();
    }
  });
}

function handleAfterDelete(response) {
  if (!response.success) {
    Swal.fire("Gagal", response.message, "error");
    return;
  }
  Swal.fire({
    icon: "success",
    title: "Berhasil",
    text: response.message,
    timer: 1200,
    showConfirmButton: false,
  });
  loadTree();
}

function showAjaxError() {
  Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
}

/* ===================== MUAT & GAMBAR POHON ===================== */

function loadTree() {
  $("#treeContainer").html('<p class="text-muted">Memuat data...</p>');

  $.getJSON(
    SurveyManage.api,
    { action: "is_layanan_based", type_id: SurveyManage.currentTypeId },
    function (res) {
      SurveyManage.isLayananBased = !!res.data;

      $.getJSON(
        SurveyManage.api,
        { action: "tree", type_id: SurveyManage.currentTypeId },
        function (treeRes) {
          if (!treeRes.success) {
            $("#treeContainer").html(
              '<p class="text-danger">' + treeRes.message + "</p>",
            );
            return;
          }

          if (SurveyManage.isLayananBased) {
            renderLayananTree(treeRes.data);
          } else {
            renderCategoryTree(treeRes.data);
          }
        },
      );
    },
  );
}

function renderLayananTree(layananList) {
  let html = `
    <div class="d-flex justify-content-end mb-3">
      <button class="btn btn-primary btn-sm btn-add-layanan"><i class="bi bi-plus-circle"></i> Tambah Layanan</button>
    </div>
  `;

  if (!layananList.length) {
    html +=
      '<p class="text-muted">Belum ada Layanan untuk jenis survey ini.</p>';
  }

  layananList.forEach(function (layanan) {
    html += `
      <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>Layanan: ${escapeHtml(layanan.name)}</strong>
          <div class="d-flex gap-1">
            <button class="btn btn-outline-primary btn-sm btn-edit-layanan" data-id="${layanan.id}" data-name="${escapeAttr(layanan.name)}"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-outline-danger btn-sm btn-delete-layanan" data-id="${layanan.id}"><i class="bi bi-trash"></i></button>
            <button class="btn btn-outline-success btn-sm btn-add-category" data-layanan-id="${layanan.id}"><i class="bi bi-plus-circle"></i> Aspek</button>
          </div>
        </div>
        <div class="card-body">
    `;

    if (!layanan.aspek || !layanan.aspek.length) {
      html +=
        '<p class="text-muted small mb-0">Belum ada Aspek pada Layanan ini.</p>';
    }

    (layanan.aspek || []).forEach(function (aspek) {
      html += renderAspekBlock(aspek);
    });

    html += `</div></div>`;
  });

  $("#treeContainer").html(html);
}

function renderAspekBlock(aspek) {
  let html = `
    <div class="border rounded p-3 mb-3">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <strong class="small">${escapeHtml(aspek.name)}</strong>
        <div class="d-flex gap-1">
          <button class="btn btn-outline-primary btn-sm btn-edit-category" data-id="${aspek.id}" data-name="${escapeAttr(aspek.name)}"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-outline-danger btn-sm btn-delete-category" data-id="${aspek.id}"><i class="bi bi-trash"></i></button>
        </div>
      </div>
      <ul class="list-group mb-2">
  `;

  (aspek.questions || []).forEach(function (q) {
    html += `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <span style="font-size:13px;">${escapeHtml(q.question_text)}</span>
        <div class="d-flex gap-1">
          <button class="btn btn-outline-primary btn-sm btn-edit-question" data-id="${q.id}" data-text="${escapeAttr(q.question_text)}" data-category-id="${aspek.id}"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-outline-danger btn-sm btn-delete-question" data-id="${q.id}"><i class="bi bi-trash"></i></button>
        </div>
      </li>
    `;
  });

  if (!aspek.questions || !aspek.questions.length) {
    html +=
      '<li class="list-group-item text-muted small">Belum ada pertanyaan.</li>';
  }

  html += `
      </ul>
      <button class="btn btn-outline-success btn-sm btn-add-question" data-category-id="${aspek.id}"><i class="bi bi-plus-circle"></i> Tambah Pertanyaan</button>
    </div>
  `;

  return html;
}

function renderCategoryTree(categories) {
  let html = `
    <div class="d-flex justify-content-end mb-3">
      <button class="btn btn-primary btn-sm btn-add-category"><i class="bi bi-plus-circle"></i> Tambah Kategori</button>
    </div>
  `;

  if (!categories.length) {
    html +=
      '<p class="text-muted">Belum ada Kategori untuk jenis survey ini.</p>';
  }

  categories.forEach(function (cat) {
    html += `
      <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <strong>${escapeHtml(cat.name)}</strong>
          <div class="d-flex align-items-center gap-2">
            <label class="small text-muted mb-0">Unit PJ:</label>
            <select class="form-select form-select-sm unit-pj-select" data-category-id="${cat.id}" style="width:200px;">
              <option value="">Memuat...</option>
            </select>
            <button class="btn btn-outline-primary btn-sm btn-edit-category" data-id="${cat.id}" data-name="${escapeAttr(cat.name)}"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-outline-danger btn-sm btn-delete-category" data-id="${cat.id}"><i class="bi bi-trash"></i></button>
          </div>
        </div>
        <div class="card-body">
          <ul class="list-group mb-2">
    `;

    (cat.questions || []).forEach(function (q) {
      html += `
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span style="font-size:13px;">${escapeHtml(q.question_text)}</span>
          <div class="d-flex gap-1">
            <button class="btn btn-outline-primary btn-sm btn-edit-question" data-id="${q.id}" data-text="${escapeAttr(q.question_text)}" data-category-id="${cat.id}"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-outline-danger btn-sm btn-delete-question" data-id="${q.id}"><i class="bi bi-trash"></i></button>
          </div>
        </li>
      `;
    });

    if (!cat.questions || !cat.questions.length) {
      html +=
        '<li class="list-group-item text-muted small">Belum ada pertanyaan.</li>';
    }

    html += `
          </ul>
          <button class="btn btn-outline-success btn-sm btn-add-question" data-category-id="${cat.id}"><i class="bi bi-plus-circle"></i> Tambah Pertanyaan</button>
        </div>
      </div>
    `;
  });

  $("#treeContainer").html(html);

  loadUnitDropdowns(categories);
}

/* ===================== MODAL: LAYANAN ===================== */

function openLayananModal(id, name) {
  $("#layanan_id").val(id);
  $("#layanan_type_id").val(SurveyManage.currentTypeId);
  $("#layanan_name").val(name);
  $("#layananModalTitle").text(id ? "Edit Layanan" : "Tambah Layanan");
  SurveyManage.layananModal.show();
}

function saveLayanan() {
  const formData = $("#layananForm").serialize();

  $.post(
    SurveyManage.api + "?action=save_layanan",
    formData,
    function (response) {
      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }
      SurveyManage.layananModal.hide();
      Swal.fire({
        icon: "success",
        title: "Berhasil",
        text: response.message,
        timer: 1200,
        showConfirmButton: false,
      });
      loadTree();
    },
  ).fail(showAjaxError);
}

/* ===================== MODAL: KATEGORI / ASPEK ===================== */

function openCategoryModal(id, name, layananId) {
  $("#category_id").val(id);
  $("#category_type_id").val(SurveyManage.currentTypeId);
  $("#category_layanan_id").val(layananId);
  $("#category_name").val(name);
  $("#categoryModalTitle").text(
    id ? "Edit Kategori/Aspek" : "Tambah Kategori/Aspek",
  );
  SurveyManage.categoryModal.show();
}

function saveCategory() {
  const formData = $("#categoryForm").serialize();

  $.post(
    SurveyManage.api + "?action=save_category",
    formData,
    function (response) {
      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }
      SurveyManage.categoryModal.hide();
      Swal.fire({
        icon: "success",
        title: "Berhasil",
        text: response.message,
        timer: 1200,
        showConfirmButton: false,
      });
      loadTree();
    },
  ).fail(showAjaxError);
}

function loadUnitDropdowns(categories) {
  $.getJSON(SurveyManage.api, { action: "get_units" }, function (res) {
    if (!res.success) return;

    const optionsHtml = res.data
      .map(
        (u) =>
          `<option value="${u.id}">${escapeHtml(u.code + " - " + u.name)}</option>`,
      )
      .join("");

    $(".unit-pj-select").each(function () {
      const categoryId = parseInt($(this).data("category-id"), 10);
      const cat = categories.find((c) => c.id == categoryId);
      const currentUnitId = cat ? cat.responsible_unit_id : null;

      $(this).html(
        '<option value="">-- Belum Ditentukan --</option>' + optionsHtml,
      );

      if (currentUnitId) {
        $(this).val(currentUnitId);
      }
    });
  });
}

function saveCategoryUnit(categoryId, unitId) {
  $.post(
    SurveyManage.api + "?action=save_category_unit",
    { category_id: categoryId, unit_id: unitId },
    function (response) {
      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }
      Swal.fire({
        icon: "success",
        title: "Tersimpan",
        timer: 900,
        showConfirmButton: false,
      });
    },
  ).fail(showAjaxError);
}

/* ===================== MODAL: PERTANYAAN ===================== */

function openQuestionModal(id, text, categoryId) {
  $("#question_id").val(id);
  $("#question_category_id").val(categoryId);
  $("#question_text").val(text);
  $("#questionModalTitle").text(id ? "Edit Pertanyaan" : "Tambah Pertanyaan");
  SurveyManage.questionModal.show();
}

function saveQuestion() {
  const formData = $("#questionForm").serialize();

  $.post(
    SurveyManage.api + "?action=save_question",
    formData,
    function (response) {
      if (!response.success) {
        Swal.fire("Gagal", response.message, "error");
        return;
      }
      SurveyManage.questionModal.hide();
      Swal.fire({
        icon: "success",
        title: "Berhasil",
        text: response.message,
        timer: 1200,
        showConfirmButton: false,
      });
      loadTree();
    },
  ).fail(showAjaxError);
}

/* ===================== UTIL ===================== */

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

function escapeAttr(text) {
  return (text || "").replace(/"/g, "&quot;");
}
