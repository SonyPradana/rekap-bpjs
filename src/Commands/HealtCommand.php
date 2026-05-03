<?php

declare(strict_types=1);

namespace App\Commands;

use function System\Console\fail;
use function System\Console\ok;

final class HealtCommand extends Command
{
    public function __main(): int
    {
        $all_ok = true;

        // PCare Client Check
        try {
            $pcare_status = $this->pcare->status();
            if ($pcare_status) {
                ok('PCare Client: Connected')->out(false);
            } else {
                fail('PCare Client: Disconnected')->out(false);
                $all_ok = false;
            }
        } catch (\Throwable $th) {
            fail('PCare Client: Error - ' . $th->getMessage())->out(false);
            $all_ok = false;
        }

        // Cache Ping-Pong Check
        try {
            $ping_key   = 'ping';
            $ping_value = 'pong';
            $this->cache->set($ping_key, $ping_value, 60);
            $pong = $this->cache->get($ping_key);

            if ($pong === $ping_value) {
                ok('Cache: Ping-Pong Success')->out(false);
            } else {
                fail('Cache: Ping-Pong Failed')->out(false);
                $all_ok = false;
            }
        } catch (\Throwable $th) {
            fail('Cache: Error - ' . $th->getMessage())->out(false);
            $all_ok = false;
        }

        return $all_ok ? 0 : 1;
    }
}
