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

class Run extends AbstractCommand
{
    public static bool $query = false;
    protected DB $migrationDB;

    protected string $description = 'Run database migration (all or one migration file)';

    public function __construct(private readonly Application $application)
    {
    }

    public function settings(): void
    {
        $this->addOption('query', null, InputOption::NONE, 'Show SQL query')
            ->addOption('version', 'v', InputOption::OPTIONAL, 'Select a specific version of migration file')
            ->addOption('pop', null, InputOption::NONE, 'Run database population after migration');
    }

    public function execute(Input $input, Output $output): bool|string
    {
        /** @var MigrationFile $migrationRunner */
        $migrationRunner = $this->application->get(MigrationFile::class);
        self::$query = $input->option('query');
        $pop = false;
        $version = null;
        if($input->hasOption('version')){
            $version = $input->option('version');
        }elseif($input->hasOption('pop')){
            $pop = $input->option('pop');
        }
        try {
            $migrationRunner->actionMigration('up', $version);
            if ($pop) {
                exec('php mky populator:run', $outputMessage);
                for ($i = 0; $i < count($outputMessage); $i++) {
                    echo $output[$i];
                }
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