<?php
/**
 * getTrendingMusic()
 *
 * Stahuje žebříček top písní z iTunes RSS feedu
 * a vrací náhodně vybraný set trending skladeb.
 *
 * Výstupní pole obsahuje:
 *   - title       : název skladby
 *   - image       : obal (největší dostupný)
 *   - description : umělec
 *   - score       : iTunes nemá rating → "N/A"
 *
 * @param int $limit      Kolik výsledků chceme vrátit (výchozí 4)
 * @param int $fetchCount Kolik položek stáhneme z API (výchozí 16)
 * @return array          Pole skladeb
 *
 * @package MyVibe\Api
 * @author  Safik
 */

function getTrendingMusic($limit = 4, $fetchCount = 16)
{
    $endpoint = "https://itunes.apple.com/us/rss/topsongs/limit=$fetchCount/json";

    // Stažení dat z API
    $json = @file_get_contents($endpoint);
    if (!$json) {
        return []; // API nedostupné
    }

    $data = json_decode($json, true);

    // Ověření struktury
    if (!isset($data['feed']['entry']) || !is_array($data['feed']['entry'])) {
        return [];
    }

    $songs = [];

    foreach ($data['feed']['entry'] as $entry) {

        // Získání obrázku: vezmeme poslední (největší)
        $images = $entry['im:image'] ?? [];
        $lastImage = end($images);
        $imageUrl = $lastImage['label'] ?? 'default/item_default.png';

        // Získání preview URL (hledáme link s rel="enclosure" nebo type="audio/...")
        $previewUrl = null;
        $links = $entry['link'] ?? [];
        // Pokud je link jen jeden objekt, převedeme na pole
        if (isset($links['attributes'])) {
            $links = [$links];
        }

        foreach ($links as $link) {
            $attrs = $link['attributes'] ?? [];
            if (isset($attrs['rel']) && $attrs['rel'] === 'enclosure') {
                $previewUrl = $attrs['href'];
                break;
            }
            if (isset($attrs['type']) && strpos($attrs['type'], 'audio/') === 0) {
                $previewUrl = $attrs['href'];
                break;
            }
        }

        $songs[] = [
            'title' => $entry['im:name']['label'] ?? 'Unknown Song',
            'image' => $imageUrl,
            'description' => $entry['im:artist']['label'] ?? 'Unknown Artist',
            'score' => 'N/A',
            'preview_url' => $previewUrl
        ];
    }

    // Zamíchání pořadí
    shuffle($songs);

    // Vracíme skutečný požadovaný limit
    return array_slice($songs, 0, $limit);
}
