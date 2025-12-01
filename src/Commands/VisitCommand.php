<?php

declare(strict_types=1);

namespace App\Commands;

use function System\Console\info;
use function System\Console\ok;

final class VisitCommand extends Command
{
    public function __main(): int
    {
        $microtime   = microtime(true);

        $date    = $this->option('date', date('m') . '-' . date('Y'));
        $start   = (int) $this->option('start', 0);
        $end     = (int) $this->option('end', 5000);
        $sakit   = $this->getKunjungan('sakit', $date, $start, $end);
        $sehat   = $this->getKunjungan('sehat', $date, $start, $end);
        $rujukan = $this->getKunjungan('rujukan', $date, $start, $end);

        $all = [
            'sakit-perempuan'   => $sakit['perempuan'],
            'sakit-laki'        => $sakit['laki'],
            'sehat-perempuan'   => $sehat['perempuan'],
            'sehat-laki'        => $sehat['laki'],
            'rujukan-perempuan' => $rujukan['perempuan'],
            'rujukan-laki'      => $rujukan['laki'],
        ];

        $arrayable = var_export($all, true);
        $res       = file_put_contents($this->base_dir . '/logs/current.php', "<?php return {$arrayable};");
        $count     = count($sakit['laki']) + count($sakit['perempuan'])
                   + count($sehat['laki']) + count($sehat['perempuan'])
                   + count($rujukan['laki']) + count($rujukan['perempuan']);

        ok('Done in ' . round(microtime(true) - $microtime, 2) . ' seconds, total ' . $count . ' recorded.')->out();

        return false === $res ? 1 : 0;
    }

    private function getKunjungan(string $jenis, string $date, int $start = 0, int $end = 5_000)
    {
        $log = null;
        while ($log === null) {
            if ($this->option('verbose', false)) {
                info("Memulai melihat kunjunagn {$jenis }.")->out(false);
            }
            $res  = $this->pcare->kunjungan($jenis, $date, $start, $end);
            $body = $res->getBody()->getContents();
            $json = json_decode($body, true);

            $log = $json['log'] ?? null;
            $this->ratelimter($log, 2);
        }

        return $log;
    }
}
