<?php
/**
 * COLLECTION DETAIL VIEW
 *
 * Zobrazuje detail jedné kolekce, všechny položky, modaly pro editaci a přidávání.
 *
 * BEZPEČNOST:
 * - Vše escapované přes htmlspecialchars().
 * - Žádný inline JS.
 * - Podmínky pro editaci/přidávání založené na vlastnictví nebo roli admina.
 *
 * VSTUP (od controlleru):
 * $data = [
 *   'collection' => [
 *       'id' => int,
 *       'title' => string,
 *       'theme_type' => string,
 *       'custom_theme_name' => string|null
 *   ],
 *   'items' => array of items [
 *       'id' => int,
 *       'title' => string,
 *       'image' => string|null,
 *       'rating' => int,
 *       'comment' => string
 *   ],
 *   'isOwner' => bool,
 *   'success' => string|null,
 *   'error' => string|null
 * ]
 *
 * @package MyVibe\Views
 * @author  Safik
 */

// ======================================================
// 1. OVĚŘENÍ DAT
// ======================================================
if (!isset($data) || !is_array($data)) {
  echo '<div class="error error-collection-load">⚠️ Collection data not loaded properly.</div>';
  return;
}

$collection = $data['collection'] ?? null;

if (!$collection) {
  echo '<div class="error error-collection-load">⚠️ Collection not found.</div>';
  return;
}

