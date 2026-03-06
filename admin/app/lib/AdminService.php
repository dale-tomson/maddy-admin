<?php
// Centralized admin logic for views in admin/app
// Lives at admin/app/lib/ so it is served under /app/lib/ via the existing volume mount.

require_once __DIR__ . '/../maddy_connector.php';

class AdminService {

    private static array $actionMap = [
        'accounts' => [
            'create'      => 'acctCreate',
            'delete'      => 'acctDelete',
            'create_imap' => 'acctCreateImap',
            'delete_imap' => 'acctDeleteImap',
            'create_smtp' => 'acctCreateSmtp',
            'delete_smtp' => 'acctDeleteSmtp',
        ],
        'smtp' => [
            'create_smtp' => 'acctCreateSmtp',
            'delete_smtp' => 'acctDeleteSmtp',
        ],
        'passwd' => [
            'set_password' => 'passwdSet',
        ],
        'dns' => [
            'gen-dkim' => 'dnsGenDkim',
        ],
    ];

    // Generic POST dispatcher: scope is one of 'accounts','smtp','passwd','dns'
    public static function handlePost(string $scope): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $action  = $_POST['action'] ?? '';
        $handler = self::$actionMap[$scope][$action] ?? null;
        if ($handler && is_callable([self::class, $handler])) {
            forward_static_call([self::class, $handler]);
        }
    }

    // ----- Account handlers -----
    private static function acctCreate(): void {
        $id         = preg_replace('/[^a-zA-Z0-9._+*\\-]/', '', trim($_POST['identifier'] ?? ''));
        $pw         = $_POST['new_password'] ?? '';
        $no_mailbox = isset($_POST['no_mailbox']) && $_POST['no_mailbox'] === '1';

        if (!$id) { setFlash('Username is required.', 'err'); header('Location: /accounts.php'); exit; }

        if (strpos($id, '*') !== false) {
            $base  = preg_replace('/\*.*$/', '', $id);
            if ($base === '') $base = 'noreply';
            $email = $base . '@' . DOMAIN;
            if (!$pw) $pw = bin2hex(random_bytes(12));
            maddy('maddy creds create ' . escapeshellarg($email), $pw);
            setFlash('Created sending-only credential: ' . $email);
            header('Location: /accounts.php'); exit;
        }

        $email = $id . '@' . DOMAIN;
        if (!$pw) { setFlash('Password is required for real accounts.', 'err'); header('Location: /accounts.php'); exit; }

        maddy('maddy creds create ' . escapeshellarg($email), $pw);
        if (!$no_mailbox) maddy('maddy imap-acct create ' . escapeshellarg($email));
        setFlash(!$no_mailbox ? 'Created: ' . $email : 'Created sending-only credential (no mailbox): ' . $email);
        header('Location: /accounts.php'); exit;
    }

    private static function acctDelete(): void {
        $email = $_POST['email'] ?? '';
        if ($email && str_contains($email, '@')) {
            maddy('maddy creds remove '     . escapeshellarg($email));
            maddy('maddy imap-acct remove ' . escapeshellarg($email));
            setFlash('Deleted: ' . $email);
        }
        header('Location: /accounts.php'); exit;
    }

    private static function acctCreateImap(): void {
        $email = $_POST['email'] ?? '';
        if ($email) { maddy('maddy imap-acct create ' . escapeshellarg($email)); setFlash('Created IMAP account: ' . $email); }
        header('Location: /accounts.php'); exit;
    }

    private static function acctDeleteImap(): void {
        $email = $_POST['email'] ?? '';
        if ($email) { maddy('maddy imap-acct remove ' . escapeshellarg($email)); setFlash('Removed IMAP account: ' . $email); }
        header('Location: /accounts.php'); exit;
    }

    private static function acctCreateSmtp(): void {
        $email = $_POST['email'] ?? '';
        $pw    = $_POST['password'] ?? '';
        if ($email) {
            if (!$pw) $pw = bin2hex(random_bytes(12));
            maddy('maddy creds create ' . escapeshellarg($email), $pw);
            setFlash('Created SMTP credential: ' . $email);
        }
        header('Location: /accounts.php'); exit;
    }

    private static function acctDeleteSmtp(): void {
        $email = $_POST['email'] ?? '';
        if ($email) { maddy('maddy creds remove ' . escapeshellarg($email)); setFlash('Removed SMTP credential: ' . $email); }
        header('Location: /accounts.php'); exit;
    }

    // ----- Password -----
    private static function passwdSet(): void {
        $email = $_POST['email'] ?? '';
        $pw    = $_POST['new_password'] ?? '';
        $pw2   = $_POST['confirm_password'] ?? '';
        if (!$email || !$pw)  { setFlash('Email and new password are required.', 'err'); }
        elseif ($pw !== $pw2) { setFlash('Passwords do not match.', 'err'); }
        else { maddy('maddy creds password ' . escapeshellarg($email), $pw); setFlash('Password updated for: ' . $email); }
        header('Location: /passwd.php?email=' . urlencode($email)); exit;
    }

    // ----- DNS / DKIM -----
    private static function dnsGenDkim(): void {
        $dkimSelector = 'default';
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if ($key) {
            openssl_pkey_export($key, $privatePem);
            $d = DOMAIN; $s = $dkimSelector;
            maddy("sh -c 'mkdir -p /data/dkim_keys/$d'");
            maddy("sh -c 'cat > /data/dkim_keys/$d/$s.key'", $privatePem);
            setFlash('DKIM key pair generated. Add the DNS record below, then restart Maddy.');
        } else {
            setFlash('Failed to generate DKIM key — openssl_pkey_new() failed.', 'err');
        }
        header('Location: /dns.php'); exit;
    }

    // ----- Data helpers -----
    public static function getAccountsData(): array {
        $accounts  = listAccounts();
        $creds_raw = maddy('maddy creds list');
        $creds     = array_filter(array_map('trim', explode("\n", $creds_raw)));
        $imap_raw  = maddy('maddy imap-acct list');
        $imaps     = array_filter(array_map('trim', explode("\n", $imap_raw)));
        return ['accounts' => $accounts, 'creds' => $creds, 'imaps' => $imaps];
    }

    public static function getConnInfo(): array {
        $conf_file = __DIR__ . '/../../maddy_data/maddy.conf';
        $conn = ['smtp' => [], 'submission' => [], 'imap' => [], 'hostname' => DOMAIN];
        if (is_readable($conf_file)) {
            $c = file_get_contents($conf_file);
            if (preg_match('/^\$\(hostname\)\s*=\s*(\S+)/m', $c, $m)) $conn['hostname'] = trim($m[1]);
            if (preg_match_all('/^\s*(smtp|submission|imap)\s+([^\{\n]+)/m', $c, $mats, PREG_SET_ORDER)) {
                foreach ($mats as $m) {
                    $key   = $m[1];
                    $parts = preg_split('/\s+/', trim($m[2]));
                    foreach ($parts as $p) {
                        if (preg_match('/:(\d+)/', $p, $pm)) {
                            $port  = $pm[1];
                            $proto = strpos($p, 'tls://') === 0 || strpos($p, 'smtps://') === 0 ? 'tls' : (strpos($p, 'tcp://') === 0 ? 'tcp' : 'unknown');
                            $conn[$key][] = ['port' => $port, 'proto' => $proto, 'raw' => $p];
                        }
                    }
                }
            }
        }
        return $conn;
    }

    public static function getDnsData(): array {
        $dkimSelector = 'default';
        $dkimKeyPath  = '/data/dkim_keys/' . DOMAIN . '/' . $dkimSelector . '.key';
        $hostname     = 'mx.' . DOMAIN;

        $dkimValue = null; $dkimError = null; $dkimExists = false;
        $checkOutput = maddy("sh -c '[ -f " . escapeshellarg($dkimKeyPath) . " ] && echo EXISTS || echo MISSING'");
        if (trim($checkOutput) === 'EXISTS') {
            $dkimExists = true;
            $privPem    = maddy('cat ' . escapeshellarg($dkimKeyPath));
            $privKey    = !empty($privPem) ? openssl_pkey_get_private($privPem) : false;
            if ($privKey) {
                $details   = openssl_pkey_get_details($privKey);
                $pubBase64 = preg_replace('/-----[^-]+-----|\s+/', '', $details['key']);
                $dkimValue = 'v=DKIM1; k=rsa; p=' . $pubBase64;
            } else {
                $dkimError = 'Key file found but could not be parsed. Try regenerating.';
            }
        }

        $serverIp = gethostbyname($hostname);
        if ($serverIp === $hostname) $serverIp = null;

        $records = [
            ['label' => 'MX',       'name' => '@',     'type' => 'MX',  'value' => '10 ' . $hostname . '.', 'note' => 'Routes inbound email to your mail server.', 'status' => 'required'],
            ['label' => 'A (MX host)', 'name' => 'mx', 'type' => 'A',   'value' => $serverIp ?? '<YOUR_SERVER_IP>', 'note' => "Points the MX hostname to your server's IP address.", 'status' => 'required', 'warn' => !$serverIp],
            ['label' => 'SPF',      'name' => '@',     'type' => 'TXT', 'value' => 'v=spf1 mx ~all', 'note' => 'Authorises your MX server to send email. Reduces spam score.', 'status' => 'required'],
            ['label' => 'DMARC',    'name' => '_dmarc','type' => 'TXT', 'value' => 'v=DMARC1; p=quarantine; rua=mailto:postmaster@' . DOMAIN . '; fo=1', 'note' => 'DMARC policy. Start with p=none while testing, upgrade to p=quarantine/reject.', 'status' => 'required'],
        ];

        return ['dkimSelector' => $dkimSelector, 'dkimExists' => $dkimExists, 'dkimValue' => $dkimValue, 'dkimError' => $dkimError, 'serverIp' => $serverIp, 'records' => $records];
    }

}
