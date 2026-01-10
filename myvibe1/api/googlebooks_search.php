<?php
/**
 * Google Books API Search Endpoint
 *
 * Tento PHP skript slouží jako jednoduché API pro vyhledávání knih
 * pomocí veřejného Google Books API.
 *
 * Vstup:
 *   GET /googlebooks_search.php?query=...
 *
 * Výstup:
 *   JSON pole knih s názvem, autory, rokem vydání, hodnocením, obrázkem atd.
 *
 * @package MyVibe\Api
 * @author  Safik
 */

header('Content-Type: application/json; charset=utf-8');

// Povolená metoda pouze GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Získání a základní validace dotazu
$q = isset($_GET['query']) ? trim($_GET['query']) : '';

if ($q === '') {
    echo json_encode(['error' => 'Missing query parameter']);
    exit;
}

// Google API endpoint – pro základní vyhledávání není potřeba API key
$url = 'https://www.googleapis.com/books/v1/volumes?q=' . urlencode($q) . '&maxResults=10';

// Bezpečné stažení dat z API (potlačí warningy)
$resp = @file_get_contents($url);

if ($resp === false) {
    http_response_code(502); // Bad Gateway – chyba mezi servery
    echo json_encode(['error' => 'Failed to connect to Google Books']);
    exit;
}

$data = json_decode($resp, true);

// Pokud nejsou nalezeny žádné položky, vrátíme prázdné pole
if (!isset($data['items']) || !is_array($data['items'])) {
    echo json_encode([]);
    exit;
}

$results = [];

// Zpracování položek do čistšího formátu
foreach ($data['items'] as $b) {
    $info = $b['volumeInfo'] ?? [];

    $results[] = [
        'id' => $b['id'] ?? null,
        'name' => $info['title'] ?? 'Unknown',
        'author' => isset($info['authors']) ? implode(', ', $info['authors']) : 'Unknown author',
        'released' => $info['publishedDate'] ?? 'Unknown',
        'rating' => isset($info['averageRating']) ? (float) $info['averageRating'] : null,
        'image' => $info['imageLinks']['thumbnail'] ?? null,
        'description' => $info['description'] ?? ''
    ];
}

// Výstup JSON výsledků
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
