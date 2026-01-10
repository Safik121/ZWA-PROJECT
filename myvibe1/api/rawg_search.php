<?php
/**
 * RAWG Game Search API Proxy
 *
 * Tento endpoint slouží jako proxy mezi MyVibe a RAWG Video Games Database API.
 *
 * Endpoint:
 *    GET /api/rawg_search.php?query=elden ring
 *
 * Parametry:
 *    - query : hledaný název hry
 *
 * Výstup:
 *    JSON pole her se základními informacemi (id, name, image, released, rating)
 *
 * @package MyVibe\Api
 * @author  Safik
 */

header('Content-Type: application/json; charset=utf-8');

// Povolit pouze GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Načtení query
$queryRaw = isset($_GET['query']) ? trim($_GET['query']) : '';
if ($queryRaw === '') {
    echo json_encode(['error' => 'Missing query parameter']);
    exit;
}

// Zabezpečené načtení RAWG API klíče z config souboru
$RAWG_API_KEY = null;
if (file_exists(__DIR__ . '/config/config.php')) {
    $config = include __DIR__ . '/config/config.php';
    $RAWG_API_KEY = $config['RAWG_API_KEY'] ?? null;
}

// Fallback (ale prázdný, ne veřejný klíč)
if (!$RAWG_API_KEY) {
    http_response_code(500);
    echo json_encode(['error' => 'RAWG API key missing']);
    exit;
}

$query = urlencode($queryRaw);
$apiUrl = "https://api.rawg.io/api/games?key={$RAWG_API_KEY}&search={$query}&page_size=6";

// Stažení dat
$response = @file_get_contents($apiUrl);
if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to connect to RAWG API']);
    exit;
}

// Dekódování
$data = json_decode($response, true);
if (!is_array($data)) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid response from RAWG API']);
    exit;
}

$results = [];

// Ověření struktury
if (!empty($data['results']) && is_array($data['results'])) {
    foreach ($data['results'] as $game) {

        // Obrázek – pokud RAWG nevrátí, použijeme default
        $image = $game['background_image'] ?? 'default/game_placeholder.png';

        // Rating – RAWG někdy vrací null nebo string
        $rating = isset($game['rating']) && is_numeric($game['rating'])
            ? (float) $game['rating']
            : 0.0;

        $results[] = [
            'id' => $game['id'] ?? null,
            'name' => $game['name'] ?? 'Unknown',
            'image' => $image,
            'released' => $game['released'] ?? 'Unknown',
            'rating' => $rating
        ];
    }
}

// Výstupní JSON
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
