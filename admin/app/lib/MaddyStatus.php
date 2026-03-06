<?php
// Checks whether the Maddy container is up, starting, or down.

class MaddyStatus
{
    // Returns ['state'=>'up'|'starting'|'down', 'msg'=>string, 'started_at'=>string|null]
    public static function get(): array
    {
        $container = getenv('MADDY_CONTAINER') ?: 'maddy';
        $docker    = '/usr/bin/docker';
        $running   = trim(shell_exec("$docker inspect -f '{{.State.Running}}' " . escapeshellarg($container) . ' 2>/dev/null') ?: '');
        $started   = trim(shell_exec("$docker inspect -f '{{.State.StartedAt}}' " . escapeshellarg($container) . ' 2>/dev/null') ?: '');

        if ($running !== 'true') {
            return ['state' => 'down', 'msg' => 'Container not running', 'started_at' => null];
        }

        $started_at = null;
        if ($started) {
            $t = strtotime($started);
            if ($t !== false) $started_at = $t;
        }
        if ($started_at !== null && (time() - $started_at) < 15) {
            return ['state' => 'starting', 'msg' => 'Container recently started', 'started_at' => $started];
        }
        return ['state' => 'up', 'msg' => 'Maddy running', 'started_at' => $started ?: null];
    }
}
