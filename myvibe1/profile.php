<?php
/**
 * PROFILE CONTROLLER
 *
 * Obsluhuje zobrazení profilů (vlastní i cizí), upload avataru a update BIO.
 *
 * Funkce:
 *  - zobrazení cizího profilu pomocí ?user=username
 *  - zobrazení vlastního profilu (bez parametrů)
 *  - upload avataru (validace, generování unikátního názvu)
 *  - aktualizace BIO
 *  - načítání kolekcí uživatele
 *  - flash messages (úspěch / chyba)
 *
 * Bezpečnost:
 *  - prepared statements (PDO)
 *  - validace souboru (MIME, velikost, povolené typy)
 *  - mazání starého avataru pouze v rámci uživatelova adresáře
 *  - žádné inline skripty ve view
 *  - všechny výstupy escapovány až ve view
 *
 * @package MyVibe
 * @author  Safik
 */

session_start();
require __DIR__ . '/app/core/db.php';
require __DIR__ . '/app/core/paths.php';


// ======================================================
// 1) Zobrazení cizího profilu (?user=username)
// ======================================================
// ======================================================
// 1) Zobrazení cizího profilu (?user=username NEBO ?id=123)
// ======================================================
if (isset($_GET['user']) || isset($_GET['id'])) {

    $user = null;

    if (isset($_GET['id'])) {
        $id = (int) $_GET['id'];
        // Pokud je to moje ID, přesměruj na můj profil
        if (isset($_SESSION['user_id']) && $id == $_SESSION['user_id']) {
            header('Location: profile.php');
            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT id, username, email, avatar, created_at, last_login, role, bio, display_name
             FROM users WHERE id = ?'
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch();

    } elseif (isset($_GET['user'])) {
        $username = trim($_GET['user']);

        // Pokud je to moje username (nepravděpodobné, ale možné), přesměruj
        if (isset($_SESSION['username']) && $username === $_SESSION['username']) {
            header('Location: profile.php');
            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT id, username, email, avatar, created_at, last_login, role, bio, display_name
             FROM users WHERE username = ?'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();
    }

    // Pokud neexistuje → error view
    if (!$user) {
        $data = [
            'user' => null,
            'collections' => [],
            'success' => '',
            'error' => 'User not found.',
            'viewingOwn' => false
        ];
        include __DIR__ . '/views/profile_view.php';
        exit;
    }

    // Načtení kolekcí cizího uživatele
    $stmt = $pdo->prepare(
        'SELECT id, title, theme_type, custom_theme_name, cover
         FROM collections WHERE user_id = ? ORDER BY created_at DESC'
    );
    $stmt->execute([$user['id']]);
    $collections = $stmt->fetchAll();

    // Předání dat do view
    $data = [
        'user' => $user,
        'collections' => $collections,
        'success' => '',
        'error' => '',
        'viewingOwn' => false
    ];

    include __DIR__ . '/views/profile_view.php';
    exit;
}


// ======================================================
// 2) Zobrazení vlastního profilu (bez ?user=)
// ======================================================
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, username, email, avatar, created_at, last_login, role, bio, display_name
     FROM users WHERE id = ?'
);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    exit('User not found.');
}

$success = $error = '';


// ======================================================
// 3) Upload avataru
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_avatar') {

    $file = $_FILES['avatar'] ?? null;
    $maxSize = 2 * 1024 * 1024; // 2MB
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    // Validace souboru
    if ($file && $file['error'] === UPLOAD_ERR_OK && $file['size'] <= $maxSize) {

        $mime = mime_content_type($file['tmp_name']);

        if (isset($allowed[$mime])) {

            $avatarDir = getUserPath($user['username'], 'avatar');
            $newName = 'avatar_' . uniqid('', true) . '.' . $allowed[$mime];
            $filePath = $avatarDir . $newName;

            // Smazání starého avataru (pokud není default)
            if (
                !empty($user['avatar']) &&
                $user['avatar'] !== getDefaultImage('avatar') &&
                file_exists($user['avatar'])
            ) {
                unlink($user['avatar']);
            }

            // Přesun nahraného souboru
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $stmt = $pdo->prepare('UPDATE users SET avatar = ? WHERE id = ?');
                $stmt->execute([$filePath, $user['id']]);
                $_SESSION['upload_success'] = 'Profile picture updated successfully.';
            } else {
                $_SESSION['upload_error'] = 'File upload failed.';
            }

        } else {
            $_SESSION['upload_error'] = 'Only JPG, PNG or WEBP allowed.';
        }

    } else {
        $_SESSION['upload_error'] = 'Upload error or file too large.';
    }

    header('Location: profile.php');
    exit;
}


// ======================================================
// 4) Aktualizace BIO
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_bio') {

    $bio = trim($_POST['bio'] ?? '');

    $stmt = $pdo->prepare('UPDATE users SET bio = ? WHERE id = ?');
    $stmt->execute([$bio, $user['id']]);

    $_SESSION['msg_success'] = 'Bio updated.';
    header('Location: profile.php');
    exit;
}


// ======================================================
// 5) Flash messages (session → view)
// ======================================================
if (!empty($_SESSION['upload_success'])) {
    $success = $_SESSION['upload_success'];
    unset($_SESSION['upload_success']);
}
if (!empty($_SESSION['upload_error'])) {
    $error = $_SESSION['upload_error'];
    unset($_SESSION['upload_error']);
}
if (!empty($_SESSION['msg_success'])) {
    $success = $_SESSION['msg_success'];
    unset($_SESSION['msg_success']);
}


// ======================================================
// 6) Načtení kolekcí pro vlastní profil
// ======================================================
$stmt = $pdo->prepare(
    'SELECT id, title, theme_type, custom_theme_name, cover
     FROM collections WHERE user_id = ? ORDER BY created_at DESC'
);
$stmt->execute([$user['id']]);
$collections = $stmt->fetchAll();


// ======================================================
// 7) Předání dat do view
// ======================================================
$data = [
    'user' => $user,
    'collections' => $collections,
    'success' => $success,
    'error' => $error,
    'viewingOwn' => true
];

include __DIR__ . '/views/profile_view.php';
