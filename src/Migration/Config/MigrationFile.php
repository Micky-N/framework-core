<?php

namespace MkyCore\Migration\Config;

use MkyCore\Abstracts\Migration;
use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\Migration\MigrationException;
use MkyCore\File;
use ReflectionException;

class MigrationFile
{

    private string $databaseDir;

    /**
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     */
    public function __construct(private readonly Application $app, private readonly array $config)
    {
        $this->databaseDir = File::makePath([$this->app->get('path:base'), 'database', 'migrations']);
    }

    /**
     * @throws MigrationException
     */
    public function actionMigration(string $direction = 'up'): void
    {
        if (!file_exists($this->databaseDir)) {
            throw MigrationException::DATABASE_MIGRATION_DIRECTORY_NOT_FOUND();
        }
        $this->browser_dir($direction);
    }

    private function browser_dir(string $direction = 'up'): void
    {
        $dir = glob(File::makePath([$this->databaseDir, "*"]));
        foreach ($dir as $test) {
            $model = require $test;
            if(in_array($direction, ['up', 'down'])){
                if($model instanceof Migration && method_exists($model, $direction)){
                    $model->{$direction}();
                }
            }
        }
    }
}
