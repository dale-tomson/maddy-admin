<?php
// Returns ['state'=>'up'|'down'|'starting','msg'=>string,'started_at'=>string|null]
function get_maddy_status(): array {
    $container = getenv('MADDY_CONTAINER') ?: 'maddy';

    // Is the container present and running?
    // Use absolute docker binary to avoid PATH issues inside the PHP process
    $docker = '/usr/bin/docker';
    $running = trim(shell_exec("$docker inspect -f '{{.State.Running}}' " . escapeshellarg($container) . " 2>/dev/null") ?: '');
    $started = trim(shell_exec("$docker inspect -f '{{.State.StartedAt}}' " . escapeshellarg($container) . " 2>/dev/null") ?: '');

    if ($running === 'true') {
        // If the container started recently, show 'starting', otherwise consider it UP.
        $started_at = null;
        if ($started) {
            $t = strtotime($started);
            if ($t !== false) $started_at = $t;
        }
        $now = time();
        if ($started_at !== null && ($now - $started_at) < 15) {
            return ['state' => 'starting', 'msg' => 'Container recently started', 'started_at' => $started ?: null];
        }
        return ['state' => 'up', 'msg' => 'Maddy running', 'started_at' => $started ?: null];
    }

    return ['state' => 'down', 'msg' => 'Container not running', 'started_at' => null];
}
