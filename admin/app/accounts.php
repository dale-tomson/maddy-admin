<?php
require '_auth.php';

$flash = popFlash();

require_once __DIR__ . '/accounts_logic.php';
// Handle any POST actions (may redirect)
handle_accounts_post();
// Fetch view data
$data = get_accounts_data();
$accounts = $data['accounts'];
$creds = $data['creds'];
$imaps = $data['imaps'];

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

<?php
// -- Connection info: parse maddy config for ports and hostname
$conf_file = __DIR__ . '/../../maddy_data/maddy.conf';
$conn = ['smtp' => [], 'submission' => [], 'imap' => [], 'hostname' => DOMAIN];
if (is_readable($conf_file)) {
    $c = file_get_contents($conf_file);
    if (preg_match('/^\$\(hostname\)\s*=\s*(\S+)/m', $c, $m)) {
        $conn['hostname'] = trim($m[1]);
    }
    if (preg_match_all('/^\s*(smtp|submission|imap)\s+([^\{\n]+)/m', $c, $mats, PREG_SET_ORDER)) {
        foreach ($mats as $m) {
            $key = $m[1];
            $parts = preg_split('/\s+/', trim($m[2]));
            foreach ($parts as $p) {
                if (preg_match('/:(\d+)/', $p, $pm)) {
                    $port = $pm[1];
                    $proto = strpos($p, 'tls://') === 0 || strpos($p, 'smtps://') === 0 ? 'tls' : (strpos($p, 'tcp://') === 0 ? 'tcp' : 'unknown');
                    $conn[$key][] = ['port' => $port, 'proto' => $proto, 'raw' => $p];
                }
            }
        }
    }
}
?>

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
              placeholder="username" autocomplete="off" required
              value="<?= htmlspecialchars($_GET['identifier'] ?? '') ?>">
            <span class="input-addon">@<?= htmlspecialchars(DOMAIN) ?></span>
          </div>
        </div>
        <div class="ax-form-group">
          <label class="ax-label">Initial Password</label>
          <input type="password" name="new_password" class="ax-input"
            placeholder="Password" required autocomplete="new-password">
        </div>
        <div class="ax-form-group ax-form-group--small">
          <label style="display:flex;align-items:center;gap:.5rem;margin:0">
            <input type="checkbox" id="no_mailbox" name="no_mailbox" value="1" <?= (isset($_GET['no_mailbox']) && $_GET['no_mailbox']==='1') ? 'checked' : '' ?>>
            <span style="font-size:.9rem">No mailbox (sending only)</span>
          </label>
        </div>
        <div class="form-actions">
          <button type="submit" class="ax-btn ax-btn--primary">
            + Create
          </button>
        </div>
      </div>
    </form>
    <div style="color:#64748b;font-size:.85rem;margin-top:.5rem;margin-left:4px">
      Checking "No mailbox" creates credentials that can be used to authenticate and send mail,
      but no IMAP mailbox will be created and incoming mail will not be stored for that address.
    </div>
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
        <th>IMAP</th>
        <th>SMTP Cred</th>
        <th style="text-align:right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($accounts as $acc): ?>
      <?php $has_imap = in_array($acc, $imaps, true); $has_cred = in_array($acc, $creds, true); ?>
      <tr>
        <td><span class="mono"><?= htmlspecialchars($acc) ?></span></td>
        <td>
          <?php if ($has_imap): ?>
            <span class="mini-badge mini-badge--imap">IMAP</span>
          <?php else: ?>
            <span class="mini-badge mini-badge--noimap">No IMAP</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($has_cred): ?>
            <span class="mini-badge mini-badge--smtp">SMTP</span>
          <?php else: ?>
            <span class="mini-badge mini-badge--nosmtp">No SMTP</span>
          <?php endif; ?>
        </td>
        <td class="act-cell">
          <?php $hid = substr(md5($acc),0,8); ?>
          <div style="position:relative;display:inline-block">
            <button type="button" class="kebab-btn ax-btn ax-btn--sm" aria-controls="menu-<?= $hid ?>" aria-expanded="false" onclick="toggleMenu('menu-<?= $hid ?>', this)">⋯</button>
            <div id="menu-<?= $hid ?>" class="action-menu" style="display:none;">
              <?php if (!$has_imap): ?>
              <form method="post">
                <input type="hidden" name="action" value="create_imap">
                <input type="hidden" name="email" value="<?= htmlspecialchars($acc) ?>">
                <button type="submit">Create IMAP</button>
              </form>
              <?php else: ?>
              <form method="post" onsubmit="return confirm('Delete IMAP account and mail?');">
                <input type="hidden" name="action" value="delete_imap">
                <input type="hidden" name="email" value="<?= htmlspecialchars($acc) ?>">
                <button type="submit">Delete IMAP</button>
              </form>
              <?php endif; ?>

              <?php if (!$has_cred): ?>
              <a href="/accounts.php?identifier=<?= urlencode(explode('@',$acc)[0]) ?>&no_mailbox=1">Create Sending Cred</a>
              <?php else: ?>
              <form method="post" onsubmit="return confirm('Remove SMTP credential?');">
                <input type="hidden" name="action" value="delete_smtp">
                <input type="hidden" name="email" value="<?= htmlspecialchars($acc) ?>">
                <button type="submit">Remove Cred</button>
              </form>
              <?php endif; ?>

              <a href="/passwd.php?email=<?= urlencode($acc) ?>">Change Password</a>
              <button type="button" onclick="openDelete(<?= htmlspecialchars(json_encode($acc)) ?>)">Delete Account</button>
            </div>
          </div>
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
