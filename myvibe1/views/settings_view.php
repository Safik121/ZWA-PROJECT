<?php
/**
 * SETTINGS VIEW
 *
 * Discord-style settings page.
 *
 * @package MyVibe\Views
 * @author  Safik
 */

if (!isset($data) || !is_array($data) || empty($data['user'])) {
  echo '<div class="error">⚠️ User data not loaded properly.</div>';
  return;
}

$user = $data['user'];
$emailSafe = htmlspecialchars($user['email']);
$usernameSafe = htmlspecialchars($user['username']);
$avatarSafe = htmlspecialchars($user['avatar'] ?: 'default/avatar_default.png');
$isAdmin = (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');
$displayNameSafe = htmlspecialchars($user['display_name'] ?? $user['username']);

// Get base URL for asset paths
require_once __DIR__ . '/../app/core/paths.php';
$baseUrl = getBaseUrl();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Settings - MyVibe</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/css/style.css">
  <?php if ($isAdmin): ?>
    <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/js/admin_panel.js" defer></script>
    <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/js/admin_modal.js" defer></script>
  <?php endif; ?>
  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/js/settings.js" defer></script>
</head>

<body>

  <?php include __DIR__ . '/partials/header.php'; ?>
  <?php if ($isAdmin)
    include __DIR__ . '/partials/admin_modal.php'; ?>

  <main class="settings-main">

    <!-- Flash Messages -->
    <?php if (!empty($data['success'])): ?>
      <div class="msg-success"><?= htmlspecialchars($data['success']) ?></div>
    <?php endif; ?>
    <?php if (!empty($data['error'])): ?>
      <div class="msg-error"><?= htmlspecialchars($data['error']) ?></div>
    <?php endif; ?>

    <!-- Back Button -->
    <div class="settings-nav">
      <a href="<?= htmlspecialchars($data['backUrl']) ?>" class="btn-back">← Back</a>
    </div>

    <div class="settings-container">

      <!-- Profile Banner & Header -->
      <div class="profile-banner">
        <!-- Placeholder banner color/image -->
      </div>

      <div class="profile-header-section">
        <div class="profile-avatar-wrapper">
          <img src="<?= $avatarSafe ?>" alt="Avatar" class="profile-avatar-large">
        </div>

        <div class="profile-header-info">
          <h2 class="profile-username"><?= $usernameSafe ?></h2>
          <button class="btn-primary" onclick="openModal('avatarModal')">Change Avatar</button>
        </div>
      </div>

      <!-- User Info Card -->
      <div class="user-info-card"> <!-- Display Name Row -->
        <div class="info-row">
          <div class="info-content">
            <label>DISPLAY NAME</label>
            <div class="info-value"><?= $displayNameSafe ?></div>
          </div>
          <button class="btn-secondary" onclick="openModal('displayNameModal')">Edit</button>
        </div>


        <!-- Username Row -->
        <div class="info-row">
          <div class="info-content">
            <label>USERNAME</label>
            <div class="info-value"><?= $usernameSafe ?></div>
          </div>
          <!-- Username usually not editable or separate flow, keeping static for now as per req -->
        </div>

        <!-- Email Row -->
        <div class="info-row">
          <div class="info-content">
            <label>EMAIL</label>
            <div class="info-value masked-email">
              <?= $emailSafe ?>
              <!-- <span class="reveal-link">Reveal</span> -->
            </div>
          </div>
          <button class="btn-secondary" onclick="openModal('emailModal')">Edit</button>
        </div>

        <!-- Password Row -->
        <div class="info-row">
          <div class="info-content">
            <label>PASSWORD</label>
            <div class="info-value">****************</div>
          </div>
          <button class="btn-secondary" onclick="openModal('passwordModal')">Change Password</button>
        </div>

      </div>

      <!-- Delete Account Section -->
      <div class="user-info-card delete-account-card">
        <div class="info-row">
          <div class="info-content">
            <label class="delete-account-label">DELETE ACCOUNT</label>
            <div class="info-value delete-account-desc">Permanently delete your account and all data.</div>
          </div>
          <button class="btn-secondary btn-delete-account" onclick="openModal('deleteAccountModal')">Delete
            Account</button>
        </div>
      </div>

    </div>

  </main>

  <!-- =========================
       MODALS
  ========================= -->

  <!-- 1) Change Avatar Modal -->
  <div id="avatarModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Select an Image</h3>
        <span class="modal-close" onclick="closeModal('avatarModal')">&times;</span>
      </div>
      <div class="modal-body">
        <form method="post" enctype="multipart/form-data" action="settings.php">
          <input type="hidden" name="action" value="update_avatar">
          <input type="hidden" name="back" value="<?= htmlspecialchars($data['backUrl']) ?>">
          <div class="file-upload-area">
            <input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp" required>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeModal('avatarModal')">Cancel</button>
            <button type="submit" class="btn-save">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- 2) Change Display Name Modal -->
  <div id="displayNameModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Change Display Name</h3>
        <span class="modal-close" onclick="closeModal('displayNameModal')">&times;</span>
      </div>
      <div class="modal-body">
        <p class="modal-desc">Choose how you want to appear to other users.</p>
        <form method="post" action="settings.php">
          <input type="hidden" name="action" value="update_display_name">
          <input type="hidden" name="back" value="<?= htmlspecialchars($data['backUrl']) ?>">

          <div class="form-group">
            <label>Display Name</label>
            <input type="text" name="display_name" value="<?= $displayNameSafe ?>" maxlength="50" required>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeModal('displayNameModal')">Cancel</button>
            <button type="submit" class="btn-save">Done</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- 3) Change Email Modal -->
  <div id="emailModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Change Email</h3>
        <span class="modal-close" onclick="closeModal('emailModal')">&times;</span>
      </div>
      <div class="modal-body">
        <p class="modal-desc">Enter your new email address and your current password to confirm.</p>
        <form method="post" action="settings.php">
          <input type="hidden" name="action" value="update_email">
          <input type="hidden" name="back" value="<?= htmlspecialchars($data['backUrl']) ?>">

          <div class="form-group">
            <label>New Email</label>
            <input type="email" name="new_email" required>
          </div>

          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeModal('emailModal')">Cancel</button>
            <button type="submit" class="btn-save">Done</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- 4) Change Password Modal -->
  <div id="passwordModal" class="modal-overlay">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Change Password</h3>
        <span class="modal-close" onclick="closeModal('passwordModal')">&times;</span>
      </div>
      <div class="modal-body">
        <form method="post" action="settings.php">
          <input type="hidden" name="action" value="update_password">
          <input type="hidden" name="back" value="<?= htmlspecialchars($data['backUrl']) ?>">

          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
          </div>

          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" minlength="6" required>
          </div>

          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" minlength="6" required>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeModal('passwordModal')">Cancel</button>
            <button type="submit" class="btn-save">Done</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- 5) Delete Account Modal -->
  <div id="deleteAccountModal" class="modal-overlay">
    <div class="modal-box modal-box-delete">
      <div class="modal-header">
        <h3 class="modal-header-delete">Delete Account</h3>
        <span class="modal-close" onclick="closeModal('deleteAccountModal')">&times;</span>
      </div>
      <div class="modal-body">
        <p class="modal-desc modal-desc-warning">⚠️ This action is irreversible. All your data will be lost.</p>
        <p class="modal-desc">Please enter your email and password to confirm.</p>

        <form method="post" action="settings.php">
          <input type="hidden" name="action" value="delete_account">

          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
          </div>

          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeModal('deleteAccountModal')">Cancel</button>
            <button type="submit" class="btn-save btn-delete-account">Delete Account</button>
          </div>
        </form>
      </div>
    </div>
  </div>


</body>

</html>