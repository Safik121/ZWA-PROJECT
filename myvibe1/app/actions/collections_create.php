<?php
/**
 * COLLECTIONS CREATE CONTROLLER
 *
 * Zpracování vytvoření nové kolekce:
 *  - validace vstupních dat
 *  - bezpečné nahrání cover obrázku (MIME + velikost)
 *  - uložení kolekce do databáze
 *
 * Bezpečnost:
 *  - SQL injection: prepared statements
 *  - MIME kontrola a velikost souborů u uploadů
 *  - uživatel smí vytvořit kolekci pouze pokud je přihlášen
 *  - žádný inline JavaScript
 *  - default obrázek generován přes getDefaultImage()
 *
 * @package MyVibe\Actions
 * @author  Safik
 */

session_start();
require __DIR__ . '/../core/db.php';
require __DIR__ . '/../core/paths.php';


// =====================================================
// 1) Kontrola přihlášení uživatele
// =====================================================
if (empty($_SESSION['user_id'])) {
    header('Location: ../../auth.php');
    exit;
}


// =====================================================
// 2) Zpracování POST požadavku
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $isAdmin = (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');

    // Pokud je admin a je zadán target_username, vytvoříme kolekci pro tohoto uživatele
    if ($isAdmin && !empty($_POST['target_username'])) {
        $targetUsername = trim($_POST['target_username']);

        // Získáme ID cílového uživatele
        $stmtUser = $pdo->prepare('SELECT id, username FROM users WHERE username = ?');
        $stmtUser->execute([$targetUsername]);
        $targetUser = $stmtUser->fetch();

        if ($targetUser) {
            $userId = $targetUser['id'];
            $username = $targetUser['username'];
        }
    }
    $title = trim($_POST['title'] ?? '');
    $themeType = trim($_POST['theme_type'] ?? 'custom');
    $customTheme = trim($_POST['custom_theme_name'] ?? '');
    $coverPath = null;

    // -------------------------------------------------
    // Validace titulku
    // -------------------------------------------------
    if ($title === '') {
        $_SESSION['msg_error'] = 'Title is required.';
        header('Location: ../../collections.php');
        exit;
    }


    // =====================================================
    // 3) Upload cover obrázku
    // =====================================================
    if (!empty($_FILES['cover']['name'])) {

        $file = $_FILES['cover'];

        $maxSize = 2 * 1024 * 1024; // 2 MB
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if ($file['error'] === UPLOAD_ERR_OK && $file['size'] <= $maxSize) {

            $mime = mime_content_type($file['tmp_name']);

            if (array_key_exists($mime, $allowed)) {

                $userDir = getUserPath($username, 'collections');
                $fileName = 'collection_' . uniqid('', true) . '.' . $allowed[$mime];
                $coverPath = $userDir . $fileName; // Relative path for DB (e.g. uploads/user/collections/file.png)

                // Absolute path for file system operation
                $absolutePath = __DIR__ . '/../../' . $coverPath;

                // Zpracování a uložení obrázku pomocí nativního GD
                $srcPath = $file['tmp_name'];
                $destPath = $absolutePath;
                $maxWidth = 1200;
                $quality = 80;

                list($width, $height, $type) = getimagesize($srcPath);
                $srcImg = null;

                switch ($type) {
                    case IMAGETYPE_JPEG:
                        $srcImg = imagecreatefromjpeg($srcPath);
                        break;
                    case IMAGETYPE_PNG:
                        $srcImg = imagecreatefrompng($srcPath);
                        break;
                    case IMAGETYPE_WEBP:
                        $srcImg = imagecreatefromwebp($srcPath);
                        break;
                }

                if ($srcImg) {
                    // Výpočet nových rozměrů (Scale)
                    $newWidth = $width;
                    $newHeight = $height;

                    if ($width > $maxWidth) {
                        $ratio = $height / $width;
                        $newWidth = $maxWidth;
                        $newHeight = round($maxWidth * $ratio);
                    }

                    // Vytvoření nového plátna
                    $destImg = imagecreatetruecolor($newWidth, $newHeight);

                    // Zachování průhlednosti
                    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_WEBP) {
                        imagealphablending($destImg, false);
                        imagesavealpha($destImg, true);
                        $transparent = imagecolorallocatealpha($destImg, 255, 255, 255, 127);
                        imagefilledrectangle($destImg, 0, 0, $newWidth, $newHeight, $transparent);
                    }

                    // Změna velikosti
                    imagecopyresampled(
                        $destImg,
                        $srcImg,
                        0,
                        0,
                        0,
                        0,
                        $newWidth,
                        $newHeight,
                        $width,
                        $height
                    );

                    // Uložení
                    $saved = false;
                    switch ($type) {
                        case IMAGETYPE_JPEG:
                            $saved = imagejpeg($destImg, $destPath, $quality);
                            break;
                        case IMAGETYPE_PNG:
                            $saved = imagepng($destImg, $destPath);
                            break;
                        case IMAGETYPE_WEBP:
                            $saved = imagewebp($destImg, $destPath, $quality);
                            break;
                    }

                    imagedestroy($srcImg);
                    imagedestroy($destImg);

                    if (!$saved) {
                        $_SESSION['msg_error'] = 'Failed to save image (GD error).';
                        header('Location: ../../collections.php');
                        exit;
                    }

                } else {
                    $_SESSION['msg_error'] = 'Unsupported image type or GD error.';
                    header('Location: ../../collections.php');
                    exit;
                }

            } else {
                $_SESSION['msg_error'] = 'Invalid image type. Only JPG, PNG, and WEBP are allowed.';
                header('Location: ../../collections.php');
                exit;
            }

        } else {
            $_SESSION['msg_error'] = 'Upload failed or file is too large.';
            header('Location: ../../collections.php');
            exit;
        }
    }


    // =====================================================
    // 4) Default obrázek pokud žádný upload nebyl
    // =====================================================
    if (!$coverPath) {
        $coverPath = getDefaultImage('collection');
    }


    // =====================================================
    // 5) Uložení nové kolekce do databáze
    // =====================================================
    $stmt = $pdo->prepare(
        'INSERT INTO collections (user_id, title, theme_type, custom_theme_name, cover, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );

    $stmt->execute([
        $userId,
        $title,
        $themeType,
        $customTheme,
        $coverPath
    ]);


    // =====================================================
    // 6) Flash message a redirect
    // =====================================================
    $_SESSION['msg_success'] = 'Collection created successfully.';

    // Redirect zpět na správný profil (pokud admin tvořil pro někoho jiného)
    $redirectUrl = '../../collections.php';
    if ($username !== $_SESSION['username']) {
        $redirectUrl .= '?user=' . urlencode($username);
    }

    header('Location: ' . $redirectUrl);
    exit;
}
