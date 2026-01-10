<?php
/**
 * SETTINGS CONTROLLER
 *
 * Správa uživatelského účtu:
 *  - změna e-mailu
 *  - změna hesla
 *  - změna profilového obrázku
 *
 * Bezpečnost:
 *  - prepared statements proti SQL injection
 *  - ověřené MIME typy obrázků + max velikost
 *  - žádný inline JavaScript
 *  - kontrola vlastnictví účtu
 *
 * @package MyVibe
 * @author  Safik
 */

session_start();
require __DIR__ . '/app/core/db.php';
require __DIR__ . '/app/core/paths.php';


// ======================================================
// 1) Ověření přihlášení
// ======================================================
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];


// ======================================================
// 2) Načtení aktuálního uživatele
// ======================================================
$stmt = $pdo->prepare('SELECT id, username, email, avatar, display_name FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    exit('User not found.');
}

$success = '';
$error = '';

// Capture back URL
$backUrl = $_REQUEST['back'] ?? 'profile.php';
// Basic sanitization to prevent open redirects to external sites
if (strpos($backUrl, '/') !== 0 && strpos($backUrl, 'http') !== false) {
    $backUrl = 'profile.php';
}



// ======================================================
// 3) ZMĚNA DISPLAY NAME
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_display_name') {

    $newDisplayName = trim($_POST['display_name'] ?? '');

    if (strlen($newDisplayName) < 1 || strlen($newDisplayName) > 50) {
        $error = 'Display name must be between 1 and 50 characters.';
    } else {
        $stmt = $pdo->prepare('UPDATE users SET display_name = ? WHERE id = ?');
        $stmt->execute([$newDisplayName, $userId]);

        $success = 'Display name updated successfully.';
    }
}


// ======================================================
// 4) ZMĚNA EMAILU
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_email') {

    $newEmail = trim($_POST['new_email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';

    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Verify password first
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
            $error = 'Incorrect password. Email update cancelled.';
        } else {
            // Check if email is taken
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$newEmail, $userId]);

            if ($stmt->fetch()) {
                $error = 'This email is already taken.';
            } else {
                $stmt = $pdo->prepare('UPDATE users SET email = ? WHERE id = ?');
                $stmt->execute([$newEmail, $userId]);

                $success = 'Email updated successfully.';
            }
        }
    }
}


// ======================================================
// 4) ZMĚNA HESLA
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_password') {

    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {

        // Ověření aktuálního hesla
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password_hash'])) {
            $error = 'Current password incorrect.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([$hash, $userId]);

            $success = 'Password changed successfully.';
        }
    }
}


// ======================================================
// 5) ZMĚNA AVATARU
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_avatar') {

    $file = $_FILES['avatar'] ?? null;
    $maxSize = 2 * 1024 * 1024;

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if ($file && $file['error'] === UPLOAD_ERR_OK && $file['size'] <= $maxSize) {

        $mime = mime_content_type($file['tmp_name']);

        if (array_key_exists($mime, $allowed)) {

            $avatarDir = getUserPath($username, 'avatar');
            $fileName = 'avatar_' . uniqid('', true) . '.' . $allowed[$mime];
            $filePath = $avatarDir . $fileName;

            // Smazání starého avataru
            if (
                !empty($user['avatar']) &&
                $user['avatar'] !== getDefaultImage('avatar') &&
                file_exists($user['avatar'])
            ) {

                unlink($user['avatar']);
            }

            // Pokus o nahrání souboru
            if (@move_uploaded_file($file['tmp_name'], $filePath)) {
                @chmod($filePath, 0777);

                // Update DB pouze pokud se upload zdařil
                $stmt = $pdo->prepare('UPDATE users SET avatar = ? WHERE id = ?');
                $stmt->execute([$filePath, $userId]);
                $success = 'Profile picture updated.';

            } else {
                $error = 'Failed to save image. Check server permissions for "uploads" folder.';
            }
        } else {
            $error = 'Only JPG, PNG, or WEBP are allowed.';
        }
    } else {
        $error = 'Upload failed or file too large (max 2MB).';
    }
}


// ======================================================
// 6) SMAZÁNÍ ÚČTU
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_account') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. Ověření emailu a hesla
    $stmt = $pdo->prepare('SELECT id, username, email, password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch();

    if (!$currentUser) {
        $error = 'User not found.';
    } elseif (strcasecmp($email, $currentUser['email']) !== 0) {
        $error = 'Incorrect email address.';
    } elseif (!password_verify($password, $currentUser['password_hash'])) {
        $error = 'Incorrect password.';
    } else {
        // 2. Smazání souborů
        deleteUserDirectory($currentUser['username']);

        // 3. Smazání z DB
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);

        // 4. Logout a redirect
        session_destroy();
        header('Location: index.php');
        exit;
    }
}


// ======================================================
// 7) Aktualizace dat uživatele po změnách
// ======================================================
$stmt = $pdo->prepare('SELECT id, username, email, avatar, display_name FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    // Pokud byl uživatel smazán (a script nezastavil exit), přesměrujeme
    session_destroy();
    header('Location: index.php');
    exit;
}


// ======================================================
// 7) Poslání do view
// ======================================================
$data = [
    'user' => $user,
    'success' => $success,
    'error' => $error,
    'backUrl' => $backUrl
];

include __DIR__ . '/views/settings_view.php';
