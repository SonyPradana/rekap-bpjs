<?php

declare(strict_types=1);

namespace App\Commands;

use System\Console\Style\Style;

use function System\Console\info;
use function System\Console\ok;

final class ServicesCacheWarmCommand extends Command
{
    public function __main(): int
    {
        $services = require $this->base_dir . '/logs/current.php';
        $fails    = [];
        $index    = 0;

        foreach ($services as $service) {
            foreach ($service as $bpjs) {
                $nik = $this->getNIK($bpjs);
                $index++;

                if (null === $nik) {
                    $fails[] = $bpjs;

                    continue;
                }

                info("{$index} Warming up cache {$bpjs}:{$nik} success")->out(false);
            }
        }

        ok('done')->out();

        if (count($fails) > 0) {
            $failsnik = new Style('fail get nik');
            foreach ($fails as $nik) {
                $failsnik->tabs()->push($nik)->textDim()->newline();
            }
            $failsnik->out();
        }

        return 0;
    }

    private function getNIK(string $bpjs): ?string
    {
        $nik = $this->cache->get($bpjs, null);
        if (null === $nik || $nik === '') {
            $this->cache->delete($bpjs);
            $count = 0;

            while ($nik === null) {
                $res  = $this->pcare->bpjs($bpjs);
                $body = $res->getBody()->getContents();
                $json = json_decode($body, true);
                $nik  = $json['nik'] ?? null;

                $this->ratelimter($nik);
                $count++;
                if ($count > 9) {
                    info("Skipping BPJS {$bpjs} after 10 attempts, will skip this.")->out();

                    return null;
                }
            }

            $this->cache->set($bpjs, $nik);
        }

        return $nik;
    }
}
