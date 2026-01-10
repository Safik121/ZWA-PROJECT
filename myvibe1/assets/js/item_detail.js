/**
 * Item Detail Modal Handler
 * -------------------------
 * Tento skript zajišťuje otevírání modálního okna s detailem položky.
 *
 * Funkce:
 *  - kliknutí na položku zobrazí modal s obrázkem, názvem, hodnocením a komentářem
 *  - modal se nezobrazí při kliknutí na tlačítka "edit" nebo "delete"
 *  - zavírání modalu na tlačítko "close" nebo kliknutím mimo modal
 *
 * Bezpečnost:
 *  - kontrola existence položek a modalu před přidáním listenerů
 *  - delegovaný click handler pro dynamicky generované položky
 */

// ======================================================
// 1) Inicializace modálního zobrazení detailu
// ======================================================
document.addEventListener("DOMContentLoaded", () => {
  const cards = document.querySelectorAll(".item-card");
  const modal = document.getElementById("detailModal");
  const closeBtn = modal?.querySelector(".close");
  const detailImage = document.getElementById("modalImage");
  const detailTitle = document.getElementById("modalTitle");
  const detailDescription = document.getElementById("modalDescription");

  if (!cards.length || !modal) return;

  // ======================================================
  // 2) Delegovaný click handler pro všechny itemy
  // ======================================================
  document.addEventListener("click", (e) => {
    const card = e.target.closest(".item-card");
    if (!card || card.classList.contains("add-item")) return;

    // Zamez otevření při kliknutí na tlačítka edit/delete nebo jejich formy
    if (e.target.closest(".edit-button") || e.target.closest(".delete-button") || e.target.closest(".item-actions")) {
      e.stopPropagation();
      return;
    }

    // Získání hodnot z itemu
    const imgEl = card.querySelector("img");
    const titleEl = card.querySelector("h3");
    const ratingEl = card.querySelector("p:nth-of-type(1)");
    const commentEl = card.querySelector("p:nth-of-type(2)");

    const img = imgEl ? imgEl.src : "default/item_default.png";
    const title = titleEl ? titleEl.textContent : "Unknown item";
    const rating = ratingEl ? ratingEl.textContent : "⭐ N/A";
    const comment = commentEl ? commentEl.textContent : "No description.";

    // Naplnění modal daty
    detailImage.src = img;
    detailTitle.textContent = `${title} ${rating}`;
    detailDescription.textContent = comment;

    // 2a) Audio Preview (pokud existuje)
    const oldAudio = modal.querySelector("audio");
    if (oldAudio) oldAudio.remove();

    const previewUrl = card.getAttribute("data-preview-url");
    if (previewUrl) {
      const audio = document.createElement("audio");
      audio.controls = true;
      audio.src = previewUrl;
      audio.style.marginTop = "15px";
      audio.style.width = "100%";
      detailDescription.after(audio);
    }

    // Zobrazení modalu
    modal.classList.add("show");
  });

  // ======================================================
  // 3) Zavření modalu na X
  // ======================================================
  if (closeBtn) {
    closeBtn.addEventListener("click", () => {
      modal.classList.remove("show");
      const audio = modal.querySelector("audio");
      if (audio) {
        audio.pause();
        audio.currentTime = 0;
      }
    });
  }

  // ======================================================
  // 4) Zavření modalu kliknutím mimo něj
  // ======================================================
  window.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.classList.remove("show");
      const audio = modal.querySelector("audio");
      if (audio) {
        audio.pause();
        audio.currentTime = 0;
      }
    }
  });
  // ======================================================
  // 5) Potvrzení smazání položky (delegováno)
  // ======================================================
  document.addEventListener("submit", (e) => {
    if (e.target.classList.contains("delete-item-form")) {
      if (!confirm("Delete this item?")) {
        e.preventDefault();
      }
    }
  });
});
