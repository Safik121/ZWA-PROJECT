/**
 * Avatar Upload Validation
 * ------------------------
 * Skript kontroluje velikost souboru při nahrávání avatara.
 *
 * Funkce:
 *  - omezuje velikost souboru na 2 MB
 *  - zobrazuje chybovou zprávu přímo pod inputem
 *  - blokuje odeslání formuláře, pokud je soubor příliš velký
 *
 * Bezpečnost:
 *  - neodesílá velké soubory na server
 *  - výběr souboru musí splňovat limit → minimalizuje zbytečné zátěže serveru
 */

// ======================================================
// 1) Inicializace DOM elementů
// ======================================================
document.addEventListener('DOMContentLoaded', function () {
  const fileInput = document.getElementById('avatar');
  const form = fileInput?.closest('form');
  const maxSize = 2 * 1024 * 1024; // 2 MB
  const uploadBtn = form?.querySelector('button[type="submit"]');

  if (!fileInput || !form) return;

  // ======================================================
  // 2) Přidání chybového elementu
  // ======================================================
  let errorMsg = document.createElement('p');
  errorMsg.className = 'error hidden';
  errorMsg.textContent = '';
  form.insertBefore(errorMsg, uploadBtn);

  // ======================================================
  // 3) Validace velikosti souboru při změně inputu
  // ======================================================
  fileInput.addEventListener('change', function () {
    const file = fileInput.files[0];
    if (!file) return;

    if (file.size > maxSize) {
      errorMsg.textContent = '⚠️ File too large (max 2 MB). Please choose another image.';
      errorMsg.classList.remove('hidden');
      uploadBtn.disabled = true;
      fileInput.value = ''; // clear invalid file
    } else {
      errorMsg.textContent = '';
      errorMsg.classList.add('hidden');
      uploadBtn.disabled = false;
    }
  });
});
