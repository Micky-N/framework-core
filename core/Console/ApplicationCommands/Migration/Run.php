<?php

namespace MkyCore\Console\ApplicationCommands\Migration;

use Exception;
use MkyCommand\Exceptions\CommandException;
use MkyCommand\Exceptions\InputOptionException;
use MkyCommand\Input;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;
use MkyCore\Abstracts\Command;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Migration\MigrationFile;
use MkyCore\Migration\Schema;
use ReflectionException;

class Run extends Create
{

    protected string $description = 'Run database migration (all or one migration file)';

    public function settings(): void
    {
        $this->addOption('query', null, InputOption::NONE, 'Show SQL query')
            ->addOption('version', 'v', InputOption::OPTIONAL, 'Select a specific version of migration file')
            ->addOption('pop', null, InputOption::NONE, 'Run database population after migration');
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
        $pop = false;
        $version = null;
        if($input->option('version')){
            $version = $input->option('version');
        }elseif($input->option('pop')){
            $pop = $input->option('pop');
        }
        try {
            $migrationRunner->actionMigration('up', $version);
            if ($pop) {
                exec('php mky populator:run', $outputMessage);
                for ($i = 0; $i < count($outputMessage); $i++) {
                    echo $outputMessage[$i]."\n";
                }
            }
            $this->sendResponse($output, Schema::$SUCCESS, Schema::$ERRORS);
        } catch (Exception $e) {
            $output->error($e->getMessage());
        }
    }
}