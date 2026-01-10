/**
 * Admin Modal
 * -----------
 * Skript pro správu uživatelů a kolekcí v admin panelu.
 *
 * Funkce:
 *  - načtení seznamu uživatelů přes JSON
 *  - stránkování a vyhledávání
 *  - otevření modalu pro editaci uživatele
 *  - validace a odeslání editace
 *  - otevření modalu pro vytvoření kolekce
 *  - odeslání formuláře pro novou kolekci
 *
 * Bezpečnost:
 *  - HTML5 validace hesla
 *  - potvrzení u smazání (je v admin_panel.js)
 *  - žádné inline skripty, všechna interakce přes fetch POST
 */

// ======================================================
// 1) Inicializace a načtení uživatelů
// ======================================================
document.addEventListener("DOMContentLoaded", async () => {
  const userList = document.getElementById("adminUserList");
  const prevBtn = document.getElementById("prevPage");
  const nextBtn = document.getElementById("nextPage");
  const pageInfo = document.getElementById("pageInfo");
  const searchInput = document.getElementById("searchUserInput");

  let users = [];
  let filteredUsers = [];
  let currentPage = 1;
  const perPage = 10;

  try {
    const res = await fetch("app/actions/admin_actions.php?action=list_json");
    const text = await res.text();
    users = JSON.parse(text);
    filteredUsers = users;
  } catch (err) {
    userList.innerHTML = "<p style='color:red'>Failed to load users.</p>";
    console.error("Fetch error:", err);
    return;
  }

  // ======================================================
  // 2) Renderování stránky uživatelů
  // ======================================================
  function renderPage(page) {
    userList.innerHTML = "";
    const start = (page - 1) * perPage;
    const end = start + perPage;
    const pageUsers = filteredUsers.slice(start, end);

    if (pageUsers.length === 0) {
      userList.innerHTML = "<p style='color:gray;'>No users found.</p>";
    }

    pageUsers.forEach(u => {
      const isMe = (parseInt(u.id) === parseInt(window.currentAdminId));
      const isAdmin = (u.role === 'admin');

      let buttonsHtml = '';

      if (isMe) {
        // Me (Admin): NO buttons (cannot edit self or create collection for self here)
        buttonsHtml = '';
      } else if (isAdmin) {
        // Other Admin: NO buttons.
        buttonsHtml = '';
      } else {
        // Regular User: All buttons.
        buttonsHtml = `
          <button class="create-collection" data-username="${u.username}" title="Create Collection">➕</button>
          <button class="edit-user" data-id="${u.id}" data-email="${u.email}" data-avatar="${u.avatar}" title="Edit User">✏️</button>
          <button class="promote-user" data-username="${u.username}" title="Promote to Admin">👑</button>
          <button class="delete-user" data-username="${u.username}" title="Delete User">❌</button>
        `;
      }

      const roleLabel = isAdmin ? '<span class="role-badge admin">Admin</span>' : '<span class="role-badge user">User</span>';

      const row = document.createElement("div");
      row.className = "admin-user-row";
      row.innerHTML = `
        <div class="admin-user-info">
          <span><a href="search_users.php?q=${encodeURIComponent(u.username)}" target="_blank">${u.username}</a></span>
          ${roleLabel}
        </div>
        <div class="admin-actions">
          ${buttonsHtml}
        </div>
      `;
      userList.appendChild(row);
    });

    // ======================================================
    // 2a) Renderování stránkování (Advanced)
    // ======================================================
    const paginationContainer = document.querySelector(".pagination-controls");
    paginationContainer.innerHTML = ""; // Clear existing controls

    const totalPages = Math.ceil(filteredUsers.length / perPage);

    // 1. Previous Button
    const prevBtn = document.createElement("button");
    prevBtn.className = "pagination-nav-btn";
    prevBtn.textContent = "← Previous";
    prevBtn.disabled = page === 1;
    prevBtn.onclick = () => {
      if (currentPage > 1) {
        currentPage--;
        renderPage(currentPage);
      }
    };
    paginationContainer.appendChild(prevBtn);

    // 2. Page Numbers (Sliding Window: current-2 to current+2)
    let startPage = Math.max(1, page - 2);
    let endPage = Math.min(totalPages, page + 2);

    // Adjust window if near start or end to always show 5 numbers if possible
    if (endPage - startPage < 4) {
      if (startPage === 1) {
        endPage = Math.min(totalPages, startPage + 4);
      } else if (endPage === totalPages) {
        startPage = Math.max(1, endPage - 4);
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      const pageBtn = document.createElement("button");
      pageBtn.className = `page-btn ${i === page ? "active" : ""}`;
      pageBtn.textContent = i;
      pageBtn.onclick = () => {
        currentPage = i;
        renderPage(currentPage);
      };
      paginationContainer.appendChild(pageBtn);
    }

    // 3. Next Button
    const nextBtn = document.createElement("button");
    nextBtn.className = "pagination-nav-btn";
    nextBtn.textContent = "Next →";
    nextBtn.disabled = page === totalPages || totalPages === 0;
    nextBtn.onclick = () => {
      if (currentPage < totalPages) {
        currentPage++;
        renderPage(currentPage);
      }
    };
    paginationContainer.appendChild(nextBtn);

    // 4. Custom Page Input
    if (totalPages > 1) {
      const customInput = document.createElement("input");
      customInput.type = "number";
      customInput.className = "custom-page-input";
      customInput.min = 1;
      customInput.max = totalPages;
      customInput.placeholder = "#";
      customInput.value = ""; // Don't show current page, just placeholder

      customInput.onchange = (e) => {
        let val = parseInt(e.target.value);
        if (isNaN(val)) return;

        if (val < 1) val = 1;
        if (val > totalPages) val = totalPages;

        currentPage = val;
        renderPage(currentPage);
      };

      paginationContainer.appendChild(customInput);

      // Total Pages Label
      const totalLabel = document.createElement("span");
      totalLabel.style.marginLeft = "5px";
      totalLabel.style.color = "#888";
      totalLabel.textContent = `/ ${totalPages}`;
      paginationContainer.appendChild(totalLabel);
    }
  }

  // ======================================================
  // 2b) Promote User Action
  // ======================================================
  document.body.addEventListener("click", async (e) => {
    if (e.target.classList.contains("promote-user")) {
      const username = e.target.dataset.username;
      if (!confirm(`Are you sure you want to promote user "${username}" to Admin?`)) return;

      try {
        const res = await fetch("app/actions/admin_actions.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: `action=promote&username=${encodeURIComponent(username)}`
        });
        const text = await res.text();
        alert(text);
        location.reload();
      } catch (err) {
        console.error(err);
        alert("Error promoting user.");
      }
    }
  });

  // ======================================================
  // 3) Vyhledávání uživatelů
  // ======================================================
  function updateSearch() {
    const q = searchInput.value.toLowerCase();
    filteredUsers = users.filter(u => u.username.toLowerCase().includes(q));
    currentPage = 1;
    renderPage(currentPage);
  }

  searchInput.addEventListener("input", updateSearch);

  // Initial Render
  renderPage(currentPage);

  // ======================================================
  // 4) Otevření modalu pro editaci uživatele
  // ======================================================
  document.body.addEventListener("click", (e) => {
    if (e.target.classList.contains("edit-user")) {
      const modal = document.getElementById("editUserModal");
      modal.style.display = "block";
      document.getElementById("editUserId").value = e.target.dataset.id;
      document.getElementById("editUserEmail").value = e.target.dataset.email;

      const pwd = document.getElementById("editUserPassword");
      pwd.value = "";
      pwd.setCustomValidity("");
    }
  });

  // Zavření edit modalu
  document.getElementById("closeEdit").addEventListener("click", () => {
    document.getElementById("editUserModal").style.display = "none";
  });

  // ======================================================
  // 5) Validace hesla (HTML5)
  // ======================================================
  const passwordInput = document.getElementById("editUserPassword");
  passwordInput.addEventListener("input", () => {
    if (passwordInput.value && passwordInput.value.length < 6) {
      passwordInput.setCustomValidity("Password must be at least 6 characters long.");
    } else {
      passwordInput.setCustomValidity("");
    }
  });

  // ======================================================
  // 6) Odeslání editace uživatele
  // ======================================================
  document.getElementById("editUserForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const form = e.target;

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const data = new FormData(form);

    try {
      const res = await fetch("app/actions/admin_actions.php?action=update", {
        method: "POST",
        body: data
      });

      const text = await res.text();
      if (text.includes("✅")) {
        document.getElementById("editUserModal").style.display = "none";
        location.reload();
      } else {
        alert(text);
      }
    } catch (err) {
      console.error(err);
      alert("⚠️ Failed to update user.");
    }
  });

  // ======================================================
  // 7) Otevření modalu pro vytvoření kolekce
  // ======================================================
  document.body.addEventListener("click", (e) => {
    if (e.target.classList.contains("create-collection")) {
      const modal = document.getElementById("createCollectionModal");
      modal.style.display = "block";
      document.getElementById("targetUsername").value = e.target.dataset.username;
    }
  });

  // Zavření modalu pro kolekci
  document.getElementById("closeCreateCollection").addEventListener("click", () => {
    document.getElementById("createCollectionModal").style.display = "none";
  });

  // ======================================================
  // 8) Odeslání formuláře pro vytvoření kolekce
  // ======================================================
  document.getElementById("createCollectionForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const data = new FormData(e.target);

    try {
      const res = await fetch("app/actions/admin_actions.php?action=create_collection", {
        method: "POST",
        body: data
      });

      const text = await res.text();
      alert(text);
      if (text.includes("✅")) {
        document.getElementById("createCollectionModal").style.display = "none";
      }
    } catch (err) {
      console.error(err);
      alert("⚠️ Failed to create collection.");
    }
  });
});
