/**
 * REGISTER FORM VALIDATION
 * ------------------------
 * Klientská validace registračního formuláře.
 *
 * Funkce:
 *  - kontroluje délku uživatelského jména (3–50 znaků)
 *  - validuje e-mail pomocí regulárního výrazu
 *  - kontroluje délku hesla (min. 6 znaků)
 *  - porovnává heslo a potvrzení hesla
 *  - pokud validace selže, blokuje submit a zobrazí chyby
 *
 * Bezpečnost:
 *  - žádné inline skripty
 *  - využívá HTML5 validation API
 *  - všechny chybové zprávy jsou definovány lokálně (žádné externí vstupy)
 *
 * Poznámky:
 *  - validace běží pouze po kliknutí na submit
 *  - formulář musí mít id="registerForm"
 */

// ======================================================
// 1) INIT
// ======================================================
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('registerForm');
  if (!form) return; // formulář neexistuje → nic neděláme

  // ======================================================
  // 2) FORM SUBMIT EVENT
  // ======================================================
  form.addEventListener('submit', (event) => {

    // ======================================================
    // 2.1 RESET CUSTOM VALIDITY
    // ======================================================
    for (const input of form.querySelectorAll('input')) {
      input.setCustomValidity('');
    }

    // ======================================================
    // 2.2 TRIM INPUTS
    // ======================================================
    const username = form.username.value.trim();
    const email    = form.email.value.trim();
    const password = form.password.value;
    const confirm  = form.confirm.value;

    // ======================================================
    // 2.3 USERNAME VALIDATION
    // ======================================================
    if (username.length < 3 || username.length > 50) {
      form.username.setCustomValidity(
        'Username must be between 3 and 50 characters long.'
      );
    }

    // ======================================================
    // 2.4 EMAIL VALIDATION
    // ======================================================
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
      form.email.setCustomValidity('Please enter a valid email address.');
    }

    // ======================================================
    // 2.5 PASSWORD VALIDATION
    // ======================================================
    if (password.length < 6) {
      form.password.setCustomValidity(
        'Password must be at least 6 characters long.'
      );
    }

    // ======================================================
    // 2.6 PASSWORD CONFIRMATION
    // ======================================================
    if (password !== confirm) {
      form.confirm.setCustomValidity('Passwords do not match.');
    }

    // ======================================================
    // 2.7 CHECK FORM VALIDITY
    // ======================================================
    if (!form.checkValidity()) {
      event.preventDefault(); // zabrání odeslání formuláře
      form.reportValidity();  // zobrazí chybové zprávy
    }
  });
});
