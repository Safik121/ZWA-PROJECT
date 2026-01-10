<?php
/**
 * COLLECTIONS CONTROLLER (FINAL VERSION)
 *
 * Správa uživatelských kolekcí:
 *  - načtení konkrétního profilu (?user=)
 *  - zobrazení kolekcí uživatele
 *  - vytvoření kolekce (owner/admin)
 *  - smazání kolekce (owner/admin)
 *
 * Bezpečnost:
 *  - prepared statements proti SQL injection
 *  - validace vstupů (trim, intval)
 *  - kontrola oprávnění (owner/admin)
 *  - bezpečné nahrávání obrázků (MIME + max velikost)
 *  - mazání starých obrázků
 *  - žádný inline JavaScript
 *
 * @package MyVibe
 * @author  Safik
 */

session_start();
require __DIR__ . '/app/core/db.php';
require __DIR__ . '/app/core/paths.php';


// ======================================================
// 1) Session a přihlášený uživatel
// ======================================================
$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? 'guest';

$success = '';
$error = '';
$info = '';
$isOwner = false;


// ======================================================
// 2) Detekce toho, čí profil se zobrazuje
// ======================================================
if (isset($_GET['user'])) {

    $target = trim($_GET['user']);

    $stmt = $pdo->prepare('SELECT id, username FROM users WHERE username = ?');
    $stmt->execute([$target]);
    $u = $stmt->fetch();

    if (!$u)
        exit('User not found.');

    $viewUserId = $u['id'];
    $viewUsername = $u['username'];

    // Owner nebo admin
    $isOwner = ($viewUserId == $userId || $role === 'admin');
} else {
    // Zobrazení vlastních kolekcí
    if (!$userId) {
        header('Location: auth.php');
        exit;
    }

    $viewUserId = $userId;
    $viewUsername = $username;
    $isOwner = true;
}


// ======================================================
// 3) Načtení kolekcí pro view (s Paginací)
// ======================================================
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 6; // Počet položek na stránku
$offset = ($page - 1) * $perPage;

// Získání celkového počtu kolekcí
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM collections WHERE user_id = ?');
$countStmt->execute([$viewUserId]);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $perPage);

// Získání položek pro aktuální stránku
$stmt = $pdo->prepare('SELECT * FROM collections WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
// PDO LIMIT/OFFSET vyžaduje integer, ne string
$stmt->bindValue(1, $viewUserId, PDO::PARAM_INT);
$stmt->bindValue(2, $perPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$collections = $stmt->fetchAll();


// ======================================================
// 4) Flash messages
// ======================================================
$success = $_SESSION['msg_success'] ?? '';
$error = $_SESSION['msg_error'] ?? '';
$info = $_SESSION['msg_info'] ?? '';

unset($_SESSION['msg_success'], $_SESSION['msg_error'], $_SESSION['msg_info']);


// ======================================================
// 5) Data pro view
// ======================================================
$data = [
    'collections' => $collections,
    'success' => $success,
    'error' => $error,
    'info' => $info,
    'isOwner' => $isOwner,
    'username' => $viewUsername,
    'role' => $role,
    'pagination' => [
        'page' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems
    ]
];


// ======================================================
// 6) View
// ======================================================
include __DIR__ . '/views/collections_view.php';
?>