<?php
/**
 * TMDb Movie Search API Proxy
 *
 * Tento endpoint slouží jako proxy mezi MyVibe a The Movie Database (TMDb) API.
 *
 * Endpoint:
 *    GET /api/tmdb_search.php?query=inception
 *
 * Parametry:
 *    - query : hledaný název filmu
 *
 * Výstup:
 *    JSON pole filmů s informacemi:
 *      - id
 *      - name
 *      - image
 *      - released
 *      - rating
 *      - overview
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

// Zabezpečené načtení TMDb API klíče z config souboru
$TMDB_API_KEY = null;
$configPath = __DIR__ . '/config/config.php';

if (file_exists($configPath)) {
    $config = include $configPath;
    $TMDB_API_KEY = $config['TMDB_API_KEY'] ?? null;
}

if (!$TMDB_API_KEY) {
    http_response_code(500);
    echo json_encode(['error' => 'TMDb API key missing']);
    exit;
}

// Sestavení API URL
$query = urlencode($queryRaw);
$apiUrl =
    "https://api.themoviedb.org/3/search/movie?"
    . "api_key={$TMDB_API_KEY}"
    . "&query={$query}"
    . "&include_adult=false"
    . "&language=en-US"
    . "&page=1";

// Stažení dat
$response = @file_get_contents($apiUrl);
if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to connect to TMDb API']);
    exit;
}

// Dekódování JSON
$data = json_decode($response, true);
if (!is_array($data)) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid response from TMDb API']);
    exit;
}

// Sestavení výsledků
$results = [];

if (!empty($data['results']) && is_array($data['results'])) {
    foreach ($data['results'] as $movie) {

        // Obrázek — TMDb používá relativní cesty
        $image = null;
        if (!empty($movie['poster_path'])) {
            $image = "https://image.tmdb.org/t/p/w500" . $movie['poster_path'];
        }

        $results[] = [
            'id' => $movie['id'] ?? null,
            'name' => $movie['title'] ?? 'Unknown',
            'image' => $image,
            'released' => $movie['release_date'] ?? 'Unknown',
            'rating' => isset($movie['vote_average']) ? (float) $movie['vote_average'] : 0.0,
            'overview' => $movie['overview'] ?? ''
        ];
    }
}

// Výstup JSON
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
