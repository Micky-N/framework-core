<?php

namespace MkyCore\Console\ApplicationCommands\Install;

use MkyCommand\AbstractCommand;
use MkyCommand\Input;
use MkyCommand\Output;
use MkyCore\Application;
use MkyCore\Console\ApplicationCommands\Create\Middleware;

class Remember extends AbstractCommand
{

    protected string $description = 'Implement the remember authenticate system';

    public function __construct(private readonly Application $application)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $migrationAR = false;
        $migrationModel = file_get_contents(dirname(__DIR__) . '/models/install/remember/migration.model');
        $middlewareModel = file_get_contents(dirname(__DIR__) . '/models/install/remember/middleware.model');
        $databasePath = $this->application->get('path:base') . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        $middlewareFile = $this->application->get('path:app') . DIRECTORY_SEPARATOR . 'Middlewares' . DIRECTORY_SEPARATOR . 'RememberTokenMiddleware.php';
        if (!is_dir($databasePath)) {
            mkdir($databasePath, '0777', true);
        }
        if (!glob($databasePath . DIRECTORY_SEPARATOR . '*create_remember_tokens_table.php')) {
            $migrationFile = $databasePath . DIRECTORY_SEPARATOR . time() . '_create_remember_tokens_table.php';
            file_put_contents($migrationFile, $migrationModel);
        } else {
            $migrationAR = true;
        }
        $middlewareAR = false;
        $middlewareOut = false;
        if(!file_exists($middlewareFile)){
            $middleware = new Middleware($this->application, [
                'name' => 'rememberToken',
                'module' => 'root',
                'type' => 'global'
            ]);
            $middlewareOut = $middleware->execute($input, $output);
        }else{
            $middlewareAR = true;
        }
        
        if(!$middlewareAR && $middlewareOut){
            file_put_contents($middlewareFile, $middlewareModel);
        }

        if ($middlewareAR && $migrationAR) {
            echo $output->coloredMessage('remember token middleware and migration file already exist', 'red', 'bold');
            return false;
        } else if ($migrationAR) {
            echo $output->coloredMessage('remember token migration file already exists', 'red', 'bold')."\n";
            echo $output->coloredMessage('remember token middleware created successfully', 'green', 'bold') . "\n";
        } else if ($middlewareAR) {
            echo $output->coloredMessage('remember token middleware already exists', 'red', 'bold');
            echo $output->coloredMessage('remember token migration file created successfully', 'green', 'bold') . "\n";
            echo '> run ' . $output->coloredMessage('php mky migration:run', 'yellow') . ' to migrate the Remember token table';
        } else {
            echo $output->coloredMessage('remember token middleware created successfully', 'green', 'bold') . "\n";
            echo $output->coloredMessage('remember token migration file created successfully', 'green', 'bold') . "\n";
            echo '> run ' . $output->coloredMessage('php mky migration:run', 'yellow') . ' to migrate the Remember token table';
        }
        $output->success('Remember system installed successfully');
        return self::SUCCESS;
    }
}