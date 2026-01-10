/**
 * BIO EDIT TOGGLE
 * ------------------------
 * Skript pro přepínání mezi zobrazením bio a formulářem pro editaci.
 *
 * Funkce:
 *  - zobrazení formuláře pro editaci bio po kliknutí na tlačítko "Edit"
 *  - skrytí formuláře a návrat k původnímu zobrazení po kliknutí na "Cancel"
 *
 * Bezpečnost:
 *  - žádné inline skripty
 *  - kontrola existence všech elementů před přidáním event listenerů
 *
 * Poznámky:
 *  - formulář pro bio musí mít id="bioForm"
 *  - tlačítko pro editaci musí mít id="editBioBtn"
 *  - tlačítko cancel musí mít id="cancelEdit"
 *  - display přepíná pouze mezi "block" a "none"
 */

// ======================================================
// 1) INIT
// ======================================================
document.addEventListener("DOMContentLoaded", () => {
  const editBtn   = document.getElementById("editBioBtn");
  const bioDisplay = document.getElementById("bioDisplay");
  const bioForm    = document.getElementById("bioForm");
  const cancelBtn  = document.getElementById("cancelEdit");

  // Pokud některý element neexistuje, nic neděláme
  if (!editBtn || !bioDisplay || !bioForm) return;

  // ======================================================
  // 2) SHOW FORM
  // ======================================================
  editBtn.addEventListener("click", () => {
      bioDisplay.style.display = "none";
      bioForm.style.display    = "block";
  });

  // ======================================================
  // 3) CANCEL EDIT
  // ======================================================
  cancelBtn.addEventListener("click", () => {
      bioForm.style.display    = "none";
      bioDisplay.style.display = "block";
  });
});
