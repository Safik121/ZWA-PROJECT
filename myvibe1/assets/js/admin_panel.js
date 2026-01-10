/**
 * Admin Panel
 * -----------
 * Skript pro správu uživatelů v admin modalu.
 *
 * Funkce:
 *  - otevírání/zavírání admin modalu
 *  - mazání uživatelů s potvrzením
 *  - editace uživatelů s validací hesla
 *
 * Bezpečnost:
 *  - potvrzení před smazáním uživatele
 *  - validace délky hesla při editaci
 *  - žádné inline skripty, všechna interakce přes fetch POST
 */

// ======================================================
// 1) Inicializace modalu a tlačítek
// ======================================================
document.addEventListener("DOMContentLoaded", () => {
  const adminBtn = document.getElementById("adminPanelBtn");
  const adminModal = document.getElementById("adminModal");
  const closeModal = adminModal.querySelector(".close");

  // Otevření modalu
  adminBtn.addEventListener("click", () => {
    adminModal.style.display = "block";
  });

  // Zavření modalu kliknutím na X
  closeModal.addEventListener("click", () => {
    adminModal.style.display = "none";
  });

  // Zavření modalu kliknutím mimo něj
  window.addEventListener("click", (event) => {
    if (event.target === adminModal) {
      adminModal.style.display = "none";
    }
  });

  // ======================================================
  // 2) Mazání uživatele
  // ======================================================
  document.body.addEventListener("click", async (e) => {
    if (e.target.classList.contains("delete-user")) {
      const username = e.target.dataset.username;
      if (!confirm(`Are you sure you want to delete ${username}?`)) return;

      try {
        const res = await fetch("app/actions/admin_actions.php?action=delete", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: `username=${encodeURIComponent(username)}`
        });
        const text = await res.text();
        alert(text);
        if (res.ok) e.target.closest(".admin-user-row")?.remove();
      } catch {
        alert("⚠️ Failed to delete user.");
      }
    }
  });

  // ======================================================
  // 3) Editace uživatele
  // ======================================================
  document.getElementById("editUserForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const form = e.target;
    const password = form.password.value.trim();

    // Validace hesla: pokud není prázdné, musí mít alespoň 6 znaků
    if (password !== "" && password.length < 6) {
      alert("Password must be at least 6 characters long.");
      return;
    }

    const data = new FormData(form);

    try {
      const res = await fetch("app/actions/admin_actions.php?action=update", {
        method: "POST",
        body: data
      });

      const text = await res.text();
      alert(text);

      // Pokud update proběhne úspěšně, reload stránky
      if (text.includes("✅")) location.reload();
    } catch {
      alert("⚠️ Failed to update user.");
    }
  });
});
