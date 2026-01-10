<?php
/**
 * Jikan Anime Search API Proxy
 *
 * Tento endpoint slouží jako proxy mezi MyVibe a Jikan API (MyAnimeList).
 *
 * Endpoint:
 *    GET /api/jikan_search.php?query=...
 *
 * Parametry:
 *    - query : název anime
 *
 * Návratová hodnota:
 *    JSON pole anime objektů obsahující:
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

// Dotaz
$q = isset($_GET['query']) ? trim($_GET['query']) : '';
if ($q === '') {
    echo json_encode(['error' => 'Missing query parameter']);
    exit;
}

// Jikan API endpoint (bez nutnosti API klíče)
$apiUrl = 'https://api.jikan.moe/v4/anime?sfw=true&limit=12&q=' . urlencode($q);

// Stažení dat
$resp = @file_get_contents($apiUrl);
if ($resp === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to connect to Jikan API']);
    exit;
}

// Dekódování JSON
$payload = json_decode($resp, true);

// Ověření struktury výstupu
if (!is_array($payload) || !isset($payload['data']) || !is_array($payload['data'])) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid response from Jikan API']);
    exit;
}

$out = [];

// Zpracování výsledků
foreach ($payload['data'] as $a) {

    // Obrázek – preferuj JPG, jinak WEBP
    $img = null;
    if (!empty($a['images']['jpg']['image_url'])) {
        $img = $a['images']['jpg']['image_url'];
    } elseif (!empty($a['images']['webp']['image_url'])) {
        $img = $a['images']['webp']['image_url'];
    }

    // Datum vydání (ISO timestamp → YYYY-MM-DD)
    $released = null;
    if (!empty($a['aired']['from'])) {
        $released = substr($a['aired']['from'], 0, 10);
    }

    $out[] = [
        'id' => $a['mal_id'] ?? null,
        'name' => $a['title'] ?? 'Unknown',
        'image' => $img,
        'released' => $released ?: 'Unknown',
        'rating' => isset($a['score']) ? (float) $a['score'] : 0.0, // 0–10 škála
        'overview' => $a['synopsis'] ?? '',
    ];
}

// Výstup
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
