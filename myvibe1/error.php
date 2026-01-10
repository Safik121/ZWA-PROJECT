<?php
/**
 * ERROR PAGE CONTROLLER
 *
 * Zobrazuje chybové stránky (403, 404, 500) s příslušnými zprávami a obrázky.
 *
 * Funkce:
 *  - detekce base URL pro správné načtení stylů a obrázků
 *  - zobrazení specifické chybové hlášky podle HTTP kódu
 *
 * @package MyVibe
 * @author  Safik
 */
// Special base URL detection for error pages
// When called via ErrorDocument, we need to detect the base path differently
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Try to detect base URL from REQUEST_URI
if (preg_match('#^(/~[^/]+/[^/]+)/#', $requestUri, $matches)) {
    // Server user directory: /~username/app/
    $baseUrl = $matches[1];
} elseif (preg_match('#^(/[^/]+)/#', $requestUri, $matches)) {
    // Standard directory: /app/
    $baseUrl = $matches[1];
} else {
    // Fallback: try from SCRIPT_NAME
    $baseUrl = dirname($scriptName);
    if ($baseUrl === '/' || $baseUrl === '\\') {
        $baseUrl = '';
    }
}

$code = isset($_GET['code']) ? (int) $_GET['code'] : 404;

// Define error messages and images
$errors = [
    403 => [
        'title' => 'Forbidden',
        'message' => 'You do not have permission to access this resource.',
        'image' => $baseUrl . '/assets/img/errors/icon403nobg.png'
    ],
    404 => [
        'title' => 'Page Not Found',
        'message' => 'The page you are looking for does not exist.',
        'image' => $baseUrl . '/assets/img/errors/icon404nobg.png'
    ],
    500 => [
        'title' => 'Internal Server Error',
        'message' => 'Something went wrong on our end.',
        'image' => $baseUrl . '/assets/img/errors/icon500nobg.png'
    ]
];

// Get current error details or default to 404
$error = $errors[$code] ?? $errors[404];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($error['title']) ?> - MyVibe</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/css/style.css">
</head>

<body class="error-body">

    <div class="error-card">
        <div class="error-content">
            <h1><?= htmlspecialchars($error['title']) ?></h1>
            <div class="error-code-display"><?= $code ?> | Error</div>
            <p><?= htmlspecialchars($error['message']) ?></p>

            <div class="btn-group">
                <a href="javascript:history.back()" class="btn btn-primary">Back</a>
                <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/" class="btn btn-secondary">Home</a>
            </div>
        </div>
        <div class="error-image-container">
            <img src="<?= htmlspecialchars($error['image']) ?>" alt="Error Illustration" class="error-image">
        </div>
    </div>

</body>

</html>