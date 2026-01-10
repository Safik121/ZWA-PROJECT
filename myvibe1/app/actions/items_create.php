<?php
/**
 * ITEM CREATE CONTROLLER
 *
 * Přidává novou položku do kolekce.
 *
 * Funkce:
 *  - validace vstupů
 *  - kontrola, zda položku přidává vlastník kolekce
 *  - bezpečný upload obrázku (MIME + max 3 MB)
 *  - uložení položky do DB
 *
 * Bezpečnost:
 *  - všechny vstupy ošetřeny přes trim(), intval()
 *  - SQL injection chráněno přes prepared statements
 *  - obrázek validuje MIME typ + velikost + ukládání jen do vlastních složek
 *  - žádný inline JavaScript
 *
 * @package MyVibe\Actions
 * @author  Safik
 */

session_start();
require __DIR__ . '/../core/db.php';
require __DIR__ . '/../core/paths.php';

// ======================================
// 1) Kontrola přihlášení
// ======================================
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth.php');
    exit;
}


// ======================================
// 2) Zpracování POST
// ======================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $collectionId = (int) ($_POST['collection_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $rating = (int) ($_POST['rating'] ?? 0);

    if ($collectionId <= 0 || $title === '') {
        $_SESSION['msg_error'] = 'Invalid data.';
        header("Location: ../../collection_detail.php?id=$collectionId");
        exit;
    }

    // ======================================
    // 3) Ověření vlastnictví kolekce
    // ======================================
    $stmt = $pdo->prepare('SELECT user_id FROM collections WHERE id = ?');
    $stmt->execute([$collectionId]);
    $collection = $stmt->fetch();

    if (!$collection) {
        $_SESSION['msg_error'] = 'Collection not found.';
        header("Location: ../../collections.php");
        exit;
    }

    $isOwner = $collection['user_id'] == $_SESSION['user_id'] ||
        (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');

    if (!$isOwner) {
        $_SESSION['msg_error'] = 'You do not have permission to add items to this collection.';
        header("Location: ../../collection_detail.php?id=$collectionId");
        exit;
    }


    // ======================================
    // 4) Upload obrázku (volitelný) nebo API URL
    // ======================================
    $imagePath = getDefaultImage('item'); // default placeholder
    $source = 'manual';
    $apiId = null;

    // A) Upload souboru
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

                // správná složka uživatele
                // správná složka uživatele
                $userDir = getUserPath($_SESSION['username'], 'items');
                // getUserPath already handles mkdir with absolute paths, so we don't need to do it here again
                // unless we want to be double sure, but we must use absolute path for mkdir too.
                // For now, let's trust getUserPath or check absolute path.

                $absoluteDir = __DIR__ . '/../../' . $userDir;
                if (!is_dir($absoluteDir)) {
                    @mkdir($absoluteDir, 0777, true);
                }

                // unikátní název
                $newName = 'item_' . uniqid('', true) . '.' . $allowed[$mime];
                $filePath = $userDir . $newName; // Relative for DB
                $absolutePath = $absoluteDir . $newName; // Absolute for move_uploaded_file

                if (@move_uploaded_file($file['tmp_name'], $absolutePath)) {
                    @chmod($absolutePath, 0777);
                    $imagePath = $filePath;
                } else {
                    $_SESSION['msg_error'] = 'Failed to upload image. Check server permissions.';
                    header("Location: ../../collection_detail.php?id=$collectionId");
                    exit;
                }
            }
        }
    }
    // B) API Image URL
    elseif (!empty($_POST['image_url'])) {
        $imagePath = trim($_POST['image_url']);
        $source = 'api';
        $apiId = $_POST['api_id'] ?? null;
    }

    // Získání preview_url (pouze pro hudbu, ale controller to přijme obecně, pokud je posláno)
    $previewUrl = !empty($_POST['preview_url']) ? trim($_POST['preview_url']) : null;


    // ======================================
    // 5) Uložení položky do databáze
    // ======================================
    $stmt = $pdo->prepare('
        INSERT INTO items (collection_id, title, comment, rating, image, preview_url, source, api_id, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ');

    $stmt->execute([
        $collectionId,
        $title,
        $comment,
        max(1, min(5, $rating ?: 3)), // rozsah 1–5
        $imagePath,
        $previewUrl,
        $source,
        $apiId
    ]);

    $_SESSION['msg_success'] = 'Item added successfully.';
    header("Location: ../../collection_detail.php?id=$collectionId");
    exit;
}
