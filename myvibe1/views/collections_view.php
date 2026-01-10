<?php
/**
 * COLLECTIONS VIEW
 *
 * Zobrazuje všechny kolekce uživatele (vlastní i cizí).
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
  <title>
    <?= $data['isOwner']
      ? 'My Collections - MyVibe'
      : htmlspecialchars($data['username']) . "'s Collections - MyVibe" ?>
  </title>

  <link rel="stylesheet" href="assets/css/style.css">

  <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <script src="assets/js/admin_panel.js" defer></script>
    <script src="assets/js/admin_modal.js" defer></script>
  <?php endif; ?>
</head>

<body>

  <?php include __DIR__ . '/partials/header.php'; ?>
  <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin')
    include __DIR__ . '/partials/admin_modal.php'; ?>

  <main class="collections-page">

    <h1>
      <?= $data['isOwner']
        ? 'My Collections'
        : htmlspecialchars($data['username']) . "'s Collections" ?>
    </h1>

    <?php
    $isOwner = !empty($data['isOwner']);
    $isAdmin = (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');
    $isAdminOnForeign = ($isAdmin && !$isOwner);
    ?>

    <?php if ($isOwner || $isAdminOnForeign): ?>
      <div class="edit-mode-toggle">
        <button id="toggleEditMode" class="edit-mode-btn">🛠️ Edit Mode</button>
      </div>
    <?php endif; ?>

    <?php if (!empty($data['success'])): ?>
      <div class="msg-success"><?= htmlspecialchars($data['success']) ?></div>
    <?php endif; ?>

    <?php if (!empty($data['error'])): ?>
      <div class="msg-error"><?= htmlspecialchars($data['error']) ?></div>
    <?php endif; ?>

    <?php if (!empty($data['info'])): ?>
      <div class="msg-info"><?= htmlspecialchars($data['info']) ?></div>
    <?php endif; ?>

    <section class="collection-grid">

      <?php if ($isOwner || $isAdminOnForeign): ?>
        <div class="collection-card create-card" id="createCard">
          <div class="plus-icon">+</div>
          <p>Create New Collection</p>
        </div>
      <?php endif; ?>

      <?php if (empty($data['collections'])): ?>
        <p class="empty-info">
          <?= $isOwner
            ? 'No collections yet. Create your first one!'
            : 'This user has no collections yet.' ?>
        </p>
      <?php else: ?>

        <?php foreach ($data['collections'] as $col): ?>
          <div class="collection-card">

            <a href="collection_detail.php?id=<?= (int) $col['id'] ?>">
              <img src="<?= htmlspecialchars(!empty($col['cover']) ? $col['cover'] : 'default/collection_default.png') ?>"
                alt="<?= htmlspecialchars($col['title']) ?>" class="collection-cover">
            </a>

            <h3><?= htmlspecialchars($col['title']) ?></h3>

            <?php if ($isOwner || $isAdminOnForeign): ?>
              <div class="collection-actions">

                <?php if ($isOwner): ?>
                  <button class="edit-button" data-id="<?= (int) $col['id'] ?>"
                    data-title="<?= htmlspecialchars($col['title']) ?>">
                    ✏️ Edit
                  </button>
                <?php endif; ?>

                <form method="post" action="app/actions/collections_delete.php" class="delete-form">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="collection_id" value="<?= (int) $col['id'] ?>">
                  <button type="submit" class="delete-button">🗑️ Delete</button>
                </form>

              </div>
            <?php endif; ?>

          </div>
        <?php endforeach; ?>

      <?php endif; ?>

    </section>

    <?php if (!empty($data['pagination']) && $data['pagination']['totalPages'] > 1): ?>
      <div class="pagination">
        <?php
        $p = $data['pagination'];
        $urlParams = $_GET;
        ?>

        <?php if ($p['page'] > 1): ?>
          <?php $urlParams['page'] = $p['page'] - 1; ?>
          <a href="?<?= http_build_query($urlParams) ?>" class="page-link btn-pagination">&laquo; Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $p['totalPages']; $i++): ?>
          <?php $urlParams['page'] = $i; ?>
          <a href="?<?= http_build_query($urlParams) ?>"
            class="page-link btn-pagination <?= $i === $p['page'] ? 'active' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>

        <?php if ($p['page'] < $p['totalPages']): ?>
          <?php $urlParams['page'] = $p['page'] + 1; ?>
          <a href="?<?= http_build_query($urlParams) ?>" class="page-link btn-pagination">Next &raquo;</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </main>

  <!-- EDIT MODAL -->
  <?php if ($isOwner || $isAdminOnForeign): ?>
    <div class="modal" id="editCollectionModal">
      <div class="modal-content">
        <span class="close close-edit">&times;</span>
        <h2>Edit Collection</h2>

        <form method="post" action="app/actions/collections_edit.php" enctype="multipart/form-data">
          <input type="hidden" name="collection_id" id="editCollectionId">

          <label>Title:</label>
          <input type="text" name="title" id="editCollectionTitle" required>

          <label>Change cover image (optional):</label>
          <input type="file" name="cover" accept=".jpg,.jpeg,.png,.webp">

          <button type="submit" class="button-create">Save Changes</button>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <!-- CREATE MODAL -->
  <?php if ($isOwner || $isAdminOnForeign): ?>
    <div class="modal" id="createModal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Create New Collection</h2>

        <form method="post" action="app/actions/collections_create.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="target_username" value="<?= htmlspecialchars($data['username']) ?>">

          <label>Title:</label>
          <input type="text" name="title" required>

          <label>Theme:</label>
          <select name="theme_type" required>
            <option value="games">Games</option>
            <option value="music">Music</option>
            <option value="movies">Movies</option>
            <option value="books">Books</option>
            <option value="anime">Anime/Manga</option>
            <option value="recipes">Recipes</option>
            <option value="custom">Custom</option>
          </select>

          <label>Custom Theme Name (optional):</label>
          <input type="text" name="custom_theme_name">

          <label>Cover image (optional):</label>
          <input type="file" name="cover" accept=".jpg,.jpeg,.png,.webp">

          <button type="submit" class="button-create">Create</button>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <footer>
    <p>&copy; 2025 MyVibe. All rights reserved.</p>
  </footer>

  <?php if ($isOwner || $isAdminOnForeign): ?>
    <script src="assets/js/edit_mode_toggle.js"></script>
    <script src="assets/js/collection_edit.js"></script>
    <script src="assets/js/collection_modal.js"></script>
    <script src="assets/js/collection_delete_confirm.js"></script>
  <?php endif; ?>

</body>

</html>