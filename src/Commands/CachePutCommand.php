<?php

declare(strict_types=1);

namespace App\Commands;

use function System\Console\fail;
use function System\Console\ok;

final class CachePutCommand extends Command
{
    public function __main(): int
    {
        $key   = $this->option('key', null);
        $value = $this->option('value', null);

        if (null === $key) {
            fail('Put cachge requied key')->out();

            return 1;
        }

        if (null === $value) {
            fail('Put Cache can set value `null`')->out();

            return 1;
        }

        if ($this->cache->set($key, $value)) {
            ok("Cache with key: {$key} has been set")->out();

            return 0;
        }

        fail("Failed to set cache with key: {$$key}")->out();

        return 1;
    }
}
