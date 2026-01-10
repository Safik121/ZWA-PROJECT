<?php
/**
 * COLLECTION DETAIL CONTROLLER
 *
 * Zpracování detailu jedné kolekce:
 *  - načtení konkrétní kolekce z databáze
 *  - přidání nové položky (ručně i z externího API)
 *  - mazání existujících položek
 *  - načtení všech položek pro předání do view
 *
 * Bezpečnost:
 *  - SQL injection: všechny dotazy používají prepared statements (PDO::prepare + ->execute).
 *  - Oprávnění: přidávat/mazat položky může pouze vlastník kolekce nebo admin.
 *  - Upload souborů: kontrola MIME typu, maximální velikosti a povolených formátů (JPG, PNG, WEBP).
 *  - Cesty k souborům: generují se na straně serveru, uživatel neposílá vlastní cestu.
 *  - XSS: tento controller nic přímo nevypisuje; escapování probíhá ve view (htmlspecialchars).
 *  - Žádný inline JavaScript: controller pouze připravuje data, view načítá externí .js soubory.
 *
 * @package MyVibe
 * @author  Safik
 */

session_start();
require __DIR__ . '/app/core/db.php';
require __DIR__ . '/app/core/paths.php';


// =====================================================
// 1) Validace vstupu – ID kolekce
// =====================================================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    exit('Invalid collection ID.');
}
$collectionId = (int) $_GET['id'];


// =====================================================
// 2) Načtení kolekce z databáze
// =====================================================
$stmt = $pdo->prepare(
    'SELECT c.*, u.username
     FROM collections c
     JOIN users u ON c.user_id = u.id
     WHERE c.id = ?'
);
$stmt->execute([$collectionId]);
$collection = $stmt->fetch();

if (!$collection) {
    exit('Collection not found.');
}

$username = $collection['username'];


// =====================================================
// 3) Oprávnění – vlastník / admin
// =====================================================
$isOwner =
    !empty($_SESSION['user_id']) &&
    (
        $_SESSION['user_id'] == $collection['user_id']
        || (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin')
    );

$error = '';
$success = '';
$info = '';

// =====================================================
// 6) Načtení všech položek kolekce
// =====================================================
$stmt = $pdo->prepare(
    'SELECT *
     FROM items
     WHERE collection_id = ?
     ORDER BY created_at DESC'
);
$stmt->execute([$collectionId]);
$items = $stmt->fetchAll();


// =====================================================
// 7) Flash messages (success / error)
// =====================================================
if (!empty($_SESSION['msg_success'])) {
    $success = $_SESSION['msg_success'];
    unset($_SESSION['msg_success']);
}
if (!empty($_SESSION['msg_error'])) {
    $error = $_SESSION['msg_error'];
    unset($_SESSION['msg_error']);
}
if (!empty($_SESSION['msg_info'])) {
    $info = $_SESSION['msg_info'];
    unset($_SESSION['msg_info']);
}


// =====================================================
// 8) Data pro view
// =====================================================
$data = [
    'collection' => $collection,
    'items' => $items,
    'success' => $success,
    'error' => $error,
    'info' => $info,
    'isOwner' => $isOwner,
];


// =====================================================
// 9) Načtení view šablony
// =====================================================
include __DIR__ . '/views/collection_detail_view.php';
