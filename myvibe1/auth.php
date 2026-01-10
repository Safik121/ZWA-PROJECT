<?php
/**
 * AUTH VIEW CONTROLLER
 * --------------------
 * Zobrazuje přihlašovací a registrační formulář.
 *
 * Funkce:
 *  - zobrazuje chybové/úspěšné hlášky ze session
 *  - předvyplňuje formuláře při chybě (old_input)
 *  - obsahuje HTML strukturu pro flip animaci (login/register)
 *
 * Bezpečnost:
 *  - htmlspecialchars() pro výpis dat (XSS ochrana)
 *  - session data se po zobrazení mažou (flash messages)
 *
 * @package MyVibe
 * @author  Safik
 */
session_start();
$error = $_SESSION['msg_error'] ?? '';
$success = $_SESSION['msg_success'] ?? '';
$old = $_SESSION['old_input'] ?? [];

// Clear session messages after fetch
unset($_SESSION['msg_error'], $_SESSION['msg_success'], $_SESSION['old_input']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyVibe – Auth</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-page">

  <header>
    <h1>MyVibe</h1>
  </header>

  <main class="login-wrap">

    <!-- CARD CONTAINER -->
    <div class="auth-card <?= !empty($old['username']) && empty($old['user']) ? 'flipped' : '' ?>">

      <!-- FRONT = LOGIN -->
      <div class="auth-side auth-front">

        <div class="login-panel">
          <div class="login-left">
            <form id="loginForm" method="post" action="app/actions/login.php">
              <fieldset>
                <?php if ($error && empty($old['username'])): ?>
                  <div class="msg-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                  <div class="msg-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <label for="loginUser">Username / Email</label>
                <input type="text" id="loginUser" name="user" placeholder="Username / Email" required
                  value="<?= htmlspecialchars($old['user'] ?? '') ?>">

                <label for="loginPass">Password</label>
                <input type="password" id="loginPass" name="password" placeholder="Password" required minlength="6">

                <button type="submit" class="login-btn">Log In</button>
              </fieldset>
            </form>
          </div>

          <div class="login-right">
            <div class="login-text">
              <h2 class="t1">Our vibes missed you</h2>
              <h3 class="t2">Let's hop back in</h3>
            </div>
          </div>

          <div class="login-register">
            <p>Don't have an account?
              <a href="#" id="goRegister">Register here.</a>
            </p>
          </div>
        </div>

      </div>

      <!-- BACK = REGISTER -->
      <div class="auth-side auth-back">

        <div class="login-panel">
          <div class="login-left">
            <form id="registerForm" method="post" action="app/actions/register.php" enctype="multipart/form-data">
              <fieldset>
                <?php if ($error && !empty($old['username'])): ?>
                  <div class="msg-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="form-row">
                  <div class="input-group">
                    <label for="regUser">Username</label>
                    <input type="text" id="regUser" name="username" placeholder="Username" required
                      value="<?= htmlspecialchars($old['username'] ?? '') ?>">
                  </div>
                  <div class="input-group">
                    <label for="regEmail">Email</label>
                    <input type="email" id="regEmail" name="email" placeholder="Email" required
                      value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                  </div>
                </div>

                <div class="form-row">
                  <div class="input-group">
                    <label for="regPass">Password</label>
                    <input type="password" id="regPass" name="password" placeholder="Password" required minlength="6">
                  </div>
                  <div class="input-group">
                    <label for="regConfirm">Confirm</label>
                    <input type="password" id="regConfirm" name="confirm_password" placeholder="Confirm" required
                      minlength="6">
                  </div>
                </div>

                <label for="regAvatar">Avatar (optional)</label>
                <input type="file" id="regAvatar" name="avatar" accept=".jpg,.jpeg,.png,.webp">

                <button type="submit" class="login-btn">Register</button>
              </fieldset>
            </form>
          </div>

          <div class="login-right">
            <div class="login-text">
              <h2 class="t1">We are excited to meet you</h2>
              <h3 class="t2">Let's get you started</h3>
            </div>
          </div>

          <div class="login-register">
            <p>Already have an account?
              <a href="#" id="goLogin">Log in here.</a>
            </p>
          </div>
        </div>

      </div>

    </div>
  </main>

  <!-- FOOTER AND RIGHTS -->
  <footer>
    <p>&copy; 2025 MyVibe. All rights reserved.</p>
  </footer>

  <script src="assets/js/auth_flip.js"></script>

</body>

</html>