// ======================================================
// 2. OPRÁVNĚNÍ UŽIVATELE
// ======================================================
$isOwner = !empty($data['isOwner']) || (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <!-- ====================================================== -->
  <!-- 3. HEAD -->
  <!-- ====================================================== -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>
    <?= htmlspecialchars($collection['title']) ?> - MyVibe
  </title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body data-theme-type="<?= htmlspecialchars($collection['theme_type']) ?>"
  data-collection-id="<?= (int) $collection['id'] ?>">

  <?php
  // ======================================================
// 4. HEADER INCLUDE
// ======================================================
  include __DIR__ . '/partials/header.php';
  if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    include __DIR__ . '/partials/admin_modal.php';
  }
  ?>

  <!-- ====================================================== -->
  <!-- 5. HEADER KOLEKCE -->
  <!-- ====================================================== -->
  <header>
    <h1>
      <?= htmlspecialchars($collection['title']) ?>
      (
      <?= htmlspecialchars(
        $collection['theme_type'] === 'custom'
        ? $collection['custom_theme_name']
        : ucfirst($collection['theme_type'])
      ) ?>)
    </h1>
  </header>

  <main>

    <!-- FLASH MESSAGES -->
    <?php if (!empty($data['success'])): ?>
      <div class="msg-success"><?= htmlspecialchars($data['success']) ?></div>
    <?php endif; ?>
    <?php if (!empty($data['error'])): ?>
      <div class="msg-error"><?= htmlspecialchars($data['error']) ?></div>
    <?php endif; ?>
    <?php if (!empty($data['info'])): ?>
      <div class="msg-info"><?= htmlspecialchars($data['info']) ?></div>
    <?php endif; ?>

    <?php if ($isOwner): ?>
      <div class="edit-mode-toggle">
        <button id="toggleEditMode" class="edit-mode-btn">🛠️ Edit Mode</button>
      </div>
    <?php endif; ?>

    <!-- ====================================================== -->
    <!-- 8. GRID ITEMŮ -->
    <!-- ====================================================== -->
    <?php if (empty($data['items'])): ?>
      <p class="empty-info empty-collection-msg">
        <?= $isOwner ? 'No items yet. Add your first one!' : 'This collection is empty.' ?>
      </p>
    <?php endif; ?>


    <section class="item-grid">

      <?php if ($isOwner): ?>
        <!-- 8a. Manuální přidání itemu -->
        <div class="item-card add-item" id="addItemCard">
          <div class="plus-icon">+</div>
          <p>Add New Item</p>
        </div>

        <!-- 8b. Přidání z API podle typu kolekce -->
        <?php
        $theme = $collection['theme_type'];
        $apiCards = [
          'games' => ['id' => 'addFromApiCard', 'icon' => '🔍', 'label' => 'Add from RAWG'],
          'movies' => ['id' => 'addFromApiCardMovies', 'icon' => '🎬', 'label' => 'Add from TMDb'],
          'music' => ['id' => 'addFromApiCardMusic', 'icon' => '🎵', 'label' => 'Add from iTunes'],
          'books' => ['id' => 'addFromApiCardBooks', 'icon' => '📘', 'label' => 'Add from Google Books'],
          'anime' => ['id' => 'addFromApiCardAnime', 'icon' => '🗾', 'label' => 'Add from MyAnimeList (Jikan)'],
          'recipes' => ['id' => 'addFromApiCardRecipes', 'icon' => '🍝', 'label' => 'Add from Spoonacular']
        ];
        if (isset($apiCards[$theme])):
          $apiCard = $apiCards[$theme];
          ?>
          <div class="item-card add-item" id="<?= $apiCard['id'] ?>">
            <div class="plus-icon">
              <?= $apiCard['icon'] ?>
            </div>
            <p>
              <?= $apiCard['label'] ?>
            </p>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <!-- 8c. Existující položky -->
      <?php if (!empty($data['items'])): ?>
        <?php foreach ($data['items'] as $item): ?>
          <div class="item-card" data-preview-url="<?= htmlspecialchars($item['preview_url'] ?? '') ?>">
            <img src="<?= htmlspecialchars($item['image'] ?: 'default/item_default.png') ?>"
              alt="<?= htmlspecialchars($item['title']) ?>">
            <h3>
              <?= htmlspecialchars($item['title']) ?>
            </h3>
            <p>⭐
              <?= htmlspecialchars($item['rating']) ?>/5
            </p>
            <p>
              <?= htmlspecialchars($item['comment']) ?>
            </p>

            <?php if ($isOwner): ?>
              <div class="item-actions">
                <button class="edit-button" data-id="<?= (int) $item['id'] ?>"
                  data-title="<?= htmlspecialchars($item['title']) ?>" data-rating="<?= htmlspecialchars($item['rating']) ?>"
                  data-comment="<?= htmlspecialchars($item['comment']) ?>">
                  ✏️ Edit
                </button>

                <form method="post" action="app/actions/items_delete.php" class="delete-form delete-item-form">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                  <button type="submit" class="delete-button">🗑️ Delete</button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <!-- ====================================================== -->
    <!-- 9. DETAIL MODAL PRO VŠECHNY -->
    <!-- ====================================================== -->
    <div id="detailModal" class="modal modal-detail">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitle"></h2>
        <img id="modalImage" alt="">
        <p id="modalDescription"></p>
      </div>
    </div>

    <!-- ====================================================== -->
    <!-- 10. MODALY PRO OWNERA/ADMINA -->
    <!-- ====================================================== -->
    <?php if ($isOwner): ?>

      <!-- 10a. Manuální přidání -->
      <div class="modal" id="manualAddModal">
        <div class="modal-content">
          <span class="close">&times;</span>
          <h2>Add New Item</h2>
          <form method="post" action="app/actions/items_create.php" enctype="multipart/form-data">
            <input type="hidden" name="collection_id" id="collectionId" value="<?= (int) $collection['id'] ?>">
            <label>Title:</label>
            <input type="text" name="title" required>
            <label>Description / Comment:</label>
            <textarea name="comment" rows="3" required></textarea>
            <label>Rating (1–5):</label>
            <select name="rating" required>
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <option value="<?= $i ?>">
                  <?= str_repeat('⭐', $i) ?>
                </option>
              <?php endfor; ?>
            </select>
            <label>Cover image (optional):</label>
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
            <button type="submit" class="button-create">Add Item</button>
          </form>
        </div>
      </div>

      <!-- 10b. Editace -->
      <div class="modal" id="editItemModal">
        <div class="modal-content">
          <span class="close-edit">&times;</span>
          <h2>Edit Item</h2>
          <form method="post" action="app/actions/items_edit.php" enctype="multipart/form-data">
            <input type="hidden" name="item_id" id="editItemId">
            <label>Title:</label>
            <input type="text" name="title" id="editTitle" required>
            <label>Description / Comment:</label>
            <textarea name="comment" id="editComment" rows="3" required></textarea>
            <label>Rating (1–5):</label>
            <select name="rating" id="editRating" required>
              <option value="1">⭐</option>
              <option value="2">⭐⭐</option>
              <option value="3">⭐⭐⭐</option>
              <option value="4">⭐⭐⭐⭐</option>
              <option value="5">⭐⭐⭐⭐⭐</option>
            </select>
            <label>Change image (optional):</label>
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
            <button type="submit" class="button-create">Save Changes</button>
          </form>
        </div>
      </div>

      <!-- 10c. API MODALY -->
      <?php
      $apiModals = [
        'games' => ['id' => 'apiAddModal', 'label' => 'RAWG', 'input' => 'apiQuery', 'form' => 'apiSearchForm', 'results' => 'apiResults', 'placeholder' => 'Enter game name...'],
        'movies' => ['id' => 'apiAddModalMovies', 'label' => 'TMDb', 'input' => 'movieQuery', 'form' => 'movieSearchForm', 'results' => 'movieResults', 'placeholder' => 'Enter movie title...'],
        'music' => ['id' => 'apiAddModalMusic', 'label' => 'iTunes', 'input' => 'musicQuery', 'form' => 'musicSearchForm', 'results' => 'musicResults', 'placeholder' => 'Enter song name...'],
        'books' => ['id' => 'apiAddModalBooks', 'label' => 'Google Books', 'input' => 'bookQuery', 'form' => 'bookSearchForm', 'results' => 'bookResults', 'placeholder' => 'Enter book title...'],
        'anime' => ['id' => 'apiAddModalAnime', 'label' => 'MyAnimeList', 'input' => 'animeQuery', 'form' => 'animeSearchForm', 'results' => 'animeResults', 'placeholder' => 'Enter anime title...'],
        'recipes' => ['id' => 'apiAddModalRecipes', 'label' => 'Spoonacular', 'input' => 'recipeQuery', 'form' => 'recipeSearchForm', 'results' => 'recipeResults', 'placeholder' => 'Enter recipe name...'],
      ];

      if (isset($apiModals[$theme])):
        $m = $apiModals[$theme];
        ?>
        <div class="modal" id="<?= $m['id'] ?>">
          <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Search from
              <?= $m['label'] ?> API
            </h2>
            <form id="<?= $m['form'] ?>">
              <input id="<?= $m['input'] ?>" type="text" placeholder="<?= $m['placeholder'] ?>" required>
              <button type="submit" class="button-create">Search</button>
            </form>
            <div id="<?= $m['results'] ?>" class="api-results"></div>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </main>

  <!-- ====================================================== -->
  <!-- 11. FOOTER -->
  <!-- ====================================================== -->
  <footer>
    <p>&copy; 2025 MyVibe. All rights reserved.</p>
  </footer>

  <!-- ====================================================== -->
  <!-- 12. JS -->
  <!-- ====================================================== -->
  <?php if ($isOwner): ?>
    <script src="assets/js/item_modal.js"></script>
    <script src="assets/js/api_add.js"></script>
    <script src="assets/js/item_edit.js"></script>
    <script src="assets/js/edit_mode_toggle.js"></script>
  <?php endif; ?>
  <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <script src="assets/js/admin_panel.js"></script>
    <script src="assets/js/admin_modal.js"></script>
  <?php endif; ?>
  <script src="assets/js/item_detail.js"></script>

</body>

</html>
<?php if (!empty($data['info'])): ?>
  <div class="msg-info"><?= htmlspecialchars($data['info']) ?></div>
<?php endif; ?>