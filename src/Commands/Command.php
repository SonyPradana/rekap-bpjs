<?php

declare(strict_types=1);

namespace App\Commands;

use App\PCare;
use System\Cache\Storage\FileStorage;
use System\Console\Command as BaseCommand;

use function System\Console\option;
use function System\Console\warn;

abstract class Command extends BaseCommand
{
    public function __construct(
        array $argv,
        protected string $base_dir,
        protected FileStorage $cache,
        protected PCare $pcare,
    ) {
        parent::__construct($argv);
    }

    abstract public function __main(): int;

    protected function ratelimter(string|array|null $key, int $time = 10): void
    {
        if ($key === null) {
            warn("limited by server sleep for {$time} second!")->out(false);
            sleep($time);
            $this->checkLogin();
        }
    }

    protected function checkLogin(): void
    {
        $res   = $this->pcare->get('/check');
        if ($res->getStatusCode() > 200) {
            option('mohon login terlebih dahulu!', [
                'yes' => static function () {},
            ]);
        }
    }
}
