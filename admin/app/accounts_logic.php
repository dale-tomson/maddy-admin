<?php
// Logic for accounts page: handle POSTs and provide data for the view.

function handle_accounts_post() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
      // Allow '*' in identifiers for wildcard/dummy addresses.
      $id = preg_replace('/[^a-zA-Z0-9._+*\\-]/', '', trim($_POST['identifier'] ?? ''));
      $pw = $_POST['new_password'] ?? '';
      $no_mailbox = isset($_POST['no_mailbox']) && $_POST['no_mailbox'] === '1';

      if (!$id) {
        setFlash('Username is required.', 'err');
      } else {
        if (strpos($id, '*') !== false) {
          $base = preg_replace('/\*.*$/', '', $id);
          if ($base === '') $base = 'noreply';
          $email = $base . '@' . DOMAIN;
          if (!$pw) $pw = bin2hex(random_bytes(12));
          maddy('maddy creds create ' . escapeshellarg($email), $pw);
          setFlash('Created sending-only credential: ' . $email);
        } else {
          $email = $id . '@' . DOMAIN;
          if (!$pw) {
            setFlash('Password is required for real accounts.', 'err');
          } else {
            maddy('maddy creds create ' . escapeshellarg($email), $pw);
            if (!$no_mailbox) {
              maddy('maddy imap-acct create ' . escapeshellarg($email));
              setFlash('Created: ' . $email);
            } else {
              setFlash('Created sending-only credential (no mailbox): ' . $email);
            }
          }
        }
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

    if ($action === 'create_imap') {
      $email = ($_POST['email'] ?? '');
      if ($email) {
        maddy('maddy imap-acct create ' . escapeshellarg($email));
        setFlash('Created IMAP account: ' . $email);
      }
      header('Location: /accounts.php'); exit;
    }

    if ($action === 'delete_imap') {
      $email = ($_POST['email'] ?? '');
      if ($email) {
        maddy('maddy imap-acct remove ' . escapeshellarg($email));
        setFlash('Removed IMAP account: ' . $email);
      }
      header('Location: /accounts.php'); exit;
    }

    if ($action === 'create_smtp') {
      $email = ($_POST['email'] ?? '');
      $pw = $_POST['password'] ?? '';
      if ($email) {
        if (!$pw) $pw = bin2hex(random_bytes(12));
        maddy('maddy creds create ' . escapeshellarg($email), $pw);
        setFlash('Created SMTP credential: ' . $email);
      }
      header('Location: /accounts.php'); exit;
    }

    if ($action === 'delete_smtp') {
      $email = ($_POST['email'] ?? '');
      if ($email) {
        maddy('maddy creds remove ' . escapeshellarg($email));
        setFlash('Removed SMTP credential: ' . $email);
      }
      header('Location: /accounts.php'); exit;
    }
}

function get_accounts_data() {
    $accounts = listAccounts();
    $creds_raw = maddy('maddy creds list');
    $creds = array_filter(array_map('trim', explode("\n", $creds_raw)));
    $imap_raw = maddy('maddy imap-acct list');
    $imaps = array_filter(array_map('trim', explode("\n", $imap_raw)));
    return ['accounts'=>$accounts,'creds'=>$creds,'imaps'=>$imaps];
}

function get_conn_info() {
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
    return $conn;
}

?>
