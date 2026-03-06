<?php
// Wraps all interactions with the Maddy mail server container.

class Maddy
{
    // Execute a command inside the Maddy container, optionally piping $stdin into it.
    public static function exec(string $cmd, ?string $stdin = null): string
    {
        $c = escapeshellarg(defined('CONTAINER') ? CONTAINER : (getenv('MADDY_CONTAINER') ?: 'maddy'));
        if ($stdin !== null) {
            $full = 'echo ' . escapeshellarg($stdin) . " | docker exec -i $c $cmd 2>&1";
        } else {
            $full = "docker exec $c $cmd 2>&1";
        }
        return trim(shell_exec($full) ?? '');
    }

    // Return sorted list of credential accounts from `maddy creds list`.
    public static function listAccounts(): array
    {
        $raw = self::exec('maddy creds list');
        $accounts = [];
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line !== '') $accounts[] = $line;
        }
        return $accounts;
    }

    // Resolve the primary domain: MADDY_DOMAIN env → maddy.conf → empty string.
    public static function domain(): string
    {
        $env = getenv('MADDY_DOMAIN');
        if ($env) return $env;
        $conf = __DIR__ . '/../../maddy_data/maddy.conf';
        if (is_readable($conf)) {
            $c = file_get_contents($conf);
            if (preg_match('/^\$\(primary_domain\)\s*=\s*(\S+)/m', $c, $m)) {
                return trim($m[1]);
            }
        }
        return '';
    }
}
