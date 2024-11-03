<?php

declare(strict_types=1);

namespace App\Commands;

use function System\Console\info;
use function System\Console\ok;
use function System\Console\warn;

final class AnalizeCommand extends Command
{
    public function __main(): int
    {
        if (false === file_exists($log = $this->base_dir . '/logs/current.json')) {
            warn("No log file founded at '{$log}'")->out(false);
            info('Suggest run `php cli services`.')->out();

            return 1;
        }
        $load = file_get_contents($log);

        $services = json_decode($load, true);
        foreach ($services as $service_name => $service) {
            $pbi  = 0;
            $non  = 0;
            foreach ($service as ['jenis' => $jenis]) {
                if (str_starts_with($jenis, 'PBI')) {
                    $pbi++;
                    continue;
                }
                $non++;
            }

            ok("{$service_name} -> pbi: {$pbi}; non: {$non}")->out();
        }

        return 0;
    }
}
