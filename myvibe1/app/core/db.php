<?php
/**
 * DATABASE CONNECTION (PDO)
 *
 * Bezpečné připojení k MySQL databázi s využitím PDO.
 *
 * Bezpečnost:
 * - žádné vypisování chyb a detailů databáze do výstupu
 * - chyby se chytají přes PDO::ERRMODE_EXCEPTION
 * - emulované prepared statements jsou vypnuté
 * - charset UTF8MB4 pro plnou Unicode podporu
 *
 * @package MyVibe\Core
 * @author  Safik
 */

// ======================================================
// 1. KONFIGURACE DATABASE
// ======================================================
$host = '127.0.0.1';
$db = 'myvibe_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// ======================================================
// 2. DSN & PDO OPTIONS
// ======================================================
$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false, // skutečné prepared statements
];

// ======================================================
// 3. PŘIPOJENÍ K DB
// ======================================================
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Nezobrazovat detaily chyby (bezpečnostní důvod)
    exit('Database connection failed.');
}
