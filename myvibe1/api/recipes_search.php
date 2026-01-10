<?php
/**
 * Spoonacular Recipes Search API Proxy
 *
 * Tento endpoint slouží jako proxy mezi MyVibe a Spoonacular Recipe API.
 *
 * Endpoint:
 *    GET /api/recipes_search.php?query=pasta
 *
 * Parametry:
 *    - query : hledaný název receptu
 *
 * Výstup:
 *    JSON pole receptů s informacemi:
 *      - id
 *      - name
 *      - image
 *      - released (čas přípravy)
 *      - rating
 *      - overview (vyčištěné summary bez HTML tagů a odkazů)
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

// Načtení query parametru
$queryRaw = isset($_GET['query']) ? trim($_GET['query']) : '';
if ($queryRaw === '') {
    echo json_encode(['error' => 'Missing query parameter']);
    exit;
}

// Zabezpečené načtení Spoonacular API klíče z config souboru
$API_KEY = null;
$configPath = __DIR__ . '/config/config.php';

if (file_exists($configPath)) {
    $config = include $configPath;
    $API_KEY = $config['SPOONACULAR_API_KEY'] ?? null;
}

if (!$API_KEY) {
    http_response_code(500);
    echo json_encode(['error' => 'Spoonacular API key missing']);
    exit;
}

// Sestavení API URL
$query = urlencode($queryRaw);
$apiUrl =
    "https://api.spoonacular.com/recipes/complexSearch?"
    . "query={$query}"
    . "&number=10"
    . "&addRecipeInformation=true"
    . "&apiKey={$API_KEY}";

// Stažení dat
$response = @file_get_contents($apiUrl);
if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to connect to Spoonacular API']);
    exit;
}

// Dekódování JSON
$data = json_decode($response, true);
if (!isset($data['results']) || !is_array($data['results'])) {
    echo json_encode([]);
    exit;
}

/**
 * Vyčistí HTML summary z Spoonacular API.
 * Odstraní HTML tagy, URL a sjednotí mezery.
 *
 * @param string $summary Původní HTML text
 * @return string         Vyčištěný text
 */
function cleanSummary($summary)
{
    if (!$summary) {
        return '';
    }

    // Odstranění HTML tagů
    $text = strip_tags($summary);

    // Odstranění URL
    $text = preg_replace('/https?:\/\/\S+/', '', $text);

    // Komprimace whitespace
    $text = trim(preg_replace('/\s+/', ' ', $text));

    return $text;
}

// Sestavení výsledků
$results = [];

foreach ($data['results'] as $r) {

    $summaryClean = cleanSummary($r['summary'] ?? '');

    $results[] = [
        'id' => $r['id'],
        'name' => $r['title'] ?? 'Unknown Recipe',
        'image' => $r['image'] ?? null,
        'released' => !empty($r['readyInMinutes'])
            ? $r['readyInMinutes'] . " min"
            : "N/A",
        'rating' => $r['spoonacularScore'] ?? 0,
        'overview' => $summaryClean
    ];
}

// Výstup JSON
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
