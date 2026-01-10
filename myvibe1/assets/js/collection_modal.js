/**
 * Collection Modal
 * ----------------
 * Skript umožňuje otevírat a zavírat modal pro vytvoření nové kolekce.
 *
 * Funkce:
 *  - zobrazení modalu po kliknutí na "Create Collection" kartu
 *  - zavření modalu kliknutím na X nebo mimo modal
 *
 * Bezpečnost:
 *  - kontroluje existenci tlačítka a modalu před přidáním listeneru
 */

// ======================================================
// 1) Inicializace a získání referencí
// ======================================================
document.addEventListener("DOMContentLoaded", () => {
  const createCard = document.getElementById("createCard");
  const modal = document.getElementById("createModal");
  const closeBtn = document.querySelector("#createModal .close");

  if (!createCard || !modal) return;

  // ======================================================
  // 2) Otevření modalu po kliknutí
  // ======================================================
  createCard.addEventListener("click", (e) => {
      e.preventDefault();
      modal.style.display = "flex";
  });

  // ======================================================
  // 3) Zavření modalu
  // ======================================================
  closeBtn?.addEventListener("click", () => (modal.style.display = "none"));

  window.addEventListener("click", (e) => {
      if (e.target === modal) modal.style.display = "none";
  });
});
