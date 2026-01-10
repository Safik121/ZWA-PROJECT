/**
 * Item Modal Handler
 * ------------------
 * Tento skript zajišťuje otevírání a zavírání modálních oken pro přidání položek.
 *
 * Funkce:
 *  - otevírá manuální modal pro přidání položky
 *  - otevírá API modal pro přidání položky z externího API
 *  - zavírá modaly kliknutím na tlačítko "close" nebo kliknutím mimo modal
 *
 * Bezpečnost:
 *  - žádné inline skripty
 *  - pouze DOM manipulace a event listening
 */

// ======================================================
// 1) Inicializace modálních prvků a tlačítek
// ======================================================
document.addEventListener("DOMContentLoaded", () => {
  const manualModal = document.getElementById("manualAddModal");
  const apiModal = document.getElementById("apiAddModal");
  const manualBtn = document.getElementById("addItemCard");
  const apiBtn = document.getElementById("addFromApiCard");
  const closeBtns = document.querySelectorAll(".modal .close");

  // ======================================================
  // 2) Otevírání modálních oken
  // ======================================================
  if (manualBtn) {
    manualBtn.addEventListener("click", () => {
      manualModal.style.display = "flex";
    });
  }

  if (apiBtn) {
    apiBtn.addEventListener("click", () => {
      apiModal.style.display = "flex";
    });
  }

  // ======================================================
  // 3) Zavírání modálních oken pomocí tlačítka "close"
  // ======================================================
  closeBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      manualModal.style.display = "none";
      apiModal.style.display = "none";
    });
  });

  // ======================================================
  // 4) Zavírání modálních oken kliknutím mimo modal
  // ======================================================
  window.addEventListener("click", (e) => {
    if (e.target === manualModal) manualModal.style.display = "none";
    if (e.target === apiModal) apiModal.style.display = "none";
  });
});
