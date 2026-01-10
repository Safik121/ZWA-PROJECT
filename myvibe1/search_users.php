<?php
/**
 * SEARCH USERS CONTROLLER
 *
 * Funkce:
 *  - přijímá dotaz q přes GET
 *  - vyhledává uživatele pomocí LIKE (case-insensitive)
 *  - stránkování výsledků (10 na stránku)
 *  - zobrazuje výsledky ve view
 *
 * Bezpečnost:
 *  - prepared statements (SQL injection ochrana)
 *  - trim() pro sanitaci vstupu
 *
 * @package MyVibe
 * @author  Safik
 */

session_start();
require __DIR__ . '/app/core/db.php';

// ==============================
// 1) Načtení vstupu
// ==============================
$q = trim($_GET['q'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

if ($q === '') {
    header('Location: index.php');
    exit;
}

// LIKE hledání musí použít wildcardy
$searchTerm = '%' . strtolower($q) . '%';


// ==============================
// 2) Získání celkového počtu (pro stránkování)
// ==============================
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE LOWER(username) LIKE ?
");
$countStmt->execute([$searchTerm]);
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);


// ==============================
// 3) Vyhledání uživatelů (stránkované)
// ==============================
$stmt = $pdo->prepare("
    SELECT id, username, avatar, bio, display_name
    FROM users
    WHERE LOWER(username) LIKE ?
    ORDER BY username ASC
    LIMIT ? OFFSET ?
");

// PDO LIMIT/OFFSET vyžaduje integer, ne string
$stmt->bindValue(1, $searchTerm, PDO::PARAM_STR);
$stmt->bindValue(2, $perPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();

$users = $stmt->fetchAll();


// ==============================
// 4) Načtení view
// ==============================
include __DIR__ . '/views/search_users_view.php';
