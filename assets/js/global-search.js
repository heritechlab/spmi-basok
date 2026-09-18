let searchDebounceTimer = null;

$(document).ready(function () {
  $("#globalSearchInput").on("input", function () {
    const q = $(this).val().trim();

    clearTimeout(searchDebounceTimer);

    if (q.length < 2) {
      $("#globalSearchResults").hide();
      return;
    }

    $("#globalSearchResults")
      .html('<div class="global-search-loading">Mencari...</div>')
      .show();

    searchDebounceTimer = setTimeout(function () {
      performSearch(q);
    }, 300);
  });

  $(document).on("click", function (e) {
    if (!$(e.target).closest(".topbar-search").length) {
      $("#globalSearchResults").hide();
    }
  });

  $("#globalSearchInput").on("focus", function () {
    if (
      $(this).val().trim().length >= 2 &&
      $("#globalSearchResults").children().length
    ) {
      $("#globalSearchResults").show();
    }
  });
});

function performSearch(q) {
  $.getJSON(SIQUA.BASE_URL + "search/api.php", { q: q }, function (res) {
    if (!res.success) return;

    if (!res.data.length) {
      $("#globalSearchResults").html(
        '<div class="global-search-empty">Tidak ditemukan hasil untuk "' +
          q +
          '".</div>',
      );
      return;
    }

    const grouped = {};

    res.data.forEach(function (item) {
      if (!grouped[item.category]) {
        grouped[item.category] = [];
      }
      grouped[item.category].push(item);
    });

    let html = "";

    Object.keys(grouped).forEach(function (category) {
      html += `<div class="global-search-category">${category}</div>`;

      grouped[category].forEach(function (item) {
        html += `
          <a href="${item.url}" class="global-search-item">
            <i class="bi ${item.icon}"></i>
            <div class="global-search-item-text">
              <div class="global-search-item-label">${item.label}</div>
              <div class="global-search-item-sub">${item.sub}</div>
            </div>
          </a>
        `;
      });
    });

    $("#globalSearchResults").html(html);
  });
}
