<?php
/**
 * REGISTER CONTROLLER
 *
 * Registrace nového uživatele.
 *
 * Funkce:
 *  - přijímá pouze POST požadavky
 *  - validace username, emailu a hesla
 *  - kontrola potvrzení hesla
 *  - kontrola duplicity (unikátní email/username)
 *  - bezpečné hashování hesla (password_hash)
 *  - fallback HTML error page pouze při chybách
 *
 * Bezpečnost:
 *  - prepared statements (ochrana proti SQL injection)
 *  - žádné echo citlivých PDO chyb
 *  - htmlspecialchars při výpisu chyb
 *  - žádný inline JavaScript
 *
 * @package MyVibe\Actions
 * @author  Safik
 */

session_start();
require __DIR__ . '/../core/db.php';


// ======================================================
// 1) Povolen pouze POST
// ======================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../auth.php');
    exit;
}


// ======================================================
// 2) Načtení a očištění vstupů
// ======================================================
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

$errors = [];


// ======================================================
// 3) Validace vstupních dat
// ======================================================
if (strlen($username) < 3 || strlen($username) > 50) {
    $errors[] = 'Username must be between 3 and 50 characters.';
}

if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
    $errors[] = 'Username can only contain letters and numbers (no special characters or spaces).';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

if (strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters long.';
}

if ($password !== $confirm) {
    $errors[] = 'Passwords do not match.';
}


// ======================================================
// 4) Pokud jsou chyby → Redirect zpět s chybami
// ======================================================
if (!empty($errors)) {
    $_SESSION['msg_error'] = $errors[0]; // Zobrazíme první chybu
    $_SESSION['old_input']['username'] = $username;
    $_SESSION['old_input']['email'] = $email;
    header('Location: ../../auth.php');
    exit;
}


// ======================================================
// 5) Uložení uživatele do databáze
// ======================================================
$hash = password_hash($password, PASSWORD_DEFAULT);

// ======================================================
// 5a) Avatar Upload (Optional)
// ======================================================
$avatarPath = 'default/avatar_default.png'; // Default avatar

if (!empty($_FILES['avatar']['name'])) {
    $file = $_FILES['avatar'];
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    // Basic validation
    if ($file['error'] === UPLOAD_ERR_OK) {
        if ($file['size'] > $maxSize) {
            $errors[] = 'Avatar image is too large (max 2MB).';
        } else {
            $mime = mime_content_type($file['tmp_name']);
            if (!array_key_exists($mime, $allowedTypes)) {
                $errors[] = 'Invalid image format. Allowed: JPG, PNG, WEBP.';
            }
        }
    } else {
        // Ignorujeme chybu "No file uploaded" (error 4), ostatní hlásíme
        if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Error uploading file.';
        }
    }

    // Pokud nejsou chyby, provedeme upload
    if (empty($errors)) {
        // Vytvoření složky pro uživatele (použijeme username)
        // Pozor: username už prošlo validací délky, ale pro jistotu sanitizujeme pro FS
        $safeUsername = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);
        $uploadDir = __DIR__ . '/../../uploads/' . $safeUsername . '/avatar/';

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            chmod($uploadDir, 0777);
        }

        $extension = $allowedTypes[$mime];
        $fileName = 'avatar_' . uniqid() . '.' . $extension;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            chmod($targetPath, 0777);
            $avatarPath = 'uploads/' . $safeUsername . '/avatar/' . $fileName;
        } else {
            $errors[] = 'Failed to save uploaded image.';
        }
    }
}

// Pokud se objevily chyby během uploadu, vrátíme uživatele zpět
if (!empty($errors)) {
    $_SESSION['msg_error'] = $errors[0];
    $_SESSION['old_input']['username'] = $username;
    $_SESSION['old_input']['email'] = $email;
    header('Location: ../../auth.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, email, password_hash, display_name, avatar)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$username, $email, $hash, $username, $avatarPath]);

    // Úspěch → zpět na login
    $_SESSION['msg_success'] = 'Registration successful! Please log in.';
    header('Location: ../../auth.php');
    exit;

} catch (PDOException $e) {

    // Default chybová zpráva
    $message = 'Registration failed. Please try again.';

    // 23000 = duplicate key (email/username)
    if ($e->getCode() === '23000') {
        $message = 'Username or email already exists.';
    }

    $_SESSION['msg_error'] = $message;
    $_SESSION['old_input']['username'] = $username;
    $_SESSION['old_input']['email'] = $email;
    header('Location: ../../auth.php');
    exit;
}
