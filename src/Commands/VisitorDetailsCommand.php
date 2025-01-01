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
        $cache  = $this->base_dir . '/logs/huge.php';
        $visits = [];
        if (false === file_exists($cache)) {
            $visits = $this->getVisitor(1, 11, 2024);
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
            if (null === ($detail = $this->getVisitDetails($visit))) {
                continue;
            }

            if (str_starts_with($detail['hp'], '08')) {
                $details[$visit] = $detail;
            }
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

    private function getVisitDetails(string $bpjs): ?array
    {
        if (null == ($detail = $this->cache->get("visit.{$bpjs}", null))) {
            while ($detail === null) {
                $res  = $this->pcare->bpjs($bpjs);
                $body = $res->getBody()->getContents();
                $json = json_decode($body, true);

                if ('AKTIF' === ($json['statusAktif']['nama'] ?? '') && $_SERVER['KODE_FASKES'] === ($json['kdPpkPst']['kdPPK'] ?? '')) {
                    $detail = [
                        'nama'   => $json['nama'],
                        'bpjs'   => $json['noKartu'],
                        'tgl'    => $json['tglLahir'],
                        'alamat' => str_replace('  ', ' ', trim($json['alamat'])),
                        'hp'     => $json['noHP'],
                        'nik'    => $json['nik'],
                    ];
                } else {
                    break;
                }

                $this->ratelimter($json);
            }

            if (null !== $detail) {
                $this->cache->set("visit.{$bpjs}", $detail);
            }
        }

        return $detail;
    }
}
