/**
 * Home Trending Modals
 * --------------------
 * Tento skript zajišťuje zobrazení detailního modalu pro karty na homepage.
 *
 * Funkce:
 *  - po kliknutí na trending card se otevře modal s detailem položky
 *  - modal zobrazuje: název, obrázek, popis
 *  - možnost zavřít modal kliknutím na křížek nebo mimo modal
 *
 * Bezpečnost:
 *  - jednoduchá ochrana proti chybám při neexistujících datech
 */

// ======================================================
// 1) Inicializace modalu a získání referencí
// ======================================================
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("detailModal");
    const modalTitle = document.getElementById("modalTitle");
    const modalImage = document.getElementById("modalImage");
    const modalDescription = document.getElementById("modalDescription");
    const closeBtn = modal.querySelector(".close");

    // ======================================================
    // 2) Přidání click handleru na všechny trending karty
    // ======================================================
    const cards = document.querySelectorAll(".trending-card");
    cards.forEach(card => {
        card.addEventListener("click", () => {
            const title = card.dataset.title || "Unknown Title";
            const image = card.dataset.image || "default/item_default.png";
            const desc = card.dataset.description || "No description available.";
            const previewUrl = card.dataset.previewUrl;

            modalTitle.textContent = title;
            modalImage.src = image;
            modalDescription.textContent = desc;

            // Audio Preview handling
            const oldAudio = modal.querySelector("audio");
            if (oldAudio) oldAudio.remove();

            if (previewUrl) {
                const audio = document.createElement("audio");
                audio.controls = true;
                audio.src = previewUrl;
                audio.style.marginTop = "15px";
                audio.style.width = "100%";
                modalDescription.after(audio);
            }

            modal.style.display = "flex";
        });
    });

    // ======================================================
    // 3) Zavírání modalu (klik na X nebo mimo modal)
    // ======================================================
    const stopAudio = () => {
        const audio = modal.querySelector("audio");
        if (audio) {
            audio.pause();
            audio.currentTime = 0;
        }
    };

    closeBtn.addEventListener("click", () => {
        stopAudio();
        modal.style.display = "none";
    });

    window.addEventListener("click", (e) => {
        if (e.target === modal) {
            stopAudio();
            modal.style.display = "none";
        }
    });
});
