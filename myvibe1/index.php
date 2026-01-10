<?php
/**
 * INDEX CONTROLLER
 *
 * Domovská stránka MyVibe – zobrazuje doporučení z API.
 *
 * Funkce:
 *  - využívá 4 externí API (Jikan, TMDB, iTunes, GoogleBooks)
 *  - výsledky se ukládají do cache (JSON + session fallback)
 *  - zobrazuje náhodné 2 trendy sekce po 4 položkách
 *
 * Bezpečnost:
 *  - žádné inline skripty (script se načítá až ve view)
 *  - API výsledky nejdou přímo do HTML → escapují se ve view
 *  - kontrola existence cache + obnovy dat
 *  - žádné přímé uživatelské vstupy → nulové riziko SQL/XSS
 *
 * Performance:
 *  - cache se obnovuje po 6 hodinách
 *  - každý API call dostane max 16 položek → view zobrazuje 4
 *  - fallback na session cache pokud file cache nelze vytvořit
 *
 * @package MyVibe
 * @author  Safik
 */

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/api/jikan_trending.php';
require_once __DIR__ . '/api/tmdb_trending.php';
require_once __DIR__ . '/api/itunes_trending.php';
require_once __DIR__ . '/api/googlebooks_trending.php';

$cacheFile = __DIR__ . '/cache/trending_full.json';
$cacheTTL = 6 * 60 * 60; // 6 hodin


// ======================================
// 1) Načtení cache nebo refresh API
// ======================================
$cached = null;
$cacheSource = 'none';

// Pokus #1: Načtení z file cache
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTTL)) {
    $cached = json_decode(file_get_contents($cacheFile), true);
    $cacheSource = 'file';
}

// Pokus #2: Načtení ze session cache (fallback pro servery bez write permissions)
if (!$cached && isset($_SESSION['trending_cache'])) {
    $sessionCache = $_SESSION['trending_cache'];
    if (isset($sessionCache['timestamp']) && (time() - $sessionCache['timestamp'] < $cacheTTL)) {
        $cached = $sessionCache['data'];
        $cacheSource = 'session';
    }
}

// Pokus #3: Načtení z API (pokud není žádná validní cache)
if (!$cached) {
    $cacheSource = 'api';

    // Vytvoříme cache složku, pokud chybí
    $cacheDir = __DIR__ . '/cache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0777, true);
        @chmod($cacheDir, 0777);
    }

    // Načteme data z externích API
    $cached = [
        'anime' => getTrendingAnime(16),
        'movies' => getTrendingMovies(16),
        'books' => getTrendingBooks(16),
        'music' => getTrendingMusic(16),
        'generated_at' => date('c'),
        'cache_source' => $cacheSource
    ];

    // Pokusíme se zapsat do file cache
    $jsonData = json_encode($cached, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $writeResult = @file_put_contents($cacheFile, $jsonData);

    // Pokud zápis selhal, pokusíme se nastavit správná oprávnění
    if ($writeResult === false && !file_exists($cacheFile)) {
        @touch($cacheFile);
        @chmod($cacheFile, 0644);
        $writeResult = @file_put_contents($cacheFile, $jsonData);
    }

    // Vždy uložíme do session cache jako fallback
    $_SESSION['trending_cache'] = [
        'data' => $cached,
        'timestamp' => time()
    ];
}


// ======================================
// 2) Výběr náhodných 2 kategorií na homepage
// ======================================
$available = ['anime', 'movies', 'books', 'music'];
shuffle($available);
$selected = array_slice($available, 0, 2);


// ======================================
// 3) Příprava dat pro view
// ======================================
$data = ['sections' => []];

$titles = [
    'anime' => 'Trending Anime',
    'movies' => 'Top Movies This Week',
    'books' => 'Popular Books',
    'music' => 'Trending Music'
];

foreach ($selected as $type) {

    if (empty($cached[$type])) {
        continue;
    }

    $items = $cached[$type];
    shuffle($items);
    $chosen = array_slice($items, 0, 4);

    $data['sections'][] = [
        'title' => $titles[$type],
        'items' => $chosen
    ];
}


// ======================================
// 4) Načtení view
// ======================================
include __DIR__ . '/views/index_view.php';
