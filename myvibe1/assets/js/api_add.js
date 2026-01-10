/**
 * Universal API Add Script for MyVibe
 * -----------------------------------
 * Tento skript umožňuje přidávat položky z externích API (RAWG, TMDb, iTunes, Google Books, Jikan, Spoonacular)
 * do uživatelských kolekcí s komentářem a ratingem.
 *
 * Funkce:
 *  - rozlišuje typ kolekce podle `data-theme-type` v <body> ("games", "movies", "music", "books", "anime", "recipes")
 *  - otevírá a zavírá modální okna pro výběr položky
 *  - vyhledává položky z příslušného API a zobrazuje je jako karty
 *  - umožňuje přidání položky do kolekce s komentářem a ratingem
 *  - podporuje audio preview pro hudbu
 *
 * Bezpečnost:
 *  - kontrola existence všech potřebných DOM elementů
 *  - žádné inline skripty ani nebezpečné manipulace s DOM
 *  - API data se používají jen pro tvorbu formuláře (nikdy přímo do innerHTML bez escapování)
 *
 * Poznámky:
 *  - rating defaultně 3 (1–5)
 *  - flexibilní zobrazení metadat (release/artist/album)
 */

// ======================================================
// 1) Initialize theme config and collection
// ======================================================
document.addEventListener("DOMContentLoaded", () => {
    const themeType = document.body.dataset.themeType;
    const collectionId = document.getElementById("collectionId")?.value;
    if (!themeType || !collectionId) return;

    const cfg = (() => {
        switch (themeType) {
            case "movies":
                return {
                    apiLabel: "TMDb",
                    itemLabel: "Movie",
                    apiUrl: "api/tmdb_search.php",
                    modalId: "apiAddModalMovies",
                    openBtnId: "addFromApiCardMovies",
                    formId: "movieSearchForm",
                    queryId: "movieQuery",
                    resultsId: "movieResults",
                };
            case "music":
                return {
                    apiLabel: "iTunes",
                    itemLabel: "Song",
                    apiUrl: "api/itunes_search.php",
                    modalId: "apiAddModalMusic",
                    openBtnId: "addFromApiCardMusic",
                    formId: "musicSearchForm",
                    queryId: "musicQuery",
                    resultsId: "musicResults",
                };
            case "books":
                return {
                    apiLabel: "Google Books",
                    itemLabel: "Book",
                    apiUrl: "api/googlebooks_search.php",
                    modalId: "apiAddModalBooks",
                    openBtnId: "addFromApiCardBooks",
                    formId: "bookSearchForm",
                    queryId: "bookQuery",
                    resultsId: "bookResults",
                };
            case "anime":
                return {
                    apiLabel: "Jikan",
                    itemLabel: "Anime",
                    apiUrl: "api/jikan_search.php",
                    modalId: "apiAddModalAnime",
                    openBtnId: "addFromApiCardAnime",
                    formId: "animeSearchForm",
                    queryId: "animeQuery",
                    resultsId: "animeResults",
                };
            case "recipes":
                return {
                    apiLabel: "Spoonacular",
                    itemLabel: "Recipe",
                    apiUrl: "api/recipes_search.php",
                    modalId: "apiAddModalRecipes",
                    openBtnId: "addFromApiCardRecipes",
                    formId: "recipeSearchForm",
                    queryId: "recipeQuery",
                    resultsId: "recipeResults",
                };
            default: // games
                return {
                    apiLabel: "RAWG",
                    itemLabel: "Game",
                    apiUrl: "api/rawg_search.php",
                    modalId: "apiAddModal",
                    openBtnId: "addFromApiCard",
                    formId: "apiSearchForm",
                    queryId: "apiQuery",
                    resultsId: "apiResults",
                };
        }
    })();

    // ======================================================
    // 2) Modal open/close
    // ======================================================
    const apiModal = document.getElementById(cfg.modalId);
    const apiOpenButton = document.getElementById(cfg.openBtnId);
    const closeBtn = apiModal?.querySelector(".close");

    if (apiOpenButton && apiModal) {
        apiOpenButton.addEventListener("click", () => {
            apiModal.style.display = "block";
        });

        if (closeBtn) {
            closeBtn.addEventListener("click", () => {
                apiModal.style.display = "none";
            });
        }

        window.addEventListener("click", (e) => {
            if (e.target === apiModal) apiModal.style.display = "none";
        });
    }

    // ======================================================
    // 3) Search form handling
    // ======================================================
    const apiForm = document.getElementById(cfg.formId);
    const queryInput = document.getElementById(cfg.queryId);
    const apiResults = document.getElementById(cfg.resultsId);

    if (!apiForm || !queryInput || !apiResults) return;

    apiForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const query = queryInput.value.trim();
        if (!query) return;

        apiResults.innerHTML = `<p>Searching ${cfg.itemLabel.toLowerCase()}s…</p>`;

        try {
            const response = await fetch(
                `${cfg.apiUrl}?query=${encodeURIComponent(query)}`,
                { headers: { Accept: "application/json" } }
            );
            const data = await response.json();

            if (!Array.isArray(data) || data.length === 0) {
                apiResults.innerHTML = `<p>No ${cfg.itemLabel.toLowerCase()}s found.</p>`;
                return;
            }

            renderResults(data);
        } catch (err) {
            console.error(`${cfg.apiLabel} fetch error:`, err);
            apiResults.innerHTML = `<p>Error fetching from ${cfg.apiLabel} API.</p>`;
        }
    });

    // ======================================================
    // 4) Render API results as clickable cards
    // ======================================================
    function renderResults(items) {
        apiResults.innerHTML = "";
        const frag = document.createDocumentFragment();

        items.forEach((item) => {
            const card = document.createElement("div");
            card.className = "api-item selectable";
            card.style.cursor = "pointer";

            const img = document.createElement("img");
            img.className = "api-thumb";
            img.src = item.image || "default/item_default.png";
            img.alt = item.name || cfg.itemLabel;

            const title = document.createElement("h4");
            title.textContent = item.name || "Unknown";

            const meta1 = document.createElement("p");
            if (themeType === "music") {
                const artist = item.artist ? `by ${item.artist}` : "";
                const album = item.album ? ` — ${item.album}` : "";
                meta1.textContent = [artist, album].filter(Boolean).join(" ");
            } else {
                meta1.textContent = `Released: ${item.released || "Unknown"}`;
            }

            if (typeof item.rating === "number") {
                const rating = document.createElement("p");
                rating.textContent = `${cfg.apiLabel} Rating: ${item.rating.toFixed(1)}`;
                card.append(img, title, meta1, rating);
            } else {
                card.append(img, title, meta1);
            }

            card.addEventListener("click", () => showAddForm(item));
            frag.append(card);
        });

        apiResults.appendChild(frag);
    }

    // ======================================================
    // 5) Show add form for selected item
    // ======================================================
    function showAddForm(item) {
        apiResults.innerHTML = "";

        const wrapper = document.createElement("div");
        wrapper.className = "api-form";

        const h3 = document.createElement("h3");
        h3.textContent = `Add "${item.name}"`;

        const img = document.createElement("img");
        img.className = "api-thumb-large";
        img.src = item.image || "default/item_default.png";
        img.alt = item.name || cfg.itemLabel;

        const formAdd = document.createElement("form");
        formAdd.method = "post";
        formAdd.action = "app/actions/items_create.php";

        formAdd.append(
            makeHidden("action", "add"),
            makeHidden("title", item.name || "Unknown"),
            makeHidden("image_url", item.image || ""),
            makeHidden("api_id", item.id || ""),
            makeHidden("collection_id", collectionId)
        );

        if (themeType === "music" && item.preview) {
            formAdd.appendChild(makeHidden("preview_url", item.preview));
        }

        const labelComment = document.createElement("label");
        labelComment.textContent = "Your comment:";

        const commentArea = document.createElement("textarea");
        commentArea.name = "comment";
        commentArea.rows = 3;

        if (themeType === "music") {
            const by = item.artist ? ` by ${item.artist}` : "";
            const album = item.album ? ` (album: ${item.album})` : "";
            commentArea.value = `Added from iTunes API${by}${album}`;
        } else {
            commentArea.value =
                item.overview || item.description || `Added from ${cfg.apiLabel} API`;
        }

        const labelRating = document.createElement("label");
        labelRating.textContent = "Your rating (1–5):";

        const ratingSelect = document.createElement("select");
        ratingSelect.name = "rating";
        for (let i = 1; i <= 5; i++) {
            const opt = document.createElement("option");
            opt.value = i;
            opt.textContent = "⭐".repeat(i);
            if (i === 3) opt.selected = true;
            ratingSelect.append(opt);
        }

        const submit = document.createElement("button");
        submit.type = "submit";
        submit.textContent = `Add ${cfg.itemLabel}`;
        submit.className = "button-create";

        formAdd.append(labelComment, commentArea, labelRating, ratingSelect, submit);

        if (themeType === "music" && item.preview) {
            const audio = document.createElement("audio");
            audio.controls = true;
            audio.src = item.preview;
            audio.style.marginTop = "10px";
            audio.style.width = "100%";
            wrapper.append(h3, img, formAdd, audio);
        } else {
            wrapper.append(h3, img, formAdd);
        }

        apiResults.append(wrapper);
    }

    // ======================================================
    // 6) Utility: create hidden input
    // ======================================================
    function makeHidden(name, value) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = name;
        input.value = value;
        return input;
    }
});
