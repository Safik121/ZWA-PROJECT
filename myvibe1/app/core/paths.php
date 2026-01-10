<?php
/**
 * PATH UTILITIES
 *
 * Obsahuje funkce pro správu adresářů uživatele a výchozích obrázků.
 *
 * Bezpečnost:
 *  - username je sanitizováno přes regex (povoleny pouze a–z, A–Z, 0–9, -, _)
 *  - žádné absolutní cesty od uživatele, vše pevně řízeno
 *  - žádné echo ani output → bezpečné pro includování
 *  - výchozí přípony i struktura jsou pevně dané
 *
 * @package MyVibe\Core
 * @author  Safik
 */

// ======================================================
// 1. FUNKCE: getBaseUrl
// ======================================================
/**
 * Zjistí základní URL cestu aplikace.
 * Funguje automaticky na localhost i produkčním serveru.
 *
 * @return string Základní URL cesta (např. "/myvibe1" nebo "/myvibe")
 */
function getBaseUrl()
{
    // Detekce z aktuálního skriptu
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

    // Získáme cestu ke kořenovému adresáři aplikace
    // Např. /myvibe1/index.php → /myvibe1
    // Nebo /myvibe/index.php → /myvibe
    $basePath = dirname($scriptName);

    // Pokud je na root úrovni, vrať prázdný řetězec
    if ($basePath === '/' || $basePath === '\\') {
        return '';
    }

    return $basePath;
}

// ======================================================
// 2. FUNKCE: getUserPath
// ======================================================
/**
 * Vytvoří adresářovou strukturu pro daného uživatele.
 *
 * @param string $username Uživatelské jméno (sanitizováno)
 * @param string $type     Typ složky: "avatar", "collections", "items"
 * @return string          Relativní cesta do složky (ukončená lomítkem)
 */
function getUserPath($username, $type)
{
    // 1a. Sanitizace jména (povoleny jen základní znaky)
    $safeUsername = preg_replace('/[^a-zA-Z0-9_-]/', '_', $username);

    // 1b. Základní cesta uživatele → uploads/<user>/
    $baseDir = __DIR__ . '/../../uploads/' . $safeUsername . '/';
    $subDir = $baseDir . $type . '/';

    // 1c. Vytvoření adresářů pokud neexistují
    if (!is_dir($baseDir)) {
        @mkdir($baseDir, 0777, true);
        @chmod($baseDir, 0777);
    }

    if (!is_dir($subDir)) {
        @mkdir($subDir, 0777, true);
        @chmod($subDir, 0777);
    }

    // 1d. Vracíme RELATIVNÍ cestu pro web (bez __DIR__)
    return 'uploads/' . $safeUsername . '/' . $type . '/';
}

// ======================================================
// 3. FUNKCE: getDefaultImage
// ======================================================
/**
 * Vrací výchozí obrázek pro daný typ.
 *
 * @param string $type "avatar", "collection", "item"
 * @return string      Relativní cesta k souboru
 */
function getDefaultImage($type)
{
    // 2a. Výchozí obrázky
    $defaults = [
        'avatar' => 'default/avatar_default.png',
        'collection' => 'default/collection_default.png',
        'item' => 'default/item_default.png'
    ];

    // 2b. Vracíme výchozí, pokud typ není znám
    return $defaults[$type] ?? 'default/collection_default.png';
}

// ======================================================
// 4. FUNKCE: deleteUserDirectory
// ======================================================
/**
 * Rekurzivně smaže složku uživatele v uploads.
 *
 * @param string $username Uživatelské jméno
 * @return bool            True pokud se podařilo smazat (nebo neexistovala)
 */
function deleteUserDirectory($username)
{
    $safeUsername = preg_replace('/[^a-zA-Z0-9_-]/', '_', $username);
    $dir = __DIR__ . '/../../uploads/' . $safeUsername;

    if (!is_dir($dir)) {
        return true; // Složka neexistuje, považujeme za smazané
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        @$todo($fileinfo->getRealPath());
    }

    return @rmdir($dir);
}
