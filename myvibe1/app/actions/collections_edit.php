<?php
/**
 * COLLECTION EDIT CONTROLLER
 *
 * Úprava existující kolekce:
 *  - validace vstupu
 *  - ověření vlastnictví kolekce
 *  - bezpečný upload nové cover fotografie
 *  - smazání starého obrázku (pokud je manuální)
 *  - update v databázi
 *
 * Bezpečnost:
 *  - prepared statements => ochrana před SQL injection
 *  - validace MIME a velikosti uploadu
 *  - uživatel může upravit kolekci pouze pokud je vlastníkem nebo adminem
 *
 * @package MyVibe\Actions
 * @author  Safik
 */

session_start();
require __DIR__ . '/../core/db.php';
require __DIR__ . '/../core/paths.php';


// =====================================================
// 1) Ověření přihlášení
// =====================================================
if (empty($_SESSION['user_id'])) {
    header('Location: ../../auth.php');
    exit;
}


// =====================================================
// 2) Zpracování POST
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];

    $collectionId = intval($_POST['collection_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');

    if ($collectionId <= 0 || $title === '') {
        $_SESSION['msg_error'] = 'Invalid form data.';
        header('Location: ../../collections.php');
        exit;
    }


    // =====================================================
    // 3) Ověření, že kolekce patří uživateli (nebo je admin)
    // =====================================================
    $stmt = $pdo->prepare(
        'SELECT c.cover, c.user_id, u.username 
         FROM collections c
         JOIN users u ON c.user_id = u.id
         WHERE c.id = ?'
    );
    $stmt->execute([$collectionId]);
    $collection = $stmt->fetch();

    if (!$collection) {
        $_SESSION['msg_error'] = 'Collection not found.';
        header('Location: ../../collections.php');
        exit;
    }

    $isOwner =
        ($collection['user_id'] == $userId) ||
        (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');

    if (!$isOwner) {
        $_SESSION['msg_error'] = 'You do not have permission to edit this collection.';
        header('Location: ../../collections.php');
        exit;
    }


    // =====================================================
    // 4) Upload nového cover obrázku
    // =====================================================
    $newCoverPath = null;

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

                $newCoverPath = $userDir . $fileName;
                $absolutePath = __DIR__ . '/../../' . $newCoverPath;

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

                // Pokud měla kolekce starý manuální obrázek – smažeme ho
                if (
                    !empty($collection['cover']) &&
                    $collection['cover'] !== getDefaultImage('collection') &&
                    file_exists($collection['cover'])
                ) {
                    unlink($collection['cover']);
                }

            } else {
                $_SESSION['msg_error'] = 'Invalid image format. Only JPG, PNG, and WEBP are allowed.';
                header('Location: ../../collections.php');
                exit;
            }

        } else {
            $_SESSION['msg_error'] = 'Image upload failed or file is too large.';
            header('Location: ../../collections.php');
            exit;
        }
    }


    // =====================================================
    // 5) UPDATE v databázi
    // =====================================================
    if ($newCoverPath) {
        $stmt = $pdo->prepare(
            'UPDATE collections 
             SET title = ?, cover = ?, updated_at = NOW() 
             WHERE id = ?'
        );
        $stmt->execute([$title, $newCoverPath, $collectionId]);

    } else {
        $stmt = $pdo->prepare(
            'UPDATE collections 
             SET title = ?, updated_at = NOW() 
             WHERE id = ?'
        );
        $stmt->execute([$title, $collectionId]);
    }


    // =====================================================
    // 6) Flash message + redirect
    // =====================================================
    $_SESSION['msg_info'] = 'Collection updated successfully.';
    $_SESSION['msg_info'] = 'Collection updated successfully.';

    // Redirect zpět na správný profil
    $redirectUrl = '../../collections.php';
    if ($collection['username'] !== $_SESSION['username']) {
        $redirectUrl .= '?user=' . urlencode($collection['username']);
    }

    header('Location: ' . $redirectUrl);
    exit;
}
