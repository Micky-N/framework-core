<?php

namespace MkyCore\Console\ApplicationCommands\Migration;

use MkyCommand\Exceptions\CommandException;
use MkyCommand\Exceptions\InputOptionException;
use MkyCommand\Input;
use MkyCommand\Output;

class Reset extends Create
{
    protected string $direction = 'up';

    protected string $description = 'Reset and re-run migration';

    public function settings(): void
    {
        $this->addOption('pop', null, Input\InputOption::NONE, 'Run database population after resetting');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return void
     * @throws InputOptionException
     */
    public function execute(Input $input, Output $output): void
    {
        $pop = $input->option('pop');
        exec('php mky migration:rollback -n all', $outputClear);
        for ($i = 0; $i < count($outputClear); $i++) {
            echo $outputClear[$i] . "\n";
        }
        exec('php mky migration:run' . ($pop ? ' --pop' : ''), $outputRun);
        for ($i = 0; $i < count($outputRun); $i++) {
            echo $outputRun[$i] . "\n";
        }
    }
}