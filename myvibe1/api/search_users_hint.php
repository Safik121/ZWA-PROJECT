<?php
/**
 * SEARCH USERS HINT API
 *
 * Vrací JSON seznam uživatelů odpovídajících vyhledávacímu dotazu.
 * Používá se pro našeptávač ve vyhledávací liště v hlavičce.
 *
 * @package MyVibe\Api
 * @author  Safik
 */

require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/paths.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');

if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $searchTerm = '%' . strtolower($q) . '%';

    $stmt = $pdo->prepare("
        SELECT id, username, avatar, display_name
        FROM users
        WHERE LOWER(username) LIKE ? OR LOWER(display_name) LIKE ?
        ORDER BY 
            CASE 
                WHEN LOWER(username) LIKE ? THEN 1 
                ELSE 2 
            END,
            username ASC
        LIMIT 5
    ");

    // Bind parameters: 1. username match, 2. display_name match, 3. exact start match priority
    $startMatch = strtolower($q) . '%';
    $stmt->execute([$searchTerm, $searchTerm, $startMatch]);

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add full avatar URL
    $baseUrl = getBaseUrl();
    // Fix: getBaseUrl returns current script dir, so if we are in /api, we need to go up
    $baseUrl = str_replace('/api', '', $baseUrl);

    foreach ($users as &$user) {
        if (empty($user['avatar'])) {
            $user['avatar'] = 'default/avatar_default.png';
        }
        // If avatar is not a full URL (external), prepend base URL
        if (strpos($user['avatar'], 'http') !== 0) {
            $user['avatar'] = $baseUrl . '/' . $user['avatar'];
        }
    }

    echo json_encode($users);

} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
