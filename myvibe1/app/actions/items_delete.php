<?php
/**
 * ITEMS DELETE CONTROLLER
 *
 * Handles deletion of items from a collection.
 *
 * @package MyVibe\Actions
 * @author  Safik
 */

session_start();
require __DIR__ . '/../core/db.php';
require __DIR__ . '/../core/paths.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $userId = $_SESSION['user_id'] ?? null;
    $role = $_SESSION['role'] ?? 'guest';
    $itemId = (int) ($_POST['item_id'] ?? 0);

    if (!$userId) {
        header('Location: ../../auth.php');
        exit;
    }

    // Get item and collection info to verify ownership
    $stmt = $pdo->prepare('
        SELECT i.id, i.image, i.source, c.user_id as owner_id, i.collection_id
        FROM items i
        JOIN collections c ON i.collection_id = c.id
        WHERE i.id = ?
    ');
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();

    if ($item) {
        $collectionId = $item['collection_id'];
        $isOwner = ($item['owner_id'] == $userId || $role === 'admin');

        if ($isOwner) {
            // Delete image if manual and not default
            if ($item['source'] === 'manual' && !empty($item['image']) && $item['image'] !== getDefaultImage('item') && file_exists($item['image'])) {
                unlink($item['image']);
            }

            $stmt = $pdo->prepare('DELETE FROM items WHERE id = ?');
            $stmt->execute([$itemId]);

            $_SESSION['msg_error'] = 'Item deleted.';
        } else {
            $_SESSION['msg_error'] = 'Unauthorized.';
        }
        header("Location: ../../collection_detail.php?id=$collectionId");
        exit;
    }

    // Fallback if item not found
    header('Location: ../../collections.php');
    exit;
}

header('Location: ../../collections.php');
exit;
