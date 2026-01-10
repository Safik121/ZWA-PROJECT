/**
 * Edit Mode Toggle
 * ----------------
 * Skript umožňuje přepínat "edit mode" na celé stránce.
 *
 * Funkce:
 *  - přepíná CSS třídu `edit-mode-active` na `<body>`
 *  - mění text tlačítka podle stavu režimu
 *
 * Bezpečnost:
 *  - kontroluje existenci tlačítka před přidáním listeneru
 */

// ======================================================
// 1) Inicializace a získání referencí
// ======================================================
document.addEventListener("DOMContentLoaded", () => {
  const toggleBtn = document.getElementById("toggleEditMode");
  const body = document.body;
  let editMode = false;

  if (!toggleBtn) return;

  // ======================================================
  // 2) Přepnutí edit mode po kliknutí
  // ======================================================
  toggleBtn.addEventListener("click", () => {
      editMode = !editMode;
      body.classList.toggle("edit-mode-active", editMode);
      toggleBtn.textContent = editMode ? "❌ Exit Edit Mode" : "🛠️ Edit Mode";
  });
});
