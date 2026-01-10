<?php
/**
 * LOGOUT CONTROLLER
 *
 * Bezpečně odhlásí uživatele a smaže session.
 *
 * Bezpečnost:
 *  - session_unset() odstraní všechny session proměnné
 *  - session_destroy() ukončí session na serveru
 *  - new session cookie pomocí session_regenerate_id(true)
 *  - žádný inline JS/HTML, pouze redirect
 *
 * @package MyVibe\Actions
 * @author  Safik
 */

session_start();

// Vymaž všechny session proměnné
session_unset();

// Znič session soubor
session_destroy();

// Smaž session cookie z prohlížeče
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Redirect na homepage
header('Location: ../../index.php');
exit;
