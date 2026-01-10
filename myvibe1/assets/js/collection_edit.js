/**
 * Collection Edit Modal
 * ---------------------
 * Skript umožňuje otevírat a zavírat modal pro úpravu existující kolekce.
 *
 * Funkce:
 *  - otevření modalu po kliknutí na tlačítko "edit" u kolekce
 *  - naplnění modalu aktuálními daty kolekce (ID, název)
 *  - zavření modalu kliknutím na X nebo mimo modal
 *
 * Bezpečnost:
 *  - kontroluje existenci tlačítka a modalu před přidáním listeneru
 */

// ======================================================
// 1) Inicializace a získání referencí
// ======================================================
document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("editCollectionModal");
  const closeBtn = document.querySelector("#editCollectionModal .close-edit");
  const editButtons = document.querySelectorAll(".collection-card .edit-button");

  // ======================================================
  // 2) Otevření modalu po kliknutí na tlačítko edit
  // ======================================================
  editButtons.forEach(btn => {
      btn.addEventListener("click", () => {
          modal.style.display = "flex";
          document.getElementById("editCollectionId").value = btn.dataset.id;
          document.getElementById("editCollectionTitle").value = btn.dataset.title;
      });
  });

  // ======================================================
  // 3) Zavření modalu
  // ======================================================
  closeBtn?.addEventListener("click", () => (modal.style.display = "none"));
  window.addEventListener("click", (e) => {
      if (e.target === modal) modal.style.display = "none";
  });
});
