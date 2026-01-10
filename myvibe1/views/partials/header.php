<?php
/**
 * HEADER COMPONENT
 *
 * Hlavní navigační lišta pro celý projekt.
 *
 * Funkce:
 *  - zobrazuje logiku přihlášeného/nepřihlášeného uživatele
 *  - poskytuje odkazy na Home, Profile, Collections, Settings, Logout
 *  - obsahuje search bar pro vyhledávání uživatelů
 *  - načítá mini verzi profilu (avatar + username)
 *
 * Bezpečnost:
 *  - všechny dynamické hodnoty escapované přes htmlspecialchars → XSS prevence
 *  - žádné inline styly nebo inline JavaScript → splňuje CSP-ready strukturu
 *  - využívá pouze prepared statements → prevence SQL injection
 *  - validní HTML5 struktura
 *
 * Poznámky:
 *  - avatar má fallback na defaultní obrázek
 *  - zvýraznění aktivní stránky probíhá na základě basename aktuální URL
 *  - SESSION SE MUSÍ STARTOVAT V HLAVNÍM CONTROLLERU, NE ZDE!
 *
 * @package MyVibe\Views\Partials
 * @author  Safik
 */

// ======================================================
// 1. INCLUDE PATHS & DB
// ======================================================
require_once __DIR__ . '/../../app/core/paths.php';
require_once __DIR__ . '/../../app/core/db.php';

// ======================================================
// 2. ZJIŠTĚNÍ AKTUÁLNÍ STRÁNKY
// ======================================================
$currentPage = basename(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
    '.php'
);

// ======================================================
// 3. NAČTENÍ MINI PROFILU UŽIVATELE
// ======================================================
$miniUser = null;
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT username, avatar FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $miniUser = $stmt->fetch();
}

// ======================================================
// 4. AVATAR FALLBACK
// ======================================================
$avatar = $miniUser['avatar'] ?? null;
if (!$avatar) {
    $avatar = 'default/avatar_default.png';
}

// ======================================================
// 5. BASE URL PRO NAVIGACI
// ======================================================
$baseUrl = getBaseUrl();

?>

<!-- ====================================================== -->
<!-- 6. HEADER NAVIGACE -->
<!-- ====================================================== -->
<header class="topbar">
    <nav class="nav-container">

        <!-- 6a. LEVÁ STRANA NAVIGACE -->
        <div class="nav-left">
            <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/index.php"
                class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">
                Home
            </a>

            <?php if ($miniUser): ?>
                <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/profile.php"
                    class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>">
                    Profile
                </a>

                <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/collections.php"
                    class="nav-link <?= $currentPage === 'collections' ? 'active' : '' ?>">
                    Collections
                </a>
            <?php else: ?>
                <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/auth.php" class="nav-link">Profile</a>
                <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/auth.php" class="nav-link">Collections</a>
            <?php endif; ?>
        </div>

        <!-- 6b. STŘEDNÍ SEARCH BAR -->
        <div class="nav-center">
            <form action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/search_users.php" method="get"
                class="search-form">
                <label for="searchUsers" class="visually-hidden">Search users</label>
                <input type="text" id="searchUsers" name="q" placeholder="Search other users..." required
                    autocomplete="off">
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- 6c. PRAVÁ STRANA NAVIGACE -->
        <div class="nav-right">
            <?php if ($miniUser): ?>
                <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/profile.php" class="user-mini">
                    <img src="<?= htmlspecialchars(strpos($avatar, 'http') === 0 ? $avatar : $baseUrl . "/" . $avatar, ENT_QUOTES, 'UTF-8') ?>"
                        alt="User avatar">
                    <span><?= htmlspecialchars($miniUser['username'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>

                <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/settings.php?back=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                    class="settings-link <?= $currentPage === 'settings' ? 'active' : '' ?>">
                    Settings
                </a>

                <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/app/actions/logout.php"
                    class="logout-btn">Log Out</a>

            <?php else: ?>
                <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/auth.php" class="nav-link">Log In</a>
            <?php endif; ?>
        </div>

    </nav>
</header>
<script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/js/search_autocomplete.js"></script>