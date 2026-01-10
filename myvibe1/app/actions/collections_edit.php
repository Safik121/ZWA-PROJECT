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

                if (move_uploaded_file($file['tmp_name'], $absolutePath)) {
                    @chmod($absolutePath, 0777);
                } else {
                    $_SESSION['msg_error'] = 'Failed to upload image. Check server permissions.';
                    header('Location: ../../collections.php');
                    exit;
                }

                // Pokud měla kolekce starý manuální obrázek – smažeme ho
                if (
                    !empty($collection['cover']) &&
                    $collection['cover'] !== getDefaultImage('collection') &&
                    file_exists(__DIR__ . '/../../' . $collection['cover'])
                ) {
                    unlink(__DIR__ . '/../../' . $collection['cover']);
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
