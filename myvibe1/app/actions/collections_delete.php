<?php
/**
 * COLLECTIONS DELETE CONTROLLER
 *
 * Handles deletion of collections.
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
    $colId = intval($_POST['collection_id'] ?? 0);

    if (!$userId) {
        header('Location: ../../auth.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT c.user_id, c.cover, u.username FROM collections c JOIN users u ON c.user_id = u.id WHERE c.id = ?');
    $stmt->execute([$colId]);
    $c = $stmt->fetch();

    if ($c && ($c['user_id'] == $userId || $role === 'admin')) {
        // Delete cover image if not default
        if (!empty($c['cover']) && $c['cover'] !== getDefaultImage('collection') && file_exists($c['cover'])) {
            unlink($c['cover']);
        }

        $stmt = $pdo->prepare('DELETE FROM collections WHERE id = ?');
        $stmt->execute([$colId]);

        $_SESSION['msg_error'] = 'Collection deleted.';

        // Redirect back to the user's collection list
        header('Location: ../../collections.php?user=' . urlencode($c['username']));
        exit;
    } else {
        $_SESSION['msg_error'] = 'Unauthorized or not found.';
    }

    // Fallback redirect
    header('Location: ../../collections.php');
    exit;
}

header('Location: ../../collections.php');
exit;
