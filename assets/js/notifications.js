$(document).ready(function () {
  loadNotifications();

  setInterval(loadNotifications, 30000);

  $(document).on("click", ".btn-notif-item", function (e) {
    const id = $(this).data("id");
    const link = $(this).data("link");

    $.post(
      SIQUA.BASE_URL + "notifications/api.php?action=mark_read",
      { id: id },
      function () {
        if (link) {
          window.location.href = link;
        } else {
          loadNotifications();
        }
      },
    );
  });

  $("#btnMarkAllRead").on("click", function (e) {
    e.preventDefault();

    $.post(
      SIQUA.BASE_URL + "notifications/api.php?action=mark_all_read",
      function () {
        loadNotifications();
      },
    );
  });
});

function loadNotifications() {
  $.getJSON(
    SIQUA.BASE_URL + "notifications/api.php",
    { action: "list" },
    function (res) {
      if (!res.success) return;

      const unread = res.unread || 0;

      if (unread > 0) {
        $("#notifBadge")
          .text(unread > 9 ? "9+" : unread)
          .show();
      } else {
        $("#notifBadge").hide();
      }

      if (!res.data.length) {
        $("#notifList").html(
          '<div class="text-center text-muted py-4">Belum ada notifikasi.</div>',
        );
        return;
      }

      let html = "";

      res.data.forEach(function (n) {
        const unreadClass = n.is_read == 0 ? "fw-bold" : "";
        const bg = n.is_read == 0 ? "background:#f4f2fb;" : "";

        html += `
        <button type="button" class="btn-notif-item w-100 text-start border-0 px-3 py-2 border-bottom" style="${bg}" data-id="${n.id}" data-link="${n.link || ""}">
          <div class="${unreadClass}" style="font-size:13px;">${n.title}</div>
          <div class="text-muted" style="font-size:12px;">${n.message || ""}</div>
          <div class="text-muted" style="font-size:11px;">${n.created_at}</div>
        </button>
      `;
      });

      $("#notifList").html(html);
    },
  );
}
