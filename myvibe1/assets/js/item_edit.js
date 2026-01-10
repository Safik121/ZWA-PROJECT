/**
 * Item Edit Modal Handler
 * -----------------------
 * Tento skript zajišťuje otevírání a zavírání modálního okna pro úpravu položek.
 *
 * Funkce:
 *  - otevření modalu po kliknutí na libovolné tlačítko "edit"
 *  - naplnění formuláře existujícími daty položky (title, comment, rating, id)
 *  - zavření modalu kliknutím na tlačítko "close" nebo mimo modal
 *
 * Bezpečnost:
 *  - kontrola existence modalu a tlačítek před přidáním listenerů
 *  - žádné inline skripty
 */

// ======================================================
// 1) Inicializace modálu a tlačítek pro úpravu
// ======================================================
document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("editItemModal");
  const closeBtn = document.querySelector(".close-edit");
  const editButtons = document.querySelectorAll(".edit-button");

  // 🚨 Bezpečnostní kontrola — pokud modaly nebo tlačítka neexistují, ukonči skript
  if (!modal || !closeBtn || editButtons.length === 0) {
    return;
  }

  // ======================================================
  // 2) Otevírání modálu a naplnění formuláře
  // ======================================================
  editButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      modal.style.display = "flex";
      document.getElementById("editItemId").value = btn.dataset.id;
      document.getElementById("editTitle").value = btn.dataset.title;
      document.getElementById("editComment").value = btn.dataset.comment;
      document.getElementById("editRating").value = btn.dataset.rating;
    });
  });

  // ======================================================
  // 3) Zavírání modálu kliknutím na tlačítko
  // ======================================================
  closeBtn.addEventListener("click", () => {
    modal.style.display = "none";
  });

  // ======================================================
  // 4) Zavírání modálu kliknutím mimo modal
  // ======================================================
  window.addEventListener("click", (e) => {
    if (e.target === modal) modal.style.display = "none";
  });
});
