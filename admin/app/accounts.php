<?php
require '_auth.php';

$flash = popFlash();

// ── Handle POST actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $id = preg_replace('/[^a-zA-Z0-9._+\-]/', '', trim($_POST['identifier'] ?? ''));
        $pw = $_POST['new_password'] ?? '';

        if (!$id || !$pw) {
            setFlash('Username and password are required.', 'err');
        } else {
            $email = $id . '@' . DOMAIN;
            maddy('maddy creds create ' . escapeshellarg($email), $pw);
            maddy('maddy imap-acct create ' . escapeshellarg($email));
            setFlash('Created: ' . $email);
        }
        header('Location: /accounts.php'); exit;
    }

    if ($action === 'delete') {
        $email = $_POST['email'] ?? '';
        if ($email && str_contains($email, '@')) {
            maddy('maddy creds remove '    . escapeshellarg($email));
            maddy('maddy imap-acct remove '. escapeshellarg($email));
            setFlash('Deleted: ' . $email);
        }
        header('Location: /accounts.php'); exit;
    }
}

// ── Fetch account list ────────────────────────────────────────────────────────
$accounts = listAccounts();

// ── Render ────────────────────────────────────────────────────────────────────
$page  = 'accounts';
$title = 'Accounts';
require '_head.php';
?>

<div class="page-head">
  <h2 class="page-title">Email Accounts</h2>
  <span class="page-sub"><?= htmlspecialchars(DOMAIN) ?></span>
</div>

<?php if ($flash['msg']): ?>
<div class="flash flash-<?= $flash['type'] ?>">
  <?= $flash['type'] === 'ok' ? '✔' : '✖' ?> <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Create new account -->
<div class="panel">
  <div class="panel-head"><h3>New Account</h3></div>
  <div class="panel-body">
    <form method="post">
      <input type="hidden" name="action" value="create">
      <div class="form-row">
        <div class="ax-form-group">
          <label class="ax-label">Username</label>
          <div class="input-group">
            <input type="text" name="identifier" class="ax-input"
              placeholder="username" autocomplete="off" required>
            <span class="input-addon">@<?= htmlspecialchars(DOMAIN) ?></span>
          </div>
        </div>
        <div class="ax-form-group">
          <label class="ax-label">Initial Password</label>
          <input type="password" name="new_password" class="ax-input"
            placeholder="Password" required autocomplete="new-password">
        </div>
        <div>
          <button type="submit" class="ax-btn ax-btn--primary">
            + Create
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Account list -->
<div class="panel">
  <div class="panel-head">
    <h3>All Accounts</h3>
    <span class="badge"><?= count($accounts) ?></span>
  </div>

  <?php if (empty($accounts)): ?>
  <div class="empty">No accounts yet. Create one above.</div>
  <?php else: ?>
  <table class="clean-table">
    <thead>
      <tr>
        <th>Email Address</th>
        <th style="text-align:right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($accounts as $acc): ?>
      <tr>
        <td><span class="mono"><?= htmlspecialchars($acc) ?></span></td>
        <td class="act-cell">
          <a href="/passwd.php?email=<?= urlencode($acc) ?>"
             class="ax-btn ax-btn--sm ax-btn--outline-primary"
             style="min-height:30px;padding:.2rem .75rem;font-size:.78rem">
            Change PW
          </a>
          <button type="button"
            class="ax-btn ax-btn--sm ax-btn--outline-danger"
            style="min-height:30px;padding:.2rem .75rem;font-size:.78rem"
            onclick="openDelete(<?= htmlspecialchars(json_encode($acc)) ?>)">
            Delete
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<!-- Delete modal -->
<div id="delOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);
  z-index:100;align-items:center;justify-content:center;padding:1rem">
  <div style="background:#fff;border-radius:10px;padding:1.5rem;width:100%;max-width:380px;
    box-shadow:0 20px 40px rgba(0,0,0,.15)">
    <h4 style="margin:0 0 .5rem;font-size:1rem;font-weight:700;color:#0f172a">Delete Account</h4>
    <p style="margin:0 0 1.25rem;color:#64748b;font-size:.875rem">
      Permanently delete <strong id="delEmailLabel" style="color:#0f172a"></strong>?
      This removes all credentials and mailbox data.
    </p>
    <form method="post" style="display:flex;gap:.5rem;justify-content:flex-end">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="email" id="delEmailInput">
      <button type="button" class="ax-btn ax-btn--sm ax-btn--secondary" onclick="closeModal()">Cancel</button>
      <button type="submit" class="ax-btn ax-btn--sm ax-btn--danger">Delete</button>
    </form>
  </div>
</div>

<script>
function openDelete(email) {
  document.getElementById('delEmailLabel').textContent = email;
  document.getElementById('delEmailInput').value = email;
  document.getElementById('delOverlay').style.display = 'flex';
}
function closeModal() {
  document.getElementById('delOverlay').style.display = 'none';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>

<?php require '_foot.php'; ?>
