<?php
/**
 * LOGIN CONTROLLER
 *
 * Zpracovává přihlášení uživatele pomocí username nebo emailu.
 *
 * Funkce:
 *  - ověření odeslaného formuláře
 *  - načtení uživatele (email OR username)
 *  - kontrola hesla pomocí password_verify()
 *  - uložení session hodnot
 *  - update last_login
 *
 * Bezpečnost:
 *  - prepared statements (proti SQL injection)
 *  - trim() pro textové vstupy
 *  - session_regenerate_id(true) proti session fixation
 *  - žádný inline JS / HTML v controlleru
 *
 * @package MyVibe\Actions
 * @author  Safik
 */

session_start();
require __DIR__ . '/../core/db.php';


// ======================================
// 1) Přijímáme pouze POST požadavky
// ======================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../auth.php');
    exit;
}


// ======================================
// 2) Validace vstupů
// ======================================
$userInput = trim($_POST['user'] ?? '');
$password = $_POST['password'] ?? '';

if ($userInput === '' || $password === '') {
    $_SESSION['msg_error'] = 'Please fill out all fields.';
    $_SESSION['old_input']['user'] = $userInput;
    header('Location: ../../auth.php');
    exit;
}


// ======================================
// 3) Pokus o načtení uživatele
// ======================================
$stmt = $pdo->prepare(
    'SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1'
);
$stmt->execute([$userInput, $userInput]);
$user = $stmt->fetch();


// ======================================
// 4) Ověření hesla
// ======================================
if ($user && password_verify($password, $user['password_hash'])) {

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'] ?? 'user';

    // Update last login timestamp
    $update = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
    $update->execute([$user['id']]);

    header('Location: ../../index.php');
    exit;
}


// ======================================
// 5) Neúspěch – bezpečný redirect
// ======================================
$_SESSION['msg_error'] = 'Incorrect username/email or password.';
$_SESSION['old_input']['user'] = $userInput;
header('Location: ../../auth.php');
exit;
