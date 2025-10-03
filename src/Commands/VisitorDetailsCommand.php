<?php

declare(strict_types=1);

namespace App\Commands;

use System\Console\Style\ProgressBar;

use function System\Console\info;
use function System\Console\ok;

final class VisitorDetailsCommand extends Command
{
    public function __main(): int
    {
        $source = $this->hasOption('bpjs');
        $cache  = $this->base_dir . '/logs/huge.json';
        $visits = [];
        if (false === file_exists($cache)) {
            $start  = (int) $this->option('start', 1);
            $end    = (int) $this->option('end', date('m'));
            $year   = (int) $this->option('year', date('Y'));
            $visits = $this->getVisitor($start, $end, $year);

            file_put_contents($cache, json_encode($visits));
        } else {
            $getCache = file_get_contents($cache);
            $visits   = json_decode($getCache, true);
        }

        $details        = [];
        $progress       = new ProgressBar();
        $progress->maks = count($visits);
        foreach ($visits as $visit) {
            $progress->current++;
            $progress->tick();
            if (null === ($detail = $this->getVisitDetails($visit, $source))) {
                continue;
            }

            $details[$visit] = $detail;
        }

        ok('Done, saving file...')->out();

        return false === file_put_contents($this->base_dir . '/logs/huge_visitor.json', json_encode($details)) ? 0 : 1;
    }

    private function getVisitor(int $month_start, int $month_end, int $year): array
    {
        $bpjs = [];
        for ($month = $month_start; $month <= $month_end; $month++) {
            $content = $this->pcare->kunjungan('sakit', str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '-' . $year)->getBody()->getContents();
            $json    = json_decode($content, true);
            $visits  = [...$json['log']['laki'], ...$json['log']['perempuan']];
            $count   = count($visits);
            info("bulan {$month}-{$year} ada {$count}")->out(false);

            foreach (array_unique($visits) as $visit) {
                $bpjs[$visit] = $visit;
            }
        }

        return $bpjs;
    }

    private function getVisitDetails(string $identias, bool $bpjs = true): ?array
    {
        if (null == ($detail = $this->cache->get("visit.{$identias}", null))) {
            while ($detail === null) {
                /** @var \Psr\Http\Message\ResponseInterface */
                $res  = $bpjs ? $this->pcare->bpjs($identias) : $this->pcare->nik($identias);
                $body = $res->getBody()->getContents();
                $json = json_decode($body, true);

                if ('AKTIF' === ($json['statusAktif']['nama'] ?? '') && $_SERVER['KODE_FASKES'] === ($json['kdPpkPst']['kdPPK'] ?? '')) {
                    $detail = [
                        'nik'    => $json['nik'],
                        'bpjs'   => $json['noKartu'],
                        'nama'   => $json['nama'],
                        'jk'     => $json['sex'] === 'L' ? 'Laki-laki' : 'Perempuan',
                        'tgl'    => $json['tglLahir'],
                        'alamat' => str_replace('  ', ' ', trim($json['alamat'])),
                    ];
                } else {
                    break;
                }

                $this->ratelimter($json);
            }

            if (null !== $detail) {
                $this->cache->set("visit.{$identias}", $detail);
            }
        }

        return $detail;
    }
}
