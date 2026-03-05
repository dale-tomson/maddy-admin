<?php
// ── Login page ────────────────────────────────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
session_start();

define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'changeme');

// Already logged in → go to accounts
if (!empty($_SESSION['auth'])) {
    header('Location: /accounts.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (hash_equals(ADMIN_PASSWORD, $_POST['password'])) {
        session_regenerate_id(true);
        $_SESSION['auth'] = true;
        header('Location: /accounts.php'); exit;
    }
    $error = 'Invalid password.';
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — Maddy Admin</title>
<link rel="stylesheet" href="https://unpkg.com/axis-twelve@2.0.2/dist/axis-twelve.min.css">
<style>
  body { background:#f8fafc; }
  .login-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1rem; }
  .flash-err { background:#fef2f2; border:1px solid #fecaca; color:#b91c1c;
               padding:.6rem 1rem; border-radius:6px; font-size:.875rem;
               display:flex; align-items:center; gap:.4rem; margin-bottom:1rem; }
  .login-logo { width:40px; height:40px; background:#0f172a; border-radius:10px;
                display:flex; align-items:center; justify-content:center;
                font-size:1.3rem; margin-bottom:1rem; }
</style>
</head>
<body>
<div class="login-wrap">
  <div style="width:100%;max-width:380px">
    <div class="ax-card ax-card--shadow">

      <div class="ax-card__body" style="padding:2rem">
        <div class="login-logo">📬</div>
        <h1 style="font-size:1.05rem;font-weight:700;color:#0f172a;margin:0 0 .25rem">Maddy Admin</h1>
        <p style="font-size:.825rem;color:#94a3b8;margin:0 0 1.5rem">Sign in to manage email accounts</p>

        <?php if ($error): ?>
        <div class="flash-err">✖ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
          <div class="ax-form-group">
            <label class="ax-label" for="pw">Password</label>
            <input
              id="pw" type="password" name="password"
              class="ax-input" placeholder="Admin password"
              autofocus required autocomplete="current-password">
          </div>
          <button type="submit" class="ax-btn ax-btn--primary ax-btn--block" style="margin-top:1rem">
            Sign In →
          </button>
        </form>
      </div>

    </div>
  </div>
</div>
</body>
</html>
