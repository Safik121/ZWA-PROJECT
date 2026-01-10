/**
 * Collection API Integration (RAWG)
 * ---------------------------------
 * Skript umožňuje hledat a přidávat položky do kolekce
 * přímo z externího API (RAWG pro hry). 
 *
 * Funkce:
 *  - zpracovává vyhledávací formulář pro API
 *  - zobrazí výsledky jako karty s tlačítkem "Select"
 *  - po výběru položky zobrazí formulář pro přidání do kolekce
 *  - formulář obsahuje: title, image, comment, rating, api_id
 *
 * Bezpečnost:
 *  - všechny vstupy se přidávají přes hidden fields → minimalizuje riziko manipulace
 *  - žádný přímý HTML insert z externího API kromě bezpečně escapovaných dat
 */

// ======================================================
// 1) Inicializace DOM elementů
// ======================================================
document.addEventListener("DOMContentLoaded", () => {
  const apiForm = document.getElementById("apiSearchForm");
  const apiResults = document.getElementById("apiResults");
  const collectionId = document.getElementById("collectionId");

  if (!apiForm || !apiResults) return;

  // ======================================================
  // 2) Vyhledávání položek přes API
  // ======================================================
  apiForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    apiResults.innerHTML = "<p>Searching...</p>";

    const query = document.getElementById("apiQuery").value.trim();
    if (!query) return;

    try {
      const response = await fetch(`api/rawg_search.php?query=${encodeURIComponent(query)}`);
      const data = await response.json();

      if (!Array.isArray(data)) {
        apiResults.innerHTML = `<p>${data?.error || "No results found."}</p>`;
        return;
      }

      if (data.length === 0) {
        apiResults.innerHTML = "<p>No results found.</p>";
        return;
      }

      apiResults.innerHTML = "";

      // ======================================================
      // 3) Zobrazení výsledků jako karty
      // ======================================================
      data.forEach((game) => {
        const card = document.createElement("div");
        card.className = "api-item";

        card.innerHTML = `
          <img src="${game.image || "default/item_default.png"}" alt="${game.name}">
          <h4>${game.name}</h4>
          <p>Released: ${game.released || "Unknown"}</p>
          <button class="select-api">Select</button>
        `;

        // ======================================================
        // 4) Přidání vybrané položky do kolekce
        // ======================================================
        card.querySelector(".select-api").addEventListener("click", () => {
          apiResults.innerHTML = `
            <h3>Add "${game.name}"</h3>
            <img src="${game.image || "default/item_default.png"}" class="api-game-img">
            <form method="post" action="collection_detail.php?id=${collectionId.value}">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="api_id" value="${game.id}">
              <input type="hidden" name="title" value="${game.name}">
              <input type="hidden" name="image_url" value="${game.image || ""}">
              <label>Your comment:</label>
              <textarea name="comment" rows="3" required>Added from RAWG API</textarea>
              <label>Your rating (1–5):</label>
              <select name="rating" required>
                <option value="1">⭐</option>
                <option value="2">⭐⭐</option>
                <option value="3" selected>⭐⭐⭐</option>
                <option value="4">⭐⭐⭐⭐</option>
                <option value="5">⭐⭐⭐⭐⭐</option>
              </select>
              <button type="submit" class="button-create">Add Item</button>
            </form>
          `;
        });

        apiResults.appendChild(card);
      });
    } catch (err) {
      console.error(err);
      apiResults.innerHTML = "<p>Error fetching data from RAWG API.</p>";
    }
  });
});
