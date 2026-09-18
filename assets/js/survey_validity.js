const SurveyValidity = {
  api: SIQUA.BASE_URL + "survey/validity/api.php",
  currentTypeId: null,
};

$(document).ready(function () {
  $("#typeSelector").on("change", function () {
    SurveyValidity.currentTypeId = $(this).val();
    if (SurveyValidity.currentTypeId) {
      loadValidity();
    } else {
      $("#validityContainer").html(
        '<p class="text-muted">Silakan pilih Jenis Survey terlebih dahulu.</p>',
      );
    }
  });

  $(document).on("submit", "#validityForm", function (e) {
    e.preventDefault();
    saveValidity();
  });
});

function loadValidity() {
  $("#validityContainer").html('<p class="text-muted">Memuat data...</p>');

  $.getJSON(
    SurveyValidity.api,
    { action: "get", type_id: SurveyValidity.currentTypeId },
    function (res) {
      if (!res.success) {
        $("#validityContainer").html(
          '<p class="text-danger">' + res.message + "</p>",
        );
        return;
      }

      renderValidity(res.data);
    },
  );
}

function renderValidity(data) {
  const test = data.test || {};
  const items = data.items || [];

  let rowsHtml = "";

  items.forEach(function (it, i) {
    const checked1 = it.is_valid == 1 ? "selected" : "";
    const checked0 = it.is_valid == 0 ? "selected" : "";
    const checkedEmpty =
      it.is_valid === null || it.is_valid === undefined ? "selected" : "";

    rowsHtml += `
      <tr>
        <td class="text-center">${i + 1}</td>
        <td>${escapeHtml(it.category_name)}</td>
        <td>${escapeHtml(it.question_text)}</td>
        <td>
          <input type="number" step="0.001" class="form-control form-control-sm text-center"
            name="items[${it.question_id}][r_hitung]" value="${it.r_hitung !== null ? it.r_hitung : ""}">
        </td>
        <td>
          <select class="form-select form-select-sm" name="items[${it.question_id}][is_valid]">
            <option value="" ${checkedEmpty}>-</option>
            <option value="1" ${checked1}>Valid</option>
            <option value="0" ${checked0}>Tidak Valid</option>
          </select>
        </td>
      </tr>
    `;
  });

  const html = `
    <form id="validityForm">
      <input type="hidden" name="type_id" value="${SurveyValidity.currentTypeId}">

      <div class="card shadow-sm mb-3">
        <div class="card-header"><strong>Info Umum</strong></div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-md-3">
              <label class="form-label small">Jumlah Responden Uji Coba</label>
              <input type="number" class="form-control form-control-sm" name="n_responden" value="${test.n_responden ?? ""}">
            </div>
            <div class="col-md-3">
              <label class="form-label small">r-Tabel</label>
              <input type="number" step="0.001" class="form-control form-control-sm" name="r_tabel" value="${test.r_tabel ?? ""}">
            </div>
            <div class="col-md-3">
              <label class="form-label small">Cronbach's Alpha</label>
              <input type="number" step="0.001" class="form-control form-control-sm" name="cronbach_alpha" value="${test.cronbach_alpha ?? ""}">
            </div>
            <div class="col-md-3">
              <label class="form-label small">Kategori Reliabilitas</label>
              <input type="text" class="form-control form-control-sm" name="reliability_label" value="${escapeAttr(test.reliability_label || "")}" placeholder="mis. Sangat Reliabel">
            </div>
            <div class="col-md-12 mt-2">
              <label class="form-label small">Catatan Tambahan (opsional)</label>
              <textarea class="form-control form-control-sm" name="catatan" rows="2">${escapeHtml(test.catatan || "")}</textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm mb-3">
        <div class="card-header"><strong>Hasil Uji Validitas per Pertanyaan</strong></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-sm" style="font-size:13px;">
              <thead class="table-light">
                <tr>
                  <th width="40">No</th>
                  <th width="150">Kategori</th>
                  <th>Pernyataan</th>
                  <th width="110">r Hitung</th>
                  <th width="110">Status</th>
                </tr>
              </thead>
              <tbody>${rowsHtml}</tbody>
            </table>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
    </form>
  `;

  $("#validityContainer").html(html);
}

function saveValidity() {
  const formData = $("#validityForm").serialize();

  Swal.fire({
    title: "Menyimpan...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  $.post(SurveyValidity.api + "?action=save", formData, function (response) {
    Swal.close();

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
  }).fail(function () {
    Swal.close();
    Swal.fire("Gagal", "Terjadi kesalahan pada server.", "error");
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
