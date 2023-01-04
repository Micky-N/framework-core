<?php

namespace MkyCore\Console\Install;

use MkyCore\Console\Create\Middleware;

class Remember extends \MkyCore\Console\Create\Create
{
    public function process(): bool|string
    {
        $migrationAR = false;
        $migrationModel = file_get_contents(dirname(__DIR__) . '/models/install/remember/migration.model');
        $middlewareModel = file_get_contents(dirname(__DIR__) . '/models/install/remember/middleware.model');
        $databasePath = $this->app->get('path:base') . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        $middlewareFile = $this->app->get('path:app') . DIRECTORY_SEPARATOR . 'Middlewares' . DIRECTORY_SEPARATOR . 'RememberTokenMiddleware.php';
        if (!is_dir($databasePath)) {
            mkdir($databasePath, '0777', true);
        }
        if (!glob($databasePath . DIRECTORY_SEPARATOR . '*create_remember_tokens_table.php')) {
            $migrationFile = $databasePath . DIRECTORY_SEPARATOR . time() . '_create_remember_tokens_table.php';
            file_put_contents($migrationFile, $migrationModel);
        } else {
            $migrationAR = true;
        }
        $middleware = new Middleware($this->app, [], [
            'name' => 'rememberToken',
            'module' => 'root',
            'write' => false
        ]);
        $middlewareOut = $middleware->process();
        $middlewareAR = $middlewareOut == false;
        if($middlewareOut){
            file_put_contents($middlewareFile, $middlewareModel);
        }

        if ($middlewareAR && $migrationAR) {
            echo $this->getColoredString('remember token middleware and migration file already exist', 'red', 'bold');
        } else if ($migrationAR) {
            echo $this->getColoredString('remember token migration file already exists', 'red', 'bold');
        } else if ($middlewareAR) {
            echo $this->getColoredString('remember token middleware already exists', 'red', 'bold');
        } else {
            echo $this->getColoredString('remember token middleware created successfully', 'green', 'bold') . "\n";
            echo $this->getColoredString('remember token migration file created successfully', 'green', 'bold') . "\n";
            echo '> run ' . $this->getColoredString('php mky migration:run', 'yellow') . ' to migrate the Remember token table';
        }
        return true;
    }
}