<?php

declare(strict_types=1);

namespace App\Commands;

use GuzzleHttp\Client;
use System\Cache\Storage\FileStorage;
use System\Console\Command;

use function System\Console\info;
use function System\Console\ok;
use function System\Console\option;
use function System\Console\warn;

final class PCareCommand extends Command
{
    private string $base_dir;
    private FileStorage $cache;
    private Client $pcare;

    public function __construct(array $argv, string $base_dir)
    {
        parent::__construct($argv);
        $this->base_dir = $base_dir;
        $this->cache    = new FileStorage($this->base_dir . '/cache');
        $this->pcare    = new Client([
            'base_uri'    => 'http://127.0.0.1:3000',
            'http_errors' => false,
        ]);
    }

    public function entry(): int
    {
        return match (true) {
            'info' === $this->CMD    => $this->getInfo(),
            'analize' === $this->CMD => $this->analize(),
            default                  => $this->default(),
        };
    }

    public function default(): int
    {
        warn('Ooo..!!!')->out();

        return 1;
    }

    public function getInfo(): int
    {
        $services = require $this->base_dir . '/logs/current.php';

        foreach ($services as $service_name => $service) {
            info("begain run '{$service_name}'")->out(false);
            $all = [];

            foreach ($service as $index => $bpjs) {
                $nik   = $this->getNIK($bpjs);
                $jenis = $this->getJenisBPJS($nik);
                $all[] = [
                    'bpjs'  => $bpjs,
                    'jenis' => $jenis,
                ];

                info("{$index} bpjs: {$bpjs}, jenis: {$jenis}")->out(false);

                usleep(1_200_000); // 1.2 detik
            }

            $json   = json_encode($all);
            $return = file_put_contents($this->base_dir . "/logs/{$service_name}.json", $json);
            ok('done')->out();

            return false === $return ? 1 : 0;
        }
    }

    public function analize(): int
    {
        $name = $this->option('target');
        $load = file_get_contents($this->base_dir . "/logs/{$name}.json");
        $json = json_decode($load, true);
        $pbi  = 0;
        $non  = 0;
        foreach ($json as ['jenis' => $jenis]) {
            if (str_starts_with($jenis, 'PBI')) {
                $pbi++;
                continue;
            }
            $non++;
        }

        ok("{$name} -> pbi: {$pbi}; non: {$non}")->out();

        return 0;
    }

    private function getNIK(string $bpjs): string
    {
        if (null == ($nik = $this->cache->get($bpjs, null))) {
            while ($nik === null) {
                $res  = $this->pcare->get("/info/{$bpjs}/bpjs");
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
            $res   = $this->pcare->get("/info/{$nik}/nik");
            $body  = $res->getBody()->getContents();
            $json  = json_decode($body, true);
            $jenis = $json['jnsPeserta']['nama'] ?? null;

            $this->ratelimter($jenis);
        }

        return $jenis;
    }

    private function ratelimter(string $key): void
    {
        if ($key === null) {
            warn('limited by server sleep for 10 second!')->out(false);
            sleep(10);
            $this->checkLogin();
        }
    }

    private function checkLogin(): void
    {
        $res   = $this->pcare->get('/check');
        if ($res->getStatusCode() > 200) {
            option('mohon login terlebih dahulu!', [
                'yes' => static function () {},
            ]);
        }
    }
}
