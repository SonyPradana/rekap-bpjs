<?php

declare(strict_types=1);

namespace App\Commands;

use System\Console\Style\Style;
use System\Console\Traits\PrintHelpTrait;

final class HelpCommand extends Command
{
    use PrintHelpTrait;

    public function __main(): int
    {
        $has_visited      = [];
        $this->print_help = [
            'margin-left'         => 4,
            'column-1-min-lenght' => 16,
        ];
        $help = $this->printHelp();

        if (isset($help['commands']) && $help['commands'] !== null) {
            foreach ($help['commands'] as $command => $desc) {
                $this->command_describes[$command] = $desc;
            }
        }

        if (isset($help['options']) && $help['options'] !== null) {
            foreach ($help['options'] as $option => $desc) {
                $this->option_describes[$option] = $desc;
            }
        }

        if (isset($help['relation']) && $help['relation'] != null) {
            foreach ($help['relation'] as $option => $desc) {
                $this->command_relation[$option] = $desc;
            }
        }

        $printer = new Style();
        $printer
            ->push('Usage:')
            ->newLines()->push('    ')
            ->push('php')->textGreen()
            ->push(' cli [command] ')
            ->push('[option]')->textDim()
            ->newLines(2)
        ;

        $printer->push('Avilabe command:')->newLines();
        $printer = $this->printCommands($printer)->newLines();

        $printer->push('Avilabe options:')->newLines();
        $printer = $this->printOptions($printer);

        $printer->out();

        return 0;
    }

    /**
     * @return array<string, array<string, string|string[]>>
     */
    public function printHelp()
    {
        return [
            'commands'  => [
                'service:visit'      => 'Gets the number of visits in a month',
                'service:detail'     => 'Get details about BPJS types',
                'service:analize'    => 'Analyzing and summarizing BPJS types',
                'visit:detail'       => 'Get more details about BPJS information',
                'visit:export'       => 'Export BPJS information to file format (xml)',
                'cache:forget'       => 'Directly delete an item from the cache by its unique key',
                'cache:get'          => 'Directly fetches a value from the cache',
                'cache:put'          => 'Directly persists data in the cache',
                'cache:service:warm' => 'Warming up `service:detail` by hit only important data',
            ],
            'options'   => [
                '--date'  => 'Visited date, format `mm-YYYY`',
                '--key'   => 'Unique key',
                '--value' => 'Value of cache',
            ],
            'relation'  => [
                'service:visit' => ['--date'],
                'cache:forget'  => ['--key'],
                'cache:get'     => ['--key'],
                'cache:put'     => ['--key', '--value'],
            ],
        ];
    }
}
