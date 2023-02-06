<?php

namespace MkyCore\Console\ApplicationCommands\Install;

use MkyCommand\AbstractCommand;
use MkyCommand\Input;
use MkyCommand\Output;
use MkyCore\Application;

class Jwt extends AbstractCommand
{
    protected string $description = 'Implement json web token migration and config file';

    public function __construct(private readonly Application $application)
    {
    }

    public function execute(Input $input, Output $output): void
    {
        $configAR = false;
        $migrationAR = false;
        $configModel = file_get_contents(dirname(__DIR__) . '/models/install/jwt/config.model');
        $migrationModel = file_get_contents(dirname(__DIR__) . '/models/install/jwt/migration.model');
        $configPath = $this->application->get('path:config');
        if (!file_exists($configPath . DIRECTORY_SEPARATOR . 'jwt.php')) {
            file_put_contents($configPath . DIRECTORY_SEPARATOR . 'jwt.php', $configModel);
        } else {
            $configAR = true;
        }
        $databasePath = $this->application->get('path:base') . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        if(!is_dir($databasePath)){
            mkdir($databasePath, '0777', true);
        }
        if (!glob($databasePath . DIRECTORY_SEPARATOR . '*create_json_web_tokens_table.php')) {
            $migrationFile = $databasePath . DIRECTORY_SEPARATOR . time() .'_create_json_web_tokens_table.php';
            file_put_contents($migrationFile, $migrationModel);
        } else {
            $migrationAR = true;
        }
        if ($migrationAR && $configAR) {
            echo $output->coloredMessage('jwt config file and migration file already exists', 'red', 'bold');
        } elseif ($migrationAR) {
            echo $output->coloredMessage('jwt config file created successfully', 'green', 'bold');
        } elseif ($configAR) {
            echo $output->coloredMessage('migration file created successfully', 'green', 'bold')."\n";
            echo $output->coloredMessage('run php mky migration:run to migrate the Jwt table', 'green', 'bold');
        } else {
            echo $output->coloredMessage('jwt config file and migration file created successfully', 'green', 'bold')."\n";
            echo '> run ' . $output->coloredMessage('php mky migration:run', 'yellow') . ' to migrate the Jwt table';
        }
    }
}