<?php

declare(strict_types=1);

namespace App\Commands;

use function System\Console\info;

final class HelpCommand extends Command
{
    public function __main(): int
    {
        info('help command not avilable yet')->out();

        return 1;
    }
}
