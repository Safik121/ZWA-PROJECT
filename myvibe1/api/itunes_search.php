<?php
/**
 * iTunes Music Search API Handler
 *
 * Tento endpoint vyhledává hudbu na Apple iTunes API podle dotazu ?query=
 * a vrací pole tracků s názvem, interpretem, albem, obrázkem a ukázkou.
 *
 * Výstup: JSON
 *
 * @package MyVibe\Api
 * @author  Safik
 */

header('Content-Type: application/json; charset=utf-8');

// Povolen pouze GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Získání dotazu
$q = isset($_GET['query']) ? trim($_GET['query']) : '';

if ($q === '') {
    echo json_encode([]); // prázdný dotaz → prázdný výstup
    exit;
}

// iTunes Search endpoint
$apiUrl = 'https://itunes.apple.com/search?media=music&entity=musicTrack&limit=12&term=' . urlencode($q);

// Stažení dat
$resp = @file_get_contents($apiUrl);
if ($resp === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to reach iTunes']);
    exit;
}

// Dekódování JSON
$json = json_decode($resp, true);

// Pokud API vrátilo nevalidní JSON
if (!is_array($json) || !isset($json['results'])) {
    echo json_encode([]);
    exit;
}

/**
 * Zvýší rozlišení artwork URL z defaultních 100x100 na větší.
 *
 * iTunes poskytuje obrázky ve formátu /100x100bb.jpg
 * Proto regulárním výrazem nahradíme rozměry za vyšší hodnotu.
 *
 * @param string $url  Původní URL obrázku
 * @param int    $size Požadovaná velikost (px)
 * @return string      Upravená URL
 */
function upsizeArtwork($url, $size = 600)
{
    // Pokročilý replace (100x100bb, 100x100bb-85 atd.)
    $out = preg_replace('/\/\d+x\d+(bb(-\d+)?)\./', "/{$size}x{$size}$1.", $url);

    if (!$out) {
        // Jednodušší fallback
        $out = str_replace('100x100bb', "{$size}x{$size}bb", $url);
    }

    return $out;
}

$results = [];

// Zpracování každého výsledku
foreach ($json['results'] as $it) {
    $art = $it['artworkUrl100'] ?? null;

    $results[] = [
        'id' => $it['trackId'] ?? $it['collectionId'] ?? null,
        'name' => $it['trackName'] ?? $it['collectionName'] ?? 'Unknown',
        'artist' => $it['artistName'] ?? 'Unknown Artist',
        'album' => $it['collectionName'] ?? null,
        'image' => $art ? upsizeArtwork($art, 600) : null,
        'preview' => $it['previewUrl'] ?? null,
        'released' => substr($it['releaseDate'] ?? '', 0, 10), // YYYY-MM-DD
        'rating' => null // iTunes nevrací rating → vždy null
    ];
}

// Výstup JSON
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
