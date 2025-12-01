<?php

declare(strict_types=1);

namespace App\Commands;

use function System\Console\info;
use function System\Console\ok;
use function System\Console\warn;

final class ServicesCommand extends Command
{
    private int $max_retry  = 4;
    private int $base_delay = 6; // dalam detik
    private int $interval   = 1_200_000; // 1.2 detik

    public function __main(): int
    {
        $services = require $this->base_dir . '/logs/current.php';
        $all      = [];
        $skipped  = [];

        foreach ($services as $service_name => $service) {
            info("begain run '{$service_name}'")->out(false);
            $this->cache->set($service_name . '-success', false);

            foreach ($service as $index => $bpjs) {
                $hit_nik = $this->cache->has($bpjs);
                $nik     = $this->getNIK($bpjs);
                if (null === $nik) {
                    info("{$index} {$service_name} bpjs: {$bpjs}, nik: NOT FOUND")->out(false);
                    $skipped[$service_name][] = [
                        'bpjs'  => $bpjs,
                        'jenis' => 'NOT FOUND',
                    ];

                    continue;
                }

                $hit_bpjs = $this->cache->has($nik);
                $jenis    = $this->getJenisBPJS($nik);
                if (null === $jenis) {
                    info("{$index} {$service_name} bpjs: {$bpjs}, jenis: UNKNOW")->out(false);
                    $skipped[$service_name][] = [
                        'bpjs'  => $bpjs,
                        'jenis' => 'UNKNOW',
                    ];

                    continue;
                }

                $all[$service_name][] = [
                    'bpjs'  => $bpjs,
                    'jenis' => $jenis,
                ];

                info("{$index} {$service_name} bpjs: {$bpjs}, jenis: {$jenis}")->out(false);

                $interval = ($hit_nik ? 0 : $this->interval) + ($hit_bpjs ? 0 : $this->interval);
                usleep($interval);
            }

            // cache to prevent fail
            $this->cache->set($service_name, $all[$service_name]);
            $this->cache->set($service_name . '-success', true);
        }

        $json   = json_encode($all, JSON_PRETTY_PRINT);
        $return = file_put_contents($this->base_dir . '/logs/current.json', $json);

        ok('done')->out(false);

        if (count($skipped) > 0 && false !== $return) {
            $json   = json_encode($skipped, JSON_PRETTY_PRINT);
            $return = file_put_contents($this->base_dir . '/logs/current-skipped.json', $json);

            warn('done - skipped details, do it manual!!!')->out();
        }

        return false === $return ? 1 : 0;
    }

    private function getNIK(string $bpjs): ?string
    {
        if (null == ($nik = $this->cache->get($bpjs, null))) {
            $retry = 0;
            while ($nik === null) {
                $res  = $this->pcare->bpjs($bpjs);
                $body = $res->getBody()->getContents();
                $json = json_decode($body, true);
                $nik  = $json['nik'] ?? null;

                $this->ratelimter($nik, $this->delay($this->base_delay, $retry));

                $retry++;
                if ($retry >= $this->max_retry) {
                    return null;
                }
            }

            $this->cache->set($bpjs, $nik);
        }

        return $nik;
    }

    private function getJenisBPJS(string $nik): ?string
    {
        if (null == ($jenis = $this->cache->get($nik, null))) {
            $retry = 0;
            while ($jenis === null) {
                $res   = $this->pcare->nik($nik);
                $body  = $res->getBody()->getContents();
                $json  = json_decode($body, true);
                $jenis = $json['jnsPeserta']['nama'] ?? null;

                $this->ratelimter($jenis, $this->delay($this->base_delay, $retry));

                $retry++;
                if ($retry >= $this->max_retry) {
                    return null;
                }
            }

            $this->cache->set($nik, $jenis);
        }

        return $jenis;
    }

    private function delay(int $base, int $retry): int
    {
        $delay  = $base * pow(1.8, $retry);
        $delay  = (int) min($delay, 60);
        $jitter = $delay * 0.3;
        $jitter = mt_rand(-100, 100) / 100 * $jitter;

        return (int) max($delay + $jitter, 1);
    }
}
