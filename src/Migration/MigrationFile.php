<?php

namespace MkyCore\Migration;

use MkyCore\Abstracts\Migration;
use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\Migration\MigrationException;
use MkyCore\File;
use ReflectionException;

class MigrationFile
{

    const TYPES = ['creation', 'alteration'];
    private string $databaseDir;

    /**
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     */
    public function __construct(private readonly Application $app)
    {
        $this->databaseDir = File::makePath([$this->app->get('path:base'), 'database', 'migrations']);
    }

    /**
     * @param string $direction
     * @param string|null $file
     * @throws FailedToResolveContainerException
     * @throws MigrationException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function actionMigration(string $direction = 'up', ?string $file = null): void
    {
        if (!file_exists($this->databaseDir)) {
            throw MigrationException::DATABASE_MIGRATION_DIRECTORY_NOT_FOUND();
        }
        $this->browser_dir($direction, $file);
    }

    /**
     * @throws FailedToResolveContainerException
     * @throws MigrationException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function browser_dir(string $direction = 'up', ?string $file = null): void
    {
        if ($file) {
            $migrationFile = File::makePath([$this->app->get('path:database'), 'migrations', "{$file}.php"], true);
            if (!$migrationFile) {
                throw MigrationException::MIGRATION_FILE_NOT_FOUND($migrationFile);
            }
            $this->instantiateMigration($direction, require $migrationFile);
        } else {
            $dir = glob(File::makePath([$this->databaseDir, "*"]));
            usort($dir, fn($d1, $d2) => ($direction == 'up' ? str_contains($d1, 'alter_') : !str_contains($d1, 'alter_')) ? 1 : -1);
            $arrayDirs = [];
            for ($i = 0; $i < count($dir); $i++) {
                $migrationFile = $dir[$i];
                $type = str_contains($migrationFile, 'alter_') ? 'alteration' : 'creation';
                $arrayDirs[$type][] = require $migrationFile;
            }
            $types = $direction == 'up' ? self::TYPES : array_reverse(self::TYPES);
            for ($i = 0; $i < count($types); $i++) {
                $type = $types[$i];
                $dirsType = $arrayDirs[$type] ?? [];
                usort($dirsType, fn(Migration $file1, Migration $file2) => $file1->getPriority() < $file2->getPriority() ? 1 : -1);
                for ($j = 0; $j < count($dirsType); $j++) {
                    $migration = $dirsType[$j];
                    $this->instantiateMigration($direction, $migration);
                }
            }
        }
    }

    /**
     * @param string $direction
     * @param Migration $migration
     * @return void
     */
    private function instantiateMigration(string $direction, Migration $migration)
    {
        if (in_array($direction, ['up', 'down'])) {
            if (method_exists($migration, $direction)) {
                $migration->{$direction}();
            }
        }
    }
}
