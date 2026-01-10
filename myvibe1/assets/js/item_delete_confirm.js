/**
 * Item Delete Confirmation
 * ------------------------
 * Tento skript zajišťuje potvrzení smazání položky.
 *
 * Funkce:
 *  - při odeslání formuláře smazání se zobrazí potvrzovací dialog
 *  - pokud uživatel odmítne, formulář se neodešle
 *
 * Bezpečnost:
 *  - jednoduchá ochrana proti náhodnému smazání položky
 */

// ======================================================
// 1) Přidání confirm handleru na všechny delete formy
// ======================================================
document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function (e) {
        if (!confirm('Delete this item?')) {
            e.preventDefault();
        }
    });
});
