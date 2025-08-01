<?php

declare(strict_types=1);

namespace App\Commands;

use function System\Console\fail;
use function System\Console\ok;

final class CacheGetCommand extends Command
{
    public function __main(): int
    {
        $key = $this->option('key', null);

        if (null === $key) {
            fail('Forget cachge requied key')->out();

            return 1;
        }

        $value = $this->cache->get($key, 'null');
        $value = json_encode($value);

        ok("Cache with key: {$key} has value: {$value}")->out();

        return 0;
    }
}
