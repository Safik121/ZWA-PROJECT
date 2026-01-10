<?php
/**
 * TMDb Trending Movies Fetcher
 *
 * Tato funkce slouží k načtení nejpopulárnějších filmů (trending)
 * z The Movie Database (TMDb) API.
 *
 * Výstup:
 *    Pole filmů s informacemi:
 *      - title
 *      - image
 *      - description
 *      - score
 *
 * @param int $limit      Kolik výsledků chceme vrátit (výchozí 4)
 * @param int $fetchCount Kolik filmů stáhnout z API (výchozí 16)
 * @return array          Pole filmů
 *
 * @package MyVibe\Api
 * @author  Safik
 */

function getTrendingMovies($limit = 4, $fetchCount = 16)
{
    // Načtení TMDb klíče z configu
    $apiKey = null;
    $configPath = __DIR__ . '/config/config.php';

    if (file_exists($configPath)) {
        $config = include $configPath;
        $apiKey = $config['TMDB_API_KEY'] ?? null;
    }

    if (!$apiKey) {
        return [];
    }

    // Trending endpoint (týdenní trendy)
    $url =
        "https://api.themoviedb.org/3/trending/movie/week?"
        . "api_key={$apiKey}"
        . "&language=en-US&page=1";

    // Načtení dat z API
    $raw = @file_get_contents($url);
    if (!$raw) {
        return [];
    }

    $json = json_decode($raw, true);
    if (!isset($json['results']) || !is_array($json['results'])) {
        return [];
    }

    $movies = [];

    foreach ($json['results'] as $m) {
        // Film musí mít název a poster
        if (empty($m['title']) || empty($m['poster_path'])) {
            continue;
        }

        $movies[] = [
            'title' => $m['title'],
            'image' => "https://image.tmdb.org/t/p/w500" . $m['poster_path'],
            'description' => $m['overview'] ?? 'No description available.',
            'score' => isset($m['vote_average'])
                ? round($m['vote_average'], 1)
                : "N/A"
        ];
    }

    // Náhodné pořadí
    shuffle($movies);

    // Správné použití $limit (původně tam bylo chybně $fetchCount)
    return array_slice($movies, 0, $limit);
}
