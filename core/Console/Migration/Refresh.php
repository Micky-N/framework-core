<?php

namespace MkyCore\Console\Migration;

use Exception;
use MkyCommand\AbstractCommand;
use MkyCommand\Input;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;
use MkyCore\Application;
use MkyCore\Migration\DB;
use MkyCore\Migration\MigrationFile;
use MkyCore\Migration\Schema;

class Refresh extends AbstractCommand
{
    public static bool $query = false;
    protected DB $migrationDB;

    protected string $description = 'Refresh database migration (one or multiple)';

    public function __construct(private readonly Application $application)
    {
        $this->migrationDB = $application->get(DB::class);
    }

    public function settings(): void
    {
        $this->addOption('query', null, InputOption::NONE, 'Show SQL query')
            ->addOption('version', 'v', InputOption::OPTIONAL, 'Select which version to refresh to')
            ->addOption('number', 'n', InputOption::OPTIONAL, 'Number a migration to rollback');
    }

    public function execute(Input $input, Output $output): int
    {
        /** @var MigrationFile $migrationRunner */
        $migrationRunner = $this->application->get(MigrationFile::class);
        self::$query = $input->option('query');
        $migrationLogs = [];
        if ($input->hasOption('version')) {
            $version = $input->option('version');
            $migrationLogs = $this->migrationDB->getTo((int)$version);
        } elseif ($input->hasOption('number')) {
            $number = $input->option('number');
            $migrationLogs = $this->migrationDB->getLast($number);
        }
        try {
            if (!$migrationLogs) {
                return false;
            }
            foreach ($migrationLogs as $log) {
                $file = $this->application->get('path:database') . DIRECTORY_SEPARATOR . $log['log'];
                $migrationRunner->actionMigration('down', $file);
            }
            foreach ($migrationLogs as $log) {
                $file = $this->application->get('path:database') . DIRECTORY_SEPARATOR . $log['log'];
                $migrationRunner->actionMigration('up', $file);
            }
            $this->sendResponse($output, Schema::$SUCCESS, Schema::$ERRORS);
            return self::SUCCESS;
        } catch (Exception $e) {
            $output->error($e->getMessage());
            return self::ERROR;
        }
    }

    protected function sendResponse(Output $output, array $success, array $errors)
    {
        if ($success) {
            for ($i = 0; $i < count($success); $i++) {
                $response = $success[$i];
                $output->coloredMessage($response[0], 'green', 'bold') . (isset($response[1]) ? ": $response[1]" : '') . "\n";
            }
        }
        if ($errors) {
            for ($i = 0; $i < count($errors); $i++) {
                $response = $errors[$i];
                $output->coloredMessage($response[0], 'red', 'bold') . (isset($response[1]) ? ": $response[1]" : '') . "\n";
            }
        }
    }
}