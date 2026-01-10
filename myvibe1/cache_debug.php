<?php
/**
 * CACHE DEBUG HELPER
 *
 * Pomocný skript pro zobrazení stavu cache a její vyčištění.
 * DŮLEŽITÉ: Smaž tento soubor po dokončení testování!
 *
 * @package MyVibe
 * @author  Safik
 */

session_start();

$cacheFile = __DIR__ . '/cache/trending_full.json';
$cacheDir = __DIR__ . '/cache';

echo "<!DOCTYPE html>
<html lang='cs'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Cache Debug - MyVibe</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
        .section { background: #2a2a2a; padding: 15px; margin: 15px 0; border-radius: 8px; }
        .status { display: inline-block; padding: 5px 10px; border-radius: 4px; margin: 5px 0; }
        .status.ok { background: #2d5; color: #000; }
        .status.warning { background: #fa0; color: #000; }
        .status.error { background: #f44; color: #fff; }
        .btn { display: inline-block; padding: 10px 20px; background: #4a9eff; color: #fff; 
               text-decoration: none; border-radius: 5px; margin: 10px 5px 0 0; cursor: pointer; border: none; }
        .btn:hover { background: #3a8eef; }
        pre { background: #1a1a1a; padding: 10px; border-radius: 4px; overflow-x: auto; }
        h1 { color: #4a9eff; }
        h2 { color: #6ac; margin-top: 0; }
    </style>
</head>
<body>
    <h1>🔍 MyVibe Cache Debugger</h1>
";

// ===== FILE CACHE STATUS =====
echo "<div class='section'>";
echo "<h2>📁 File Cache Status</h2>";

if (file_exists($cacheFile)) {
    $fileSize = filesize($cacheFile);
    $fileTime = filemtime($cacheFile);
    $fileAge = time() - $fileTime;
    $fileAgeHours = round($fileAge / 3600, 1);

    echo "<span class='status ok'>✓ Soubor existuje</span><br>";
    echo "<strong>Cesta:</strong> $cacheFile<br>";
    echo "<strong>Velikost:</strong> " . number_format($fileSize) . " bytes<br>";
    echo "<strong>Vytvořeno:</strong> " . date('Y-m-d H:i:s', $fileTime) . "<br>";
    echo "<strong>Stáří:</strong> $fileAgeHours hodin<br>";

    if ($fileAge < 21600) {
        echo "<span class='status ok'>Cache je validní (< 6 hodin)</span>";
    } else {
        echo "<span class='status warning'>Cache je stará (> 6 hodin)</span>";
    }

    // Zobraz obsah
    $content = file_get_contents($cacheFile);
    $data = json_decode($content, true);
    echo "<br><br><strong>Obsah cache:</strong>";
    echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "...</pre>";

} else {
    echo "<span class='status error'>✗ Soubor neexistuje</span><br>";
    echo "<strong>Cesta:</strong> $cacheFile<br>";

    // Check directory permissions
    if (!is_dir($cacheDir)) {
        echo "<span class='status error'>Cache adresář neexistuje!</span><br>";
    } else {
        $perms = substr(sprintf('%o', fileperms($cacheDir)), -4);
        echo "<strong>Cache dir permissions:</strong> $perms<br>";

        if (is_writable($cacheDir)) {
            echo "<span class='status ok'>Adresář je zapisovatelný</span>";
        } else {
            echo "<span class='status error'>Adresář NENÍ zapisovatelný!</span><br>";
            echo "<strong>Řešení:</strong> Spusťte: <code>chmod 755 $cacheDir</code>";
        }
    }
}

echo "</div>";

// ===== SESSION CACHE STATUS =====
echo "<div class='section'>";
echo "<h2>💾 Session Cache Status</h2>";

if (isset($_SESSION['trending_cache'])) {
    $sessionCache = $_SESSION['trending_cache'];
    $timestamp = $sessionCache['timestamp'] ?? 0;
    $age = time() - $timestamp;
    $ageHours = round($age / 3600, 1);

    echo "<span class='status ok'>✓ Session cache existuje</span><br>";
    echo "<strong>Vytvořeno:</strong> " . date('Y-m-d H:i:s', $timestamp) . "<br>";
    echo "<strong>Stáří:</strong> $ageHours hodin<br>";

    if ($age < 21600) {
        echo "<span class='status ok'>Session cache je validní</span>";
    } else {
        echo "<span class='status warning'>Session cache je stará</span>";
    }

    $dataCount = count($sessionCache['data'] ?? []);
    echo "<br><strong>Počet klíčů:</strong> $dataCount";

} else {
    echo "<span class='status warning'>Session cache neexistuje</span><br>";
    echo "Cache bude vytvořena při prvním načtení stránky.";
}

echo "</div>";

// ===== ACTIONS =====
echo "<div class='section'>";
echo "<h2>⚙️ Akce</h2>";

if (isset($_GET['clear_file_cache'])) {
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
        echo "<span class='status ok'>✓ File cache smazána!</span><br>";
    }
}

if (isset($_GET['clear_session_cache'])) {
    unset($_SESSION['trending_cache']);
    echo "<span class='status ok'>✓ Session cache smazána!</span><br>";
}

echo "<a href='?cache_debug=1&clear_file_cache=1' class='btn'>Smazat File Cache</a>";
echo "<a href='?cache_debug=1&clear_session_cache=1' class='btn'>Smazat Session Cache</a>";
echo "<a href='?cache_debug=1&clear_file_cache=1&clear_session_cache=1' class='btn'>Smazat Obě</a>";
echo "<br><a href='index.php' class='btn' style='background: #666; margin-top: 20px;'>← Zpět na hlavní stránku</a>";

echo "</div>";

// ===== RECOMMENDATION =====
echo "<div class='section'>";
echo "<h2>💡 Doporučení</h2>";

if (!file_exists($cacheFile) && !isset($_SESSION['trending_cache'])) {
    echo "<span class='status error'>Žádná cache není aktivní!</span><br>";
    echo "Aplikace bude volat API při každém načtení stránky.<br><br>";
    echo "<strong>Řešení:</strong><br>";
    echo "1. Nastavte práva na cache složku: <code>chmod 755 " . htmlspecialchars($cacheDir) . "</code><br>";
    echo "2. Nebo použijte session cache (funguje automaticky)";
} elseif (file_exists($cacheFile)) {
    echo "<span class='status ok'>✓ File cache funguje správně</span><br>";
    echo "Toto je ideální stav - rychlé načítání bez opakovaných API volání.";
} elseif (isset($_SESSION['trending_cache'])) {
    echo "<span class='status warning'>⚠ Používá se pouze session cache</span><br>";
    echo "Funguje, ale data se mažou při zavření prohlížeče/vypršení session.<br>";
    echo "<strong>Doporučeno:</strong> Opravte práva na cache složku pro trvalou cache.";
}

echo "</div>";

echo "</body></html>";
