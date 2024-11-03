<?php

declare(strict_types=1);

namespace App\Commands;

use function System\Console\info;
use function System\Console\ok;

final class ServicesCommand extends Command
{
    public function __main(): int
    {
        $services = require $this->base_dir . '/logs/current.php';
        $all      = [];

        foreach ($services as $service_name => $service) {
            info("begain run '{$service_name}'")->out(false);
            $this->cache->set($service_name . '-success', false, 2_1600);

            foreach ($service as $index => $bpjs) {
                $nik                  = $this->getNIK($bpjs);
                $jenis                = $this->getJenisBPJS($nik);
                $all[$service_name][] = [
                    'bpjs'  => $bpjs,
                    'jenis' => $jenis,
                ];

                info("{$index} bpjs: {$bpjs}, jenis: {$jenis}")->out(false);

                usleep(1_200_000); // 1.2 detik
            }

            // cache to prevent fail
            $this->cache->set($service_name, $all[$service_name], 2_1600); // 6 jam
            $this->cache->set($service_name . '-success', true, 2_1600);
        }

        $json   = json_encode($all);
        $return = file_put_contents($this->base_dir . '/logs/current.json', $json);
        ok('done')->out();

        return false === $return ? 1 : 0;
    }

    private function getNIK(string $bpjs): string
    {
        if (null == ($nik = $this->cache->get($bpjs, null))) {
            while ($nik === null) {
                $res  = $this->pcare->bpjs($bpjs);
                $body = $res->getBody()->getContents();
                $json = json_decode($body, true);
                $nik  = $json['nik'] ?? null;

                $this->ratelimter($nik);
            }

            $this->cache->set($bpjs, $nik);
        }

        return $nik;
    }

    private function getJenisBPJS(string $nik): string
    {
        $jenis = null;
        while ($jenis === null) {
            $res   = $this->pcare->nik($nik);
            $body  = $res->getBody()->getContents();
            $json  = json_decode($body, true);
            $jenis = $json['jnsPeserta']['nama'] ?? null;

            $this->ratelimter($jenis);
        }

        return $jenis;
    }
}
