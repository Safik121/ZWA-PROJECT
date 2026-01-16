<?php
/**
 * INDEX VIEW (HOME PAGE)
 *
 * Zobrazuje doporučené sekce (trending / doporučené).
 *
 * BEZPEČNOST & STANDARDY:
 * - Žádný inline JavaScript.
 * - Admin skripty pouze pokud role === 'admin'.
 * - Escapování všech dynamických hodnot (htmlspecialchars).
 * - Include cesty přes __DIR__ . '/../includes/' (protože jsme ve /views/).
 * - Modal pro detail ovládán přes home_modals.js.
 * - Data-* atributy escapované pro ochranu proti XSS.
 *
 * VSTUP (od controlleru):
 * $data = [
 *   'sections' => [
 *       [
 *           'title' => string,
 *           'items' => [
 *               [
 *                   'title' => string,
 *                   'image' => string|null,
 *                   'description' => string|null,
 *                   'score' => float|null
 *               ],
 *               ...
 *           ]
 *       ],
 *       ...
 *   ]
 * ]
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
    <title>Welcome to MyVibe</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <script src="assets/js/admin_panel.js" defer></script>
        <script src="assets/js/admin_modal.js" defer></script>
    <?php endif; ?>
</head>

<body>

    <?php include __DIR__ . '/partials/header.php'; ?>
    <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <?php include __DIR__ . '/partials/admin_modal.php'; ?>
    <?php endif; ?>

    <main class="recommendations">

        <!-- ====================================================== -->
        <!-- 1) Sekce doporučení -->
        <!-- ====================================================== -->
        <?php if (!empty($data['sections']) && is_array($data['sections'])): ?>

            <?php foreach ($data['sections'] as $section): ?>
                <div class="trending-section">

                    <h2><?= htmlspecialchars($section['title'] ?? 'Untitled Section') ?></h2>

                    <div class="trending-grid">

                        <?php if (!empty($section['items']) && is_array($section['items'])): ?>

                            <?php foreach ($section['items'] as $item): ?>
                                <?php
                                // ======================================================
                                // 2) Bezpečné fallbacky pro jednotlivé položky
                                // ======================================================
                                $itemTitle = htmlspecialchars($item['title'] ?? 'Unknown');
                                $itemImage = htmlspecialchars($item['image'] ?? 'default/item_default.png');
                                $itemDesc = htmlspecialchars($item['description'] ?? 'No description available.');
                                $score = isset($item['score']) ? round((float) $item['score'] / 2, 2) : '0';
                                ?>

                                <div class="trending-card" data-title="<?= $itemTitle ?>" data-image="<?= $itemImage ?>"
                                    data-description="<?= $itemDesc ?>"
                                    data-preview-url="<?= htmlspecialchars($item['preview_url'] ?? '') ?>">

                                    <img src="<?= $itemImage ?>" alt="<?= $itemTitle ?>">
                                    <h3><?= $itemTitle ?></h3>
                                    <p>⭐ <?= $score ?></p>

                                </div>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <p class="empty-info">No items available in this section.</p>
                        <?php endif; ?>

                    </div>


                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <p class="empty-info empty-info-centered">
                No recommendations available right now.
            </p>
        <?php endif; ?>

    </main>

    <!-- ====================================================== -->
    <!-- 3) DETAIL MODAL -->
    <!-- ====================================================== -->
    <div id="detailModal" class="modal modal-detail">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">&nbsp;</h2>
            <img id="modalImage" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" alt="">
            <p id="modalDescription"></p>
        </div>
    </div>

    <!-- ====================================================== -->
    <!-- 4) Externí JS -->
    <!-- ====================================================== -->
    <script src="assets/js/home_modals.js"></script>

</body>

</html>
