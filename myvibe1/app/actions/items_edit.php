<?php
/**
 * ITEM EDIT CONTROLLER
 *
 * Upravuje existující položku v kolekci.
 *
 * Funkce:
 *  - validace vstupů
 *  - kontrola oprávnění (owner/admin)
 *  - volitelný bezpečný upload obrázku (MIME + velikost)
 *  - uložení upravených dat
 *
 * Bezpečnost:
 *  - SQL injection → prepared statements
 *  - validace vstupů přes trim(), intval()
 *  - MIME + velikost při uploadu
 *  - kontrola přístupu k položce i kolekci
 *  - žádný inline JS, žádné echo neescapovaného obsahu
 *
 * @package MyVibe\Actions
 * @author  Safik
 */

session_start();
require __DIR__ . '/../core/db.php';
require __DIR__ . '/../core/paths.php';


// ======================================
// 1) Musí být přihlášen
// ======================================
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth.php');
    exit;
}


// ======================================
// 2) POST zpracování
// ======================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $itemId = (int) ($_POST['item_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $rating = (int) ($_POST['rating'] ?? 0);

    if ($itemId <= 0 || $title === '') {
        $_SESSION['msg_error'] = 'Invalid item data.';
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../../index.php'));
        exit;
    }


    // ======================================
    // 3) Načtení položky + kontrola vlastnictví
    // ======================================
    $stmt = $pdo->prepare(
        'SELECT i.*, c.user_id AS owner_id
         FROM items i
         JOIN collections c ON i.collection_id = c.id
         WHERE i.id = ?'
    );
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();

    if (!$item) {
        $_SESSION['msg_error'] = 'Item not found.';
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../../index.php'));
        exit;
    }

    $isOwner = (
        $item['owner_id'] == $_SESSION['user_id'] ||
        (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin')
    );

    if (!$isOwner) {
        $_SESSION['msg_error'] = 'You do not have permission to edit this item.';
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../../index.php'));
        exit;
    }


    // ======================================
    // 4) Upload nového obrázku (volitelný)
    // ======================================
    $imagePath = $item['image'];

    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

        $file = $_FILES['image'];
        $maxSize = 3 * 1024 * 1024; // 3 MB
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if ($file['size'] <= $maxSize) {
            $mime = mime_content_type($file['tmp_name']);

            if (isset($allowed[$mime])) {

                $userDir = getUserPath($_SESSION['username'], 'items');
                // getUserPath handles mkdir with absolute paths internally.

                $absoluteDir = __DIR__ . '/../../' . $userDir;
                if (!is_dir($absoluteDir)) {
                    @mkdir($absoluteDir, 0777, true);
                }

                $newName = 'item_' . uniqid('', true) . '.' . $allowed[$mime];
                $filePath = $userDir . $newName; // Relative for DB
                $absolutePath = $absoluteDir . $newName; // Absolute for move_uploaded_file

                // Zpracování a uložení obrázku pomocí nativního GD
                $srcPath = $file['tmp_name'];
                $destPath = $absolutePath;
                $maxWidth = 1000;
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

                    if ($saved) {
                        @chmod($absolutePath, 0777);
                        $imagePath = $filePath;
                    } else {
                        $_SESSION['msg_error'] = 'Failed to save image (GD error).';
                        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../../index.php'));
                        exit;
                    }

                } else {
                    $_SESSION['msg_error'] = 'Unsupported image type or GD error.';
                    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../../index.php'));
                    exit;
                }
            }
        }
    }


    // ======================================
    // 5) UPDATE položky v databázi
    // ======================================
    $stmt = $pdo->prepare(
        'UPDATE items 
         SET title = ?, comment = ?, rating = ?, image = ?, updated_at = NOW()
         WHERE id = ?'
    );

    $stmt->execute([
        $title,
        $comment,
        max(1, min(5, $rating ?: 3)),
        $imagePath,
        $itemId
    ]);


    $_SESSION['msg_info'] = 'Item updated successfully.';
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../../index.php'));
    exit;
}
