/**
 * Login Form Validation
 * ---------------------
 * Tento skript ověřuje přihlášovací formulář uživatele.
 *
 * Funkce:
 *  - kontroluje délku uživatelského jména / emailu (min. 3 znaky)
 *  - kontroluje délku hesla (min. 6 znaků)
 *  - zamezuje odeslání formuláře při nevalidních datech
 *
 * Bezpečnost:
 *  - žádné inline skripty
 *  - validace probíhá na straně klienta, server by měl stále provést vlastní kontrolu
 */

// ======================================================
// 1) Inicializace a ověření existence formuláře
// ======================================================
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('loginForm');
  if (!form) return; // pokud formulář neexistuje, skript se nevykoná

  // ======================================================
  // 2) Validace při odeslání formuláře
  // ======================================================
  form.addEventListener('submit', function (event) {
      // reset validací
      form.user.setCustomValidity('');
      form.password.setCustomValidity('');

      const user = form.user.value.trim();
      const password = form.password.value;

      // validace uživatelského jména / emailu
      if (user.length < 3) {
          form.user.setCustomValidity('Please enter your username or email.');
      }

      // validace hesla
      if (password.length < 6) {
          form.password.setCustomValidity('Password must be at least 6 characters long.');
      }

      // pokud formulář není validní, zablokujeme odeslání a zobrazíme chyby
      if (!form.checkValidity()) {
          event.preventDefault();
          form.reportValidity();
      }
  });
});
