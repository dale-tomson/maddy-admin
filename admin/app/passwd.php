<?php
require '_auth.php';

$flash = popFlash();
$accounts = listAccounts();

// Pre-select account from query string (from "Change PW" link)
$selected = $_GET['email'] ?? '';

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $pw    = $_POST['new_password'] ?? '';
    $pw2   = $_POST['confirm_password'] ?? '';

    if (!$email || !$pw) {
        setFlash('Email and new password are required.', 'err');
    } elseif ($pw !== $pw2) {
        setFlash('Passwords do not match.', 'err');
    } else {
        maddy('maddy creds password ' . escapeshellarg($email), $pw);
        setFlash('Password updated for: ' . $email);
        $selected = $email;
    }
    header('Location: /passwd.php?email=' . urlencode($selected)); exit;
}

$page  = 'passwd';
$title = 'Change Password';
require '_head.php';
?>

<div class="page-head">
  <h2 class="page-title">Change Password</h2>
</div>

<?php if ($flash['msg']): ?>
<div class="flash flash-<?= $flash['type'] ?>">
  <?= $flash['type'] === 'ok' ? '✔' : '✖' ?> <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem">

  <!-- Left: account list -->
  <div class="panel">
    <div class="panel-head">
      <h3>Select Account</h3>
      <span class="badge"><?= count($accounts) ?></span>
    </div>
    <?php if (empty($accounts)): ?>
    <div class="empty">No accounts found.</div>
    <?php else: ?>
    <table class="clean-table">
      <tbody>
        <?php foreach ($accounts as $acc): ?>
        <tr style="cursor:pointer<?= $acc === $selected ? ';background:#eff6ff' : '' ?>"
            onclick="selectAccount(<?= htmlspecialchars(json_encode($acc)) ?>)">
          <td>
            <span class="mono<?= $acc === $selected ? '' : '' ?>"
              style="<?= $acc === $selected ? 'color:#2563eb;font-weight:600' : '' ?>">
              <?= htmlspecialchars($acc) ?>
            </span>
          </td>
          <td style="width:24px;text-align:right;color:#94a3b8;font-size:.75rem">
            <?= $acc === $selected ? '▶' : '' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Right: password form -->
  <div class="panel">
    <div class="panel-head"><h3>Set New Password</h3></div>
    <div class="panel-body">
      <form method="post">
        <div class="ax-form-group">
          <label class="ax-label">Account</label>
          <input type="text" id="emailDisplay" class="ax-input"
            value="<?= htmlspecialchars($selected) ?>"
            placeholder="Select an account on the left" readonly
            style="background:#f8fafc;cursor:default">
          <input type="hidden" name="email" id="emailInput"
            value="<?= htmlspecialchars($selected) ?>">
        </div>
        <div class="ax-form-group">
          <label class="ax-label" for="pw1">New Password</label>
          <input id="pw1" type="password" name="new_password"
            class="ax-input" placeholder="New password"
            autocomplete="new-password" required>
        </div>
        <div class="ax-form-group">
          <label class="ax-label" for="pw2">Confirm Password</label>
          <input id="pw2" type="password" name="confirm_password"
            class="ax-input" placeholder="Repeat password"
            autocomplete="new-password" required>
          <span id="matchHint" class="ax-form-text" style="font-size:.78rem"></span>
        </div>
        <button type="submit" class="ax-btn ax-btn--primary ax-btn--block" style="margin-top:.25rem">
          Update Password
        </button>
      </form>
    </div>
  </div>

</div>

<script>
function selectAccount(email) {
  document.getElementById('emailDisplay').value = email;
  document.getElementById('emailInput').value   = email;
  // Reload page with email param so row highlights server-side
  window.location.href = '/passwd.php?email=' + encodeURIComponent(email);
}

// Live password match hint
const pw1 = document.getElementById('pw1');
const pw2 = document.getElementById('pw2');
const hint = document.getElementById('matchHint');

function checkMatch() {
  if (!pw2.value) { hint.textContent = ''; return; }
  if (pw1.value === pw2.value) {
    hint.textContent = '✔ Passwords match';
    hint.style.color = '#059669';
  } else {
    hint.textContent = '✘ Passwords do not match';
    hint.style.color = '#dc2626';
  }
}
pw1.addEventListener('input', checkMatch);
pw2.addEventListener('input', checkMatch);
</script>

<?php require '_foot.php'; ?>
