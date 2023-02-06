<?php

namespace MkyCore\Console\ApplicationCommands\Migration;

use Exception;
use MkyCommand\Input;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;
use MkyCore\Migration\MigrationFile;
use MkyCore\Migration\Schema;

class Refresh extends Migration
{
    protected string $description = 'Refresh database migration (one or multiple)';


    public function settings(): void
    {
        $this->addOption('query', null, InputOption::NONE, 'Show SQL query')
            ->addOption('version', 'v', InputOption::OPTIONAL, 'Select which version to refresh to')
            ->addOption('number', 'n', InputOption::OPTIONAL, 'Number a migration to rollback');
    }

    public function execute(Input $input, Output $output): void
    {
        /** @var MigrationFile $migrationRunner */
        $migrationRunner = $this->application->get(MigrationFile::class);
        self::$query = $input->option('query');
        $migrationLogs = [];
        if ($input->option('version')) {
            $version = $input->option('version');
            $migrationLogs = $this->migrationDB->getTo((int)$version);
        } elseif ($input->option('number')) {
            $number = $input->option('number');
            $migrationLogs = $this->migrationDB->getLast($number);
        }
        try {
            if (!$migrationLogs) {
                exit();
            }
            foreach ($migrationLogs as $log) {
                $file = $this->application->get('path:database') . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . $log['log'];
                $migrationRunner->actionMigration('down', $file);
            }
            foreach ($migrationLogs as $log) {
                $file = $this->application->get('path:database') . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . $log['log'];
                $migrationRunner->actionMigration('up', $file);
            }
            $this->sendResponse($output, Schema::$SUCCESS, Schema::$ERRORS);
        } catch (Exception $e) {
            $output->error($e->getMessage());
        }
    }
}