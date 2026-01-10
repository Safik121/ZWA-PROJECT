<?php
/**
 * getTrendingBooks()
 *
 * Vrací náhodný výběr "trending" knih z Google Books API.
 *
 * Funkce:
 *   1) vybere náhodný žánr
 *   2) stáhne knihy z Google Books API
 *   3) vyfiltruje knihy bez názvu nebo obrázku (aby se něco nerozbilo)
 *   4) náhodně vybere $limit knih
 *   5) vrátí je jako pole
 *
 * @param int $limit      Kolik knih chceme vrátit uživateli (výchozí 4)
 * @param int $fetchCount Kolik knih nejprve stáhnout z API (výchozí 16)
 * @return array          Pole knih
 *
 * @package MyVibe\Api
 * @author  Safik
 */

function getTrendingBooks($limit = 4, $fetchCount = 16)
{
    // Výběr náhodného žánru
    $genres = ["fiction", "fantasy", "romance", "thriller", "science fiction", "mystery", "young adult"];
    $chosenGenre = $genres[array_rand($genres)];

    // Sestavení API URL
    $query = urlencode("subject:$chosenGenre");
    $endpoint = "https://www.googleapis.com/books/v1/volumes?q=$query&orderBy=relevance&maxResults=$fetchCount";

    // Stažení dat
    $json = @file_get_contents($endpoint);
    if (!$json) {
        // API nedostupné → vrať prázdné pole
        return [];
    }

    $data = json_decode($json, true);

    // Ověření struktury
    if (empty($data['items']) || !is_array($data['items'])) {
        return [];
    }

    $books = [];

    // Zpracování výsledků
    foreach ($data['items'] as $book) {
        $info = $book['volumeInfo'] ?? [];

        // Bez názvu a obrázku nemá smysl vracet
        if (empty($info['title']) || empty($info['imageLinks']['thumbnail'])) {
            continue;
        }

        $books[] = [
            'title' => $info['title'],
            'image' => $info['imageLinks']['thumbnail'],
            'description' => $info['description'] ?? 'No description available.',
            'score' => isset($info['averageRating'])
                ? round((float) $info['averageRating'], 1)
                : "N/A"
        ];
    }

    // Náhodné pořadí
    shuffle($books);

    // VRÁCÍME SPRÁVNÝ POČET ($limit)
    return array_slice($books, 0, $limit);
}
