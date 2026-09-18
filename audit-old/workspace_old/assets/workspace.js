/*
=========================================================
SIQUA Audit Workspace
Version : Sprint 1.6
=========================================================
*/

document.addEventListener("DOMContentLoaded", function () {
  initStatusCard();
});

/* ======================================================
STATUS CARD
====================================================== */

function initStatusCard() {
  const cards = document.querySelectorAll(".status-card");

  cards.forEach((card) => {
    card.addEventListener("click", function () {
      cards.forEach((c) => c.classList.remove("active"));

      this.classList.add("active");

      const status = this.dataset.status;

      const input = document.getElementById("audit_status");

      if (input) {
        input.value = status;
      }

      changeAnalysis(status);

      changeRecommendation(status);

      updateProgress(status);
    });
  });
}

/* ======================================================
ANALISIS
====================================================== */

function changeAnalysis(status) {
  const title = document.getElementById("analysisTitle");

  if (!title) return;

  switch (status) {
    case "0":
      title.innerHTML = "Akar Penyebab";

      break;

    case "1":
      title.innerHTML = "Analisis Kesenjangan";

      break;

    case "2":
      title.innerHTML = "Faktor Pendukung";

      break;

    case "3":
      title.innerHTML = "Best Practice";

      break;

    default:
      title.innerHTML = "Analisis";
  }
}

/* ======================================================
REKOMENDASI
====================================================== */

function changeRecommendation(status) {
  const title = document.getElementById("recommendationTitle");

  if (!title) return;

  switch (status) {
    case "0":
      title.innerHTML = "Rekomendasi Perbaikan";

      break;

    case "1":
      title.innerHTML = "Rekomendasi Pemenuhan";

      break;

    case "2":
      title.innerHTML = "Rekomendasi Peningkatan";

      break;

    case "3":
      title.innerHTML = "Peluang Replikasi";

      break;

    default:
      title.innerHTML = "Rekomendasi";
  }
}

/* ======================================================
PROGRESS
====================================================== */

function updateProgress(status) {
  const bar = document.getElementById("auditProgress");

  const text = document.getElementById("auditPercent");

  if (!bar || !text) return;

  let progress = 0;

  switch (status) {
    case "0":
      progress = 25;

      break;

    case "1":
      progress = 50;

      break;

    case "2":
      progress = 75;

      break;

    case "3":
      progress = 100;

      break;
  }

  bar.style.width = progress + "%";

  text.innerHTML = progress + "%";
}

/* ======================================================
AUTO SAVE (Dummy)
====================================================== */

function saveDraft() {
  console.log("Draft disimpan");
}

/* ======================================================
NEXT
====================================================== */

function nextIndicator() {
  console.log("Indikator berikutnya");
}

/* ======================================================
FINISH
====================================================== */

function finishAudit() {
  if (confirm("Selesaikan audit ini?")) {
    console.log("Audit selesai");
  }
}
