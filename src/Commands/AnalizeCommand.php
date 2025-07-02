<?php

declare(strict_types=1);

namespace App\Commands;

use function System\Console\info;
use function System\Console\style;
use function System\Console\warn;

final class AnalizeCommand extends Command
{
    public function __main(): int
    {
        if (false === file_exists($log = $this->base_dir . '/logs/current.json')) {
            warn("No log file founded at '{$log}'")->out(false);
            info('Suggest run `php cli service:detail`.')->out();

            return 1;
        }
        $load = file_get_contents($log);

        $services = json_decode($load, true);
        $recap    = [];
        foreach ($services as $service_name => $service) {
            $pbi  = 0;
            $non  = 0;
            foreach ($service as ['jenis' => $jenis]) {
                if (str_starts_with($jenis, 'PBI') || str_starts_with($jenis, 'PBPU')) {
                    $pbi++;
                    continue;
                }
                $non++;
            }

            $recap[$service_name]['pbi'] = $pbi;
            $recap[$service_name]['non'] = $non;
        }
        // rekap 1
        style('Rekap BPJS I: ')
            ->newlines()
            ->push('Kunjungan sakit: ')->push('(laki + perempuan)')->textDim()->newLines()
            ->push('PBI: ')->push($recap['sakit-laki']['pbi'])->textGreen()->push(' + ')->textDim()->push($recap['sakit-perempuan']['pbi'])->textGreen()
            ->push(' = ')->textDim()->push($recap['sakit-laki']['pbi'] + $recap['sakit-perempuan']['pbi'])->textYellow()->newLines()
            ->push('Non: ')->push($recap['sakit-laki']['non'])->textGreen()->push(' + ')->textDim()->push($recap['sakit-perempuan']['non'])->textGreen()
            ->push(' = ')->textDim()->push($recap['sakit-laki']['non'] + $recap['sakit-perempuan']['non'])->textYellow()->newLines(2)

            ->push('Kunjungan sehat: ')->push('(laki + perempuan)')->textDim()->newLines()
            ->push('PBI: ')->push($recap['sehat-laki']['pbi'])->textGreen()->push(' + ')->textDim()->push($recap['sehat-perempuan']['pbi'])->textGreen()
            ->push(' = ')->textDim()->push($recap['sehat-laki']['pbi'] + $recap['sehat-perempuan']['pbi'])->textYellow()->newLines()
            ->push('Non: ')->push($recap['sehat-laki']['non'])->textGreen()->push(' + ')->textDim()->push($recap['sehat-perempuan']['non'])->textGreen()
            ->push(' = ')->textDim()->push($recap['sehat-laki']['non'] + $recap['sehat-perempuan']['non'])->textYellow()->newLines(2)

            ->push('Rujukan: ')->push('(laki + perempuan)')->textDim()->newLines()
            ->push('PBI: ')->push($recap['rujukan-laki']['pbi'])->textGreen()->push(' + ')->textDim()->push($recap['rujukan-perempuan']['pbi'])->textGreen()
            ->push(' = ')->textDim()->push($recap['rujukan-laki']['pbi'] + $recap['rujukan-perempuan']['pbi'])->textYellow()->newLines()
            ->push('Non: ')->push($recap['rujukan-laki']['non'])->textGreen()->push(' + ')->textDim()->push($recap['rujukan-perempuan']['non'])->textGreen()
            ->push(' = ')->textDim()->push($recap['rujukan-laki']['non'] + $recap['rujukan-perempuan']['non'])->textYellow()->newLines(2)
            ->out()
        ;
        // rekap 2
        style('Rekap BPJS II: ')
            ->newLines()
            ->push('Non PBI: ')->push('(sakit + sehat)')->textDim()->newLines()
            ->push('Laki-laki: ')->push($recap['sakit-laki']['pbi'])->textGreen()->push(' + ')->textDim()->push($recap['sehat-laki']['pbi'])->textGreen()
            ->push(' = ')->textDim()->push($recap['sakit-laki']['pbi'] + $recap['sehat-laki']['pbi'])->textYellow()->newLines()
            ->push('Perempuan: ')->push($recap['sakit-perempuan']['pbi'])->textGreen()->push(' + ')->textDim()->push($recap['sehat-perempuan']['pbi'])->textGreen()
            ->push(' = ')->textDim()->push($recap['sakit-perempuan']['pbi'] + $recap['sehat-perempuan']['pbi'])->textYellow()->newLines(2)

            ->push('PBI: ')->push('(sakit + sehat)')->textDim()->newLines()
            ->push('Laki-laki: ')->push($recap['sakit-laki']['non'])->textGreen()->push(' + ')->textDim()->push($recap['sehat-laki']['non'])->textGreen()
            ->push(' = ')->textDim()->push($recap['sakit-laki']['non'] + $recap['sehat-laki']['non'])->textYellow()->newLines()
            ->push('Perempuan: ')->push($recap['sakit-perempuan']['non'])->textGreen()->push(' + ')->textDim()->push($recap['sehat-perempuan']['non'])->textGreen()
            ->push(' = ')->textDim()->push($recap['sakit-perempuan']['non'] + $recap['sehat-perempuan']['non'])->textYellow()->newLines(2)
            ->out(false)
        ;

        return 0;
    }
}
