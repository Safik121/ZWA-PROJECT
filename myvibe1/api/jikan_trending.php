<?php
/**
 * getTrendingAnime()
 *
 * Funkce získává TOP anime z Jikan API (MyAnimeList)
 * a vrací náhodně vybraný výběr trendy anime.
 *
 * Návratová hodnota:
 *   Pole anime objektů obsahujících:
 *      - title
 *      - image
 *      - description
 *      - score
 *
 * @param int $limit      Kolik výsledků má funkce vrátit (výchozí 4)
 * @param int $fetchCount Kolik položek stáhnout z API (výchozí 16)
 * @return array          Pole anime
 *
 * @package MyVibe\Api
 * @author  Safik
 */

function getTrendingAnime($limit = 4, $fetchCount = 16)
{

    // Jikan TOP anime endpoint
    $url = "https://api.jikan.moe/v4/top/anime?limit={$fetchCount}";
    $data = @file_get_contents($url);

    if (!$data) {
        return []; // API nedostupné
    }

    $json = json_decode($data, true);

    // Ověření základní struktury
    if (!isset($json['data']) || !is_array($json['data'])) {
        return [];
    }

    $anime = [];

    foreach ($json['data'] as $a) {

        // Bez názvu nemá smysl položku vracet
        if (empty($a['title'])) {
            continue;
        }

        // Obrázek – preferujeme JPG
        $img = $a['images']['jpg']['image_url'] ?? null;
        if (!$img) {
            continue; // když není obrázek → přeskočit
        }

        $anime[] = [
            'title' => $a['title'],
            'image' => $img,
            'description' => $a['synopsis'] ?? 'No description available.',
            'score' => isset($a['score']) ? round($a['score'], 1) : "N/A"
        ];
    }

    // Náhodné pořadí
    shuffle($anime);

    // Vracíme skutečný požadovaný limit
    return array_slice($anime, 0, $limit);
}
