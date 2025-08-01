<?php

declare(strict_types=1);

namespace App\Commands;

use function System\Console\fail;
use function System\Console\ok;

final class CacheForgetCommand extends Command
{
    public function __main(): int
    {
        $key = $this->option('key', null);

        if (null === $key) {
            fail('Forget cachge requied key')->out();

            return 1;
        }

        if (false === $this->cache->delete($key)) {
            fail("Failed to forget cache with key: {$key}")->out();

            return 1;
        }

        ok("Cache with key: {$key} has been forgotten")->out();

        return 0;
    }
}
