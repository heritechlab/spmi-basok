/* ==========================================
   SIQUA - Executive Alert
========================================== */

document.addEventListener("DOMContentLoaded", function () {
  const alerts = document.querySelectorAll(".alert-item");

  // Animasi muncul satu per satu
  alerts.forEach((item, index) => {
    item.style.opacity = "0";
    item.style.transform = "translateY(20px)";

    setTimeout(() => {
      item.style.transition = "all .45s ease";

      item.style.opacity = "1";
      item.style.transform = "translateY(0)";
    }, index * 180);
  });

  // Hover effect
  alerts.forEach((item) => {
    item.addEventListener("mouseenter", function () {
      this.style.transform = "translateX(8px)";
    });

    item.addEventListener("mouseleave", function () {
      this.style.transform = "translateX(0)";
    });
  });
});
