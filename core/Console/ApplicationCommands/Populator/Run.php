<?php

namespace MkyCore\Console\ApplicationCommands\Populator;

use Exception;
use MkyCommand\AbstractCommand;
use MkyCommand\Input;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;
use MkyCore\Abstracts\Populator;
use function MkyCore\Console\Populator\str_ends_with;

class Run extends AbstractCommand
{
    public static int $count = 0;

    protected string $description = 'Run the populator to populate the database';

    public function settings(): void
    {
        $this->addOption('file', 'f', InputOption::OPTIONAL, 'The name of populator file (\'user\' => \'UserPopulator\')', 'runner');
    }

    public function execute(Input $input, Output $output): bool|string
    {
        try {
            $file = ucfirst($input->option('file'));
            if (!str_ends_with($file, 'Populator')) {
                $file .= 'Populator';
            }
            $populator = "Database\Populators\\$file";
            if (!class_exists($populator)) {
                $output->error("Populator class not found", $populator);
                return self::ERROR;
            }
            $populator = new $populator();
            if (!($populator instanceof Populator)) {
                $output->error("Populator class must extends from MkyCore\Abstracts\Populator", $populator);
                return self::ERROR;
            }
            $populator->populate();
            $count = self::$count;
            $output->success('Database successfully populated', $count . ' record' . ($count > 1 ? 's' : ''));
            return self::SUCCESS;
        } catch (Exception $e) {
            $output->error($e->getMessage());
            return self::ERROR;
        }
    }
}