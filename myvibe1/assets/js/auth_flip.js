/**
 * Auth Card Flip
 * --------------
 * Skript umožňuje přepínat mezi login a register formulářem pomocí animace "flip".
 *
 * Funkce:
 *  - přidává/odebírá třídu `flipped` na auth-card
 *  - zachovává plynulou animaci přechodu mezi login a register
 *
 * Bezpečnost:
 *  - žádné inline skripty ani manipulace s citlivými daty
 *  - pouze vizuální efekt, žádný vliv na validaci nebo odeslání formuláře
 */

// ======================================================
// 1) Inicializace DOM elementů a event listenerů
// ======================================================
const card = document.querySelector('.auth-card');
const goRegister = document.querySelector('#goRegister');
const goLogin = document.querySelector('#goLogin');

// ======================================================
// 2) Přepnutí na registrační formulář
// ======================================================
goRegister.addEventListener('click', e => {
  e.preventDefault();
  card.classList.add('flipped');
});

// ======================================================
// 3) Přepnutí na login formulář
// ======================================================
goLogin.addEventListener('click', e => {
  e.preventDefault();
  card.classList.remove('flipped');
});
// ======================================================
// 4) Client-side validation (Password Match)
// ======================================================
const registerForm = document.getElementById('registerForm');
if (registerForm) {
  registerForm.addEventListener('submit', function (e) {
    const password = registerForm.querySelector('input[name="password"]').value;
    const confirm = registerForm.querySelector('input[name="confirm_password"]').value;

    if (password !== confirm) {
      e.preventDefault();

      // Check if error message already exists
      let errorDiv = registerForm.querySelector('.error-client');
      if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'error error-client';
        registerForm.querySelector('fieldset').prepend(errorDiv);
      }

      errorDiv.textContent = "Passwords do not match!";
    } else {
      // Clear error if fixed
      const errorDiv = registerForm.querySelector('.error-client');
      if (errorDiv) {
        errorDiv.remove();
      }
    }
  });
}
