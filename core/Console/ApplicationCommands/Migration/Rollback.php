<?php

namespace MkyCore\Console\ApplicationCommands\Migration;

use Exception;
use MkyCommand\Exceptions\CommandException;
use MkyCommand\Exceptions\InputOptionException;
use MkyCommand\Input;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Migration\MigrationFile;
use MkyCore\Migration\Schema;
use ReflectionException;

class Rollback extends Create
{

    protected string $description = 'Rollback database migration';

    public function settings(): void
    {
        $this->addOption('query', null, InputOption::NONE, 'Show SQL query')
            ->addOption('version', 'v', InputOption::OPTIONAL, 'Select which version to rollback to')
            ->addOption('number', 'n', InputOption::OPTIONAL, 'Number a migration to rollback');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return void
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws InputOptionException
     */
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
            $this->sendResponse($output, Schema::$SUCCESS, Schema::$ERRORS);
        } catch (Exception $e) {
            $output->error($e->getMessage());
        }
    }
}