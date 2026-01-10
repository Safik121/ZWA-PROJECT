/**
 * Collection Delete Confirmation
 * ------------------------------
 * Skript přidává potvrzení při mazání kolekce.
 *
 * Funkce:
 *  - při odeslání formuláře smazání kolekce zobrazí confirm dialog
 *  - pokud uživatel zruší, akce je zablokována
 *
 * Bezpečnost:
 *  - zajišťuje, že smazání kolekce není provedeno náhodně
 */

// ======================================================
// 1) Přidání potvrzení pro všechny delete formuláře
// ======================================================
document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function (e) {
        if (!confirm('Delete this collection?')) {
            e.preventDefault();
        }
    });
});
