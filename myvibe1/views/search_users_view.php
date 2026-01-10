<?php
/**
 * SEARCH USERS VIEW
 *
 * Zobrazuje výsledky vyhledávání uživatelů.
 *
 * @package MyVibe\Views
 * @author  Safik
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - MyVibe</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php
    $isAdmin = (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');
    if ($isAdmin): ?>
        <script src="assets/js/admin_panel.js" defer></script>
        <script src="assets/js/admin_modal.js" defer></script>
    <?php endif; ?>
</head>

<body>

    <?php include __DIR__ . '/partials/header.php'; ?>
    <?php if ($isAdmin)
        include __DIR__ . '/partials/admin_modal.php'; ?>

    <main class="main-content">
        <div class="container">
            <h1>Search Results for "<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>"</h1>

            <?php if (empty($users)): ?>
                <p>No users found matching your search.</p>
            <?php else: ?>
                <div class="user-grid">
                    <?php foreach ($users as $user): ?>
                        <?php
                        $avatar = $user['avatar'] ?? 'default/avatar_default.png';
                        $profileUrl = 'profile.php?user=' . urlencode($user['username']);
                        ?>
                        <div class="user-card">
                            <a href="<?= $profileUrl ?>" class="user-card-content">
                                <div class="user-avatar-container">
                                    <div class="user-avatar">
                                        <img src="<?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?>"
                                            alt="<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="user-status-indicator"></div>
                                </div>
                                <div class="user-info">
                                    <h3 class="user-display-name">
                                        <?= htmlspecialchars($user['display_name'] ?? $user['username'], ENT_QUOTES, 'UTF-8') ?>
                                    </h3>
                                    <span
                                        class="user-handle">@<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <p class="user-bio">
                                        <?= htmlspecialchars(mb_strimwidth($user['bio'] ?? '', 0, 50, '...'), ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                            </a>
                            <div class="user-card-actions">
                                <a href="<?= $profileUrl ?>" class="add-friend-btn">View Profile</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- PAGINATION -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?q=<?= urlencode($q) ?>&page=<?= $page - 1 ?>" class="page-link btn-pagination">&laquo;
                                Previous</a>
                        <?php endif; ?>

                        <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>

                        <?php if ($page < $totalPages): ?>
                            <a href="?q=<?= urlencode($q) ?>&page=<?= $page + 1 ?>" class="page-link btn-pagination">Next
                                &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </main>

</body>

</html>