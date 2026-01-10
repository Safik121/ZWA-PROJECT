<?php
/**
 * PROFILE VIEW
 *
 * Zobrazuje veřejný profil uživatele:
 *  - Avatar
 *  - Bio
 *  - Poslední kolekce
 *
 * BEZPEČNOST:
 *  - Všechny dynamické texty escapované přes htmlspecialchars()
 *  - Žádný inline JavaScript (vše ovládá profile_bio_edit.js)
 *  - Editace bio dostupná pouze pro vlastníka ($_SESSION['user_id'])
 *
 * POŽADAVKY:
 *  - profile_bio_edit.js → obsluha editace bio
 *  - admin_panel.js + admin_modal.js → pouze pokud role === 'admin'
 *  - správné include cesty: view používá ../includes/
 *
 * VSTUP (od controlleru):
 * $data = [
 *   'user' => [
 *      'id'       => int,
 *      'username' => string,
 *      'avatar'   => string|null,
 *      'bio'      => string|null,
 *      'display_name' => string|null,
 *   ],
 *   'collections' => array of recent collections (title, id, cover)
 * ];
 *
 * @package MyVibe\Views
 * @author  Safik
 */

if (!isset($data) || !is_array($data) || empty($data['user'])) {
  echo '<div class="error">⚠️ Profile data not loaded properly.</div>';
  return;
}

// ======================================================
// 1) Základní uživatelská data
// ======================================================
$user = $data['user'];

$usernameSafe = htmlspecialchars($user['username']);
$avatarSafe = htmlspecialchars($user['avatar'] ?: 'default/avatar_default.png');
$bioSafe = htmlspecialchars($user['bio'] ?: '');
$displayNameSafe = htmlspecialchars($user['display_name'] ?? $user['username']);

$isOwner = (!empty($_SESSION['user_id']) && $_SESSION['user_id'] === $user['id']);
$isAdmin = (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $displayNameSafe ?>'s Profile - MyVibe</title>

  <link rel="stylesheet" href="assets/css/style.css">

  <?php if ($isAdmin): ?>
    <script src="assets/js/admin_panel.js" defer></script>
    <script src="assets/js/admin_modal.js" defer></script>
  <?php endif; ?>
</head>

<body>

  <?php include __DIR__ . '/partials/header.php'; ?>
  <?php if ($isAdmin)
    include __DIR__ . '/partials/admin_modal.php'; ?>

  <main class="profile-page">

    <!-- ====================================================== -->
    <!-- 2) USER HEADER -->
    <!-- ====================================================== -->
    <section class="profile-header">

      <div class="profile-left">
        <img src="<?= $avatarSafe ?>" alt="Profile picture" class="profile-big">
      </div>

      <div class="profile-right">
        <h1 class="profile-name"><?= $displayNameSafe ?></h1>
        <p class="profile-username">@<?= $usernameSafe ?></p>

        <!-- ====================================================== -->
        <!-- 3) BIO -->
        <!-- ====================================================== -->
        <div class="bio-box">

          <div class="bio-header">
            <h2>Bio</h2>

            <!-- Tlačítko Edit dostupné pouze pro vlastníka -->
            <?php if ($isOwner): ?>
              <button id="editBioBtn" class="edit-btn">✏️ Edit</button>
            <?php endif; ?>
          </div>

          <!-- Zobrazení bio -->
          <div id="bioDisplay">
            <p><?= $bioSafe ?: 'This user hasn\'t written a bio yet.' ?></p>
          </div>

          <!-- Edit Form -->
          <form id="bioForm" method="post" class="bio-form hidden">
            <input type="hidden" name="action" value="update_bio">

            <textarea name="bio" id="bioTextarea" rows="3"><?= $bioSafe ?></textarea>

            <div class="bio-buttons">
              <button type="submit" class="button-create">Save</button>
              <button type="button" id="cancelEdit" class="button-secondary">Cancel</button>
            </div>
          </form>

        </div>

      </div>

    </section>

    <!-- ====================================================== -->
    <!-- 4) RECENT COLLECTIONS -->
    <!-- ====================================================== -->
    <section class="profile-collections">

      <h2>Recent Collections</h2>

      <?php if (empty($data['collections'])): ?>
        <p>This user hasn\'t created any collections yet.</p>
      <?php else: ?>
        <div class="collection-grid">

          <?php foreach (array_slice($data['collections'], 0, 3) as $col): ?>
            <?php
            $colTitle = htmlspecialchars($col['title']);
            $colImage = htmlspecialchars($col['cover'] ?: 'default/item_default.png');
            ?>
            <div class="collection-card">
              <a href="collection_detail.php?id=<?= (int) $col['id'] ?>">
                <img src="<?= $colImage ?>" alt="<?= $colTitle ?>">
              </a>
              <h3><?= $colTitle ?></h3>
            </div>
          <?php endforeach; ?>

        </div>
      <?php endif; ?>

      <div class="centered-block">
        <a href="collections.php?user=<?= $usernameSafe ?>" class="button-create">
          View all collections
        </a>
      </div>

    </section>

  </main>

  <footer>
    <p>© 2025 MyVibe. All rights reserved.</p>
  </footer>

  <script src="assets/js/profile_bio_edit.js"></script>

</body>

</html>