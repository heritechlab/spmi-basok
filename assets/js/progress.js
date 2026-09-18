document.addEventListener("DOMContentLoaded", () => {
  const bars = document.querySelectorAll(".progress-fill, .mini-fill");

  bars.forEach((bar) => {
    const target = bar.dataset.width;

    if (target) {
      setTimeout(() => {
        bar.style.width = target + "%";
      }, 300);
    }
  });
});
