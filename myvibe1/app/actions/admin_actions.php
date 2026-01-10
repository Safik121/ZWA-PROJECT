<?php
/**
 * ADMIN ACTIONS HANDLER
 *
 * AJAX endpoint pro správu uživatelů a kolekcí adminem.
 *
 * Funkce:
 *  - list_json         → vrací JSON seznam uživatelů
 *  - delete            → smaže uživatele (kromě admina)
 *  - update            → upraví email/heslo/avatar uživatele
 *  - create_collection → vytvoří novou kolekci pro uživatele
 *
 * Přístup:
 *  - pouze přihlášený admin (session role = 'admin')
 *
 * @package MyVibe\Actions
 * @author  Safik
 */

session_start();
require __DIR__ . '/../core/db.php';

// ======================================================
// 1. OCHRANA ADMINA
// ======================================================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}

$action = $_REQUEST['action'] ?? '';

// ======================================================
// 2. SEZNAM UŽIVATELŮ (JSON)
// ======================================================
if ($action === 'list_json') {
    $currentUserId = $_SESSION['user_id'] ?? 0;

    $sql = 'SELECT id, username, email, avatar, role 
            FROM users 
            ORDER BY 
              CASE WHEN id = ? THEN 0 ELSE 1 END ASC,
              CASE WHEN role = "admin" THEN 0 ELSE 1 END ASC,
              username ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$currentUserId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($users);
    exit;
}

// ======================================================
// 3. MAZÁNÍ UŽIVATELE
// ======================================================
if ($action === 'delete' && isset($_POST['username'])) {
    $username = trim($_POST['username']);

    // Nelze smazat vlastní admin účet
    if (isset($_SESSION['username']) && $username === $_SESSION['username']) {
        exit("You cannot delete your own admin account.");
    }

    $stmt = $pdo->prepare('DELETE FROM users WHERE username = ? AND role != "admin"');
    $stmt->execute([$username]);

    // Smazání souborů uživatele
    require_once __DIR__ . '/../core/paths.php';
    deleteUserDirectory($username);

    $safeUsername = htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo "User '{$safeUsername}' deleted.";
    exit;
}

// ======================================================
// 4. UPDATE UŽIVATELE (email, heslo, avatar)
// ======================================================
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $newPassword = trim($_POST['password'] ?? '');

    // Získání uživatele
    $stmt = $pdo->prepare('SELECT avatar, username, role FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user)
        exit("User not found.");

    $avatarPath = $user['avatar'];
    $username = $user['username'];
    $userRole = $user['role'];

    if ($userRole === 'admin' && $newPassword !== '') {
        exit("You cannot change password of another admin account.");
    }

    // Upload nového avatara
    if (!empty($_FILES['avatarFile']['name'])) {
        $uploadDir = __DIR__ . '/../../uploads/' . $username . '/avatar/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            chmod($uploadDir, 0777);
        }

        $file = $_FILES['avatarFile'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $maxSize = 2 * 1024 * 1024;

        if ($file['error'] === UPLOAD_ERR_OK && $file['size'] <= $maxSize) {
            $mime = mime_content_type($file['tmp_name']);
            if (isset($allowed[$mime])) {
                $fileName = 'avatar_' . uniqid('', true) . '.' . $allowed[$mime];
                $target = $uploadDir . $fileName;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    chmod($target, 0777);
                    $avatarPath = 'uploads/' . $username . '/avatar/' . $fileName;
                }
            }
        }
    }

    // Aktualizace databáze
    if ($newPassword !== '') {
        if (strlen($newPassword) < 6)
            exit("Password must be at least 6 characters long.");
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET email=?, password_hash=?, avatar=? WHERE id=?');
        $stmt->execute([$email, $hashed, $avatarPath, $id]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET email=?, avatar=? WHERE id=?');
        $stmt->execute([$email, $avatarPath, $id]);
    }

    echo 'User updated successfully.';
    exit;
}

// ======================================================
// 5. VYTVOŘENÍ KOLEKCE PRO UŽIVATELE
// ======================================================
if ($action === 'create_collection' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $themeType = trim($_POST['theme_type'] ?? 'custom');
    $customTheme = trim($_POST['custom_theme_name'] ?? '');
    $coverPath = 'default/collection_default.png';

    if ($username === '' || $title === '')
        exit('Username and title are required.');

    // Najdeme uživatele
    $stmt = $pdo->prepare('SELECT id, username FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user)
        exit('User not found.');

    // Upload cover obrázku
    if (!empty($_FILES['cover']['name'])) {
        $file = $_FILES['cover'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $maxSize = 2 * 1024 * 1024;

        if ($file['error'] === UPLOAD_ERR_OK && $file['size'] <= $maxSize) {
            $mime = mime_content_type($file['tmp_name']);
            if (isset($allowed[$mime])) {
                $userDir = __DIR__ . '/../../uploads/' . $user['username'] . '/collections/';
                if (!file_exists($userDir)) {
                    mkdir($userDir, 0777, true);
                    chmod($userDir, 0777);
                }

                $fileName = 'collection_' . uniqid('', true) . '.' . $allowed[$mime];
                $target = $userDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $target)) {
                    chmod($target, 0777);
                    $coverPath = 'uploads/' . $user['username'] . '/collections/' . $fileName;
                }
            }
        }
    }

    // Uložení kolekce
    $stmt = $pdo->prepare('
        INSERT INTO collections (user_id, title, theme_type, custom_theme_name, cover, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ');
    $stmt->execute([$user['id'], $title, $themeType, $customTheme, $coverPath]);

    $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeUsername = htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    echo "Collection '{$safeTitle}' created for user '{$safeUsername}'.";
    exit;
}

// ======================================================
// 6. POVÝŠENÍ NA ADMINA
// ======================================================
if ($action === 'promote' && isset($_POST['username'])) {
    $username = trim($_POST['username']);

    $stmt = $pdo->prepare('UPDATE users SET role = "admin" WHERE username = ?');
    $stmt->execute([$username]);

    $safeUsername = htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo "User '{$safeUsername}' promoted to Admin.";
    exit;
}

// ======================================================
// 7. NEPLATNÝ POŽADAVEK
// ======================================================
exit("Invalid request.");
